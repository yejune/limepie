<?php declare(strict_types=1);

namespace Limepie\Form\Validation;

class Exception extends \Exception
{
    public $field;

    public $type;

    public $statusCode = 400;

    public $value;

    public function field($field)
    {
        return $this->setField($field);
    }

    public function setField(string $field)
    {
        $this->field = $field;

        return $this;
    }

    public function getField()
    {
        return $this->field;
    }

    public function type($type)
    {
        return $this->setType($type);
    }

    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function statusCode($code)
    {
        return $this->setStatusCode($code);
    }

    public function setStatusCode($code)
    {
        $this->statusCode = $code;

        return $this;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getValue()
    {
        return $this->value;
    }
}
