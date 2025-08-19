<?php declare(strict_types=1);

namespace Limepie\Attribute\Route;

class Post
{
    public function __construct(public string $path, public ?string $location = null, public int $status = 201)
    {
    }
}
