<?php declare(strict_types=1);

namespace Limepie\Form\Validation;

class Message
{
    public $message;

    public $statusCode = 400;

    public $field;

    public $type;

    public $value;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function statusCode($code)
    {
        return $this->setStatusCode($code);
    }

    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function field($field)
    {
        return $this->setField($field);
    }

    public function setField($field)
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

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getValue()
    {
        return $this->value;
    }
}
