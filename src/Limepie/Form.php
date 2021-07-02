<?php declare(strict_types=1);

namespace Limepie;

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
        $this->validation                    = new \Limepie\Form\Validation($data, $language);
        $this->validation->strictMode        = $this->strictMode;
        $this->validation->reverseConditions = $this->reverseConditions;

        return $this->validation->validate($this->spec, $data);
    }

    public function getErrors()
    {
        return $this->validation->errors;
    }

    public function write(array | \Limepie\ArrayObject | null $data = [])
    {
        $generation = new \Limepie\Form\Generation;

        return $generation->write($this->spec, $data ?? []);
    }

    public function read(array $data = [])
    {
        $generation = new \Limepie\Form\Generation;

        return $generation->read($this->spec, $data);
    }

    public function list(array $data = [])
    {
        $generation = new \Limepie\Form\Generation;

        return $generation->list($this->spec, $data);
    }
}
