<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;
//https://stackoverflow.com/questions/36551105/applying-containment-to-jquery-ui-sortable-table-prevents-moving-tall-rows-to-th

class Group extends \Limepie\Form\Generation\Fields
{
    public static function write(string $prevKey, array $specs, $data)
    {
        $innerhtml = '';
        $script    = '';
        $html      = '';

        foreach ($specs['properties'] ?? [] as $propertyKey => $propertyValue) {
            if (false === isset($propertyValue['type'])) {
                throw new \Limepie\Exception('group ' . ($prevKey ? '"' . $prevKey . '" ' : '') . '"' . $propertyKey . '" type not found');
            }
            $method   = __NAMESPACE__ . '\\' . \Limepie\camelize($propertyValue['type']);
            $elements = '';

            $fixPropertyKey = $propertyKey;
            $isArray        = false;
            $strip          = false;

            if (false !== \strpos((string) $fixPropertyKey, '[]')) {
                //\pr($fixPropertyKey, $propertyValue['multiple'] ?? false);
                $fixPropertyKey = \str_replace('[]', '', $fixPropertyKey);
                $isArray        = true;
                $strip          = true;
            }
            $isArray = $propertyValue['multiple'] ?? false;

            $propertyName = $fixPropertyKey;

            if ($prevKey) {
                $propertyName = $prevKey . '[' . $fixPropertyKey . ']';
            }

            if (!$isArray && $strip) {
                $propertyName = $propertyName . '[]';
            }
            $dotKey = \str_replace(['[', ']'], ['.', ''], $propertyName);
            // pr(static::$reverseConditions, $dotKey);
            // pr( ?? '');

            $aData = '';

            if (true === \is_array($data) && $fixPropertyKey) {
                $aData = $data[$fixPropertyKey] ?? '';
            }

            $isMultiple = false;

            if (true === isset($propertyValue['multiple'])) {
                if (true === $propertyValue['multiple']) {
                    $isMultiple = true;
                }
            }

            $isSortableButton = true === isset($propertyValue['sortable_button']) ? true : false;
            $index      = 0;

            if (true === static::isValue($aData)) {
                if (false === $isArray) { // 배열이 아닐때
                    $parentId = static::getUniqueId();
                    $elements .= static::addElement(
                        $method::write($propertyName, $propertyValue, $aData),
                        $index,
                        $isMultiple,
                        $isSortableButton,
                        static::isValue($aData),
                        $parentId,
                        $propertyValue['multiple_button_onclick'] ?? ''
                    );
                } else {
                    foreach ($aData as $aKey => $aValue) {
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
                            $method::write($propertyName . '[' . $parentId . ']', $propertyValue, $aData[$aKey]),
                            $index,
                            $isMultiple,
                            $isSortableButton,
                            static::isValue($aData[$aKey]),
                            $parentId,
                            $propertyValue['multiple_button_onclick'] ?? ''
                        );
                    }
                }
            } else {
                //if (false === isset($parentId)) {
                $parentId = static::getUniqueId();
                //}

                if (false === $isArray) {
                    // TODO: default가 array면 error
                    $aData = $propertyValue['default'] ?? '';

                    $elements .= static::addElement(
                        $method::write($propertyName, $propertyValue, $aData),
                        $index,
                        $isMultiple,
                        $isSortableButton,
                        static::isValue($aData),
                        $parentId,
                        $propertyValue['multiple_button_onclick'] ?? ''
                    );
                } else {
                    if (true === isset($propertyValue['default'])) {
                        if (true === \is_array($propertyValue['default'])) {
                            $aData = $propertyValue['default'];
                        } else {
                            $aData = [$propertyValue['default']];
                        }
                    } else {
                        $aData = ['' => ''];
                    }

                    foreach ($aData as $aKey => $aValue) {
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
                            $method::write($propertyName . '[' . $parentId . ']', $propertyValue, $aData[$aKey]),
                            $index,
                            $isMultiple,
                            $isSortableButton,
                            static::isValue($aData[$aKey]),
                            $parentId,
                            $propertyValue['multiple_button_onclick'] ?? ''
                        );
                    }
                    /*
                        $index++;

                        $elements .= static::addElement(
                            $method::write($propertyName . '[' . $parentId . ']', $propertyValue, $aData),
                            $index,
                            $isMultiple,
                            $isSortableButton,
                            static::isValue($aData),
                            $parentId
                        );
                    */
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

            $collapse = '';

            if (true === isset($propertyValue['collapse'])) {
                if (true === \Limepie\is_boolean_type($propertyValue['collapse'])) {
                    $collapse = 'hide';
                } else {
                    if (false === (bool) static::getValueByDot($aData, $propertyValue['collapse'])) {
                        //\var_dump((bool) static::getValue($data, $propertyValue['collapse']));
                        $collapse = 'hide';
                    }
                }
            }

            $collapse1 = '';

            // if (true === isset($propertyValue['collapse'])) {
            //     $target = $propertyValue['collapse'];

            //     if ($prevKey) {
            //         $target = $prevKey . '[' . $target . ']';
            //     }
            //     $collapse1 = '<i class="button-collapse glyphicon glyphicon-triangle-right" data-target="' . $target . '"></i> ';
            // }

            if (true === isset($propertyValue['collapse'])) {
                $collapse1 = static::arrow(static::isValue2($aData));
            }

            $titleHtml = '';

            $collapse2 = '';

            if (true === isset($propertyValue['collapse'])) {
                $collapse2 = 'label-collapse';
            }

            $addClass2 = '';

            // if (true === isset($propertyValue['collapse'])) {
            //     if ($data[$propertyValue['collapse']] ?? '') {
            //     } else {
            //         $addClass2 = ' collapse-element collapse-hide';
            //     }
            // }
            if (true === isset($propertyValue['collapse'])) {
                if (\is_string($propertyValue['collapse'])) {
                    //\pr($aData, $propertyValue['collapse'], $aData, $propertyValue['collapse']);
                }
                //\pr($aData, $propertyValue['collapse'], $aData, $propertyValue['collapse'], \Limepie\is_boolean_type($propertyValue['collapse']), \is_int($propertyValue['collapse']), \is_bool($propertyValue['collapse']));
                //\var_dump(static::isValue($aData));
                if (
                    false === \Limepie\is_boolean_type($propertyValue['collapse'])
                    && false === (bool) static::getValueByDot($aData, $propertyValue['collapse'])
                ) {
                    $collapse1 = static::arrow(false);
                    $addClass2 = ' collapse-element collapse-hide';
                } elseif (false === static::isValue2($aData)) {
                    $collapse1 = static::arrow(false);
                    $addClass2 = ' collapse-element collapse-hide';
                }
            }

            $addClass = '';

            if (true === isset($propertyValue['class'])) {
                $addClass = ' ' . $propertyValue['class'];
            } else {
                $addClass = ' ';
            }

            $addStyle = '';

            if (true === isset($propertyValue['style'])) {
                $addStyle = ' ' . $propertyValue['style'] . ' ';
            } else {
                $addStyle = ' ';
            }

            // display_target: ../is_extra_people
            // display_target_condition:
            //   0: "display: none"
            //   1: "display: block"
            //   2: "display: block"

            if (true === isset($propertyValue['display_targets'])) {
                [$left, $right] = $propertyValue['display_targets'];

                $tmpids = \explode('.', $left);
                //\pr($tmpids, $data);
                $leftValue = &$data;
                $leftSpec  = &$specs;

                foreach ($tmpids as $tmpid) {
                    //\pr($leftValue, $tmpids, $tmpid);

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
                    //\pr($rightValue, $tmpids, $tmpid);

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

                foreach($propertyValue['display_targets_condition'] as $cond => $style) {
                    if($cond == 'eq') {
                        if($leftValue == $rightValue) {
                            $addStyle .= $style;
                        }
                    }
                }
                //pr($leftValue, $rightValue);
            }
            if (true === isset($propertyValue['display_target'])) {
                if (true === isset($data[$propertyValue['display_target']])) {
                    // 값이 있을때ㅐ
                    $targetValue = $data[$propertyValue['display_target']];

                    if (true === \is_object($targetValue)) {
                        if (true === \property_exists($targetValue, 'value')) {
                            $targetValue = $targetValue->value;
                        }
                    }

                    if (true === isset($propertyValue['display_target_condition'][$targetValue])) {
                        $addStyle .= $propertyValue['display_target_condition'][$targetValue];
                    }

                } elseif (true === isset($specs['properties'][$propertyValue['display_target']])) {
                    // 값이 없을때 default가 있는지 살펴봄
                    $targetSpec = $specs['properties'][$propertyValue['display_target']];

                    if (true === isset($targetSpec['default'])) {
                        $targetValue = $targetSpec['default'];

                        if (true === \is_object($targetValue)) {
                            if (true === \property_exists($targetValue, 'value')) {
                                $targetValue = $targetValue->value;
                            }
                        }

                        if (true === isset($propertyValue['display_target_condition'][$targetValue])) {
                            $addStyle .= $propertyValue['display_target_condition'][$targetValue];
                        }
                    }
                } else {
                    if (false !== \strpos($propertyValue['display_target'], '.')) {
                        $tmpids = \explode('.', $propertyValue['display_target']);
                        //\pr($tmpids, $data);
                        $targetValue = &$data;
                        $targetSpec  = &$specs;

                        foreach ($tmpids as $tmpid) {
                            //\pr($targetValue, $tmpids, $tmpid);

                            if (true === isset($targetValue[$tmpid])) {
                                $targetValue = &$targetValue[$tmpid];
                            }

                            if (true === isset($targetSpec['properties'][$tmpid])) {
                                $targetSpec = &$targetSpec['properties'][$tmpid];
                            }
                        }

                        //\pr($propertyKey, $tmpids, $targetValue, $targetSpec);

                        // if (true === \is_object($targetValue)) {
                        //     if (true === \property_exists($targetValue, 'value')) {
                        //         $targetValue = $targetValue->value;
                        //     }
                        // }

                        // if (true === isset($propertyValue['display_target_condition'][$targetValue])) {
                        //     $addStyle .= $propertyValue['display_target_condition'][$targetValue];
                        // }

                        if ($targetValue) {
                            if (true === \is_object($targetValue)) {
                                if (true === \property_exists($targetValue, 'value')) {
                                    $targetValue = $targetValue->value;
                                }
                            }

                            if (true === isset($propertyValue['display_target_condition'][$targetValue])) {
                                $addStyle .= $propertyValue['display_target_condition'][$targetValue];
                            }
                        } elseif (true === isset($targetSpec['default'])) {
                            $targetValue = $targetSpec['default'];

                            if (true === \is_object($targetValue)) {
                                if (true === \property_exists($targetValue, 'value')) {
                                    $targetValue = $targetValue->value;
                                }
                            }

                            if (true === isset($propertyValue['display_target_condition'][$targetValue])) {
                                $addStyle .= $propertyValue['display_target_condition'][$targetValue];
                            }
                        }
                    }
                }
            }

            if (true === isset($propertyValue['class_condition'])) {
                $conditions = $propertyValue['class_condition'];

                $conditionResult = false;

                foreach ($conditions['if'] as $conditionKey => $condition) {
                    if ('equeal' === $conditionKey) {
                    } elseif ('in' === $conditionKey) {
                        foreach ($condition as $ckey => $cvalue) {
                            $conditionValue = $data[$ckey] ?? '';
                            $tmp            = false;
                            \var_dump($conditionValue);

                            if (\in_array($conditionValue, \array_values($cvalue), $tmp)) {
                                $conditionResult = true;
                            } else {
                                $conditionResult = false;
                            }
                        }
                    }
                }

                if (false === $conditionResult) {
                    $addClass .= ' ' . $conditions['else'];
                } else {
                    $addClass .= ' ' . $conditions['then'];
                }
            }

//            $dotKey = str_replace(['[',']'],['.',''],$propertyName);

            $parts      = \explode('.', $dotKey);
            $dotParts   = [];
            $keyAsArray = [];

            foreach ($parts as $part) {
                if (1 === \preg_match('#__([^_]{13})__#', $part)) {
                    $keyAsArray[] = $part;
                    $dotParts[]   = '*';
                } else {
                    $dotParts[] = $part;
                }
            }
            $dotName = \implode('.', $dotParts);

            if (true === isset(static::$reverseConditions[$dotName])) {
                //console.log('aaaaatttt',$dotName, static::$reverseConditions[$dotName]);
                $condition = static::$reverseConditions[$dotName];

                foreach ($condition as $keyAs => $va1) {
                    $parts2      = \explode('.', $keyAs);
                    $dot2        = [];
                    $keyAsArray2 = $keyAsArray;

                    foreach ($parts2 as $part2) {
                        if ('*' === $part2) {
                            $keyAs3 = \array_shift($keyAsArray2);
                            $dot2[] = $keyAs3;
                        } else {
                            $dot2[] = $part2;
                        }
                    }
                    $keyAs2       = \implode('.', $dot2);
                    $valueResult1 = static::getValueByDot(static::$allData, $keyAs2);

                    if (null === $valueResult1) {
                        $valueResult1 = static::getDefaultByDot(static::$specs, $keyAs2);
                    }
                    // var_dump($valueResult1);
                    // pr($dotName, $keyAs2, $va1, $valueResult1);
                    if (true === isset($va1[$valueResult1])) {
                        if (false === ($va1[$valueResult1])) {
                            //return true;
                            $addClass .= ' d-none';
                        } else {
                            $addClass .= ' d-block';
                        }
                    } else {
                        //\pr($va1);

                        throw new \Exception(static::getNameByDot($keyAs) . ' value not found.');
                    }
                }
            }

            // if(true === isset(static::$reverseConditions[$dotKey])) {
            //     if(static::$reverseConditions[$dotKey]) {
            //         $addClass .= ' d-block';
            //     } else {
            //         $addClass .= ' d-none';
            //     }
            // }
            if ($title) {
                $titleHtml .= '<label class="' . $collapse2 . '">' . $collapse1 . $title . '</label>';
            }

            if ($description) {
                if (true === \is_array($description)) {
                    $title = '<div class="wrap-description">';
                    $title .= '<table class="table table-bordered description">';

                    foreach ($description as $dkey => $dvalue) {
                        $title .= '<tr><td>' . $dkey . '</td><td>' . $dvalue . '</td></tr>';
                    }
                    $title     .= '</table>';
                    $title     .= '</div>';
                    $titleHtml .= $title;
                } else {
                    $description = \preg_replace("#\\*(.*)\n#", '<span class="bold">*$1</span>' . \PHP_EOL, $description);
                    $titleHtml .= '<p class="description">' . \nl2br($description) . '</p>';
                }
            }

            if ('hidden' === $propertyValue['type']) {
                // {$titleHtml}

                $innerhtml .= <<<EOT
                <div class="x-hidden" name="{$dotKey}.layer">
                    {$elements}
                </div>
EOT;
            } elseif ('dummy' === $propertyValue['type'] && '' === $aData) {
                // {$titleHtml}

                $innerhtml .= <<<'EOT'
EOT;
            } elseif ('checkbox' === $propertyValue['type']) {
                $d = '';

                if ($description) {
                    $d = '<p class="description">' . \nl2br($description) . '</p>';
                }

                $innerhtml .= <<<EOT
                <div class="wrap-form-group{$addClass}" name="{$dotKey}.layer">
                    <div class="checkbox{$addClass2}">
                        <label>{$elements}</label>
                        {$d}
                    </div>
                </div>
EOT;
            } else {
                $sortableClass = '';

                if (true === isset($propertyValue['sortable'])) {
                    $sortableClass = 's' . \uniqid();
                }

                $innerhtml .= <<<EOT
                <div class="wrap-form-group{$addClass}" style="{$addStyle}" name="{$dotKey}.layer">
                    {$titleHtml}
                    <div class="form-group{$addClass2} {$sortableClass}">
                        {$elements}
                    </div>
                </div>
EOT;


                $sortableOnchange = '';
                if (true === isset($propertyValue['sortable_onchange'])) {
                    $sortableOnchange = $propertyValue['sortable_onchange'];
                }

                if ($sortableClass) {
                    $script .= <<<EOD
<script>
$(function() {
$(".{$sortableClass}").sortable({
    //opacity: 0.5,
    axis: 'y',
    containment: 'parent',
    helper: function(e, row) {
        row.children().each(function() {
            $(this).width($(this).width());
        });
        return row;
    },
    start: function (e, ui) {
        var sort = $(this).sortable('instance');
        ui.placeholder.height(ui.helper.height());
        sort.containment[3] += ui.helper.height()- sort.offset.click.top;;
        sort.containment[1] -= sort.offset.click.top;
    },
    update: function() {
        {$sortableOnchange}
    }
});
});
</script>
EOD;
                }
                unset($parentId);
            }
        }
        $fieldsetClass = ' ';

        if (true === isset($specs['fieldset_class'])) {
            $fieldsetClass = ' ' . $specs['fieldset_class'];
        }
        $fieldsetStyle = '';

        if (true === isset($specs['fieldset_style'])) {
            $fieldsetStyle = "style='" . $specs['fieldset_style'] . "'";
        }

        $html = <<<EOT
<div class='fieldset{$fieldsetClass} ' {$fieldsetStyle}>
{$innerhtml}
</div>
{$script}
EOT;

        return $html;
    }

    public static function read(string $prevKey, array $specs, $data)
    {
        //pr($prevKey, $data);

        $innerhtml = '';

        foreach ($specs['properties'] as $propertyKey => $propertyValue) {
            $method   = __NAMESPACE__ . '\\' . \Limepie\camelize($propertyValue['type']);
            $elements = '';
            $index    = 0;

            $fixPropertyKey = $propertyKey;
            $isArray        = false;

            if (false !== \strpos((string) $fixPropertyKey, '[]')) {
                $fixPropertyKey = \str_replace('[]', '', $fixPropertyKey);
                $isArray        = true;
            }
            $propertyName = $fixPropertyKey;

            if ($prevKey) {
                $propertyName = $prevKey . '[' . $fixPropertyKey . ']';
            }
            $aData = $data[$fixPropertyKey] ?? '';

            if ($aData) {
                if (false === $isArray) { // 배열일때
                    if (false === isset($parentId)) {
                        $parentId = static::getUniqueId();
                    }
                    $elements .= static::readElement(
                        $method::read($propertyName, $propertyValue, $aData),
                        $index
                    );
                } else {
                    foreach ($aData as $aKey => $aValue) {
                        ++$index;

                        //if (false === isset($parentId)) {
                        $parentId = $aKey;
                        //}
                        $elements .= static::readElement(
                            $method::read($propertyName . '[' . $aKey . ']', $propertyValue, $aData[$aKey]),
                            $index
                        );
                    }
                }
            } else {
                if (false === $isArray) {
                    $elements .= static::readElement(
                        $method::read($propertyName, $propertyValue, $aData),
                        $index
                    );
                } else {
                    ++$index;

                    if (false === isset($parentId)) {
                        $parentId = static::getUniqueId();
                    }

                    $elements .= static::readElement(
                        $method::read($propertyName . '[' . $parentId . ']', $propertyValue, $aData),
                        $index
                    );
                }
            }

            $language     = $propertyValue['label'][static::getLanguage()] ?? $prevKey;
            //$multipleHtml = true === isset($propertyValue['multiple']) ? static::getMultipleHtml($parentId) : '';
            $titleHtml    = '<label>' . $language . '</label>';

            if ('hidden' === $propertyValue['type']) {
                $innerhtml .= <<<EOT
                    {$elements}
EOT;
            } else {
                $innerhtml .= <<<EOT
                {$titleHtml}
                <div class="form-group">
                    {$elements}
                </div>
EOT;
            }
            unset($parentId);
        }
        $fieldsetStyle = '';

        if (true === isset($propertyValue['fieldset_style'])) {
            $fieldsetStyle = "style='" . $propertyValue['fieldset_style'] . "'";
        }

        $html = <<<EOT
<div class='fieldset'  {$fieldsetStyle}>
    {$innerhtml}
</div>

EOT;

        return $html;
    }
}
