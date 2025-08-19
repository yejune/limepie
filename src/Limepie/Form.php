<?php

declare(strict_types=1);

namespace Limepie;

use Limepie\Form\Generation;
use Limepie\Form\Generator;
use Limepie\Form\Validation;

class Form
{
    public $spec = [];

    public $reverseConditions = [];

    public $validation;

    public $strictMode = false;

    public function __construct(array $spec = [])
    {
        $this->spec = $spec;

        if (true === isset($this->spec['conditions'])) {
            foreach ($this->spec['conditions'] as $key => $value) {
                foreach ($value as $k2 => $v2) {
                    foreach ($v2 as $k3 => $v3) {
                        // if ('undefined' === typeof this.reverseConditions[k3]) {
                        //     this.reverseConditions[k3] = {};
                        // }
                        // if ('undefined' === typeof this.reverseConditions[k3][key]) {
                        //     this.reverseConditions[k3][key] = {};
                        // }
                        $this->reverseConditions[$k3][$key][$k2] = $v3;
                    }
                }
            }
        }
    }

    public function setStrictMode($mode)
    {
        $this->strictMode = $mode;

        return $this;
    }

    public function validation(array $data = [], $language = '')
    {
        $this->validation                    = new Validation($data, $language);
        $this->validation->strictMode        = $this->strictMode;
        $this->validation->reverseConditions = $this->reverseConditions;

        return $this->validation->validate($this->spec, $data);
    }

    public function getErrors()
    {
        return $this->validation->errors;
    }

    public function write(null|array|ArrayObject $data = [])
    {
        $generation = new Generation();

        return $generation->write($this->spec, $data ?? []);
    }

    public function write2(null|array|ArrayObject $data = [])
    {
        $generation = new Generator();

        return $generation->write($this->spec, $data ?? []);
    }

    public function read2(null|array|ArrayObject $data = [])
    {
        $generation = new Generator();

        return $generation->read($this->spec, $data ?? []);
    }

    public function read(array $data = [])
    {
        $generation = new Generation();

        return $generation->read($this->spec, $data);
    }

    public function list(array $data = [])
    {
        $generation = new Generation();

        return $generation->list($this->spec, $data);
    }
}
