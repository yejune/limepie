<?php declare(strict_types=1);

namespace Limepie\Form;

class Validation
{
    public static $methods = [];

    public $language = 'ko';

    public $strictMode = true;

    public $data = [];

    public $errors = [];

    public $defaultMessages = [
        'required'    => 'This field is required.',
        'remote'      => 'Please fix this field.',
        'email'       => 'Please enter a valid email address.',
        'url'         => 'Please enter a valid URL.',
        'date'        => 'Please enter a valid date.',
        'dateISO'     => 'Please enter a valid date (ISO).',
        'number'      => 'Please enter a valid number.',
        'digits'      => 'Please enter only digits.',
        'equalTo'     => 'Please enter the same value again.',
        'maxlength'   => 'Please enter no more than {0} characters.',
        'minlength'   => 'Please enter at least {0} characters.',
        'rangelength' => 'Please enter a value between {0} and {1} characters long.',
        'range'       => 'Please enter a value between {0} and {1}.',
        'max'         => 'Please enter a value less than or equal to {0}.',
        'min'         => 'Please enter a value greater than or equal to {0}.',
        'maxcount'    => 'Please enter a value less than or equal to {0}.',
        'mincount'    => 'Please enter a value greater than or equal to {0}.',
        'step'        => 'Please enter a multiple of {0}.',
        'unique'      => 'unique',
        'accept'      => 'Please enter a value with a valid mimetype.',
        'in'          => 'Not a allowed value',
        'enddate'     => 'Must be greater than {0}.',
    ];

    public function __construct($data = [], $language = '')
    {
        $this->data = $data;

        if ($language) {
            $this->language = $language;
        } else {
            $this->language = \Limepie\get_language();
        }
    }

    public function setStrictMode($mode)
    {
        $this->strictMode = $mode;

        return $this;
    }

    public static function addMethod($name, $callback)
    {
        static::$methods[$name] = $callback;
    }

    public function getMethod($name)
    {
        $callback = static::$methods[$name] ?? null;

        if (true === \is_callable($callback)) {
            return $callback->bindTo($this);
        }

        return false;
    }

    public function validate(array $specs, ?array $data = [], string $name = '')
    {
        $valid = true;
// if(false === isset($specs['properties'])) {
//     \pr($specs);
// }
        foreach ($specs['properties'] as $propertyKey => $propertyValue) {
            $propertyValue['key'] = $propertyKey;
            $fixPropertyKey       = $propertyKey;
            $isArray              = false;

            if (false !== \strpos((string) $fixPropertyKey, '[]')) {
                $fixPropertyKey = \str_replace('[]', '', $fixPropertyKey);
                $isArray        = true;
            }

            $propertyName = $fixPropertyKey;

            if ($name) {
                $propertyName = $name . '[' . $fixPropertyKey . ']';
            }

            $values = $data[$fixPropertyKey] ?? null;

            if ('group' === $propertyValue['type']) {
                if (false === $isArray) {
                    //\pr($propertyValue, $values, $propertyName);
                    $result = $this->validate($propertyValue, $values, $propertyName);

                    if (false === $result) {
                        $valid = false;
                    }
                    unset($data[$fixPropertyKey]);
                } else {
                    if (true === \is_array($values)) {
                        foreach ($values as $valueKey => $valueValue) {
                            $result = $this->validate($propertyValue, $valueValue, $propertyName . '[' . $valueKey . ']');

                            if (false === $result) {
                                $valid = false;
                            }
                            unset($data[$fixPropertyKey][$valueKey]);
                        }

                        if (!$data[$fixPropertyKey]) {
                            unset($data[$fixPropertyKey]);
                        }
                    } else {
                        // false 가 아닌 error임. 디폴트 form struct가 있어서 property spec이 배열이면 배열로 넘어와야 함.

                        throw new \Exception($fixPropertyKey . ' data not found.');
                    }
                }
            } else {
                //\pr($propertyName, $propertyValue, $values);
                // valid check
                //var dotName = preg_replace('/\[/g', '.').replace(/\]/g, '');

                $dotName = \str_replace(['[', ']'], ['.', ''], $propertyName);
                //\pr($dotName);

                if (true === isset($this->reverseConditions[$dotName])) {
                    //console.log('aaaaatttt',dotName, this.reverseConditions[dotName]);
                    $condition     = $this->reverseConditions[$dotName];
                    $continueLevel = false;

                    foreach ($condition as $key => $va1) {
                        $vl = $this->getValue($this->getNameByDot($key));

                        if (false === $va1[$vl]) {
                            $continueLevel = true;

                            continue;
                        }
                        // alert(0);
                            // return false;
                    }

                    if ($continueLevel) {
                        //delete data[fixPropertyKey];

                        continue;
                    }
                    //dd
                }

                if (false === $isArray) {
                    $result = $this->check($propertyName, $propertyValue, $values);

                    if (false === $result) {
                        $valid = false;
                    }

                    unset($data[$fixPropertyKey]);
                } else {
                    if (true === \is_array($values)) {
                        foreach ($values as $valueKey => $valueValue) {
                            $result = $this->check($propertyName . '[' . $valueKey . ']', $propertyValue, $valueValue);

                            if (false === $result) {
                                $valid = false;
                            }
                            unset($data[$fixPropertyKey][$valueKey]);
                        }

                        if (!$data[$fixPropertyKey]) {
                            unset($data[$fixPropertyKey]);
                        }
                    } else {
                        $result = $this->check($propertyName, $propertyValue, $values);

                        if (false === $result) {
                            $valid = false;
                        }
                    }
                }
            }
        }

        if ($data && $this->strictMode) {
            throw new \Exception('spec, element not equal.');
        }
        //pr($this->errors);
        return $valid;
    }

