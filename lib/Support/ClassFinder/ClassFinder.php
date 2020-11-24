<?php


namespace Nicodinus\KFG2\Common\Support\ClassFinder;


use Amp\ByteStream\Payload;
use Amp\Failure;
use Amp\Process\Process;
use Amp\Promise;
use Nicodinus\KFG2\Common\Application\ApplicationInterface;
use function Amp\call;


class ClassFinder
{
    /** @var ApplicationInterface */
    private ApplicationInterface $app;

    /** @var string */
    private string $scriptPath;

    //

    /**
     * ClassFinder constructor.
     * @param ApplicationInterface $app
     */
    public function __construct(ApplicationInterface $app)
    {
        $this->app = $app;

        $arr = $this->app->getClassloader()->getPrefixesPsr4();

        if (!isset($arr['Nicodinus\\KFG2\\Common\\'])) {
            throw new \RuntimeException("Invalid package configuration!");
        }

        $this->scriptPath = realpath($arr['Nicodinus\\KFG2\\Common\\'][0]);
    }

    /**
     * @param string $namespace
     * @param int $options
     *
     * @return Promise<string[]>|Failure<\Throwable>
     */
    public function findClassnames(string $namespace, int $options): Promise
    {
        return call(static function (self &$self) use ($namespace, $options) {

            try {

                $scriptPath = __DIR__
                    . DIRECTORY_SEPARATOR . "Internal"
                    . DIRECTORY_SEPARATOR . "SyncWrappers"
                    . DIRECTORY_SEPARATOR . "find_classnames.php"
                ;

                $scriptPathArg = escapeshellarg($scriptPath);

                $vendorPathArg = escapeshellarg($self->app->getAppDirectory() . DIRECTORY_SEPARATOR . "vendor");

                $namespaceArg = escapeshellarg($namespace);
                $optionsArg = escapeshellarg($options);

                $process = new Process("php {$scriptPathArg} {$vendorPathArg} {$namespaceArg} {$optionsArg}");
                yield $process->start();

                $resultPromise = Promise\any([
                    (new Payload($process->getStdout()))->buffer(),
                    (new Payload($process->getStderr()))->buffer(),
                ]);

                [$result, $exitCode] = yield Promise\all([
                    $resultPromise,
                    $process->join(),
                ]);

                //dump($result);

                if (!empty($result[0])) {
                    throw $result[0];
                }

                $result = trim(implode("\n", $result[1]));

                //dump($result, $exitCode);

                //trying decode json output
                $result1 = json_decode($result, true);
                if (!$result1) {
                    throw new \RuntimeException("Invalid script output!");
                }

                if (isset($result1['error'])) {
                    throw new $result1['error']['class']($result1['error']['message'], $result1['error']['code']);
                }

                if (isset($result1['result'])) {
                    return $result1['result'];
                }

                throw new \RuntimeException("There is empty result!");

            } catch (\Throwable $e) {
                return new Failure($e);
            }

        }, $this);
    }
}