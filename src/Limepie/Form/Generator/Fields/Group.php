<?php

declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\arr;
use Limepie\ArrayObject;
use Limepie\Exception;
use Limepie\Form\Generator\Fields;

class Group extends Fields
{
    public static function write(string $elementName, array $specs, $data, $ruleName = null)
    {
        $innerhtml    = '';
        $script       = '';
        $html         = '';
        $elementIndex = -1;

        if (\is_object($data)) {
            $specs = [...$specs, ...$data->property];
            $data  = $data->value;
        }

        if ($data) {
        } else {
            if (true === isset($specs['blank_message'])) {
                // $specs['properties'] = null;
                $specs['properties'] = null;
            }
        }

        foreach ($specs['properties'] ?? [] as $propertyKey => $propertyValue) {
            if (false === isset($propertyValue['type'])) {
                throw (new Exception('group ' . ($elementName ? '"' . $elementName . '" ' : '') . '"' . $propertyKey . '" type not found'))->setDebugMessage('error', __FILE__, __LINE__);
            }

            if (\preg_match('#(.*)\[([a-z\-\_]+)\]#', (string) $propertyKey, $propertyMatch)) {
                throw new Exception('not support a[x] style');
            }

            ++$elementIndex;
            $elements                 = '';
            $fieldTypeGeneratorMethod = __NAMESPACE__ . '\\' . \Limepie\camelize($propertyValue['type']);

            [
                $isArray,
                $nextElementName,
                $nextRuleName,
                $elementDotName,
                $nextElementDotName,
                $nextData,
                $propertyValue,
            ] = self::extractProperties(
                $propertyKey,
                $propertyValue,
                $elementName,
                $ruleName,
                $data
            );

            if (isset($propertyValue['form']) && false == $propertyValue['form']) {
                // form이 false이면 스팩에는 있지만 생성하지 않는다.
                continue;
            }

            $nextData = static::processNextData(
                $nextData,
                $propertyValue,
                $isArray
            );

            $elements .= static::generateElements(
                $nextData,
                $fieldTypeGeneratorMethod,
                $nextElementName,
                $propertyValue,
                $nextRuleName,
                $propertyKey,
                $isArray
            );

            $title       = static::getLocalizedValue($propertyValue, 'label')       ?? '';
            $description = static::getLocalizedValue($propertyValue, 'description') ?? '';

            if (isset($propertyValue['multiple'])) {
                if (\is_array($nextData)) {
                    if (isset($propertyValue['view_total_template'])) {
                        // \prx($nextData, \count($nextData));
                        $title .= \str_replace('{=total}', "<span class='total-count'>" . (string) \count($nextData) . '</span>', $propertyValue['view_total_template']);
                    } else {
                        if (isset($propertyValue['view_total'])) {
                            $title .= ' (<span class="total-count">' . \count($nextData) . '</span>)';
                        }
                    }
                }
            }
            $appendDescription  = '';
            $prependDescription = '';
            $titleHtml          = '';

            $labelClass = '';

            if (true === isset($propertyValue['label_class'])) {
                $labelClass = $propertyValue['label_class'];
            }

            $addClass = [
                'form-element-wrapper',
            ];

            if (true === isset($propertyValue['class'])) {
                $addClass[] = \trim($propertyValue['class']);
            }

            $addStyle = [];

            if (true === isset($propertyValue['style'])) {
                $addStyle[] = \trim($propertyValue['style'], ';');
            }

            // product standard 에서 사용
            if (true === isset($propertyValue['switch'])) {
                $else = $propertyValue['switch']['default'];
                // unset($propertyValue['switch']['default']);
                $fixedStyle = $else['style'] ?? '';
                $fixedClass = $else['class'] ?? '';

                foreach ($propertyValue['switch']['cases'] as $innerCondition => $innerStyle) {
                    $result = static::checkCondition($innerCondition, $elementName, static::$allData, static::$specs);
                    // \pr($innerCondition, $prevKey, $result, $innerStyle);

                    if ($result) {
                        $fixedStyle = $innerStyle['style'] ?? '';
                        $fixedClass = $innerStyle['class'] ?? '';

                        break;
                    }
                }

                $addStyle[] = $fixedStyle;
                $addClass[] = $fixedClass;
            }

            if (true === isset($propertyValue['element'])) {
                if (true === isset($propertyValue['element']['all_of'])) {
                    $result = true;

                    foreach ($propertyValue['element']['all_of']['conditions'] as $elementKey => $value) {
                        $elementVVV = static::resolveElementValue($elementKey, $nextElementDotName, $elementDotName);
                        // \pr($elementKey, $elementVVV, $value);

                        if (\is_array($value)) {
                            if (true == \in_array($elementVVV, $value)) {
                                // $result[$elementKey] = true;
                            } else {
                                $result = false;
                            }
                        } else {
                            if ($elementVVV == $value) {
                                // $result[$elementKey] = true;
                            } else {
                                $result = false;
                            }
                        }
                    }

                    if ($result) {
                        if (true === isset($propertyValue['element']['all_of']['inline'])) {
                            $addStyle[] = $propertyValue['element']['all_of']['inline'];
                        }

                        if (true === isset($propertyValue['element']['all_of']['class'])) {
                            $addClass[] = $propertyValue['element']['all_of']['class'];
                        }
                    } else {
                        if (true === isset($propertyValue['element']['all_of']['not']['inline'])) {
                            $addStyle[] = $propertyValue['element']['all_of']['not']['inline'];
                        }

                        if (true === isset($propertyValue['element']['all_of']['not']['class'])) {
                            $addClass[] = $propertyValue['element']['all_of']['not']['class'];
                        }
                    }
                }
            }

            // 다중 타겟 처리
            $multiTargetDisplayUnique = static::processMultiTargets(
                $propertyValue,
                $data,
                $specs
            );

            // 단일 타겟 처리
            $singleTargetResult = static::processSingleTarget(
                $propertyValue,
                $elementDotName,
                $nextElementDotName
            );

            // 디스플레이 고유 스타일 병합
            $displayUnique = \array_merge(
                $multiTargetDisplayUnique,
                $singleTargetResult['displayUnique']
            );

            // 클래스 추가
            $addClass = \array_merge(
                $addClass,
                $singleTargetResult['addClass']
            );

            // 기존 디스플레이 유니크 처리 로직
            if ($displayUnique) {
                if (1 === \count($displayUnique)) {
                    $addStyle[] = \key($displayUnique);
                } else {
                    $addStyle[] = 'display: none;';
                }
            }

            // \prx($title, $addStyle);

            if ($title) {
                $titleHtml .= '<h6 class="' . $labelClass . '">' . $title . '</h6>';
            }

            if ($description) {
                if (true === \is_array($description)) {
                    $prependDescription = '<div class="wrap-description">';
                    $prependDescription .= '<table class="table table-bordered description">';

                    foreach ($description as $dkey => $dvalue) {
                        $prependDescription .= '<tr><td>' . $dkey . '</td><td>' . $dvalue . '</td></tr>';
                    }
                    $prependDescription .= '</table>';
                    $prependDescription .= '</div>';
                } else {
                    $description        = \preg_replace("#\\*(.*)\n#", '<span class="bold">*$1</span>' . \PHP_EOL, $description);
                    $prependDescription = '<p class="description">' . \nl2br($description) . '</p>';
                }
            }

            if ('switcher' === $propertyValue['type']) {
                $appendDescription  = $prependDescription;
                $prependDescription = '';
            }

            $innerhtml .= self::generateElementTypeHtml(
                $propertyValue['type'],
                $addClass,
                $addStyle,
                $nextElementDotName,
                $titleHtml,
                $elements,
                $prependDescription,
                $appendDescription,
                $nextData,
                $description
            );
        }
        $groupClass = [
            'form-group',
        ];

        if (true === isset($specs['group_class'])) {
            if (true === \is_array($specs['group_class'])
            && true  === isset($specs['group_class']['exist'])
            && true  === isset($specs['group_class']['empty'])
            ) {
                if ($data) {
                    $groupClass[] = \trim($specs['group_class']['exist']);
                } else {
                    $groupClass[] = \trim($specs['group_class']['empty']);
                }
            } else {
                // !이 포함되어있으면 이후로는 data가 empty일때 적용할 class이다.
                if (false !== \strpos($specs['group_class'], '!')) {
                    [$exist, $empty] = \array_map('trim', \explode('!', $specs['group_class']));

                    if ($data) {
                        $groupClass[] = \trim($exist);
                    } else {
                        $groupClass[] = \trim($empty);
                    }
                } else {
                    $groupClass[] = \trim($specs['group_class']);
                }
            }
        }
        $styleTag = '';

        if ($data) {
        } else {
            if (true === isset($specs['blank_message'])) {
                $groupId      = 'blank_' . \Limepie\uniqid();
                $groupClass[] = $groupId;
                $styleTag     = <<<STYLE
                    <style nonce="{$_SESSION['nonce']}">
                    .form-container .{$groupId}::after {
                        content: "{$specs['blank_message']}";
                    }
                    </style>
                STYLE;
            }
        }

        $groupClassString = '';

        if ($groupClass) {
            $groupClassString = \implode(' ', $groupClass);
        }

        $groupStyle = '';

        if (true === isset($specs['group_style'])) {
            $groupStyle = " style='" . $specs['group_style'] . "'";
        }
        $onchange = '';

        if (isset($specs['event'])) {
            $function = '';
            $type     = [];
            // \prx($specs);

            if (isset($specs['event']['function'])) {
                $function = $specs['event']['function'];
            }

            if (isset($specs['event']['type'])) {
                $type = $specs['event']['type'];
            }

            if ($type) {
                foreach ($type as $event) {
                    if ('onload' === $event) {
                        $onchange .= ' data-onload="' . \Limepie\minify_js(\str_replace('"', '\"', $function)) . '"';
                    }
                }
            }
        }

        // if (true === isset($specs['group_class']) && false !== \strpos($specs['group_class'], 'border-primary')) {
        //     if (0 == $data['is_display']) {
        //         $groupClassString = \str_replace('border-primary', 'border-danger', $groupClassString);
        //     }
        // }
        $html = <<<EOT
        {$styleTag}<div class='{$groupClassString}'{$groupStyle}{$onchange}>{$innerhtml}</div>{$script}
        EOT;

        return $html;
    }

