<?php


namespace Nicodinus\KFG2\Common\Support\Dotenv;


use Amp\Failure;
use Amp\Promise;
use Dotenv\Exception\InvalidEncodingException;
use Dotenv\Util\Str;
use Nicodinus\KFG2\Common\Support\ClassSingletonTrait;
use PhpOption\Option;
use function Amp\call;
use function Amp\File\filesystem;


class AsyncFileReader
{
    use ClassSingletonTrait;
    
    /**
     * This class is a singleton.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    private function __construct()
    {
        //
    }

    /**
     * Read the file(s), and return their raw content.
     *
     * We provide the file path as the key, and its content as the value. If
     * short circuit mode is enabled, then the returned array with have length
     * at most one. File paths that couldn't be read are omitted entirely.
     *
     * @param string[]    $filePaths
     * @param bool        $shortCircuit
     * @param string|null $fileEncoding
     *
     * @return Promise<array<string,string>>|Failure<\Dotenv\Exception\InvalidEncodingException>
     */
    public static function read(array $filePaths, bool $shortCircuit = true, string $fileEncoding = null): Promise
    {
        return call(static function () use ($filePaths, $shortCircuit, $fileEncoding) {
            
            try {

                $output = [];

                foreach ($filePaths as $filePath) {
                    /** @var \PhpOption\Option<string> $content */
                    $content = yield self::readFromFile($filePath, $fileEncoding);
                    if ($content->isDefined()) {
                        $output[$filePath] = $content->get();
                        if ($shortCircuit) {
                            break;
                        }
                    }
                }

                return $output;
                
            } catch (\Throwable $e) {
                return new Failure($e);
            }
            
        });
    }

    /**
     * Read the given file.
     *
     * @param string      $path
     * @param string|null $encoding
     *
     * @return Promise<\PhpOption\Option<string>>|Failure<\Dotenv\Exception\InvalidEncodingException>
     */
    private static function readFromFile(string $path, string $encoding = null): Promise
    {
        return call(static function () use ($path, $encoding) {
            
            try {

                $fs = filesystem();
                $content = "";

                if (true === (yield $fs->exists($path)) && true === (yield $fs->isfile($path))) {
                    $content = yield $fs->get($path);
                }

                /** @var Option<string> */
                $content = Option::fromValue($content, false);

                return $content->flatMap(static function (string $content) use ($encoding) {
                    return Str::utf8($content, $encoding)->mapError(static function (string $error) {
                        throw new InvalidEncodingException($error);
                    })->success();
                });
                
            } catch (\Throwable $e) {
                return new Failure($e);
            }
            
        });
    }
}