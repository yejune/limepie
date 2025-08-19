<?php declare(strict_types=1);

namespace Limepie\Excel;

use Vtiful\Kernel\Format as ExcelFormat;

class Format
{
    private $format;

    public const borderConstants = [
        'NONE'                => ExcelFormat::BORDER_NONE,
        'THIN'                => ExcelFormat::BORDER_THIN,
        'MEDIUM'              => ExcelFormat::BORDER_MEDIUM,
        'DASHED'              => ExcelFormat::BORDER_DASHED,
        'DOTTED'              => ExcelFormat::BORDER_DOTTED,
        'THICK'               => ExcelFormat::BORDER_THICK,
        'DOUBLE'              => ExcelFormat::BORDER_DOUBLE,
        'HAIR'                => ExcelFormat::BORDER_HAIR,
        'MEDIUM_DASHED'       => ExcelFormat::BORDER_MEDIUM_DASHED,
        'DASH_DOT'            => ExcelFormat::BORDER_DASH_DOT,
        'MEDIUM_DASH_DOT'     => ExcelFormat::BORDER_MEDIUM_DASH_DOT,
        'DASH_DOT_DOT'        => ExcelFormat::BORDER_DASH_DOT_DOT,
        'MEDIUM_DASH_DOT_DOT' => ExcelFormat::BORDER_MEDIUM_DASH_DOT_DOT,
        'SLANT_DASH_DOT'      => ExcelFormat::BORDER_SLANT_DASH_DOT,
    ];

    public const colorConstants = [
        'BLACK'   => ExcelFormat::COLOR_BLACK,
        'BLUE'    => ExcelFormat::COLOR_BLUE,
        'BROWN'   => ExcelFormat::COLOR_BROWN,
        'CYAN'    => ExcelFormat::COLOR_CYAN,
        'GRAY'    => ExcelFormat::COLOR_GRAY,
        'GREEN'   => ExcelFormat::COLOR_GREEN,
        'LIME'    => ExcelFormat::COLOR_LIME,
        'MAGENTA' => ExcelFormat::COLOR_MAGENTA,
        'NAVY'    => ExcelFormat::COLOR_NAVY,
        'ORANGE'  => ExcelFormat::COLOR_ORANGE,
        'PINK'    => ExcelFormat::COLOR_PINK,
        'PURPLE'  => ExcelFormat::COLOR_PURPLE,
        'RED'     => ExcelFormat::COLOR_RED,
        'SILVER'  => ExcelFormat::COLOR_SILVER,
        'WHITE'   => ExcelFormat::COLOR_WHITE,
        'YELLOW'  => ExcelFormat::COLOR_YELLOW,
    ];

    private $handle;

    public function __construct($handle)
    {
        $this->handle = $handle;
        $this->format = new ExcelFormat($handle);
    }

    public function center()
    {
        $this->format->align(
            ExcelFormat::FORMAT_ALIGN_CENTER,
            ExcelFormat::FORMAT_ALIGN_VERTICAL_CENTER
        );

        return $this;
    }

    public function left()
    {
        $this->format->align(
            ExcelFormat::FORMAT_ALIGN_LEFT,
        );

        return $this;
    }

    public function right()
    {
        $this->format->align(
            ExcelFormat::FORMAT_ALIGN_RIGHT,
        );

        return $this;
    }

    public function middle()
    {
        $this->format->align(
            ExcelFormat::FORMAT_ALIGN_VERTICAL_CENTER
        );

        // Assuming vertical center is applied along with other align methods.
        return $this;
    }

    public function top()
    {
        $this->format->align(
            ExcelFormat::FORMAT_ALIGN_VERTICAL_TOP
        );

        // Assuming vertical center is applied along with other align methods.
        return $this;
    }

    //     $backgroundStyle = $format->background(
    //         Format::COLOR_RED
    //     )->toResource();

    //     $backgroundStyle2 = $format->background(
    //         Format::COLOR_WHITE
    //     )->toResource();

    public function border($style = 'none')
    {
        $this->format->border(
            self::borderConstants[\strtoupper($style)]
        );

        return $this;
    }

    public function background($color)
    {
        $this->format->background(
            self::colorConstants[\strtoupper($color)]
        );

        return $this;
    }

    public function color($color)
    {
        $this->format->fontColor(
            self::colorConstants[\strtoupper($color)]
        );

        return $this;
    }

    // public function number()
    // {
    //     $this->format->number();

    //     return $this;
    // }

    public function toResource()
    {
        return $this->format->toResource();
    }
}
