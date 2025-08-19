<?php declare(strict_types=1);

namespace Limepie\Attribute\Route;

class Get
{
    public function __construct(public string $path, public ?string $redirect = null, public int $status = 200)
    {
    }
}