    /**
     * 폼 요소의 속성 키를 처리하고 폼 생성에 필요한 메타데이터를 준비하는 메서드.
     *
     * 이 메서드의 주요 목적:
     * 1. 복잡한 중첩 폼 구조에서 각 요소의 이름, 타입, 데이터를 일관되게 처리
     * 2. 다양한 입력 형태(배열, 객체)에 대한 표준화된 전처리 수행
     * 3. 폼 요소 생성을 위한 필수 메타데이터 추출 및 변환
     *
     * 주요 처리 로직:
     * - 배열 표기(`[]`)를 가진 키 정규화
     * - 중첩된 폼 요소의 이름 동적 생성 (예: `parent[child]`)
     * - 요소의 배열 여부 결정
     * - 객체 데이터의 특수 처리 및 값 추출
     * - 점(.) 표기법으로 요소 경로 변환
     *
     * @param string      $propertyKey   원본 속성 키
     * @param array       $propertyValue 속성 값 스펙
     * @param null|string $elementName   부모 요소 이름 (선택적)
     * @param null|string $ruleName      규칙 이름 (선택적)
     * @param mixed       $data          현재 데이터
     *
     * @return array 처리된 폼 요소 메타데이터
     *               [정제된 키, 배열 여부, 요소 이름, 규칙 이름,
     *               부모 점 표기법, 다음 점 표기법, 다음 데이터, 갱신된 속성 값]
     */
    private static function extractProperties(
        string $propertyKey,    // 원본 속성 키
        array $propertyValue,   // 속성 값 스펙
        ?string $elementName,   // 부모 요소 이름 (선택적)
        ?string $ruleName,      // 규칙 이름 (선택적)
        $data                   // 현재 데이터
    ) : array {
        // 원본 속성 키 보존
        $fixPropertyKey = $propertyKey;

        // 배열 및 스트립 플래그 초기화
        $isArray = false;
        $strip   = false;

        // 배열 스타일 키 처리
        // []가 포함된 경우 키에서 []를 제거하고 배열 플래그 설정
        if (\str_contains((string) $fixPropertyKey, '[]')) {
            $fixPropertyKey = \str_replace('[]', '', $fixPropertyKey);
            $isArray        = true;
            $strip          = true;
        }

        // 속성 스펙에 따라 배열 여부 재결정
        $isArray = self::determineIsArray($propertyValue);

        // 요소 이름 생성
        // 부모 요소 이름이 있으면 해당 이름에 현재 키 추가
        $nextElementName = $elementName
            ? $elementName . '[' . $fixPropertyKey . ']'
            : $fixPropertyKey;

        // 규칙 이름 생성
        // 부모 규칙 이름이 있으면 해당 이름에 현재 키 추가
        $nextRuleName = $ruleName
            ? $ruleName . '[' . $fixPropertyKey . ']'
            : $fixPropertyKey;

        // 비배열 + 스트립된 키의 경우 []를 추가하여 보정
        // 예: a > b > c[] => a[b][c[]] -> a[b][c][] 로 보정
        if (!$isArray && $strip) {
            $nextElementName .= '[]';
            $nextRuleName    .= '[]';
        }

        // 점 표기법 이름 생성
        // 폼 요소의 중첩 구조를 점(.)으로 표현
        $elementDotName     = self::getDotName($elementName);
        $nextElementDotName = self::getDotName($nextElementName);

        // 다음 데이터 추출
        // 데이터가 배열이고 키가 존재하면 해당 값 추출, 없으면 빈 문자열
        $nextData = \is_array($data) && $fixPropertyKey
            ? ($data[$fixPropertyKey] ?? '')
            : '';

        // 객체 데이터 특수 처리
        if (\is_object($nextData) && !($nextData instanceof ArrayObject)) {
            // 객체의 속성을 현재 속성 값에 병합
            $propertyValue = [...$propertyValue, ...$nextData->property ?? []];

            // 객체의 실제 값 추출
            $nextData = $nextData->value;

            // 추출된 데이터가 연관 배열이면 배열 플래그 해제
            if (arr::is_assoc($nextData)) {
                $isArray = false;
            }
        }

        // 처리된 모든 값을 배열로 반환
        // 구조 분해 할당으로 사용 가능
        return [
            $isArray,           // 배열 여부
            $nextElementName,   // 다음 요소 이름
            $nextRuleName,      // 다음 규칙 이름
            $elementDotName,    // 부모 요소의 점 표기법 이름
            $nextElementDotName, // 다음 요소의 점 표기법 이름
            $nextData,          // 다음 데이터
            $propertyValue,      // 갱신된 속성 값 스펙
        ];
    }

