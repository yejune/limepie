<?php

namespace Limepie\Form\Parser;

/**
 * 요소 가시성 설정을 관리하는 클래스.
 *
 * 이 클래스는 'display_switch' 설정을 기반으로 특정 폼 요소의
 * 조건부 표시/숨김을 설정하고 관련 JavaScript 코드를 생성합니다.
 *
 * 주요 기능:
 * - 요소에 고유 클래스 할당
 * - 조건부 표시 스타일 설정
 * - 이벤트 핸들러(JavaScript) 코드 생성
 * - 초기 상태 설정
 * - 연계된 유효성 검사 처리
 */
class ElementVisibilityManager
{
    /**
     * 처리할 폼 구성 배열 참조 (수정 가능하도록 참조로 전달됨).
     *
     * @var array
     */
    private $arr;

    /**
     * 현재 처리 중인 폼 필드의 설정.
     *
     * @var array
     */
    private $fields;

    /**
     * 현재 처리 중인 필드의 키.
     * 디스플레이 타겟 설정에 사용됩니다.
     *
     * @var string
     */
    private $key;

    /**
     * 요소에 할당할 고유 클래스 식별자.
     * 동일한 HTML 페이지에서 여러 폼 요소가 충돌하지 않도록 합니다.
     *
     * @var string
     */
    private $uniqueClassId;

    /**
     * 모든 대상 요소의 클래스 선택자를 저장하는 배열.
     * 일괄 처리에 사용됩니다.
     *
     * @var array
     */
    private $allElementsClasses = [];

    /**
     * 각 요소가 어떤 스크립트 키에 속하는지 매핑한 배열.
     * 요소 이름을 키로, 속한 scriptKey 배열을 값으로 합니다.
     *
     * @var array
     */
    private $elementToScriptKeys = [];

    /**
     * 생성된 JavaScript 조건문을 저장하는 배열.
     *
     * @var array
     */
    private $jsConditions = [];

    /**
     * items에는 있지만 display_switch에는 없는 키 목록.
     * 이 키에 대해서는 항상 요소를 숨깁니다.
     *
     * @var array
     */
    private $diffKeys = [];

    /**
     * 필드의 기본값 (default 설정값).
     *
     * @var mixed
     */
    private $defaultValue;

    /**
     * 생성자.
     *
     * @param array  &$arr   처리할 폼 구성 배열 (참조로 전달)
     * @param array  $fields 현재 처리 중인 필드 설정
     * @param string $key    현재 처리 중인 필드 키
     */
    public function __construct(array &$arr, array $fields, string $key)
    {
        $this->arr           = $arr;
        $this->fields        = $fields;
        $this->key           = $key;
        $this->uniqueClassId = \Limepie\genRandomString();
        $this->defaultValue  = $fields['default'] ?? null;
    }

    /**
     * 요소 가시성 설정.
     *
     * 전체 가시성 설정 프로세스를 조율하는 메인 메서드입니다.
     * 다음 단계를 순차적으로 실행합니다:
     * 1. 요소와 스크립트 키 매핑
     * 2. JavaScript 조건문 생성
     * 3. 누락된 키 찾기
     * 4. 요소 표시 설정
     * 5. 변경 이벤트 핸들러 설정
     * 6. 초기 상태 설정
     *
     * @return array 업데이트된 폼 구성 배열
     */
    public function setupVisibility() : array
    {
        $this->mapElementsToScriptKeys();
        $this->generateJsConditions();
        $this->findDiffKeys();
        $this->setupElementDisplay();
        $this->setupOnChangeHandler();
        $this->setupInitialState();

        return $this->arr;
    }

