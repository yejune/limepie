<?php declare(strict_types=1);

namespace Limepie;

class UploadFile
{
    public $store = [];

    public function __construct($fileArray)
    {
        $this->store = $this->cleanup(new \Limepie\ArrayFlatten($this->nomalize($fileArray)));
    }

    // upload file을 배열구조를 정상적으로 고침.
    public function nomalize(array $files = [])
    {
        $out = [];

        foreach ($files as $key => $file) {
            if (true === isset($file['name']) && true === \is_array($file['name'])) {
                $new = [];

                foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $k) {
                    \array_walk_recursive($file[$k], function (&$data, $key, $k) {
                        $data = [$k => $data];
                    }, $k);
                    $new = \array_replace_recursive($new, $file[$k]);
                }
                $out[$key] = $new;
            } else {
                $out[$key] = $file;
            }
        }

        return $out;
    }

    // no data를 에러처리하지 않고 지움, 지우기 위해서 배열을 단순화하여 제거후 다시 unflatten
    public function cleanup(ArrayFlatten $flatten)
    {
        foreach ($flatten->store as $key => $row) {
            if (true === isset($row['error']) && 4 == $row['error']) {
                $flatten->remove($key);
            }
        }

        return $flatten->gets();
    }
}