    private static function determineIsArray(array $propertyValue) : bool
    {
        if (!isset($propertyValue['multiple'])) {
            return false;
        }

        $multiple = $propertyValue['multiple'];

        return match (true) {
            true   === $multiple => true,
            'true' === $multiple => true,
            'only' === $multiple => true,
            default              => false
        };
    }

    private static function generateElementTypeHtml(
        string $type,
        array $addClass,
        array $addStyle,
        string $nextElementDotName,
        string $titleHtml,
        string $elements,
        string $prependDescription,
        string $appendDescription,
        $nextData = null,
        ?string $description = null
    ) : string {
        $addStyleString = self::prepareStyleString($addStyle);
        $addClassString = self::prepareClassString($addClass);

        $addClassString = '';

        if ($addClass) {
            $addClassString = \implode(' ', $addClass);
        }

        if ('hidden' === $type) {
            $result = "<div class=\"x-hidden {$addClassString}\" name=\"{$nextElementDotName}-layer\">{$elements}</div>";
        } elseif ('dummy' === $type && '' === $nextData) {
            $result = '';
        } elseif ('checkbox' === $type) {
            $result = "<div class=\"{$addClassString}\" name=\"{$nextElementDotName}-layer\"><div class=\"checkbox\"><h6>{$elements}</h6>" . ($description ? '<p class="description">' . \nl2br($description) . '</p>' : '') . '</div></div>';
        } else {
            $result = "<div class=\"{$addClassString}\" style=\"{$addStyleString}\" name=\"{$nextElementDotName}-layer\">{$titleHtml}{$prependDescription}<div class=\"form-element\">{$elements}</div>{$appendDescription}</div>";
        }

        return $result;
    }

