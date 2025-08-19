<?php declare(strict_types=1);

namespace Limepie;

class CurlFile
{
    public static function serialize($data)
    {
        $preparedData = self::prepareForSerialization($data);

        return \serialize($preparedData);
    }

    // public static function unserialize($serializedData)
    // {
    //     return \unserialize($serializedData);
    // }

    public static function unserialize($serializedData)
    {
        $data = \unserialize($serializedData);

        return self::restoreFromSerialization($data);
    }

    private static function prepareForSerialization($data)
    {
        foreach ($data as $key => &$value) {
            if ($value instanceof \CURLFile) {
                $value = [
                    'is_curl_file' => true,
                    'path'         => $value->getFilename(),
                    'mime'         => $value->getMimeType(),
                    'name'         => $value->getPostFilename(),
                ];
            } elseif (\is_array($value)) {
                $value = self::prepareForSerialization($value);
            }
        }

        return $data;
    }

    private static function restoreFromSerialization($data)
    {
        foreach ($data as $key => &$value) {
            if (
                \is_array($value)
                && isset($value['is_curl_file'])
                && $value['is_curl_file']
            ) {
                $value = new \CURLFile($value['path'], $value['mime'], $value['name']);
            } elseif (\is_array($value)) {
                $value = self::restoreFromSerialization($value);
            }
        }

        return $data;
    }
}
