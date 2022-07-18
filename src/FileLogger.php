<?php

namespace YRV\Logger;

use Exception;

class FileLogger extends AbstractLogger
{
    protected LogConfigurator $configurator;

    /** @var resource */
    protected $resource;

    public string $fileName;
    public string $key;


    public function __construct(LogConfigurator $configurator, $file = 'php://error', $key = '')
    {
        $this->configurator = $configurator;
        $this->fileName = $configurator->getFile();
        $this->key = $key;
    }

    public function __destruct()
    {
//        echo "_";
//        if ($this->resource) {
//            fclose($this->resource);
//            unset($this->resource);
//        }
    }

    /**
     * @throws Exception
     */
    public function log($level, $message, array $context = array())
    {
        if (!$this->configurator->needLogging($level)) {
            return;
        }

        $message = $this->prepareMessage($message, $context);
        $data = [
            'message' => $message,
            'context' => $context,
            'level' => $level
        ];

        if ($this->configurator->needBacktrace($level)) {
            $data['backtrace'] = $this->getBacktrace();
        }

        $logData = new LogData($data, $this->configurator);

        $formatter = $this->configurator->getFormatter();
        $string = $formatter->format($logData);
        $resource = $this->getFileResource();

        fwrite($resource, $string);
    }

    /**
     * @return false|mixed|resource|null
     * @throws Exception
     */
    protected function getFileResource()
    {
        if (isset($this->resource)) {
            return $this->resource;
        }

        $this->resource = $this->getResource($this->fileName);

        if (isset($this->resource)) {
            return $this->resource;
        }

        $this->resource = @fopen($this->fileName, 'a');
        if ($this->resource === false) {
            throw new Exception("Error open log file ".$this->fileName);
        }

        $this->setResource($this->fileName, $this->resource);
        return $this->resource;
    }

    protected function getBacktrace(): array
    {
        $backtrace =  debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS|DEBUG_BACKTRACE_PROVIDE_OBJECT, 10);
        array_shift($backtrace);
        array_shift($backtrace);
        array_shift($backtrace);
//print_r ($backtrace);
        return $backtrace;
    }
}
