<?php

namespace App\Service;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class Log
{
    /**
     * The resolved object instances.
     *
     * @var LoggerInterface
     */
    private static LoggerInterface $instance;

    /**
     * Initialise logger
     * The constructor is private to prevent initiation with outer code.
     * @var LoggerInterface
     */
    private static function init()
    {
        static::$instance = new Logger('app');
        static::$instance->pushHandler(new StreamHandler(__DIR__ . '/../../'. $_ENV['LOG_PATH'], Logger::DEBUG));
    }

    /**
     * Handle dynamic, static calls to the object.
     *
     * @param  string  $method
     * @param  array   $args
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public static function __callStatic($method, $args)
    {
        if (!isset(static::$instance)) {
            static::init();
        }

        $instance = static::$instance;

        return $instance->$method(...$args);
    }
}