    private static function prepareStyleString(array $styles) : string
    {
        // 필터링: null, 빈 문자열, 공백 제거
        $filteredStyles = \array_filter(\array_map('trim', $styles), function ($style) {
            return '' !== $style;
        });

        // 중복 제거 및 유효한 스타일만 선택
        $uniqueStyles = \array_unique($filteredStyles);

        // 세미콜론 제거 및 재추가, 불필요한 공백 제거
        $cleanedStyles = \array_map(function ($style) {
            // 마지막 세미콜론 제거 후 다시 추가
            return \rtrim(\trim($style), ';') . ';';
        }, $uniqueStyles);

        return \implode(' ', $cleanedStyles);
    }

    private static function prepareClassString(array $classes) : string
    {
        // 필터링: null, 빈 문자열, 공백 제거, 중복 제거
        $filteredClasses = \array_unique(
            \array_filter(\array_map('trim', $classes), function ($class) {
                return '' !== $class;
            })
        );

        return \implode(' ', $filteredClasses);
    }

    public static function getValueFromData($pattern, $map)
    {
        $patterns = \explode('.', \str_replace(['[', ']'], ['.', '*'], $pattern));
        $maps     = \explode('.', \str_replace(['[', ']'], ['.', ''], $map));

        $new = [];

        $currentData = static::$allData;
        $currentSpec = static::$specs;

        foreach ($patterns as $index => $d) {
            if (isset($maps[$index]) && 1 === \preg_match('#__([^_]{13})__#', $maps[$index])) {
                $key = $new[] = $maps[$index];
            } else {
                $key = $new[] = $d;
            }

            if (isset($currentSpec['properties'][$key])) {
                $currentSpec = $currentSpec['properties'][$key];
            } elseif (isset($currentSpec['properties'][$key . '[]'])) {
                $currentSpec = $currentSpec['properties'][$key . '[]'];
            }

            if (isset($currentData[$key])) {
                $currentData = $currentData[$key];
            } else {
                $currentData = null;
            }
        }

        if (null === $currentData && $currentSpec['default']) {
            $currentData = $currentSpec['default'];
        }

        return $currentData;
    }