    public function sprintf($format, $param)
    {
        if (false === \is_array($param)) {
            $param = [$param];
        }
        $format = \preg_replace("/\{([0-9]+)\}/", '%s', $format);

        return \call_user_func_array('sprintf', \array_merge([$format], $param));
    }

    public function getLength($value)
    {
        if (true === \is_array($value)) {
            $length = \count($value);
        } else {
            $length = \count(\preg_split('//u', (string) $value, -1, \PREG_SPLIT_NO_EMPTY));
        }

        return $length;
    }

    public function getNameByDot($dotName)
    {
        $parts = \explode('.', $dotName);

        if (1 < \count($parts)) {
            $first = \array_shift($parts);

            return $first . '[' . \implode('][', $parts) . ']';
        }

        return $dotName;
    }

    public function getNameByArray($parts)
    {
        if (1 < \count($parts)) {
            $first = \array_shift($parts);

            return $first . '[' . \implode('][', $parts) . ']';
        }

        return $dotName;
    }

    public function getValue($name)
    {
        $name  = \str_replace(']', '', $name);
        $names = \explode('[', $name);
        $data  = $this->data;

        foreach ($names as $name) {
            if (true === isset($data[$name])) {
                $data = $data[$name];
            } else {
                return false;
            }
        }

        return $data;
    }

    public function optional($value, $name = '')
    {
        $callback = $this->getMethod('required');
        // 값이 있으면 false로 보내서 다음 check를 하게 한다.
        return !$callback($value, $name, '');
    }

    public function check($name, $property, $value)
    {
        // if (true === isset($property['rules']['required'])) {
        //     if ($this->isBool($property['rules']['required'])) {

        //     } elseif (true === \is_array($property['rules']['required'])) {
        //         // tmp
        //     }
        // }
        //\pr($name, $property, $value);
        $messages = $property['messages'] ?? [];
        $language = $this->language;
        //\pr($property);

        if (true === isset($property['rules'])) {
            foreach ($property['rules'] as $ruleName => $ruleParam) {
                if ($callback = $this->getMethod($ruleName)) {
                    //\pr([$name, $property, $ruleName, $value,  $ruleParam]);

                    if ($callback($value, $name, $ruleParam)) {
                        //\pr($ruleName, $value, $name, $ruleParam);
                    } else {
                        if ($messages[$ruleName][$language] ?? false) {
                            $message = $messages[$ruleName][$language];
                        } elseif ($messages[$ruleName] ?? false) {
                            $message = $messages[$ruleName];
                        } elseif ($messages[$language][$ruleName] ?? false) {
                            $message = $messages[$language][$ruleName];
                        } elseif ($this->defaultMessages[$ruleName] ?? false) {
                            $message = $this->defaultMessages[$ruleName];
                        } else {
                            $message = 'error';
                        }

                        $error = [
                            'field'   => $name,
                            'type'    => $ruleName,
                            'param'   => $ruleParam,
                            'value'   => $value,
                            'message' => $this->sprintf($message, $ruleParam),
                        ];
                        $this->errors[] = $error;

                        //return false;
                    }
                } else {
                    $this->errors[] = [
                        'field'   => $property['key'],
                        'type'    => $ruleName,
                        'param'   => $ruleParam,
                        'value'   => $value,
                        'message' => 'not support',
                    ];

                    //return false;
                }
            }
        }

        if ($this->errors) {
            return false;
        }

        return true;
    }
}
Validation::addMethod('required', function($value, $name, $param) {
    if (false === \Limepie\is_boolean_type($param)) {
        return true;
    }
    // require = false일 경우 true
    if (false === $param) {
        return true;
    }

    if (true === \is_array($value) && \count($value)) {
        return true;
    }

    if (0 < \strlen((string) $value)) {
        return true;
    }

    return false;
});

