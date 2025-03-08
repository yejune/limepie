<?php

declare(strict_types=1);

namespace Limepie\Form\Generator\Fields;

use Limepie\ArrayObject;
use Limepie\Exception;
use Limepie\Form\Generator\Fields;

// https://stackoverflow.com/questions/36551105/applying-containment-to-jquery-ui-sortable-table-prevents-moving-tall-rows-to-th

class Group extends Fields
{
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

    public static function getVVV($targetElementDotName, $nextDotName, $elementDotName)
    {
        if (false !== \strpos((string) $targetElementDotName, '*')) { // 참
            // a.b.*.c 정로도 넘어오기때문에 *를 현재의 13자리 문자로 바꿔줘야한다.
            // display_target: rooms.*.rooms.bed_type

            $targetElementDotName = parent::getFixedPath($nextDotName, $targetElementDotName);
        }

        if (0 === \strpos((string) $targetElementDotName, '......')) { // ..는 부모, 1단계 지원
            $targetElementDotName1 = \substr($elementDotName, 0, \strrpos($elementDotName, '.'));
            $targetElementDotName1 = \substr($targetElementDotName1, 0, \strrpos($targetElementDotName1, '.'));
            $targetElementDotName1 = \substr($targetElementDotName1, 0, \strrpos($targetElementDotName1, '.'));
            $targetElementDotName1 = \substr($targetElementDotName1, 0, \strrpos($targetElementDotName1, '.'));
            $targetElementDotName1 = \substr($targetElementDotName1, 0, \strrpos($targetElementDotName1, '.'));

            $targetElementDotName = $targetElementDotName1 . '.' . \trim($targetElementDotName, '.');
        } elseif (0 === \strpos((string) $targetElementDotName, '.....')) { // ..는 부모, 1단계 지원
            $targetElementDotName1 = \substr($elementDotName, 0, \strrpos($elementDotName, '.'));
            $targetElementDotName1 = \substr($targetElementDotName1, 0, \strrpos($targetElementDotName1, '.'));
            $targetElementDotName1 = \substr($targetElementDotName1, 0, \strrpos($targetElementDotName1, '.'));
            $targetElementDotName1 = \substr($targetElementDotName1, 0, \strrpos($targetElementDotName1, '.'));

            $targetElementDotName = $targetElementDotName1 . '.' . \trim($targetElementDotName, '.');
        } elseif (0 === \strpos((string) $targetElementDotName, '....')) { // ..는 부모, 1단계 지원
            $targetElementDotName1 = \substr($elementDotName, 0, \strrpos($elementDotName, '.'));
            $targetElementDotName1 = \substr($targetElementDotName1, 0, \strrpos($targetElementDotName1, '.'));
            $targetElementDotName1 = \substr($targetElementDotName1, 0, \strrpos($targetElementDotName1, '.'));

            $targetElementDotName = $targetElementDotName1 . '.' . \trim($targetElementDotName, '.');
        } elseif (0 === \strpos((string) $targetElementDotName, '...')) { // ..는 부모, 1단계 지원
            $targetElementDotName1 = \substr($elementDotName, 0, \strrpos($elementDotName, '.'));
            $targetElementDotName1 = \substr($targetElementDotName1, 0, \strrpos($targetElementDotName1, '.'));

            $targetElementDotName = $targetElementDotName1 . '.' . \trim($targetElementDotName, '.');
        } elseif (0 === \strpos((string) $targetElementDotName, '..')) { // ..는 부모, 1단계 지원
            $targetElementDotName = \substr($elementDotName, 0, \strrpos($elementDotName, '.', 1)) . '.' . \trim($targetElementDotName, '.');
        } elseif (0 === \strpos((string) $targetElementDotName, '.')) { // .var는 현재 위치.
            // display_target: .bed_type
            // \pr($elementDotName, $targetElementDotName);
            $targetElementDotName = $elementDotName . $targetElementDotName;
        }

        $targetValue = parent::getValueByDot(static::$allData, $targetElementDotName);

        if (true === \is_object($targetValue)) {
            if (true === \property_exists($targetValue, 'value')) {
                $targetValue = $targetValue->value;
            }
        }

        if (!\strlen((string) $targetValue)) {
            $targetSpec = static::getSpecByDot(static::$specs, $targetElementDotName);

            // if ('switcher' == $targetSpec['type']) {
            //     $targetValue = 0;
            // } else {
            // defaultValue by spec
            $targetValue = static::getDefaultByDot(static::$specs, static::$allData, $targetElementDotName);
            // }

            if (true === \is_object($targetValue)) {
                if (true === \property_exists($targetValue, 'value')) {
                    $targetValue = $targetValue->value;
                }
            }
        }

        return $targetValue;
    }

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
            ++$elementIndex;

