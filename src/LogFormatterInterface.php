<?php

namespace YRV\Logger;

interface LogFormatterInterface
{
    public function format(LogData $logData): string;
}