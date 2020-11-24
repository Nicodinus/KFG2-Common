<?php


namespace Nicodinus\KFG2\Common\Support\EventQueue;


use Amp\Emitter;
use Amp\Failure;
use Amp\Promise;
use Nicodinus\KFG2\Common\Support\AlreadyReleasedInstanceError;
use function Amp\asyncCall;
use function Amp\call;
use function Amp\delay;

class Provider implements ProviderInterface
{
    /** @var string */
    const DEFAULT_CHANNEL_NAME = 'default';

    /** @var bool */
    private bool $isReleased;

    /** @var array */
    private array $registry;


    //
    public function __construct()
    {
        $this->isReleased = false;
        $this->registry = [];
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        $this->release();
    }

    /**
     * @return bool
     */
    public function isReleased(): bool
    {
        return $this->isReleased;
    }

    /**
     * @return void
     */
    public function release(): void
    {
        if ($this->isReleased()) {
            return;
        }
        $this->isReleased = true;

        foreach ($this->registry as $registry) {

            /** @var Emitter $emitter */
            foreach ($registry as $emitter) {
                $emitter->complete();
            }

        }

        asyncCall(static function (self &$self) {

            yield delay(1000);

            $attempts = 0;
            $maxAttempts = 5;

            do {

                $count = 0;

                foreach ($self->registry as $registry) {

                    if (!$registry) {
                        continue;
                    }

                    $count += sizeof($registry);

                }

                if ($count == 0) {
                    break;
                }

                yield delay(1000);

                $attempts += 1;

            } while ($attempts < $maxAttempts);

            if ($attempts >= $maxAttempts) {
                throw new \LogicException("There are some suppliers not released yet!");
            }

            $self->registry = [];

        }, $this);
    }

    /**
     * @param string $channel
     *
     * @return SupplierInterface
     *
     * @throws AlreadyReleasedInstanceError
     */
    public function getSupplier(string $channel = self::DEFAULT_CHANNEL_NAME): SupplierInterface
    {
        if ($this->isReleased()) {
            throw new AlreadyReleasedInstanceError("Can't await queue, this supplier already released!");
        }

        if (!isset($this->registry[$channel])) {
            $this->registry[$channel] = [];
        }

        $self = &$this;
        $emitter = new Emitter();

        $supplier = new Supplier($emitter->iterate(), $channel, function (SupplierInterface $supplier, self &$self) {

            if (!isset($self->registry[$supplier->getChannel()][\spl_object_hash($supplier)])) {
                throw new \LogicException("Invalid supplier!");
            }

            try {
                /** @var Emitter $emitter */
                $emitter = $self->registry[$supplier->getChannel()][\spl_object_hash($supplier)];
                $emitter->complete();
            } catch (\Throwable $e) {
                //ignore
            }

            unset($self->registry[$supplier->getChannel()][\spl_object_hash($supplier)]);

        }, $self);

        $this->registry[$channel][\spl_object_hash($supplier)] = $emitter;
        return $supplier;
    }

    /**
     * @param object $item
     * @param string $channel
     *
     * @return Promise<int>|Failure<\Throwable>
     */
    public function queue(object $item, string $channel = self::DEFAULT_CHANNEL_NAME): Promise
    {
        return call(static function (self &$self, object $item, string $channel) {

            try {

                if ($self->isReleased()) {
                    throw new AlreadyReleasedInstanceError("Can't await queue, this supplier already released!");
                }

                $counter = 0;

                if (isset($self->registry[$channel])) {

                    /** @var Emitter $emitter */
                    foreach ($self->registry[$channel] as $emitter) {

                        yield $emitter->emit($item);

                        $counter += 1;

                    }

                }

                return $counter;

            } catch (\Throwable $e) {
                return new Failure($e);
            }

        }, $this, $item, $channel);
    }
}