    public static function checkCondition($condition, $map)
    {
        // \pr($condition);
        // 조건문 내의 {path}를 실제 데이터 값으로 대체
        $interpolatedCondition = \preg_replace_callback('/\{([^}]+)\}/', function ($matches) use ($map) {
            $path  = $matches[1];
            $value = null;

            if (false === \strpos($path, '.')) {
                $path = $map . '.' . $path;
            }
            $value = static::getValueFromData($path, $map);
            // \pr($path, $base, $value);

            if (\is_null($value)) {
                return 0;
            }

            return \is_string($value) ? "'{$value}'" : $value;
        }, $condition);
        // \pr($interpolatedCondition, eval("return {$interpolatedCondition};"));

        // 조건문 평가
        return eval("return {$interpolatedCondition};");
    }

    /**
     * 요소의 경로를 기반으로 값을 가져옵니다.
     *
     * @param string $targetElementDotName 대상 요소의 점 표기법 경로
     * @param string $nextDotName          다음 점 표기법 경로
     * @param string $elementDotName       현재 요소의 점 표기법 경로
     *
     * @return mixed 찾은 값
     */
    public static function resolveElementValue($targetElementDotName, $nextDotName, $elementDotName)
    {
        // * 패턴 처리
        if (false !== \strpos((string) $targetElementDotName, '*')) {
            // a.b.*.c 정로도 넘어오기때문에 *를 현재의 13자리 문자로 바꿔줘야한다.
            // display_target: rooms.*.rooms.bed_type
            $targetElementDotName = parent::getFixedPath($nextDotName, $targetElementDotName);
        }

        // 상대 경로 처리 (.. 표기법)
        $targetElementDotName = self::resolveRelativePath($targetElementDotName, $elementDotName);

        // 값 가져오기
        $targetValue = parent::getValueByDot(static::$allData, $targetElementDotName);
        $targetValue = self::normalizeObjectValue($targetValue);

        // 값이 없는 경우 기본값 처리
        if (!\strlen((string) $targetValue)) {
            $targetSpec = static::getSpecByDot(static::$specs, $targetElementDotName);

            // 기본값 가져오기
            $targetValue = static::getDefaultByDot(static::$specs, static::$allData, $targetElementDotName);
            $targetValue = self::normalizeObjectValue($targetValue);
        }

        return $targetValue;
    }

    /**
     * 상대 경로를 절대 경로로 해석합니다.
     *
     * @param string $targetPath  처리할 상대 경로
     * @param string $currentPath 현재 기준 경로
     *
     * @return string 해석된 절대 경로
     */

    // 현재 경로: 'parent.child.subchild'
    // '..newchild' → 'parent.newchild'
    // '...newchild' → 'child.newchild'
    // '....newchild' → 'newchild'
    private static function resolveRelativePath($targetPath, $currentPath)
    {
        // 상대 경로가 아니면 그대로 반환
        if (0 !== \strpos((string) $targetPath, '.')) {
            return $targetPath;
        }

        // 점(.) 접두사 제거 및 상위 경로 이동 횟수 계산
        $cleanPath = \ltrim((string) $targetPath, '.');
        $levelsUp  = \strlen((string) $targetPath) - \strlen($cleanPath);

        // 현재 경로를 점 기준으로 분해
        $pathParts = \explode('.', $currentPath);

        // 상위 경로로 이동
        $pathParts = \array_slice($pathParts, 0, \count($pathParts) - ($levelsUp - 1));

        // 해석된 경로 생성
        if (empty(arr::filter_recursive($pathParts))) {
            return $cleanPath;
        }

        return \implode('.', $pathParts) . '.' . $cleanPath;
    }

    /**
     * 객체 값 정규화 메서드.
     *
     * @param mixed $value 정규화할 값
     *
     * @return mixed 정규화된 값
     */
    private static function normalizeObjectValue($value) : mixed
    {
        if (\is_object($value)) {
            return \property_exists($value, 'value') ? $value->value : $value;
        }

        return $value;
    }

    private static function getDotName($name)
    {
        return \str_replace(['[]', '[', ']'], ['.*', '.', ''], $name);
    }

    /**
     * 다중 타겟 조건 처리 메서드.
     *
     * @param array $propertyValue 속성 값
     * @param array $data          데이터
     * @param array $specs         스펙
     *
     * @return array 디스플레이 고유 스타일
     */
    private static function processMultiTargets(
        array $propertyValue,
        $data,
        $specs
    ) : array {
        $displayUnique = [];

        if (!isset($propertyValue['display_targets'])) {
            return $displayUnique;
        }

        // 데이터가 배열이 아니면 빈 배열로 처리
        $data  = \is_array($data) ? $data : [];
        $specs = \is_array($specs) ? $specs : [];

        // 좌측, 우측 값 추출
        [$left, $right] = $propertyValue['display_targets'];

        // 좌측 값 해석
        $leftValue = static::resolveTargetValue($left, $data, $specs);

        // 우측 값 해석
        $rightValue = static::resolveTargetValue($right, $data, $specs);

        // 조건 스타일 처리
        foreach ($propertyValue['display_targets_condition_style'] as $cond => $style) {
            $style = \trim($style, ';');

            $conditionMet = match ($cond) {
                'eq'    => $leftValue == $rightValue,
                'lt'    => $leftValue < $rightValue,
                'gt'    => $leftValue > $rightValue,
                default => false
            };

            if ($conditionMet) {
                $displayUnique[$style] = ($displayUnique[$style] ?? 0) + 1;
            }
        }

        return $displayUnique;
    }

