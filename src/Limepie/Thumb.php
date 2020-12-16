<?php declare(strict_types=1);

namespace Limepie;

class Thumb
{
    // {{{ Variables

    public const SCALE_EXACT_FIT = 'crop'; // 사이즈에 맞게

    public const SCALE_SHOW_ALL = 'scale'; // 비율에 맞게

    public const EXPORT_JPG = 'jpg';

    public const EXPORT_GIF = 'gif';

    public const EXPORT_PNG = 'png';

    // 기본 옵션 정보
    private static $options = [
        'debug'       => false,
        'export'      => 'png',
        'preprocess'  => null,
        'postprocess' => null,
        'savepath'    => '%PATH%/%FILENAME%_thumb.%EXT%',
    ];

    // }}}
    // {{{ Functions

    /**
     * 섬네일 이미지 생성
     *
     * @param mixed      $filepath
     * @param mixed|null $width
     * @param mixed|null $height
     * @param mixed      $scale
     * @param mixed|null $options
     */
    public static function create($filepath, $width = null, $height = null, $scale = 'crop', $options = [])
    {
        // 원본 이미지가 없는 경우
        if (!\file_exists($filepath)) {
            static::raiseError('#Error: static::create() : File not found or permission error.' . ' at ' . __LINE__);
        }
        // 섬네일 크기가 잘못 지정된 경우
        if (1 >= $width && 1 >= $height) {
            static::raiseError('#Error: static::create() : Invalid thumbnail size.' . ' at ' . __LINE__);
        }

        // 스케일 지정이 안되어 있거나 틀릴 경우 기본 static::SCALE_SHOW_ALL 으로 지정
        if (!$scale || (static::SCALE_EXACT_FIT !== $scale && static::SCALE_SHOW_ALL !== $scale)) {
            $scale = static::SCALE_SHOW_ALL;
        }

        // 기타 옵션
        $options = \array_merge(static::$options, $options);

        // 옵션 중 출력 이미지 형식이 잘못 지정된 경우
        if (false === \in_array($options['export'], [static::EXPORT_JPG, static::EXPORT_GIF, static::EXPORT_PNG], true)) {
            static::raiseError('#Error: static::create() : Invalid export format.' . ' at ' . __LINE__);
        }
        // 이미지 타입이 지원되지 않는 경우
        // 1 = GIF, 2 = JPEG, 3 = png
        $type = \getimagesize($filepath);

        // 원본 이미지로부터 Image 객체 생성
        switch ($type[2]) {
            case 1: $image = \imagecreatefromgif($filepath);

                break;
            case 2: $image = \imagecreatefromjpeg($filepath);

                break;
            case 3: $image = \imagecreatefrompng($filepath);

                break;
            default:
                $imageTypeArray = array
                (
                    0=>'UNKNOWN',
                    1=>'GIF',
                    2=>'JPEG',
                    3=>'PNG',
                    4=>'SWF',
                    5=>'PSD',
                    6=>'BMP',
                    7=>'TIFF_II',
                    8=>'TIFF_MM',
                    9=>'JPC',
                    10=>'JP2',
                    11=>'JPX',
                    12=>'JB2',
                    13=>'SWC',
                    14=>'IFF',
                    15=>'WBMP',
                    16=>'XBM',
                    17=>'ICO',
                    18=>'COUNT'
                );
                throw new \Exception(($imageTypeArray[$size[2]]??"").' not support');
        }

        // AntiAlias
        if (\function_exists('imageantialias')) {
            \imageantialias($image, true);
        }

        // 이미지 크기 설정
        [$thumb_width, $thumb_height, $image_width, $image_height, $thumb_x, $thumb_y] = static::getSize($filepath, $width, $height, $scale);

        // 섬네일 객체 생성
        $thumbnail = \imagecreatetruecolor($width, $height);

        \imagealphablending($thumbnail, true);

        if (true === isset($options['background']) && 'transparent' !== $options['background']) {
            [$r2,$g2,$b2] = static::txt2rgb($options['background']); //배경색
            $transparent  = \imagecolorallocate($thumbnail, $r2, $g2, $b2);
        } else {
            $transparent = \imagecolorallocatealpha($thumbnail, 0, 0, 0, 127);
        }
        \imagefill($thumbnail, 0, 0, $transparent);

        \imagecopyresampled($thumbnail, $image, $thumb_x, $thumb_y, 0, 0, $thumb_width, $thumb_height, $image_width, $image_height);

        \imagesavealpha($thumbnail, true);


        if (true === isset($options[''])) {
            $mystring = $_SERVER['HTTP_HOST'];

            $box  = static::calculateTextBox($mystring, HTDOCS_FOLDER . 'common/font/arial/arial.ttf', 7, 0);
            $left = $box['left'] + ($thumb_width / 2)  - ($box['width'] / 2);
            $top  = $box['top']  + ($image_height / 2) - ($box['height'] / 2);

            $left = $box['left'] + $thumb_width  - $box['width'];
            $top  = $box['top']  + $image_height - $box['height'];

            $color = \imagecolorallocate($thumbnail, 255, 255, 255);
            \imagettftext(
                $thumbnail,
                7,
                0,
                $left,
                $top,
                $color,
                HTDOCS_FOLDER . 'common/font/arial/arial.ttf',
                $mystring
            );
        }
        // 지정된 포멧으로 섬네일이미지 저장
        $iserror = false;


        // 저장할 경로 생성 및 디렉토리 검사

        $savepath = \str_replace(['%SCALE%', '%PATH%', '%FILENAME%', '%EXT%', '%THUMB_WIDTH%', '%THUMB_HEIGHT%', '%IMAGE_WIDTH%', '%IMAGE_HEIGHT%'], [$scale, dirname($filepath), basename($filepath), $options['export'], $width, $height, $image_width, $image_height], $options['savepath']);
        static::validatePath($savepath);

        switch ($options['export']) {
            case static::EXPORT_GIF:
                if (!\imagegif($thumbnail, $savepath)) {
                    $iserror = true;
                }

                break;
            case static::EXPORT_PNG:
                if (!\imagepng($thumbnail, $savepath)) {
                    $iserror = true;
                }

                break;
            case static::EXPORT_JPG:
            default:
                if (!\imagejpeg($thumbnail, $savepath)) {
                    $iserror = true;
                }

                break;
        }

        if ($iserror) {
            static::raiseError('#Error: static::create() : invalid path or permission error.' . ' at ' . __LINE__);
        } elseif (static::getOption('debug')) {
            echo '@Debug: static::create() - source=' . $filepath . ', image[width=' . $image_width . ',height=' . $image_height . '], '
                . 'thumb[width=' . $width . ',height=' . $height . '], scale=' . $scale . ', scaled[x=' . $thumb_x . ',y=' . $thumb_y
                . ',width=' . $thumb_width . ',height=' . $thumb_height . ']<br />' . "\n";
        }

        return $savepath;
    }