    /**
     * 각 요소가 어떤 scriptKey에 속하는지 매핑.
     *
     * display_switch 설정을 분석하여 각 요소가 어떤 조건(scriptKey)에
     * 표시되어야 하는지 매핑하고, 고유 클래스를 생성합니다.
     */
    private function mapElementsToScriptKeys() : void
    {
        foreach ($this->fields['display_switch'] as $scriptKey => $elements) {
            if (!\is_array($elements)) {
                continue;
            }

            foreach ($elements as $element) {
                $element = \trim($element);

                if (empty($element)) {
                    continue;
                }

                // 요소에 고유 클래스 생성 및 추적
                $elementClass               = $this->generateElementClass($element);
                $this->allElementsClasses[] = '.' . $elementClass;

                // 요소가 속한 scriptKey 추적
                if (!isset($this->elementToScriptKeys[$element])) {
                    $this->elementToScriptKeys[$element] = [];
                }
                $this->elementToScriptKeys[$element][] = $scriptKey;
            }
        }

        // 중복 제거
        $this->allElementsClasses = \array_unique($this->allElementsClasses);
    }

    /**
     * 요소 클래스 생성.
     *
     * 요소 이름을 기반으로 고유한 CSS 클래스를 생성합니다.
     * 다중 항목 표기법(배열 표기법, [])을 처리하고, 고유 식별자를 추가합니다.
     *
     * @param string $element 요소 이름
     *
     * @return string 생성된 클래스 이름
     */
    private function generateElementClass(string $element) : string
    {
        // 배열 표기법 []을 __로 대체하고 고유 ID 추가
        return \str_replace('[]', '__', $element) . "_{$this->uniqueClassId}";
    }

    /**
     * JavaScript 조건문 생성.
     *
     * 각 scriptKey에 따라 표시할 요소를 정의하는
     * JavaScript 조건문을 생성합니다.
     */
    private function generateJsConditions() : void
    {
        foreach ($this->fields['display_switch'] as $scriptKey => $elements) {
            if (!\is_array($elements)) {
                continue;
            }

            $showElements = [];

            foreach ($elements as $element) {
                $element = \trim($element);

                if (empty($element)) {
                    continue;
                }

                // 표시할 요소의 JavaScript 코드 생성
                $elementClass   = $this->generateElementClass($element);
                $showElements[] = "\$self.closest('.form-group').find('." . $elementClass . "').show();";
            }

            // 조건 및 요소 표시 코드 조합 - 체크박스/라디오 버튼 고려
            if (!empty($showElements)) {
                $condition            = $this->generateElementCondition($scriptKey);
                $this->jsConditions[] = "if({$condition}) { " . \implode(' ', $showElements) . ' }';
            }
        }
    }

    /**
     * 요소 타입에 따른 조건문 생성.
     *
     * select, input text는 value만 확인하고,
     * checkbox, radio는 checked 상태와 value를 함께 확인합니다.
     *
     * @param mixed $scriptKey 확인할 값
     *
     * @return string 생성된 조건문
     */
    private function generateElementCondition($scriptKey) : string
    {
        return <<<JS
        ((this.type === 'checkbox' || this.type === 'radio')
            ? (this.checked && this.value == '{$scriptKey}')
            : this.value == '{$scriptKey}')
        JS;
    }

    /**
     * items에는 있지만 display_switch에는 없는 키 찾기.
     *
     * 이러한 키에 대해서는 요소를 항상 숨겨야 합니다.
     * 예를 들어 select 필드에 options가 5개(0,1,2,3,4)이지만
     * display_switch에는 1,2,3만 정의된 경우, 0과 4 선택 시 모든 요소 숨김.
     */
    private function findDiffKeys() : void
    {
        if (isset($this->fields['items'], $this->fields['display_switch'])) {
            $itemKeys       = \array_keys($this->fields['items']);
            $scriptKeys     = \array_keys($this->fields['display_switch']);
            $this->diffKeys = \array_diff($itemKeys, $scriptKeys);
        }
    }