    /**
     * 대상 값 해석 메서드.
     *
     * @param string $targetPath 대상 경로
     * @param array  $data       데이터
     * @param array  $specs      스펙
     *
     * @return mixed 해석된 값
     */
    private static function resolveTargetValue(
        string $targetPath,
        array $data,
        array $specs
    ) : mixed {
        $tmpids = \explode('.', $targetPath);
        $value  = $data;
        $spec   = $specs;

        // 값과 스펙 탐색
        foreach ($tmpids as $tmpid) {
            $value = $value[$tmpid]              ?? null;
            $spec  = $spec['properties'][$tmpid] ?? [];

            if (null === $value) {
                break;
            }
        }

        // 값 정규화
        if ($value) {
            $value = static::normalizeObjectValue($value);
        } elseif (isset($spec['default'])) {
            $value = static::normalizeObjectValue($spec['default']);
        }

        return $value;
    }

    /**
     * 단일 대상 조건 처리 메서드.
     *
     * @param array  $propertyValue  속성 값
     * @param string $elementDotName 요소 경로
     *
     * @return array 처리 결과
     */
    private static function processSingleTarget(
        array $propertyValue,
        string $elementDotName,
        string $nextElementDotName
    ) : array {
        $displayUnique = [];
        $addClass      = [];

        if (!isset($propertyValue['display_target'])) {
            return [
                'displayUnique' => $displayUnique,
                'addClass'      => $addClass,
            ];
        }

        $targetElementDotName = $propertyValue['display_target'];

        // * 패턴 처리
        if (false !== \strpos($targetElementDotName, '*')) {
            $targetElementDotName = parent::getFixedPath($nextElementDotName, $targetElementDotName);
        }

        // 상대 경로 해석
        $targetElementDotName = self::resolveRelativePath($targetElementDotName, $elementDotName);

        // 대상 값 가져오기

        $targetValue = parent::getValueByDot(static::$allData, $targetElementDotName);
        $targetValue = self::normalizeObjectValue($targetValue);

        // 값이 없는 경우 기본값 처리
        if (!\strlen((string) $targetValue)) {
            $targetValue = static::getDefaultByDot(static::$specs, static::$allData, $targetElementDotName);
            $targetValue = self::normalizeObjectValue($targetValue);
        }

        // 조건부 스타일 및 클래스 처리
        if (\strlen((string) $targetValue)) {
            if (isset($propertyValue['display_target_condition_style'][$targetValue])) {
                $style                 = \trim($propertyValue['display_target_condition_style'][$targetValue], ';');
                $displayUnique[$style] = ($displayUnique[$style] ?? 0) + 1;
            }

            if (isset($propertyValue['display_target_condition_class'][$targetValue])) {
                $addClass[] = \trim($propertyValue['display_target_condition_class'][$targetValue]);
            }
        }

        // \prx($propertyValue, $targetValue ?? 'p', $style ?? 'p', $displayUnique ?? []);

        return [
            'displayUnique' => $displayUnique,
            'addClass'      => $addClass,
        ];
    }

    /**
     * nextData 값을 처리하고 필요한 경우 기본값으로 대체합니다.
     *
     * 이 함수는 데이터가 없거나 null인 경우 propertyValue에서 기본값을
     * 추출하여 대체하는 역할을 합니다.
     *
     * @param mixed $nextData      처리할 데이터
     * @param array $propertyValue 속성 값 및 기본값 설정
     * @param bool  $isArray       결과가 배열이어야 하는지 여부
     *
     * @return mixed 처리된 데이터(배열 또는 스칼라 값)
     */
    private static function processNextData(
        mixed $nextData,
        array $propertyValue,
        bool $isArray
    ) : mixed {
        // 데이터가 없거나 null인 경우 처리
        if (!static::isValue($nextData)) {
            if (false === $isArray) {
                // 비배열의 경우 기본값 또는 빈 값으로 설정
                return $propertyValue['default'] ?? '';
            }
            // 배열의 경우 기본값 설정
            $parentId = static::getUniqueId();

            return isset($propertyValue['default'])
                ? (\is_array($propertyValue['default'])
                    ? $propertyValue['default']
                    : [$propertyValue['default']])
                : [$parentId => null];
        }

        return $nextData;
    }

