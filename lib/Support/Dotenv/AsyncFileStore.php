<?php


namespace Nicodinus\KFG2\Common\Support\Dotenv;


use Amp\Failure;
use Amp\Promise;
use Dotenv\Exception\InvalidPathException;
use Dotenv\Store\StoreInterface;
use function Amp\call;


class AsyncFileStore implements StoreInterface
{
    /**
     * The file paths.
     *
     * @var string[]
     */
    private array $filePaths;

    /**
     * Should file loading short circuit?
     *
     * @var bool
     */
    private bool $shortCircuit;

    /**
     * The file encoding.
     *
     * @var string|null
     */
    private ?string $fileEncoding;

    /**
     * Create a new file store instance.
     *
     * @param string[]    $filePaths
     * @param bool        $shortCircuit
     * @param string|null $fileEncoding
     *
     * @return void
     */
    public function __construct(array $filePaths, bool $shortCircuit, string $fileEncoding = null)
    {
        $this->filePaths = $filePaths;
        $this->shortCircuit = $shortCircuit;
        $this->fileEncoding = $fileEncoding;
    }

    /**
     * Read the content of the environment file(s).
     *
     * @return Promise<string>|Failure<\Dotenv\Exception\InvalidEncodingException|\Dotenv\Exception\InvalidPathException>
     */
    public function read()
    {
        return call(static function (self &$self) {
            
            try {

                if ($self->filePaths === []) {
                    throw new InvalidPathException('At least one environment file path must be provided.');
                }

                $contents = yield AsyncFileReader::read($self->filePaths, $self->shortCircuit, $self->fileEncoding);

                if (\count($contents) > 0) {
                    return \implode("\n", $contents);
                }

                throw new InvalidPathException(
                    \sprintf('Unable to read any of the environment file(s) at [%s].', \implode(', ', $self->filePaths))
                );
                
            } catch (\Throwable $e) {
                return new Failure($e);
            }
            
        }, $this);
    }
}