    /**
     * 각 요소의 표시 설정.
     *
     * display_switch에 정의된 모든 요소에 대해
     * 표시 조건 및 스타일을 설정합니다.
     */
    private function setupElementDisplay() : void
    {
        foreach ($this->fields['display_switch'] as $scriptKey => $elements) {
            if (!\is_array($elements)) {
                continue;
            }

            foreach ($elements as $element) {
                $element = \trim($element);

                if (empty($element)) {
                    continue;
                }

                // 개별 요소 설정
                $this->setupSingleElement($element, $scriptKey);
            }
        }
    }

    /**
     * 단일 요소의 표시 설정.
     *
     * 개별 요소에 대한 클래스, 스타일, 및 표시 조건을 설정합니다.
     *
     * @param string $element   설정할 요소 이름
     * @param mixed  $scriptKey 요소가 표시될 조건 값
     */
    private function setupSingleElement(string $element, $scriptKey) : void
    {
        // 요소 클래스 및 기본 스타일 설정
        $elementClass = $this->generateElementClass($element);

        // 클래스 중복 방지
        $existingClasses              = \explode(' ', $this->arr[$element]['class'] ?? '');
        $existingClasses[]            = $elementClass;
        $this->arr[$element]['class'] = \implode(' ', \array_unique($existingClasses));

        // 표시 조건 설정 - 어떤 요소의 값에 따라 표시 여부가 결정되는지 지정
        $this->arr[$element]['display_target']                 = '.' . $this->key;
        $this->arr[$element]['display_target_condition_class'] = [];

        // 스크립트 키에 따른 조건별 스타일 설정
        $this->setupElementConditionStyles($element, $scriptKey);

        // 기본 스타일 설정 (기본값 고려)
        $this->setInitialElementStyle($element);
    }

    /**
     * 요소의 조건별 스타일 설정.
     *
     * 각 조건값(scriptKey)에 따른 요소의 표시 스타일을 설정합니다.
     * 기본값(default)을 고려하여 초기 상태를 올바르게 설정합니다.
     *
     * @param string $element   설정할 요소 이름
     * @param mixed  $scriptKey 요소가 표시될 조건 값
     */
    private function setupElementConditionStyles(string $element, $scriptKey) : void
    {
        // 조건별 스타일 배열이 없으면 초기화
        if (!isset($this->arr[$element]['display_target_condition_style'])) {
            $this->arr[$element]['display_target_condition_style'] = [];

            // display_switch에 없는 키(항상 숨겨야 하는 경우)는 모두 숨김 처리
            foreach ($this->diffKeys as $diffKey) {
                $this->arr[$element]['display_target_condition_style'][$diffKey] = 'display: none;';
            }
        }

        // 현재 스크립트 키에 대해서는 표시 (block)
        $this->arr[$element]['display_target_condition_style'][$scriptKey] = 'display: block';

        // 다른 스크립트 키에 대해서는 숨김 (none)
        // 단, 이미 display: block으로 설정된 키는 넘어갑니다
        foreach ($this->fields['display_switch'] as $otherKey => $otherElements) {
            if ($otherKey !== $scriptKey
                && (!isset($this->arr[$element]['display_target_condition_style'][$otherKey])
                 || 'display: block' !== $this->arr[$element]['display_target_condition_style'][$otherKey])) {
                $this->arr[$element]['display_target_condition_style'][$otherKey] = 'display: none';
            }
        }
    }

    /**
     * 요소의 초기 스타일 설정.
     *
     * 요소의 기본 스타일을 설정합니다.
     * 기본값(default)이 있는 경우 해당 값에 따른 표시 상태를 결정합니다.
     *
     * @param string $element 설정할 요소 이름
     */
    private function setInitialElementStyle(string $element) : void
    {
        $existingStyles = \explode(';', $this->arr[$element]['style'] ?? '');

        // 기본값이 있고 해당 값에 대한 조건이 설정되어 있는 경우
        if (null !== $this->defaultValue
            && isset($this->arr[$element]['display_target_condition_style'][$this->defaultValue])) {
            // 기본값에 따른 초기 표시 상태 설정
            $defaultStyle = $this->arr[$element]['display_target_condition_style'][$this->defaultValue];

            // display 스타일이 none인 경우에만 기본 스타일에 추가
            if (false !== \strpos($defaultStyle, 'display: none')) {
                $existingStyles[] = 'display: none';
            }
            // block인 경우는 기본적으로 표시되므로 추가하지 않음
        } else {
            // 기본값이 없거나 조건이 없는 경우 기본적으로 숨김
            $existingStyles[] = 'display: none';
        }

        $this->arr[$element]['style'] = \implode('; ', \array_unique(\array_filter($existingStyles)));
    }

