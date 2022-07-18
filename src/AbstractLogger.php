<?php

namespace YRV\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use YRV\Utils\Obj;

abstract class AbstractLogger implements LoggerInterface
{

    static protected array $resources = [];

    /**
     * System is unusable.
     *
     * @param string $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function emergency($message, array $context = array())
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function alert($message, array $context = array())
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function critical($message, array $context = array())
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function error($message, array $context = array())
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function warning($message, array $context = array())
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function notice($message, array $context = array())
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function info($message, array $context = array())
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function debug($message, array $context = array())
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    abstract public function log($level, $message, array $context = array());


    protected function prepareMessage($message, $context = []): string
    {
        if (is_object($message)) {
            if (method_exists($message, '__toString')) {
                $message = (string) $message;
            } else {
                $message = Obj::name($message);
            }
        } elseif (!is_string($message)) {
            throw new \Exception('Message is incorrect type');
        }

        if (!empty($context) && preg_match_all('/{([a-zA-Z0-9_\.]+)}/m', $message, $matches)) {
            foreach ($matches[1] as $key => $val) {
                if (isset($context[$val])) {
                    $message = str_replace($matches[0][$key], Obj::name($context[$val]), $message);
                }
            }
        }
        return $message;
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    protected function getResource(string $name)
    {
        if (array_key_exists($name, static::$resources)) {
            return static::$resources[$name];
        }
        return null;
    }

    /**
     * @param string $name
     * @param mixed $resource
     * @return void
     */
    protected function setResource(string $name, &$resource)
    {
        static::$resources[$name] = &$resource;
    }
}
