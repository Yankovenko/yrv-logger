<?php

namespace YRV\Logger;

use YRV\Utils\DataObject;

class LogData extends DataObject
{

    protected LogConfigurator $configurator;

    public function __construct($data, LogConfigurator $configurator = null)
    {
        if ($configurator) {
            $this->configurator = $configurator;
        }
        parent::__construct($data);
    }

    public function get($name)
    {

        $data = $this->data;
        if (array_key_exists($name, $data)) {
            return $data[$name];
        }

        if (isset($this->configurator)) {
            return $this->configurator->getParam($name);
        }

        return null;
    }
}