    /**
     * onChange 이벤트 핸들러 설정.
     *
     * 값이 변경될 때 요소 표시/숨김을 처리하는
     * JavaScript 이벤트 핸들러를 설정합니다.
     */
    private function setupOnChangeHandler() : void
    {
        if (empty($this->jsConditions)) {
            return;
        }

        // 모든 관련 요소를 선택하는 CSS 선택자
        $allElementsSelector = \implode(', ', $this->allElementsClasses);

        // onChange 이벤트 핸들러 코드 생성 및 설정
        $this->arr[$this->key]['onchange'] = $this->generateOnChangeCode($allElementsSelector);
    }

    /**
     * onChange 이벤트 코드 생성.
     *
     * 값 변경 시 실행될 JavaScript 코드를 생성합니다:
     * 1. 모든 관련 요소 숨기기
     * 2. 조건에 따라 특정 요소 표시
     * 3. 유효성 검사 업데이트
     *
     * @param string $allElementsSelector 모든 관련 요소를 선택하는 CSS 선택자
     *
     * @return string 생성된 JavaScript 코드
     */
    private function generateOnChangeCode(string $allElementsSelector) : string
    {
        // jQuery 대상 요소 선택 및 모든 관련 요소 숨기기
        $code = "var \$self = $(this);\n";
        $code .= "\$self.closest('.form-group').find('{$allElementsSelector}').hide();\n";

        // 첫 번째 조건문 추가
        $code .= $this->jsConditions[0];

        // 나머지 조건문 추가 (else if 구조)
        for ($i = 1; $i < \count($this->jsConditions); ++$i) {
            $code .= " else {$this->jsConditions[$i]}";
        }

        // 기본 케이스 - 일치하는 조건이 없으면 모든 요소 숨김
        $code .= " else { \$self.closest('.form-group').find('{$allElementsSelector}').hide(); }";

        // 폼 유효성 검사 코드 추가
        $code .= $this->generateValidationCode($allElementsSelector);

        return $code;
    }

    /**
     * 유효성 검사 코드 생성.
     *
     * 요소 표시/숨김 후 폼 유효성 검사를 업데이트하는
     * JavaScript 코드를 생성합니다.
     *
     * @param string $allElementsSelector 모든 관련 요소를 선택하는 CSS 선택자
     *
     * @return string 생성된 JavaScript 유효성 검사 코드
     */
    private function generateValidationCode(string $allElementsSelector) : string
    {
        // 유효성 검사 대상 요소 수집
        $validElements = $this->collectValidElements();

        if (empty($validElements)) {
            return '';
        }

        // 중복 제거 및 선택자 문자열 생성
        $validElementsStr = \implode(', ', $validElements);

        // 유효성 검사 코드 생성 - 폼이 있는 경우에만 실행
        return <<<SQL
            if(\$self.closest('form').length > 0) {
                var elementsToCheck = \$('.valid-target', \$self.closest('.form-group').find('{$validElementsStr}').closest('.form-element-wrapper'));
                if(elementsToCheck.length > 0) {
                    \$self.closest('form').validate().checkByElements(elementsToCheck);
                }
            }
        SQL;
    }