    /**
     * 처리된 nextData를 기반으로 HTML 요소를 생성합니다.
     *
     * @param mixed  $nextData                 처리된 데이터
     * @param string $fieldTypeGeneratorMethod 요소 생성 메서드
     * @param string $nextElementName          다음 요소 이름
     * @param array  $propertyValue            속성 값
     * @param string $nextRuleName             다음 규칙 이름
     * @param string $propertyKey              속성 키
     * @param bool   $isArray                  배열 여부
     *
     * @return string 생성된 요소들
     */
    private static function generateElements(
        $nextData,
        string $fieldTypeGeneratorMethod,
        string $nextElementName,
        array $propertyValue,
        string $nextRuleName,
        string $propertyKey,
        bool $isArray
    ) : string {
        $elements = '';
        $index    = 0;

        if (false === $isArray) {
            // 비배열 요소 생성
            $parentId = static::getUniqueId();
            $elements .= static::addElement(
                $fieldTypeGeneratorMethod::write(
                    $nextElementName,
                    $propertyValue,
                    $nextData,
                    $nextRuleName,
                    $propertyKey
                ),
                $index,
                static::isValue($nextData),
                $parentId,
                $propertyValue
            );
        } else {
            // 배열 요소 생성
            foreach ($nextData as $aKey => $aValue) {
                ++$index;

                // 고유 ID 생성 로직
                $parentId = match (true) {
                    'only'        === $propertyValue['multiple'] => $aKey,
                    17            === \strlen((string) $aKey)    => $aKey,
                    'multichoice' === $propertyValue['type']     => '',
                    default                                      => static::getUniqueId()
                };

                $elements .= static::addElement(
                    $fieldTypeGeneratorMethod::write(
                        $nextElementName . '[' . $parentId . ']',
                        $propertyValue,
                        $aValue,
                        $nextRuleName . '[]',
                        $propertyKey
                    ),
                    $index,
                    static::isValue($aValue),
                    $parentId,
                    $propertyValue
                );
            }
        }

        return $elements;
    }

    // /**
    //  * 요소 데이터 처리 및 요소 생성 메서드.
    //  *
    //  * @param mixed  $nextData                 다음 데이터
    //  * @param string $fieldTypeGeneratorMethod 요소 생성 메서드
    //  * @param string $nextElementName          다음 요소 이름
    //  * @param array  $propertyValue            속성 값
    //  * @param string $nextRuleName             다음 규칙 이름
    //  * @param string $propertyKey              속성 키
    //  * @param bool   $isArray                  배열 여부
    //  *
    //  * @return string 생성된 요소들
    //  */
    // private static function processElementDataWithNextDataRef(
    //     &$nextData,
    //     string $fieldTypeGeneratorMethod,
    //     string $nextElementName,
    //     array $propertyValue,
    //     string $nextRuleName,
    //     string $propertyKey,
    //     bool $isArray
    // ) : string {
    //     $elements = '';
    //     $index    = 0;

    //     // 데이터가 없거나 null인 경우의 처리
    //     if (!static::isValue($nextData)) {
    //         if (false === $isArray) {
    //             // 비배열의 경우 기본값 또는 빈 값으로 요소 생성
    //             $nextData = $propertyValue['default'] ?? '';
    //             $parentId = static::getUniqueId();
    //             $elements .= static::addElement(
    //                 $fieldTypeGeneratorMethod::write(
    //                     $nextElementName,
    //                     $propertyValue,
    //                     $nextData,
    //                     $nextRuleName,
    //                     $propertyKey
    //                 ),
    //                 $index,
    //                 false, // 값이 없음을 명시
    //                 $parentId,
    //                 $propertyValue
    //             );
    //         } else {
    //             // 배열의 경우 최소한 하나의 요소 생성
    //             $parentId = static::getUniqueId();
    //             $nextData = isset($propertyValue['default'])
    //                 ? (\is_array($propertyValue['default'])
    //                     ? $propertyValue['default']
    //                     : [$propertyValue['default']])
    //                 : [$parentId => null];

    //             foreach ($nextData as $aKey => $aValue) {
    //                 ++$index;

    //                 // 고유 ID 생성 로직
    //                 $parentId = match (true) {
    //                     'only'        === $propertyValue['multiple'] => $aKey,
    //                     17            === \strlen((string) $aKey)    => $aKey,
    //                     'multichoice' === $propertyValue['type']     => '',
    //                     default                                      => static::getUniqueId()
    //                 };

