<?php declare(strict_types=1);

namespace Limepie;

class Thumbnail
{
    // {{{ Variables

    public const SCALE_EXACT_FIT = 'crop'; // 사이즈에 맞게

    public const SCALE_SHOW_ALL = 'scale'; // 비율에 맞게

    public const EXPORT_JPG = 'jpg';

    public const EXPORT_JPEG = 'jpg';

    public const EXPORT_GIF = 'gif';

    public const EXPORT_PNG = 'png';

    public const EXPORT_BMP = 'bmp';

    public $folder;

    public $thumbnail;

    // 기본 옵션 정보
    private $options = [
        'background'    => 'transparent',
        'fontsize'      => 10,
        'fontantialias' => false,
        'debug'         => false,
    ];

    public $extend_options = [];


    /**
     * 섬네일 이미지 생성.
     *
     * @param mixed      $filepath
     * @param null|mixed $width
     * @param null|mixed $height
     * @param mixed      $scale
     * @param null|mixed $options
     */
    public function __construct($filepath, $width = null, $height = null, $scale = 'crop', $options = [])
    {
        // $scale = 'scale';
        // 원본 이미지가 없는 경우
        if (!\file_exists($filepath)) {
            $this->raiseError('#Error: create() : File not found or permission error.' . ' at ' . __LINE__);
        }
        // 섬네일 크기가 잘못 지정된 경우
        if (1 >= $width && 1 >= $height) {
            $this->raiseError('#Error: create() : Invalid thumbnail size.' . ' at ' . __LINE__);
        }

        // 스케일 지정이 안되어 있거나 틀릴 경우 기본 self::SCALE_SHOW_ALL 으로 지정
        if (!$scale || (self::SCALE_EXACT_FIT !== $scale && self::SCALE_SHOW_ALL !== $scale)) {
            $scale = self::SCALE_SHOW_ALL;
        }

        // 기타 옵션
        $this->extend_options = \array_merge($this->options, $options);

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
            case 6: $image = \imagecreatefrombmp($filepath);

                break;
            default:
                $imageTypeArray = [
                    0  => 'UNKNOWN',
                    1  => 'GIF',
                    2  => 'JPEG',
                    3  => 'PNG',
                    4  => 'SWF',
                    5  => 'PSD',
                    6  => 'BMP',
                    7  => 'TIFF_II',
                    8  => 'TIFF_MM',
                    9  => 'JPC',
                    10 => 'JP2',
                    11 => 'JPX',
                    12 => 'JB2',
                    13 => 'SWC',
                    14 => 'IFF',
                    15 => 'WBMP',
                    16 => 'XBM',
                    17 => 'ICO',
                    18 => 'COUNT',
                ];

                throw new \Exception(($imageTypeArray[$type[2]] ?? '') . ' not support');
        }

        // AntiAlias
        if (\function_exists('imageantialias')) {
            \imageantialias($image, true);
        }

        // 이미지 크기 설정
        [$thumb_width, $thumb_height, $image_width, $image_height, $thumb_x, $thumb_y] = $this->getSize($filepath, $width, $height, $scale);

        // 섬네일 객체 생성
        $thumbnail = \imagecreatetruecolor((int) $width, (int) $height);

        \imagealphablending($thumbnail, true);

        if (true === isset($this->extend_options['background']) && 'transparent' !== $this->extend_options['background']) {
            [$r2,$g2,$b2] = $this->txt2rgb($this->extend_options['background']); //배경색
            $transparent  = \imagecolorallocate($thumbnail, $r2, $g2, $b2);
        } else {
            $transparent = \imagecolorallocatealpha($thumbnail, 0, 0, 0, 127);
        }
        \imagefill($thumbnail, 0, 0, $transparent);

        \imagecopyresampled($thumbnail, $image, $thumb_x, $thumb_y, 0, 0, $thumb_width, $thumb_height, $image_width, $image_height);

        \imagesavealpha($thumbnail, true);

