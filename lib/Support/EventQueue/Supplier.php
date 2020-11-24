<?php


namespace Nicodinus\KFG2\Common\Support\EventQueue;


use Amp\Failure;
use Amp\Iterator;
use Amp\Promise;
use Nicodinus\KFG2\Common\Support\AlreadyReleasedInstanceError;
use function Amp\asyncCall;
use function Amp\call;

class Supplier implements SupplierInterface
{
    /** @var bool */
    private bool $isReleased;

    /** @var string */
    private string $channel;

    /** @var Iterator */
    private Iterator $iterator;

    /** @var callable */
    private $onReleased;
    
    /** @var array */
    private array $onReleasedArgs;


    /**
     * Supplier constructor.
     * @param Iterator $iterator
     * @param string $channel
     * @param callable $onReleased
     * @param mixed ...$onReleasedArgs
     */
    public function __construct(Iterator $iterator, string $channel, callable $onReleased, ...$onReleasedArgs)
    {
        $this->iterator = $iterator;
        $this->onReleased = $onReleased;
        $this->channel = $channel;
        $this->onReleasedArgs = $onReleasedArgs;

        $this->isReleased = false;
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        $this->release();
    }

    /**
     * @return string
     */
    public function getChannel(): string
    {
        return $this->channel;
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

        asyncCall(static function (self &$self) {

            yield Iterator\discard($self->iterator);
            
            $onReleasedArgs = $self->onReleasedArgs;
            array_unshift($onReleasedArgs, $self);
            
            yield call($self->onReleased, ...$onReleasedArgs);

        }, $this);
    }

    /**
     * @return Promise<object>|Failure<\Throwable>
     */
    public function await(): Promise
    {
        return call(static function (self &$self) {

            try {

                if ($self->isReleased()) {
                    throw new AlreadyReleasedInstanceError("Can't await queue, this supplier already released!");
                }

                if (yield $self->iterator->advance()) {
                    return $self->iterator->getCurrent();
                }

                $self->release();

            } catch (\Throwable $e) {
                return new Failure($e);
            }

        }, $this);
    }
}