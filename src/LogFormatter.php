<?php

namespace YRV\Logger;

class LogFormatter implements LogFormatterInterface
{
    const
        TYPE_TEXT = 'text',
        TYPE_JSON = 'json',
        TYPE_HTML = 'html';

    public string $type = self::TYPE_TEXT;

    public int $maxNestedLevel = 10;

    public array $fields = [
//        'date' => [
//            'type' => 'date',
//            'format' => 'trim'
//        ],
//        'key' => [
//            'type' => 'string',
//        ],
//        'type' => [
//            'type' => 'string',
//            'format' => 'uppercase'
//        ],
//        'message' => [
//            'type' => 'string',
//            'format' => 'trim'
//        ],
    ];

    public string $pattern;
    public string $fieldsSeparator = "\t";
    public string $lineSeparator = PHP_EOL;

    public function __construct($format)
    {
        $this->initFormat($format);
    }

    public function format(LogData $logData): string
    {
        $data = [];

        foreach($this->fields as $name => $field) {
            $key = $field['key'] ?? $name;
            $value = $logData->get($key);
            $data[$name] = $this->prepare($value, $field);
        }
        return $this->convertToString($data);
    }

    protected function prepare($value, $field)
    {
        try {
            switch ($field['type'] ?? null) {

                case 'int':

                    $value = (string)(int)$value;
                    break;

                case 'date':

                    if (is_numeric($value)) {
                        $value = (new \DateTime())->setTimestamp($value);

                    } elseif (is_string($value)) {
                        $value = new \DateTimeImmutable($value);

                    } elseif (!$value instanceof \DateTimeInterface) {
                        $value = new \DateTimeImmutable();
                    }

                    if (isset($field['format']) && is_string($field['format']) && !empty($field['format'])) {
                        $value = $value->format($field['format']);

                    } else {
                        $value = $value->format(\DateTimeInterface::RFC3339_EXTENDED);
                    }

                    break;

                case 'bool':

                    $value = (empty($value)) ? 'false' : 'true';
                    break;

                case 'string':
                    $value = (string)$value;
                    if (
                        isset($field['format'])
                        && in_array ($field['format'],
                            ['trim', 'strtoupper', 'strtolower', 'rtrim', 'ltrim', 'length', 'md5', 'addslashes', 'htmlspecialchars']
                        )
                    ) {
                        $format = $field['format'];
                        $value = $format($value);
                    }
                    break;

                default:
                    $value = $this->safe($value);
                    if (!empty($value)
                        && $this->type == self::TYPE_TEXT
                        && isset($field['format'])
                        && $field['format'] === 'pretty'
                    ) {
                        $value = $this->lineSeparator
                            .json_encode($value, JSON_INVALID_UTF8_IGNORE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRETTY_PRINT)
                            .$this->lineSeparator;
                    }
            }

        } catch (\Exception $exception) {
            $value = 'ERROR FORMAT: '.$exception->getMessage();
        }

        return $value;
    }

    protected function convertToString($data): string
    {
        $result = '';
        switch($this->type) {
            case self::TYPE_TEXT:

                if ($this->pattern) {
                    $result = $this->pattern;
                    foreach ($data as $key => $val) {
                        if (!is_string($val)) {
                            $val = json_encode($val, JSON_INVALID_UTF8_IGNORE | JSON_INVALID_UTF8_SUBSTITUTE);
                        }
                        $result = str_replace($key, $val, $result);
                    }
                } else {
                    $result = implode ($this->fieldsSeparator, $data);
                }
                break;

            case self::TYPE_JSON:
                $result = json_encode($data, JSON_INVALID_UTF8_IGNORE | JSON_INVALID_UTF8_SUBSTITUTE);
                break;

            case self::TYPE_HTML:
                // TODO
                break;
        }
        $result .= $this->lineSeparator;
        return $result;
    }


    protected function initFormat($format)
    {
        if (is_string ($format)) {
            $this->type = self::TYPE_TEXT;

            if (preg_match_all('/\[([\w\d_\.]+)(\:(\w+))?\]/ui', $format, $matches)) {
                foreach ($matches[0] as $i => $v) {

                    if (in_array ($matches[1][$i], ['context', 'backtrace'])) {
                        $type = 'array';

                    } elseif (in_array ($matches[1][$i], ['date', 'time'])) {
                        $type = 'date';

                    } else {
                        $type = 'string';
                    }

                    $this->fields[$v] = [
                        'key' => $matches[1][$i],
                        'type' => $type,
                        'format' => $matches[3][$i] ?? null
                    ];
                }
            }
            $this->pattern = $format;
        } elseif (is_array($format)) {
            $this->fields = $format;
        }
    }

    protected function safe($obj, $level = 0)
    {
        if ($level >= $this->maxNestedLevel) {
            return '';
        }

        $result = '';
        $arg = '';

        if (is_string($obj)) {
            $result = $obj;

        } elseif (is_int($obj)) {
            $result = (string)$obj;

        } elseif (is_float($obj)) {
            $result = (string)$obj;

        } elseif (is_null($obj)) {
            $result = 'null';

        } elseif (is_bool($obj)) {
            $result = $obj ? 'true' : 'false';

        } elseif (is_float($obj)) {
            $result = (string)$obj;

        } elseif (is_array($obj)) {
            $result = [];
            foreach ($obj as $key => $val) {
                $result[$key] = $this->safe($val, $level + 1);
            }

        } elseif (is_object($obj)) {
            if ($obj instanceof \Serializable) {
                $result = $obj->serialize();

            } elseif (method_exists($obj, '__toString')) {
                $result = $obj->__toString();

            } else {
                $result = 'object: ' . get_class($obj);
            }

        } elseif (is_callable($obj, false, $arg)) {
            $result = 'function: ' . ($arg ?? 'anonymous');

        } elseif (is_resource($obj)) {
            $result = 'resource: #' . get_resource_id($obj) . ' ' . get_resource_type($obj);

        } else {
            $result = 'type: ' . gettype($obj);
        }

        return $result;
    }

}