    // END: function create();

    public function calculateTextBox($text, $fontFile, $fontSize, $fontAngle)
    {
        $rect = \imagettfbbox($fontSize, $fontAngle, $fontFile, $text);
        $minX = \min([$rect[0], $rect[2], $rect[4], $rect[6]]);
        $maxX = \max([$rect[0], $rect[2], $rect[4], $rect[6]]);
        $minY = \min([$rect[1], $rect[3], $rect[5], $rect[7]]);
        $maxY = \max([$rect[1], $rect[3], $rect[5], $rect[7]]);

        return [
            'left'   => \abs($minX) - 1,
            'top'    => \abs($minY) - 1,
            'width'  => $maxX - $minX,
            'height' => $maxY - $minY,
            'box'    => $rect,
        ];
    }

    public static function getSize($filepath, $width, $height, $scale)
    {
        $image_attr   = \getimagesize($filepath);
        $image_width  = $image_attr[0];
        $image_height = $image_attr[1];

        if (0 < $width && 0 < $height) {
            // 섬네일 크기 안에 모두 표시
            // 이미지의 가장 큰 면을 기준으로 지정
            switch ($scale) {
                case static::SCALE_SHOW_ALL:
                    $side = ($image_width >= $image_height) ? 'width' : 'height';

                    break;
                case static::SCALE_EXACT_FIT:
                default:
                    $side = ($image_width / $width <= $image_height / $height) ? 'width' : 'height';

                    break;
            }

            $thumb_x = $thumb_y = 0;

            if ('width' === $side) {
                $ratio        = $image_width / $width;
                $thumb_width  = $width;
                $thumb_height = \floor($image_height / $ratio);
                $thumb_y      = \round(($height - $thumb_height) / 2);
            } else {
                $ratio        = $image_height / $height;
                $thumb_width  = \floor($image_width / $ratio);
                $thumb_height = $height;
                $thumb_x      = \round(($width - $thumb_width) / 2);
            }
        } else {
            // width 또는 height 크기가 지정되지 않았을 경우,
            // 지정된 섬네일 크기 비율에 맞게 다른 면의 크기를 맞춤
            $thumb_x = $thumb_y = 0;

            if (!$width) {
                $thumb_width  = $width  = (int) ($image_width / ($image_height / $height));
                $thumb_height = $height;
            } elseif (!$height) {
                $thumb_width  = $width;
                $thumb_height = $height = (int) ($image_height / ($image_width / $width));
            }
        }

        return [(int)$thumb_width, (int)$thumb_height, (int)$image_width, (int)$image_height, (int)$thumb_x, (int)$thumb_y];
    }

    /**
     * 기본 옵션 항목을 변경한다.
     *
     * @param string $name  옵션명
     * @param mixed  $value 값
     *
     * @return void
     */
    public static function setOption($name, $value)
    {
        static::$options[ $name ] = $value;
    }

    /**
     * 기본 옵션 항목의 값을 반환한다.
     *
     * @param string $name 옵션명
     *
     * @return mixed 값
     */
    public static function getOption($name)
    {
        return static::$options[ $name ];
    }

    /**
     * 경로가 존재하는지 체크하고 없다면 폴더를 생성
     *
     * @param string $path 체크할 경로
     *
     * @return bool true
     */
    public static function validatePath($path)
    {
        $a = \explode('/', \dirname($path));
        $p = '';

        foreach ($a as $v) {
            $p .= $v . '/';

            if (!\is_dir($p)) {
                \mkdir($p, 0757);
            }
        }

        return true;
    }

    // END: function validatePath();

    /**
     * 오류 처리 핸들러
     *
     * @param string $msg  메시지
     * @param int    $code 오류 코드
     * @param int    $type 오류 형식
     */
    public static function raiseError($msg, $code = 0, $type = 0)
    {
        die($msg);
    }


    static function txt2rgb($txt)
    {
        return [
            \hexdec(\substr($txt, 0, 2)),
            \hexdec(\substr($txt, 2, 2)),
            \hexdec(\substr($txt, 4, 2)),
        ];
    }

}// END: class Thumbnail
