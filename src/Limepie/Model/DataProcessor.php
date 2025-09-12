<?php

declare(strict_types=1);

namespace Limepie\Model;

use Limepie\ArrayObject;
use Limepie\Aes;
use Limepie\CurlFile;

/**
 * 데이터 처리 전담 클래스
 * - 데이터 직렬화/역직렬화
 * - 데이터 타입 변환
 * - AES 암호화 처리
 */
class DataProcessor
{
    /**
     * 데이터 스타일에 따른 값 처리 (create/update용)
     */
    public static function processForStorage(string $dataStyle, mixed $value): mixed
    {
        switch ($dataStyle) {
            case 'curlfile_serialize':
                return CurlFile::serialize($value);
                
            case 'serialize':
                return \serialize($value);
                
            case 'base64':
                return \base64_encode(\serialize($value));
                
            case 'gz':
                return \gzcompress(\serialize($value), 9);
                
            case 'json':
                return false === \is_null($value) ? \json_encode($value) : null;
                
            case 'yml':
            case 'yaml':
                return \yaml_emit($value);
                
            default:
                return $value;
        }
    }

    /**
     * 데이터베이스에서 읽어온 값을 처리 (select용)
     */
    public static function processFromStorage(string $dataStyle, mixed $value): mixed
    {
        if (null === $value) {
            return null;
        }

        switch ($dataStyle) {
            case 'curlfile_serialize':
                if ($value && \Limepie\is_serialized_string($value)) {
                    try {
                        return CurlFile::unserialize($value);
                    } catch (\Exception $e) {
                        throw $e;
                    }
                }
                return null;
                
            case 'serialize':
                if ($value && \Limepie\is_serialized_string($value)) {
                    try {
                        return \unserialize($value);
                    } catch (\Exception $e) {
                        throw $e;
                    }
                }
                return null;
                
            case 'aes_serialize':
                if ($value && \Limepie\is_serialized_string($value)) {
                    try {
                        return \unserialize($value);
                    } catch (\Exception $e) {
                        throw $e;
                    }
                }
                return null;
                
            case 'base64':
                return $value ? new ArrayObject(\unserialize(\base64_decode($value, true))) : null;
                
            case 'gz':
                if ($value) {
                    if (\Limepie\is_binary($value)) {
                        return new ArrayObject(\unserialize(\gzuncompress($value)));
                    }
                }
                return null;
                
            case 'jsons':
                if ($value) {
                    $body = \json_decode($value, true);
                    return $body ? new ArrayObject($body) : null;
                }
                return null;
                
            case 'json':
                if (false === \is_null($value)) {
                    $body = \json_decode($value, true);
                    return $body ? new ArrayObject($body) : null;
                }
                return null;
                
            case 'yml':
            case 'yaml':
                if ($value) {
                    try {
                        $body = \yaml_parse($value);
                        $body = \yaml_parse($body);
                        return $body ? new ArrayObject($body) : null;
                    } catch (\Throwable $e) {
                        return null;
                    }
                }
                return null;
                
            case 'int':
            case 'tinyint':
                return (int) $value;
                
            case 'float':
            case 'decimal':
                return (float) $value;
                
            default:
                return $value;
        }
    }

    /**
     * AES 암호화 처리를 위한 SQL 생성
     */
    public static function buildAesEncryptSql(string $dataStyle, string $bindKey, string $secretKey): string
    {
        switch ($dataStyle) {
            case 'aes_serialize':
            case 'aes':
                return "AES_ENCRYPT(:{$bindKey}, :{$secretKey})";
                
            case 'aes_hex':
                return "HEX(AES_ENCRYPT(:{$bindKey}, :{$secretKey}))";
                
            default:
                throw new \InvalidArgumentException("Unknown AES data style: {$dataStyle}");
        }
    }

    /**
     * AES 복호화 처리를 위한 SQL 생성
     */
    public static function buildAesDecryptSql(string $dataStyle, string $column, string $secretKey): string
    {
        switch ($dataStyle) {
            case 'aes_serialize':
            case 'aes':
                return "AES_DECRYPT({$column}, :{$secretKey})";
                
            case 'aes_hex':
                return "AES_DECRYPT(UNHEX({$column}), :{$secretKey})";
                
            default:
                throw new \InvalidArgumentException("Unknown AES data style: {$dataStyle}");
        }
    }

    /**
     * 여러 속성에 대해 데이터 타입 처리 적용
     */
    public static function buildDataTypes(array $attributes, array $dataStyles): array
    {
        foreach ($attributes as $column => &$value) {
            if (isset($dataStyles[$column])) {
                $value = self::processFromStorage($dataStyles[$column], $value);
            }
        }
        
        return $attributes;
    }
}