            if (false === isset($propertyValue['type'])) {
                throw (new Exception('group ' . ($elementName ? '"' . $elementName . '" ' : '') . '"' . $propertyKey . '" type not found'))->setDebugMessage('error', __FILE__, __LINE__);
            }
            $method         = __NAMESPACE__ . '\\' . \Limepie\camelize($propertyValue['type']);
            $elements       = '';
            $fixPropertyKey = $propertyKey;
            $isArray        = false;
            $strip          = false;

            if (\preg_match('#(.*)\[([a-z\-\_]+)\]#', (string) $fixPropertyKey, $propertyMatch)) {
                throw new Exception('not support a[x] style');
            }

            if (false !== \strpos((string) $fixPropertyKey, '[]')) {
                $fixPropertyKey = \str_replace('[]', '', $fixPropertyKey);
                $isArray        = true; // 배열이다.
                $strip          = true; // []를 스트립했다.
            }
            $isArray = $propertyValue['multiple'] ?? false;

            if ($elementName) {
                $nextElementName = $elementName . '[' . $fixPropertyKey . ']';
            } else {
                $nextElementName = $fixPropertyKey;
            }

            if ($ruleName) {
                $nextRuleName = $ruleName . '[' . $fixPropertyKey . ']';
            } else {
                $nextRuleName = $fixPropertyKey;
            }

            if (!$isArray && $strip) { // a > b > c[] => a[b][c[]] -> a[b][c][] 로 보정
                $nextElementName = $nextElementName . '[]';
                $nextRuleName    = $nextRuleName . '[]';
            }

            $elementDotName = \str_replace(['[]', '[', ']'], ['.*', '.', ''], $elementName);
            $nextDotName    = \str_replace(['[]', '[', ']'], ['.*', '.', ''], $nextElementName);
            $nextLineName   = \str_replace(['[]', '[', ']'], ['-*', '-', ''], $nextElementName);
            $nextData       = '';

            // \pr($data, $fixPropertyKey);

            if (true === \is_array($data) && $fixPropertyKey) {
                $nextData = $data[$fixPropertyKey] ?? '';
            }
            // \pr($nextData, $fixPropertyKey);

            if (\is_object($nextData) && !($nextData instanceof ArrayObject)) {
                $propertyValue = [...$propertyValue, ...$nextData->property];
                $nextData      = $nextData->value;

                if (\Limepie\arr\is_assoc($nextData)) {
                    $isArray = false;
                }
            }

            // \pr($propertyValue, $nextData, $isArray);
            $index = 0;

            if (isset($propertyValue['form']) && false == $propertyValue['form']) {
                // form이 false이면 스팩에는 있지만 생성하지 않는다.
                //  \pr($propertyValue);

                continue;
            }

