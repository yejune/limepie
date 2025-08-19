<?php

namespace Limepie\Asset;

abstract class Loader
{
    protected static $isDevelopment = false;

    protected static $realBasePath = 'dist/theme';

    protected static $baseUrl = '';

    protected static $versions = [];

    // 자식 클래스에서 정의할 속성들
    protected static $cssGroupFiles = [];

    protected static $jsGroupFiles = [];

    /**
     * 초기화.
     */
    public static function init()
    {
        if (static::$baseUrl) {
            return;
        }

        if (!static::$isDevelopment) {
            static::$isDevelopment = 'local' === $_SERVER['STAGE_NAME'];
        }

        if (static::$isDevelopment) {
            static::$baseUrl = $_SERVER['ASSETS_URL'];
        } else {
            static::$baseUrl = $_SERVER['ASSETS_URL'] . '/' . static::$realBasePath;
        }
    }

    /**
     * CSS 출력.
     *
     * @param mixed $group
     */
    public static function css($group)
    {
        static::init();

        if (!isset(static::$cssGroupFiles[$group])) {
            return "<!-- CSS group '{$group}' not found -->";
        }

        $files = static::$cssGroupFiles[$group];

        if (static::$isDevelopment) {
            // 개발 모드: 개별 파일들
            $tags = [];

            foreach ($files as $file) {
                $url    = static::resolveDevUrl($file);
                $tags[] = '<link href="' . \htmlspecialchars($url) . '" rel="stylesheet">';
            }

            return \implode('', $tags);
        }
        // 운영 모드: 압축된 파일
        $className  = static::getClassName();
        $versionKey = \str_replace('/', '_', static::$realBasePath) . '_' . \strtolower($className) . '_' . $group . '_css';
        $version    = static::$versions[$versionKey] ?? '1.0.0';
        $url        = static::$baseUrl . '/' . \strtolower($className) . '/' . $group . '/' . $group . '-' . $version . '.min.css';

        return '<link href="' . \htmlspecialchars($url) . '" rel="stylesheet">';
    }

    /**
     * JS 출력.
     *
     * @param mixed $group
     */
    public static function js($group)
    {
        static::init();

        if (!isset(static::$jsGroupFiles[$group])) {
            return "<!-- JS group '{$group}' not found -->";
        }

        $files = static::$jsGroupFiles[$group];

        if (static::$isDevelopment) {
            // 개발 모드: 개별 파일들
            $tags = [];

            foreach ($files as $file) {
                $url    = static::resolveDevUrl($file);
                $tags[] = '<script src="' . \htmlspecialchars($url) . '"></script>';
            }

            return \implode('', $tags);
        }
        // 운영 모드: 압축된 파일
        $className  = static::getClassName();
        $versionKey = \str_replace('/', '_', static::$realBasePath) . '_' . \strtolower($className) . '_' . $group . '_js';
        $version    = static::$versions[$versionKey] ?? '1.0.0';
        $url        = static::$baseUrl . '/' . \strtolower($className) . '/' . $group . '/' . $group . '-' . $version . '.min.js';

        return '<script src="' . \htmlspecialchars($url) . '"></script>';
    }

    /**
     * 파일 URL 리스트 반환 (태그 없이 파일명/URL만).
     *
     * @param string ...$files 파일명들 (common.css, form.js, editor.css 등)
     *
     * @return array 파일 URL 리스트
     */
    public static function files(...$files)
    {
        static::init();

        $allFiles = [];

        foreach ($files as $file) {
            // 확장자로 타입 판단
            $extension = \strtolower(\pathinfo($file, PATHINFO_EXTENSION));
            $groupName = \pathinfo($file, PATHINFO_FILENAME);

            if ('css' === $extension && isset(static::$cssGroupFiles[$groupName])) {
                $groupFiles = static::$cssGroupFiles[$groupName];
                $type       = 'css';
            } elseif ('js' === $extension && isset(static::$jsGroupFiles[$groupName])) {
                $groupFiles = static::$jsGroupFiles[$groupName];
                $type       = 'js';
            } else {
                continue; // 해당 그룹이 없거나 지원하지 않는 확장자면 건너뛰기
            }

            if (static::$isDevelopment) {
                // 개발 모드: 개별 파일들의 URL
                foreach ($groupFiles as $groupFile) {
                    $allFiles[] = static::resolveDevUrl($groupFile);
                }
            } else {
                // 운영 모드: 압축된 파일 URL
                $className  = static::getClassName();
                $versionKey = \str_replace('/', '_', static::$realBasePath) . '_' . \strtolower($className) . '_' . $groupName . '_' . $type;
                $version    = static::$versions[$versionKey] ?? '1.0.0';
                $url        = static::$baseUrl . '/' . \strtolower($className) . '/' . $groupName . '/' . $groupName . '-' . $version . '.min.' . $type;

                $allFiles[] = $url;
            }
        }

        return $allFiles;
    }

    /**
     * URL 해결.
     *
     * @param mixed $file
     */
    protected static function resolveDevUrl($file)
    {
        if (0 === \strpos($file, 'http')) {
            return $file; // 외부 URL
        }

        return static::$baseUrl . '/' . \ltrim($file, '/') . '?v=' . \time();
    }

    /**
     * 클래스명 가져오기.
     */
    protected static function getClassName()
    {
        $fullClass = \get_called_class();

        return \basename(\str_replace('\\', '/', $fullClass));
    }

    /**
     * 디버그 모드 확인.
     */
    public static function isDevelopment()
    {
        if (null === static::$isDevelopment) {
            static::init();
        }

        return static::$isDevelopment;
    }

    /**
     * 사용 가능한 그룹 목록.
     */
    public static function getAvailableGroups()
    {
        return [
            'css' => \array_keys(static::$cssGroupFiles),
            'js'  => \array_keys(static::$jsGroupFiles),
        ];
    }

    /**
     * 디버그 정보.
     */
    public static function getDebugInfo()
    {
        return [
            'class'      => static::getClassName(),
            'debug_mode' => static::isDevelopment(),
            'base_url'   => static::$baseUrl,
            'css_groups' => static::$cssGroupFiles,
            'js_groups'  => static::$jsGroupFiles,
            'versions'   => static::$versions,
        ];
    }

    /**
     * Get version for a specific asset.
     *
     * @param string $key Version key (e.g., 'dist_theme_control_common_css')
     *
     * @return string Version number
     */
    public static function getVersion($key)
    {
        return self::$versions[$key] ?? '1.0.0';
    }

    /**
     * Get all versions.
     *
     * @return array All version information
     */
    public static function getAllVersions()
    {
        return self::$versions;
    }

    /**
     * Check if asset has changed (development helper).
     *
     * @param string $key             Version key
     * @param string $expectedVersion Expected version
     *
     * @return bool True if versions match
     */
    public static function checkVersion($key, $expectedVersion)
    {
        return self::getVersion($key) === $expectedVersion;
    }
}