Validation::addMethod('recaptcha', function($value, $name, $param) {
    return $this->optional($value) || $value;
});

Validation::addMethod('minlength', function($value, $name, $param) {
    $length = $this->getLength($value);

    return $this->optional($value) || $length >= $param;
});

Validation::addMethod('even', function($value, $name, $param) {
    $length = $this->getLength($value);

    return $this->optional($value) || 0 === (int) $value % 2;
});

Validation::addMethod('odd', function($value, $name, $param) {
    $length = $this->getLength($value);

    return $this->optional($value) || 1 === (int) $value % 2;
});

Validation::addMethod('match', function($value, $name, $param) {
    $a = $this->optional($value, $name) || \preg_match('/^' . $param . '$/', (string) $value, $m);
    // \pr($m);
    //pr(Validation::getMethod('required')($value,'',''),$value, $name, $param, $this->optional($value), \preg_match('/^' . $param . '$/', $value));
    return $a;
});

Validation::addMethod('maxlength', function($value, $name, $param) {
    $length = $this->getLength($value);

    return $this->optional($value) || $length <= $param;
});

Validation::addMethod('rangelength', function($value, $name, $param) {
    $length = $this->getLength($value);

    return $this->optional($value) || $length >= $param[0] && $length <= $param[1];
});

Validation::addMethod('min', function($value, $name, $param) {
    return $this->optional($value) || $value >= $param;
});

Validation::addMethod('max', function($value, $name, $param) {
    return $this->optional($value) || $value <= $param;
});

Validation::addMethod('range', function($value, $name, $param) {
    return $this->optional($value) || $value >= $param[0] && $value <= $param[1];
});

Validation::addMethod('in', function($value, $name, $param) {
    $enum = \Limepie\array_value_flatten($param);

    return $this->optional($value) || false !== \in_array($value, $enum, false);
});

// required일때 동작
Validation::addMethod('mincount', function($value, $name, $param) {
    $elements = $this->getValue($name);
    $count = 0;

    if (true === \is_array($elements)) {
        foreach ($elements as $val) {
            if (0 < \strlen($val)) {
                $count++;
            }
        }
    }

    return $this->optional($value) || $count >= $param;

    return $count >= $param;
});

Validation::addMethod('maxcount', function($value, $name, $param) {
    $elements = $this->getValue($name);
    $count = 0;

    if (true === \is_array($elements)) {
        foreach ($elements as $val) {
            if (0 < \strlen($val)) {
                $count++;
            }
        }
    }

    return $this->optional($value) || $count <= $param;

    return $count >= $param;
});

Validation::addMethod('unique', function($value, $name, $param) {
    $unique = [];
    $check = false;
    $name = \preg_replace('#\[__([0-9a-z\*]{13,13})__\]$#', '', $name);
    $data = $this->getValue($name);

    if (true === \is_array($data)) {
        foreach ($data as $v) {
            if (true === isset($unique[$v])) {
                $check = true;
            }
            $unique[$v] = 1;
        }

        return $this->optional($value) || !$check; //$length == $unique;
    }

    return true;
});
Validation::addMethod('accept', function($value, $name, $pattern) {
    if ($optionValue = $this->optional($value)) {
        return $optionValue;
    }

    if (isset($value['type'])) {
        $pattern = \str_replace(',', '|', $pattern);
        $pattern = \str_replace('/*', '/.*', $pattern);

        return \preg_match('#\.?(' . $pattern . ')$#', $value['type']);
    }

    return true;
});
Validation::addMethod('email', function($value, $name, $param) {
    return $this->optional($value) || false !== \filter_var($value, \FILTER_VALIDATE_EMAIL);
});

Validation::addMethod('url', function($value, $name, $param) {
    return $this->optional($value) || false !== \filter_var($value, \FILTER_VALIDATE_URL);
});

Validation::addMethod('date', function($value, $name, $param) {
    return $this->optional($value) || false !== \strtotime($value);
});

Validation::addMethod('dateISO', function($value, $name, $param) {
    return $this->optional($value) || \preg_match('/^\d{4}[\/\-](0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])$/', $value);
});

Validation::addMethod('number', function($value, $name, $param) {
    return $this->optional($value) || \is_numeric($value);
});

Validation::addMethod('digits', function($value, $name, $param) {
    return $this->optional($value) || \preg_match('/^\d+$/', $value);
});

Validation::addMethod('equalTo', function($value, $name, $param) {
    if ($this->optional($value)) {
        return true;
    }

    $target = \ltrim($param, '#.');

    return $this->getValue($target) === $value;
});

Validation::addMethod('enddate', function($value, $name, $param) {
    $start = $this->getValue($this->getNameByDot($param));

    return \strtotime($start) <= \strtotime($value) || '' === $value;
});
