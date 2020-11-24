<?php


namespace Nicodinus\KFG2\Common\Application\Providers;


use Nicodinus\KFG2\Common\Application\ApplicationInterface;
use Nicodinus\KFG2\Common\Support\GracefulShutdownTrait;
use Psr\Log\LoggerInterface;


abstract class AbstractAppProvider implements AppProviderInterface
{
    use AppProviderTrait, GracefulShutdownTrait;

    /**
     * @return ApplicationInterface
     */
    public function getApp(): ApplicationInterface
    {
        return $this->app;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->getApp()->getLogger();
    }
}