<?php declare(strict_types=1);

namespace Limepie;

class Excel extends \Vtiful\Kernel\Excel
{
    private $handle;

    public function __construct($fileName, $options = ['path' => '/tmp/'])
    {
        parent::__construct($options);
        $this->handle = $this->fileName($fileName)->getHandle();
    }

    public function format()
    {
        return new Excel\Format($this->handle);
    }

    public function defaultFormat($formatHandleResource = null) : self
    {
        if ($formatHandleResource instanceof Excel\Format) {
            $resource             = $formatHandleResource->toResource();
            $formatHandleResource = null;
        } else {
            $resource = $formatHandleResource;
        }

        return parent::defaultFormat($resource);
    }

    public function row(string $range, float $cellHeight, $formatHandleResource = null) : self
    {
        if ($formatHandleResource instanceof Excel\Format) {
            $resource             = $formatHandleResource->toResource();
            $formatHandleResource = null;
        } else {
            $resource = $formatHandleResource;
        }

        return parent::setRow($range, $cellHeight, $resource);
    }

    public function column(string $range, float $cellWidth, $formatHandleResource = null) : self
    {
        if ($formatHandleResource instanceof Excel\Format) {
            $resource             = (clone $formatHandleResource)->toResource();
            $formatHandleResource = null;
        } else {
            $resource = $formatHandleResource;
        }

        return parent::setColumn($range, $cellWidth, $resource);
    }
}
