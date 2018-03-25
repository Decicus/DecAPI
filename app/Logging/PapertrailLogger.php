<?php

namespace App\Logging;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Logger;

class PapertrailLogger
{
    /**
     * Create a custom Monolog instance.
     *
     * @param  array  $config
     * @return \Monolog\Logger
     */
    public function __invoke(array $config)
    {
        $ptDestination = env('PAPERTRAIL_LOG_DESTINATION', null);

        if (!empty($ptDestination)) {
            $destination = explode(':', $ptDestination);

            $handler = new SyslogUdpHandler($destination[0], $destination[1]);
        } else {
            $handler = new ErrorLogHandler();
        }

        $logger = new Logger('papertrail');

        $handler->setFormatter(new LineFormatter('%channel%.%level_name%: %message% %context% %extra%'));
        $logger->pushHandler($handler);

        return $logger;
    }
}
