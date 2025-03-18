<?php

declare(strict_types=1);

namespace Limepie\Form;

use Limepie\ArrayUtil;

/**
 * 폼 구성 처리를 담당하는 메인 클래스.
 *
 * 이 클래스는 YAML/배열 형식으로 정의된 폼 구성을 파싱하고 변환하는 역할을 합니다.
 * 주요 기능:
 * 1. 조건부 표시 스크립트 처리 (display_switch)
 * 2. 특수 키워드($ref, $after, $before, $merge, $remove 등) 처리
 * 3. 다국어 지원을 위한 언어 설정 처리
 * 4. 레퍼런스 해결 및 배열 병합/삽입/제거 작업
 */
class Parser
{
    /**
     * 처리할 원본 폼 구성 배열.
     *
     * @var array
     */
    private $arr;

    /**
     * 상대 경로 참조에 사용될 기본 경로.
     * $ref 키워드 처리 시 이 경로를 기준으로 파일을 찾습니다.
     *
     * @var string
     */
    private $basepath;

    /**
     * 처리 결과를 저장할 배열.
     *
     * @var array
     */
    private $return = [];

    /**
     * 생성자.
     *
     * @param array  $arr      처리할 폼 구성 배열
     * @param string $basepath 기본 경로 (파일 참조 시 사용)
     */
    public function __construct(array $arr, string $basepath = '')
    {
        $this->arr      = $arr;
        $this->basepath = $basepath;
    }

    /**
     * 폼 구성 처리 실행.
     *
     * 전체 처리 과정을 조율하는 메인 메서드로, 다음 순서로 작업을 수행합니다:
     * 1. DisplayScript 관련 처리 (조건부 요소 표시/숨김)
     * 2. 특수 키워드 및 일반 키 처리
     *
     * @return array 처리된 폼 구성 배열
     */
    public function processForm() : array
    {
        // 1. display_switch에 _langs 그룹 미리 추가 (껍데기만)
        foreach ($this->arr as $key => $fields) {
            if (!isset($fields['display_switch']) || !$fields['display_switch']) {
                continue;
            }

            foreach ($fields['display_switch'] as $scriptKey => $elements) {
                if (!\is_array($elements)) {
                    continue;
                }

                $newElements = [];

                foreach ($elements as $element) {
                    $element = \trim($element);

                    if (empty($element)) {
                        continue;
                    }

                    $newElements[] = $element;

                    // lang: append 설정이 있는 요소의 _langs 그룹을 display_switch에 추가
                    if (isset($this->arr[$element]['lang']) && 'append' === $this->arr[$element]['lang']) {
                        $langGroupElement = $element . '_langs';

                        if (!\in_array($langGroupElement, $newElements)) {
                            $newElements[] = $langGroupElement;
                        }
                    }
                }

                $this->arr[$key]['display_switch'][$scriptKey] = $newElements;
            }
        }

        // 2. 일반 키 처리 (LanguageHandler가 호출되어 _langs 그룹 내용 채움)
        foreach ($this->arr as $key => $value) {
            if (\is_string($key) && 0 === \strpos($key, '$')) {
                $this->processSpecialKey($key, $value);
            } else {
                $this->processNormalKey($key, $value);
            }
        }

        // 3. DisplayScript 처리 - 모든 요소가 준비된 상태에서 가시성 설정
        $displayScriptManager = new Parser\DisplayScriptManager();
        $this->return         = $displayScriptManager->process($this->return);

        return $this->return;
    }

    public static function process(array $arr, string $basepath) : array
    {
        $formProcessor = new Parser($arr, $basepath);

        return $formProcessor->processForm();
    }

    /**
     * 특수 키워드로 시작하는 키 처리.
     *
     * $로 시작하는 특수 키워드를 해석하고 처리합니다:
     * - $ref: 외부 파일/구성 참조
     * - $after: 특정 키 뒤에 새 항목 삽입
     * - $before: 특정 키 앞에 새 항목 삽입
     * - $change/$merge: 기존 항목과 병합
     * - $remove: 항목 제거
     *
     * @param string $key   특수 키워드
     * @param mixed  $value 키에 연결된 값
     */
    private function processSpecialKey(string $key, $value) : void
    {
        switch ($key) {
            case '$ref':
                // 외부 파일이나 구성 참조 처리
                $this->processReferenceKey($value);

                break;
            case '$after':
                // 특정 키 뒤에 새 항목 삽입
                $this->processAfterKey($value);

                break;
            case '$before':
                // 특정 키 앞에 새 항목 삽입
                $this->processBeforeKey($value);

                break;
            case '$change':
            case '$merge':
                // 기존 항목과 병합
                $this->processMergeKey($key, $value);

                break;
            case '$remove':
                // 항목 제거
                $this->processRemoveKey($value);

                break;
        }
    }

    /**
     * $ref 키 처리.
     *
     * 외부 파일이나 구성 참조를 해결합니다.
     * 예: $ref: "/path/to/config.yml" 또는 $ref: "(path/to/config.yml).properties"
     *
     * @param mixed $value 참조 경로 또는 참조 경로 배열
     */
    private function processReferenceKey($value) : void
    {
        $referenceResolver = new Parser\ReferenceResolver($this->basepath);
        $resolvedData      = $referenceResolver->resolve($value);

        if ($resolvedData) {
            // 해결된 참조 데이터를 현재 결과와 병합
            $this->return = \array_merge($this->return, $resolvedData);
        }
    }