    //                 $elements .= static::addElement(
    //                     $fieldTypeGeneratorMethod::write(
    //                         $nextElementName . '[' . $parentId . ']',
    //                         $propertyValue,
    //                         $aValue,
    //                         $nextRuleName . '[]',
    //                         $propertyKey
    //                     ),
    //                     $index,
    //                     false, // 값이 없음을 명시
    //                     $parentId,
    //                     $propertyValue
    //                 );
    //             }
    //         }
    //     } else {
    //         // 기존의 값이 있는 경우
    //         if (false === $isArray) {
    //             $parentId = static::getUniqueId();
    //             $elements .= static::addElement(
    //                 $fieldTypeGeneratorMethod::write(
    //                     $nextElementName,
    //                     $propertyValue,
    //                     $nextData,
    //                     $nextRuleName,
    //                     $propertyKey
    //                 ),
    //                 $index,
    //                 static::isValue($nextData),
    //                 $parentId,
    //                 $propertyValue
    //             );
    //         } else {
    //             foreach ($nextData as $aKey => $aValue) {
    //                 ++$index;

    //                 // 고유 ID 생성 로직
    //                 $parentId = match (true) {
    //                     'only'        === $propertyValue['multiple'] => $aKey,
    //                     17            === \strlen((string) $aKey)    => $aKey,
    //                     'multichoice' === $propertyValue['type']     => '',
    //                     default                                      => static::getUniqueId()
    //                 };

    //                 $elements .= static::addElement(
    //                     $fieldTypeGeneratorMethod::write(
    //                         $nextElementName . '[' . $parentId . ']',
    //                         $propertyValue,
    //                         $aValue,
    //                         $nextRuleName . '[]',
    //                         $propertyKey
    //                     ),
    //                     $index,
    //                     static::isValue($aValue),
    //                     $parentId,
    //                     $propertyValue
    //                 );
    //             }
    //         }
    //     }

    //     return $elements;
    // }

    private static function getLocalizedValue(array $property, string $key) : ?string
    {
        if (!isset($property[$key])) {
            return null;
        }

        // 배열이면 현재 언어의 값 반환, 아니면 그대로 반환
        return \is_array($property[$key])
            ? ($property[$key][static::getLanguage()] ?? null)
            : $property[$key];
    }

    public static function read(string $elementName, array $specs, $data)
    {
        // pr($elementName, $data);

        $innerhtml = '';

        foreach ($specs['properties'] ?? [] as $propertyKey => $propertyValue) {
            $fieldTypeGeneratorMethod = __NAMESPACE__ . '\\' . \Limepie\camelize($propertyValue['type']);
            $elements                 = '';
            $index                    = 0;

            $fixPropertyKey = $propertyKey;
            $isArray        = false;

            if (false !== \strpos((string) $fixPropertyKey, '[]')) {
                $fixPropertyKey = \str_replace('[]', '', $fixPropertyKey);
                $isArray        = true;
            }
            $nextElementName = $fixPropertyKey;

            if ($elementName) {
                $nextElementName = $elementName . '[' . $fixPropertyKey . ']';
            }
            $nextData = $data[$fixPropertyKey] ?? '';

            if ($nextData) {
                if (false === $isArray) { // 배열일때
                    if (false === isset($parentId)) {
                        $parentId = static::getUniqueId();
                    }
                    $elements .= static::readElement(
                        $fieldTypeGeneratorMethod::read($nextElementName, $propertyValue, $nextData),
                        $index
                    );
                } else {
                    foreach ($nextData as $aKey => $aValue) {
                        ++$index;

                        // if (false === isset($parentId)) {
                        $parentId = $aKey;
                        // }
                        $elements .= static::readElement(
                            $fieldTypeGeneratorMethod::read($nextElementName . '[' . $aKey . ']', $propertyValue, $aValue),
                            $index
                        );
                    }
                }
            } else {
                if (false === $isArray) {
                    $elements .= static::readElement(
                        $fieldTypeGeneratorMethod::read($nextElementName, $propertyValue, $nextData),
                        $index
                    );
                } else {
                    ++$index;

                    if (false === isset($parentId)) {
                        $parentId = static::getUniqueId();
                    }

                    $elements .= static::readElement(
                        $fieldTypeGeneratorMethod::read($nextElementName . '[' . $parentId . ']', $propertyValue, $nextData),
                        $index
                    );
                }
            }

            $language = $propertyValue['label'][static::getLanguage()] ?? $elementName;
            // $multipleHtml = true === isset($propertyValue['multiple']) ? static::getMultipleHtml($parentId) : '';
            $titleHtml = '<h6>' . $language . '</h6>';

            if ('hidden' === $propertyValue['type']) {
                $innerhtml .= <<<EOT
                {$elements}
                EOT;
            } else {
                $innerhtml .= <<<EOT
                {$titleHtml}<div class="form-group">{$elements}</div>
                EOT;
            }
            unset($parentId);
        }
        $groupStyle = '';

        if (true === isset($propertyValue['group_style'])) {
            $groupStyle = "style='" . $propertyValue['group_style'] . "'";
        }

        $html = <<<EOT
        <div class='form-group'  {$groupStyle}>{$innerhtml}</div>
        EOT;

        return $html;
    }
}