            if (true === static::isValue($nextData)) {
                if (false === $isArray) { // 배열이 아닐때
                    $parentId = static::getUniqueId();
                    $elements .= static::addElement(
                        $method::write(
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
                    foreach ($nextData as $aKey => $aValue) {
                        if ('only' === $propertyValue['multiple']) {
                            $parentId = $aKey;
                        } else {
                            ++$index;

                            // 배열 키는 바꾸면 안됨. 파일업로드 변경 여부 판별때문에
                            if (17 === \strlen((string) $aKey)) {
                                $parentId = $aKey;
                            } else {
                                if ('multichoice' === $propertyValue['type']) {
                                    $parentId = '';
                                } else {
                                    $parentId = static::getUniqueId();
                                }
                            }
                        }

                        $elements .= static::addElement(
                            $method::write(
                                $nextElementName . '[' . $parentId . ']',
                                $propertyValue,
                                $aValue,
                                $nextRuleName . '[]',
                                $propertyKey,
                            ),
                            $index,
                            static::isValue($aValue),
                            $parentId,
                            $propertyValue
                        );
                    }
                }
            } else {
                // if (false === isset($parentId)) {
                $parentId = static::getUniqueId();
                // }

                if (false === $isArray) {
                    // TODO: default가 array면 error

                    $nextData = $propertyValue['default'] ?? '';

                    $elements .= static::addElement(
                        $method::write(
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
                    if (true === isset($propertyValue['default'])) {
                        if (true === \is_array($propertyValue['default'])) {
                            $nextData = $propertyValue['default'];
                        } else {
                            $nextData = [$propertyValue['default']];
                        }
                    } else {
                        $nextData = [
                            $parentId => null,
                        ];
                    }

                    foreach ($nextData as $aKey => $aValue) {
                        if ('only' === $propertyValue['multiple']) {
                            $parentId = $aKey;
                        } else {
                            ++$index;

                            // 배열 키는 바꾸면 안됨. 파일업로드 변경 여부 판별때문에
                            if (17 === \strlen((string) $aKey)) {
                                $parentId = $aKey;
                            } else {
                                if ('multichoice' === $propertyValue['type']) {
                                    $parentId = '';
                                } else {
                                    $parentId = static::getUniqueId();
                                }
                            }
                        }

                        $elements .= static::addElement(
                            $method::write(
                                $nextElementName . '[' . $parentId . ']',
                                $propertyValue,
                                $aValue,
                                $nextRuleName . '[]',
                                $propertyKey,
                            ),
                            $index,
                            static::isValue($aValue),
                            $parentId,
                            $propertyValue,
                            $propertyKey
                        );
                    }
                }
            }

            $title = '';

            if (true === isset($propertyValue['label'])) {
                if (true === \is_array($propertyValue['label'])) {
                    if (true === isset($propertyValue['label'][static::getLanguage()])) {
                        $title = $propertyValue['label'][static::getLanguage()];
                    }
                } else {
                    $title = $propertyValue['label'];
                }
            }

            $description = '';

            if (true === isset($propertyValue['description'])) {
                if (true === \is_array($propertyValue['description'])) {
                    if (true === isset($propertyValue['description'][static::getLanguage()])) {
                        $description = $propertyValue['description'][static::getLanguage()];
                    }
                } else {
                    $description = $propertyValue['description'];
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

            // display_target: ../is_extra_people
            // display_target_condition_style:
            //   0: "display: none"
            //   1: "display: block"
            //   2: "display: block"

            $displayUnique = [];

            if (true === isset($propertyValue['display_targets'])) { // 2개의 타겟을 받아 서로 비교한다.
                [$left, $right] = $propertyValue['display_targets'];

                $tmpids = \explode('.', $left);
                // \pr($tmpids, $data);
                $leftValue = &$data;
                $leftSpec  = &$specs;

                foreach ($tmpids as $tmpid) {
                    // \pr($leftValue, $tmpids, $tmpid);

                    if (true === isset($leftValue[$tmpid])) {
                        $leftValue = &$leftValue[$tmpid];
                    }

                    if (true === isset($leftSpec['properties'][$tmpid])) {
                        $leftSpec = &$leftSpec['properties'][$tmpid];
                    }
                }

                $tmpids = \explode('.', $right);

                $rightValue = &$data;
                $rightSpec  = &$specs;

                foreach ($tmpids as $tmpid) {
                    // \pr($rightValue, $tmpids, $tmpid);

                    if (true === isset($rightValue[$tmpid])) {
                        $rightValue = &$rightValue[$tmpid];
                    }

                    if (true === isset($rightSpec['properties'][$tmpid])) {
                        $rightSpec = &$rightSpec['properties'][$tmpid];
                    }
                }

                if ($leftValue) {
                    if (true === \is_object($leftValue)) {
                        if (true === \property_exists($leftValue, 'value')) {
                            $leftValue = $leftValue->value;
                        }
                    }
                } elseif (true === isset($leftSpec['default'])) {
                    $leftValue = $leftSpec['default'];

                    if (true === \is_object($leftValue)) {
                        if (true === \property_exists($leftValue, 'value')) {
                            $leftValue = $leftValue->value;
                        }
                    }
                }

                if ($rightValue) {
                    if (true === \is_object($rightValue)) {
                        if (true === \property_exists($rightValue, 'value')) {
                            $rightValue = $rightValue->value;
                        }
                    }
                } elseif (true === isset($rightSpec['default'])) {
                    $rightValue = $rightSpec['default'];

                    if (true === \is_object($rightValue)) {
                        if (true === \property_exists($rightValue, 'value')) {
                            $rightValue = $rightValue->value;
                        }
                    }
                }

                foreach ($propertyValue['display_targets_condition_style'] as $cond => $style) {
                    $style = \trim($style, ';');

                    if ('eq' == $cond) {
                        if ($leftValue == $rightValue) {
                            $displayUnique[$style] ??= 0;
                            ++$displayUnique[$style];
                        }
                    } elseif ('lt' == $cond) {
                        if ($leftValue < $rightValue) {
                            $displayUnique[$style] ??= 0;
                            ++$displayUnique[$style];
                        }
                    } elseif ('gt' == $cond) {
                        if ($leftValue > $rightValue) {
                            $displayUnique[$style] ??= 0;
                            ++$displayUnique[$style];
                        }
                    }

                    // if (0 === \strpos($key, 'gt_')) {
                    //     $queryString = $leftCondition . ' > :' . $bindKeyname;
                    // } elseif (0 === \strpos($key, 'lt_')) {
                    //     $queryString = $leftCondition . ' < :' . $bindKeyname;
                    // } elseif (0 === \strpos($key, 'ge_')) {
                    //     $queryString = $leftCondition . ' >= :' . $bindKeyname;
                    // } elseif (0 === \strpos($key, 'le_')) {
                    //     $queryString = $leftCondition . ' <= :' . $bindKeyname;
                    // } elseif (0 === \strpos($key, 'eq_')) {
                    //     $queryString = $leftCondition . ' = :' . $bindKeyname;
                    // } elseif (0 === \strpos($key, 'ne_')) {
                }
                // pr($leftValue, $rightValue);
            }

            // display target 처리
            if (true === isset($propertyValue['display_target'])) { // 1개의 타겟을 받아 다른 대상과 비교한다.
                $targetElementDotName = $propertyValue['display_target'];

                if (false !== \strpos((string) $targetElementDotName, '*')) { // 참
                    // a.b.*.c 정로도 넘어오기때문에 *를 현재의 13자리 문자로 바꿔줘야한다.
                    // display_target: rooms.*.rooms.bed_type

                    $targetElementDotName = parent::getFixedPath($nextDotName, $targetElementDotName);
                }

                if (0 === \strpos((string) $targetElementDotName, '......')) { // ..는 부모, 1단계 지원
                    $targetElementDotName1 = \substr($elementDotName, 0, \strrpos($elementDotName, '.'));
                    $targetElementDotName1 = \substr($targetElementDotName1, 0, \strrpos($targetElementDotName1, '.'));
                    $targetElementDotName1 = \substr($targetElementDotName1, 0, \strrpos($targetElementDotName1, '.'));
                    $targetElementDotName1 = \substr($targetElementDotName1, 0, \strrpos($targetElementDotName1, '.'));
                    $targetElementDotName1 = \substr($targetElementDotName1, 0, \strrpos($targetElementDotName1, '.'));

                    $targetElementDotName = $targetElementDotName1 . '.' . \trim($targetElementDotName, '.');
                } elseif (0 === \strpos((string) $targetElementDotName, '.....')) { // ..는 부모, 1단계 지원
                    $targetElementDotName1 = \substr($elementDotName, 0, \strrpos($elementDotName, '.'));
                    $targetElementDotName1 = \substr($targetElementDotName1, 0, \strrpos($targetElementDotName1, '.'));
                    $targetElementDotName1 = \substr($targetElementDotName1, 0, \strrpos($targetElementDotName1, '.'));
                    $targetElementDotName1 = \substr($targetElementDotName1, 0, \strrpos($targetElementDotName1, '.'));

                    $targetElementDotName = $targetElementDotName1 . '.' . \trim($targetElementDotName, '.');
                } elseif (0 === \strpos((string) $targetElementDotName, '....')) { // ..는 부모, 1단계 지원
                    $targetElementDotName1 = \substr($elementDotName, 0, \strrpos($elementDotName, '.'));
                    $targetElementDotName1 = \substr($targetElementDotName1, 0, \strrpos($targetElementDotName1, '.'));
                    $targetElementDotName1 = \substr($targetElementDotName1, 0, \strrpos($targetElementDotName1, '.'));

                    $targetElementDotName = $targetElementDotName1 . '.' . \trim($targetElementDotName, '.');
                } elseif (0 === \strpos((string) $targetElementDotName, '...')) { // ..는 부모, 1단계 지원
                    $targetElementDotName1 = \substr($elementDotName, 0, \strrpos($elementDotName, '.'));
                    $targetElementDotName1 = \substr($targetElementDotName1, 0, \strrpos($targetElementDotName1, '.'));

                    $targetElementDotName = $targetElementDotName1 . '.' . \trim($targetElementDotName, '.');
                } elseif (0 === \strpos((string) $targetElementDotName, '..')) { // ..는 부모, 1단계 지원
                    // \print_r([$propertyKey, $elementName, $targetElementDotName, $elementDotName, \strrpos($elementDotName, '.', 1)]);

                    if (\strrpos($elementDotName, '.')) {
                        $targetElementDotName1 = \substr($elementDotName, 0, \strrpos($elementDotName, '.'));
                        $targetElementDotName  = $targetElementDotName1 . '.' . \trim($targetElementDotName, '.');
                    } else {
                        // $targetElementDotName = \ltrim($targetElementDotName, '.');
                        if ($elementDotName) {
                            $targetElementDotName = $elementDotName . '.' . \ltrim($targetElementDotName, '.');
                        } else {
                            // 없고 하나일때는 루트다
                            $targetElementDotName = \ltrim($targetElementDotName, '.');
                        }
                    }

                    //  \pr($elementName, $targetElementDotName);
                } elseif (0 === \strpos((string) $targetElementDotName, '.')) { // .var는 현재 위치.
                    // .이 하나일때는 현재 위치이므로 elementDotName가 존재한다면 붙여준다.

                    if (\strrpos($elementDotName, '.')) {
                        $targetElementDotName = $elementDotName . $targetElementDotName;
                    } else {
                        if ($elementDotName) {
                            $targetElementDotName = $elementDotName . '.' . \ltrim($targetElementDotName, '.');
                        } else {
                            // 없고 하나일때는 루트다
                            $targetElementDotName = \ltrim($targetElementDotName, '.');
                        }
                    }
                }

                // \prx($propertyValue['display_target'], $elementDotName, $targetElementDotName);

                // try {
                $targetValue = parent::getValueByDot(static::$allData, $targetElementDotName);
                // } catch (\Exception $e) {
                //     $targetValue = null;
                // }

                // \prx($targetElementDotName, $targetValue, $data, static::$allData);

                if (true === \is_object($targetValue)) {
                    if (true === \property_exists($targetValue, 'value')) {
                        $targetValue = $targetValue->value;
                    }
                }

                if (!\strlen((string) $targetValue)) {
                    // $targetSpec = static::getSpecByDot(static::$specs, $targetElementDotName);

                    // if ('switcher' == $targetSpec['type']) {
                    //     $targetValue = 0;
                    // } else {
                    // defaultValue by spec
                    try {
                        $targetValue = static::getDefaultByDot(static::$specs, static::$allData, $targetElementDotName);
                    } catch (\Exception $e) {
                        // \prx(static::$allData);
                        \prx($e);
                        $targetValue = null;
                    }
                    // }

                    if (true === \is_object($targetValue)) {
                        if (true === \property_exists($targetValue, 'value')) {
                            $targetValue = $targetValue->value;
                        }
                    }
                }

                if (\strlen((string) $targetValue)) {
                    if (true === isset($propertyValue['display_target_condition_style'][$targetValue])) {
                        $style = \trim($propertyValue['display_target_condition_style'][$targetValue], ';');

                        $displayUnique[$style] ??= 0;
                        ++$displayUnique[$style];
                    }

                    if (true === isset($propertyValue['display_target_condition_class'][$targetValue])) {
                        $addClass[] = \trim($propertyValue['display_target_condition_class'][$targetValue]);
                    }
                }
                // $addStyle[] = $targetValue;
            }

            if ($displayUnique) {
                if (1 == \count($displayUnique)) {
                    foreach ($displayUnique as $style => $flag) {
                        $addStyle[] = $style;

                        break;
                    }
                } else { // 여러개면 false
                    $addStyle[] = 'display: none;';
                }
                // \pr($nextElementName, $displayUnique, $addStyle, $propertyValue['class'] ?? '');
            }

            $testcode = <<<'EOL'
            element:
                all_of:
                    conditions:
                        is_extra_distance: 2
                        common.method: [0, 1]
                    inline: "display: none;"
                    class: ""
                    not:
                        inline: "display: block"
            EOL;

            if (true === isset($propertyValue['element'])) {
                if (true === isset($propertyValue['element']['all_of'])) {
                    $result = true;

                    foreach ($propertyValue['element']['all_of']['conditions'] as $elementKey => $value) {
                        $elementVVV = static::getVVV($elementKey, $nextDotName, $elementDotName);
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

            $addStyleString = '';

            if ($addStyle) {
                $addStyleString = \implode('; ', $addStyle);
            }

            $addClassString = '';

            if ($addClass) {
                $addClassString = \implode(' ', $addClass);
            }

            if ('switcher' === $propertyValue['type']) {
                $appendDescription  = $prependDescription;
                $prependDescription = '';
            }

            if ('hidden' === $propertyValue['type']) {
                $innerhtml .= <<<EOT
                <div class="x-hidden {$addClassString}" name="{$nextLineName}-layer">{$elements}</div>
                EOT;
            } elseif ('dummy' === $propertyValue['type'] && '' === $nextData) {
                $innerhtml .= <<<'EOT'

                EOT;
            } elseif ('checkbox' === $propertyValue['type']) {
                $d = '';

                if ($description) {
                    $d = '<p class="description">' . \nl2br($description) . '</p>';
                }

                $innerhtml .= <<<EOT
                <div class="{$addClassString}" name="{$nextLineName}-layer"><div class="checkbox"><h6>{$elements}</h6>{$d}</div></div>
                EOT;
            } else {
                $innerhtml .= <<<EOT
                <div class="{$addClassString}" style="{$addStyleString}" name="{$nextLineName}-layer">{$titleHtml}{$prependDescription}<div class="form-element">{$elements}</div>{$appendDescription}</div>
                EOT;
            }
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

    public static function read(string $elementName, array $specs, $data)
    {
        // pr($elementName, $data);

        $innerhtml = '';

        foreach ($specs['properties'] ?? [] as $propertyKey => $propertyValue) {
            $method   = __NAMESPACE__ . '\\' . \Limepie\camelize($propertyValue['type']);
            $elements = '';
            $index    = 0;

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
                        $method::read($nextElementName, $propertyValue, $nextData),
                        $index
                    );
                } else {
                    foreach ($nextData as $aKey => $aValue) {
                        ++$index;

                        // if (false === isset($parentId)) {
                        $parentId = $aKey;
                        // }
                        $elements .= static::readElement(
                            $method::read($nextElementName . '[' . $aKey . ']', $propertyValue, $aValue),
                            $index
                        );
                    }
                }
            } else {
                if (false === $isArray) {
                    $elements .= static::readElement(
                        $method::read($nextElementName, $propertyValue, $nextData),
                        $index
                    );
                } else {
                    ++$index;

                    if (false === isset($parentId)) {
                        $parentId = static::getUniqueId();
                    }

                    $elements .= static::readElement(
                        $method::read($nextElementName . '[' . $parentId . ']', $propertyValue, $nextData),
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