    /**
     * 유효성 검사 대상 요소 수집.
     *
     * display_switch에 정의된 모든 요소의 클래스 선택자를 수집합니다.
     * 이 요소들은 표시/숨김 후 유효성 검사 업데이트가 필요합니다.
     *
     * @return array 유효성 검사 대상 요소 클래스 선택자 배열
     */
    private function collectValidElements() : array
    {
        $validElements = [];

        foreach ($this->fields['display_switch'] as $scriptKey => $elements) {
            if (!\is_array($elements)) {
                continue;
            }

            foreach ($elements as $element) {
                $element = \trim($element);

                if (empty($element)) {
                    continue;
                }

                // 요소 클래스 선택자 추가
                $validElements[] = '.' . $this->generateElementClass($element);
            }
        }

        // 중복 제거 후 반환
        return \array_unique($validElements);
    }

    /**
     * 페이지 로드 시 초기 상태 설정.
     *
     * 페이지 로드 시 현재 선택된 값에 따라
     * 요소의 초기 표시/숨김 상태를 설정합니다.
     */
    private function setupInitialState() : void
    {
        if (empty($this->allElementsClasses)) {
            return;
        }

        // 모든 관련 요소를 선택하는 CSS 선택자
        $allElementsSelector = \implode(', ', $this->allElementsClasses);

        // ready 이벤트 핸들러 초기화
        if (!isset($this->arr[$this->key]['ready'])) {
            $this->arr[$this->key]['ready'] = '';
        }

        // 초기 상태 설정 코드 추가
        $this->arr[$this->key]['ready'] .= $this->generateInitialStateCode($allElementsSelector);
    }

    /**
     * 초기 상태 설정 코드 생성.
     *
     * 페이지 로드 시 실행될 JavaScript 코드를 생성합니다:
     * 1. 현재 선택된 값 가져오기
     * 2. 모든 관련 요소 숨기기
     * 3. 선택된 값에 따라 특정 요소 표시
     *
     * @param string $allElementsSelector 모든 관련 요소를 선택하는 CSS 선택자
     *
     * @return string 생성된 JavaScript 코드
     */
    private function generateInitialStateCode(string $allElementsSelector) : string
    {
        // 초기 상태 설정 기본 코드 (현재 값 가져오기 및 모든 요소 숨기기)
        $code = <<<SQL
            // 페이지 로드 시 초기 상태 설정
            var initVal = $(this).val();
            var \$self = $(this);
            // 기본적으로 모든 요소 숨기기
            \$self.closest('.form-group').find('{$allElementsSelector}').hide();
            // 초기 선택값에 따라 요소 표시
        SQL;

        // 각 조건에 대한 표시 코드 추가
        foreach ($this->fields['display_switch'] as $scriptKey => $elements) {
            if (!\is_array($elements) || empty($elements)) {
                continue;
            }

            // 이 조건에 대한 코드 생성 및 추가
            $code .= $this->generateInitialStateCondition($scriptKey, $elements);
        }

        return $code;
    }

    /**
     * 초기 상태 조건문 생성.
     *
     * 특정 조건값(scriptKey)에 대한 요소 표시 코드를 생성합니다.
     *
     * @param string $scriptKey 조건 값
     * @param array  $elements  이 조건에서 표시할 요소 배열
     *
     * @return string 생성된 조건 JavaScript 코드
     */
    private function generateInitialStateCondition(string $scriptKey, array $elements) : string
    {
        $showElementsInit = [];

        foreach ($elements as $element) {
            $element = \trim($element);

            if (empty($element)) {
                continue;
            }

            // 요소 표시 코드 생성
            $elementClass       = $this->generateElementClass($element);
            $showElementsInit[] = "\$self.closest('.form-group').find('." . $elementClass . "').show();";
        }

        if (empty($showElementsInit)) {
            return '';
        }

        // 조건문과 표시 코드 조합
        $code = <<<SQL

        if(initVal == '{$scriptKey}') {
            {$showElementsInit[0]}
        SQL;

        // 여러 요소가 있는 경우 추가
        for ($i = 1; $i < \count($showElementsInit); ++$i) {
            $code .= " {$showElementsInit[$i]}";
        }

        $code .= ' }';

        return $code;
    }
}
