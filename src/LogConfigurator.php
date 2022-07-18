<?php

namespace YRV\Logger;

use Exception;
use Psr\Log\LogLevel;
use YRV\Utils\DataObject;

class LogConfigurator extends DataObject
{
    static public array $levels = [
        LogLevel::DEBUG => 1,
        LogLevel::INFO => 2,
        LogLevel::NOTICE => 3,
        LogLevel::WARNING => 4,
        LogLevel::ERROR => 5,
        LogLevel::CRITICAL => 6,
        LogLevel::ALERT => 7,
        LogLevel::EMERGENCY => 8,
    ];

    static public string $defaultFormatter = "[date]\t[puk]\t[remote_addr]\t[[level:strtoupper]]\t[message]\t[http_host] [request_uri]\t[HTTP_USER_AGENT]\t[context:pretty]\t[backtrace:pretty]";

    public array $data = [
        'minimalLevel' => LogLevel::WARNING,
        'backtraceLevels' => [
            LogLevel::DEBUG => false,
            LogLevel::INFO => false,
            LogLevel::NOTICE => false,
            LogLevel::WARNING => true,
            LogLevel::ERROR => true,
            LogLevel::CRITICAL => true,
            LogLevel::ALERT => true,
            LogLevel::EMERGENCY => true,
        ],
        'formatter' => null,
        'filename' => '[Y-m-d].log',
        'dir' => '',

        // global default params: ip, ip4, ip6, user_agent, os, host,
        // filling by request
        'params' => []
    ];

    public function setMinimalLevel($level): self
    {
        if (array_key_exists($level, static::$levels)) {
            $this->data['minimalLevel'] = $level;
        }
        return $this;
    }

    public function setLogDir($dir): self
    {
        $this->data['dir'] = $dir;
        return $this;
    }

    public function setLogFile($file): self
    {
        $this->data['filename'] = $file;
        return $this;
    }

    public function getFile(): string
    {
        $filename = $this->data['filename'];

        if (preg_match ('/\[(.*)\]/', $filename, $matches)) {
            $date = date($matches[1]);
            $filename = str_replace($matches[0], $date, $filename);
        }

        if (isset($this->data['dir'])) {
            $filename = $this->data['dir'] . '/' . $filename ;
        }

        return $filename;
    }


    public function needLogging($level): bool
    {
        if (
            array_key_exists($level, static::$levels)
            && static::$levels[$level] >= static::$levels[$this->data['minimalLevel']]
        ) {
            return true;
        }
        return false;
    }

    public function needBacktrace($level): bool
    {
        return $this->data['backtraceLevels'][$level] ?? false;
    }

    public function setBacktraceFromLevel($level): self
    {
        if (array_key_exists($level, static::$levels)) {
            $levelNum = static::$levels[$level];
            array_walk(static::$levels, function($val, $key) use ($levelNum) {
                if ($val >= $levelNum) {
                    $this->data['backtraceLevels'][$key] = true;
                }
            });
        }
        return $this;
    }

    /**
     * @param  LogFormatterInterface|string|array $formatter
     * @return $this
     */
    public function setFormatter($formatter): self
    {
        if (is_string($formatter) || is_array($formatter)) {
            $formatter = new LogFormatter($formatter);
        }

        if (!$formatter instanceof LogFormatterInterface) {
            throw new Exception('Error formatter');
        }

        $this->data['formatter'] = $formatter;
    }

    public function getFormatter(): LogFormatterInterface
    {
        if (!$this->data['formatter']) {
            $this->data['formatter'] = new LogFormatter(static::$defaultFormatter);
        }
        return $this->data['formatter'];
    }

    public function getParam($name)
    {
        if (array_key_exists($name, $this->data['params'])) {
            return $this->data['params'][$name];
        }

        $name = strtoupper($name);
        if (isset($_SERVER[$name])) {
            return $this->data['params'][$name] = $_SERVER[$name];
        } elseif (isset($_ENV[$name])) {
            return $this->data['params'][$name] = $_ENV[$name];
        }

        return $this->data['params'][$name] = null;
    }

}