    /**
     * $after 키 처리.
     *
     * 지정된 키 뒤에 새로운 항목을 삽입합니다.
     * 예: $after: { existing_key: { new_key: new_value } }
     *
     * @param mixed $value 삽입할 항목 정보
     */
    private function processAfterKey($value) : void
    {
        foreach ($value as $k => $v) {
            foreach ($v as $v1) {
                // 지정된 키 뒤에 새 항목 삽입
                $this->return = ArrayUtil::insertAfter($this->return, $k, $v1);
            }
        }
    }

    /**
     * $before 키 처리.
     *
     * 지정된 키 앞에 새로운 항목을 삽입합니다.
     * 예: $before: { existing_key: { new_key: new_value } }
     *
     * @param mixed $value 삽입할 항목 정보
     */
    private function processBeforeKey($value) : void
    {
        foreach ($value as $k => $v) {
            foreach ($v as $v1) {
                // 지정된 키 앞에 새 항목 삽입
                $this->return = ArrayUtil::insertBefore($this->return, $k, $v1);
            }
        }
    }

    /**
     * $merge 또는 $change 키 처리.
     *
     * 기존 항목과 새로운 값을 깊은 병합(deep merge)합니다.
     * 예: $merge: { existing_key: { subkey: new_value } }
     *
     * @param string $key   키워드 ($merge 또는 $change)
     * @param mixed  $value 병합할 항목 정보
     */
    private function processMergeKey(string $key, $value) : void
    {
        foreach ($value as $k => $v) {
            if (isset($this->return[$k])) {
                // 기존 항목과 깊은 병합 수행
                $this->return[$k] = ArrayUtil::mergeDeep($this->return[$k], $v);
            } else {
                throw new \Exception($key . ': Undefined array key "' . $k . '"');
            }
        }
    }

    /**
     * $remove 키 처리.
     *
     * 지정된 항목을 제거합니다.
     * 예: $remove: ["key1", "key2"] 또는 $remove: { key1: { subkey: value } }
     *
     * @param mixed $value 제거할 항목 정보
     */
    private function processRemoveKey($value) : void
    {
        if (\is_array($value)) {
            foreach ($value as $k => $v) {
                if (isset($this->return[$k])) {
                    if (\is_array($v)) {
                        // 특정 하위 키만 제거
                        $this->return[$k] = ArrayUtil::remove($this->return[$k], $v);
                    } else {
                        // 전체 항목 제거
                        unset($this->return[$k]);
                    }
                } elseif (isset($this->return[$v])) {
                    // 배열 형태 [0 => "key1", 1 => "key2"] 처리
                    unset($this->return[$v]);
                }
            }
        } else {
            // 단일 키 제거
            unset($this->return[$value]);
        }
    }

    /**
     * 일반 키 처리.
     *
     * 일반 폼 요소와 그룹을 처리합니다. 언어 설정이 있는 경우 해당 처리를 수행합니다.
     *
     * @param mixed $key   키 (대부분 문자열이지만 정수일 수도 있음)
     * @param mixed $value 값 (폼 요소 구성)
     */
    private function processNormalKey($key, $value) : void
    {
        if (\is_array($value)) {
            if (isset($value['lang']) && \preg_match('#\[\]$#', $key, $m)) {
                // 다중 항목(배열)의 언어 설정 처리
                $this->processMultipleLang($key, $value);
            } elseif (isset($value['lang'])) {
                // 단일 항목의 언어 설정 처리
                $this->processSingleLang($key, $value);
            } else {
                // 일반 배열 처리 (재귀적 처리)
                $this->return[$key] = Parser::process($value, $this->basepath);
            }
        } else {
            // 스칼라 값 처리
            $this->return[$key] = $value;
        }
    }

    /**
     * 다중 항목의 언어 설정 처리.
     *
     * 배열 형태의 요소([] 접미사가 있는 키)에 언어 설정이 있는 경우 처리합니다.
     * 예: questions[]: { lang: append, ... }
     *
     * @param string $key   배열 형태 요소의 키
     * @param array  $value 언어 설정이 포함된 요소 구성
     */
    private function processMultipleLang(string $key, array $value) : void
    {
        $languageHandler    = new Parser\LanguageHandler();
        $this->return[$key] = $languageHandler->processMultiple($key, $value, $this->basepath);
    }

    /**
     * 단일 항목의 언어 설정 처리.
     *
     * 일반 요소에 언어 설정이 있는 경우 처리합니다.
     * 예: name: { lang: append, ... }
     *
     * 결과적으로 원래 요소와 "_langs" 접미사가 붙은 언어별 요소 그룹을 생성합니다.
     * (예: name과 name_langs)
     *
     * @param string $key   요소 키
     * @param array  $value 언어 설정이 포함된 요소 구성
     */
    private function processSingleLang(string $key, array $value) : void
    {
        $languageHandler = new Parser\LanguageHandler();
        $processed       = $languageHandler->processSingle($key, $value, $this->basepath);

        if (isset($processed['original'])) {
            // 원래 요소 설정
            $this->return[$key] = $processed['original'];
        }

        if (isset($processed['langs'])) {
            // 언어별 요소 그룹 설정 (키_langs)
            $this->return[$key . '_langs'] = $processed['langs'];
        }
    }
}