        if (true === isset($this->extend_options['mark'])) {
            $box  = $this->calculateTextBox($this->extend_options['mark'], $this->extend_options['fontpath'], $this->extend_options['fontsize'], 0);
            // $left = $box['left'] + ($width / 2)  - ($box['width'] / 2);
            // $top  = $box['top']  + ($height / 2) - ($box['height'] / 2);

            $left = $box['left'] + $width  - $box['width'];
            $top  = $box['top']  + $height - $box['height'];

            $color = \imagecolorallocate($thumbnail, 255, 255, 255);
            \imagettftext(
                $thumbnail,
                $this->extend_options['fontsize'],
                0,
                $left,
                $top,
                $color * (($this->extend_options['fontantialias'] ?? 0) ? 1 : -1),
                $this->extend_options['fontpath'],
                $this->extend_options['mark']
            );

            $this->thumbnail = $thumbnail;
        }

        return $this;
    }

    public function save($savepath)
    {
        $iserror = false;
        $info    = \pathinfo($savepath);

        switch ($info['extension']) {
            case self::EXPORT_GIF:
                if (!\imagegif($this->thumbnail, $savepath)) {
                    $iserror = true;
                }

                break;
            case self::EXPORT_BMP:
                if (!\imagebmp($this->thumbnail, $savepath)) {
                    $iserror = true;
                }

                break;
            case self::EXPORT_PNG:
                if (!\imagepng($this->thumbnail, $savepath)) {
                    $iserror = true;
                }

                break;
            case self::EXPORT_JPG:
            case self::EXPORT_JPEG:
            default:
                if (!\imagejpeg($this->thumbnail, $savepath)) {
                    $iserror = true;
                }

                break;
        }

        if ($iserror) {
            $this->raiseError('#Error: create() : invalid path or permission error.' . ' at ' . __LINE__);
        } elseif ($this->getOption('debug')) {
            $this->raiseError('#Error: create() : invalid path or permission error.' . ' at ' . __LINE__);
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

    public function getSize($filepath, $width, $height, $scale)
    {
        $image_attr   = \getimagesize($filepath);
        $image_width  = $image_attr[0];
        $image_height = $image_attr[1];

        if (0 < $width && 0 < $height) {
            // 섬네일 크기 안에 모두 표시
            // 이미지의 가장 큰 면을 기준으로 지정
            switch ($scale) {
                case self::SCALE_SHOW_ALL:
                    $side = ($image_width >= $image_height) ? 'width' : 'height';

                    break;
                case self::SCALE_EXACT_FIT:
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

        return [(int) $thumb_width, (int) $thumb_height, (int) $image_width, (int) $image_height, (int) $thumb_x, (int) $thumb_y];
    }

    /**
     * 기본 옵션 항목을 변경한다.
     *
     * @param string $name  옵션명
     * @param mixed  $value 값
     */
    public function setOption($name, $value)
    {
        $this->extend_options[$name] = $value;
    }

    /**
     * 기본 옵션 항목의 값을 반환한다.
     *
     * @param string $name 옵션명
     *
     * @return mixed 값
     */
    public function getOption($name)
    {
        return $this->extend_options[$name];
    }

    /**
     * 경로가 존재하는지 체크하고 없다면 폴더를 생성.
     *
     * @param string $path 체크할 경로
     *
     * @return bool true
     */
    public function validatePath($path)
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
     * 오류 처리 핸들러.
     *
     * @param string $msg  메시지
     * @param int    $code 오류 코드
     * @param int    $type 오류 형식
     */
    public function raiseError($msg, $code = 0, $type = 0)
    {
        exit($msg);
    }

    public function txt2rgb($txt)
    {
        return [
            \hexdec(\substr($txt, 0, 2)),
            \hexdec(\substr($txt, 2, 2)),
            \hexdec(\substr($txt, 4, 2)),
        ];
    }
}// END: class Thumbnail
