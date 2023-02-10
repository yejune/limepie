<?php declare(strict_types=1);

namespace Limepie\Excel;

/**
 * $test = new \Limepie\Excel\Encrypt();
 * $test->file();
 * $test->binary();
 * $test->password();
 * $test->output();.
 */
class Format extends \Nick\SecureSpreadsheet\Encrypt
{
    public const FILE = 1;

    public const BINARY = 2;

    public $type = self::FILE;

    public function file($path)
    {
        $this->type = self::FILE;

        return $this->input($path);
    }

    public function binary($blob)
    {
        $this->type   = self::BINARY;
        $this->NOFILE = true;

        return $this->input($blob);
    }
}
