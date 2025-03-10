<?php declare(strict_types=1);

namespace Limepie\Form;

use Limepie\ArrayObject;

class Generation
{
    public function __construct() {}

    public static function getValue($data, $key)
    {
        $keys  = \explode('.', $key);
        $value = $data;

        foreach ($keys as $id) {
            if (true === isset($value[$id])) {
                $value = $value[$id];

                continue;
            }

            return null;
        }

        return $value;
    }

    public static function getDefault($spec, $key)
    {
        $keys  = \explode('.', $key);
        $value = $spec;

        foreach ($keys as $id) {
            if (true === isset($value['properties'][$id])) {
                $value = $value['properties'][$id];

                continue;
            }

            return null;
        }

        return $value['default'] ?? null;
    }

    public function write(array $spec, array|ArrayObject $data = []) : string
    {
        $method = __NAMESPACE__ . '\Generation\Fields\\' . \Limepie\camelize($spec['type']);
        $html   = '';

        if ($data instanceof ArrayObject) {
            $data = $data->toArray();
        }

        Generation\Fields::$allData    = $data;
        Generation\Fields::$conditions = $spec['conditions'] ?? [];
        Generation\Fields::$specs      = $spec               ?? [];

        $reverseConditions = [];
        // foreach(Generation\Fields::$conditions as $key => $value ) {
        //     foreach($value as $k2 => $v2) {
        //         foreach($v2 as $k3 => $v3) {
        //             if(static::getValue($data, $key) == $k2 || static::getDefault($spec, $key) == $k2){
        //                 $reverseConditions[$k3] = $v3 ;
        //             }
        //         }
        //     }
        // }

        if (Generation\Fields::$conditions) {
            foreach (Generation\Fields::$conditions as $key => $value) {
                foreach ($value as $k2 => $v2) {
                    foreach ($v2 as $k3 => $v3) {
                        $reverseConditions[$k3][$key][$k2] = $v3;
                    }
                }
            }
        }
        // pr($reverseConditions);

        Generation\Fields::$reverseConditions = $reverseConditions;

        $label = '';

        if (true === isset($spec['label'])) {
            if (true === \is_array($spec['label'])) {
                if (true === isset($spec['label'][\Limepie\get_language()])) {
                    $label = $spec['label'][\Limepie\get_language()];
                } else {
                    $label = $spec['label'];
                }
            } else {
                $label = $spec['label'];
            }
        }

        $stepper = '';

        if (true === isset($spec['stepper'])) {
            if (true === \is_array($spec['stepper'])) {
                $i = 0;
                $c = \count($spec['stepper']);

                foreach (\array_reverse($spec['stepper']) as $step) {
                    ++$i;

                    if ($i == $c) {
                        $l = true;
                    } else {
                        $l = false;
                    }

                    // $active = $step['active'] ?? 0;
                    if (1 == $i) {
                        $active = 1;
                    } else {
                        $active = 0;
                    }

                    if (true === isset($step['label'])) {
                        if (true === \is_array($step['label'])) {
                            if (true === isset($step['label'][\Limepie\get_language()])) {
                                $label2 = $step['label'][\Limepie\get_language()];
                            } else {
                                $label2 = $step['label'];
                            }
                        } else {
                            $label2 = $step['label'];
                        }

                        if (true === isset($step['href'])) {
                            $label2 = '<a href="' . $step['href'] . '">' . $label2 . '</a>';
                        }
                        $stepper .= '<li ' . ($active ? 'class="active"' : '') . '><span>' . $label2 . '</span></li>';
                    } else {
                        $stepper .= '<li ' . ($active ? 'class="active"' : '') . '><span>' . $step . '</span></li>';
                    }
                }
            } else {
                if (true === \is_array($spec['stepper'])) {
                    if (true === isset($spec['stepper'][\Limepie\get_language()])) {
                        $label2 = $spec['stepper'][\Limepie\get_language()];
                    } else {
                        $label2 = $spec['stepper'];
                    }
                } else {
                    $label2 = $spec['stepper'];
                }

                if (true === isset($spec['href'])) {
                    $label2 = '<a href="' . $spec['href'] . '">' . $label2 . '</a>';
                }
                $stepper .= '<li class="active"><span>' . $label2 . '</span></li>';
            }
        }

        $html = '';

        // if ($stepper) {
        //     $html .= '<div class="stepper"><ul>' . $stepper . '</ul></div>';
        // }

        $title = '';

        if (true === isset($spec['title'])) {
            if (true === \is_array($spec['title'])) {
                if (true === isset($spec['title'][\Limepie\get_language()])) {
                    $title = $spec['title'][\Limepie\get_language()];
                }
            } else {
                $title = $spec['title'];
            }
        }

        if ($title) {
            $html .= '<h2 class="h6 font-weight-semi-bold">' . $title . '</h2>';
        }

        if ($label) {
            $html .= '<label class="form-label">' . $label . '</label>';
        }

        $description = '';

        if (true === isset($spec['description'])) {
            if (true === \is_array($spec['description'])) {
                if (true === isset($spec['description'][\Limepie\get_language()])) {
                    $description = $spec['description'][\Limepie\get_language()];
                }
            } else {
                $description = $spec['description'];
            }
        }

        if ($description) {
            $html .= '<div class="form-description">' . \nl2br($description) . '</div>';
        }

        if ($title || $label || $description) {
            $html .= '<hr />';
        }

        $elements = $method::write($spec['key'] ?? '', $spec, $data);

        $innerhtml = <<<EOT
        <div>
        {$html}
        {$elements}
        </div>
        EOT;

        $innerhtml .= '<hr /> <div class="clearfix">';

        if (true === isset($spec['buttons'])) {
            // {@button = form.spec.buttons}
            //     {?button.type == 'delete'}
            //         <a href='' data-method='delete' {?button.value??false} data-value="{=\Limepie\genRandomString(6)}"{/} class="btn {=button.class}">{=button.text}</a>
            //     {:}
            //         <button type='{=button.type}'{?button.name??false} name='{=button.name}'{/} class="btn {=button.class}"{?button.value??false} value="{=button.value}"{/}{?button.onclick??false} onclick="/*{=button.onclick}*/"{/}>{=button.text}</button>
            //     {/}
            // {/}
            $innerhtml .= $this->addButtons($spec['buttons']);
        } else {
            $submitButtonText = '저장';

            if ($spec['submit_button_text'] ?? false) {
                $submitButtonText = $spec['submit_button_text'];
            }

            $innerhtml .= '<input type="submit" value="' . $submitButtonText . '" class="btn btn-primary" />';

            if (isset($spec['add_buttons']) && $spec['add_buttons']) {
                $innerhtml .= $this->addButtons($spec['add_buttons']);
            }

            if (false === isset($spec['remove_list_button']) || !$spec['remove_list_button']) {
                $listButtonText = '목록';

                if ($spec['list_button_text'] ?? false) {
                    $listButtonText = $spec['list_button_text'];
                }
                $innerhtml .= '<a href="../" class="btn btn-secondary float-right">' . $listButtonText . '</a>';
            }
        }
        $innerhtml .= '</div>';

        return $innerhtml;
    }

    public function addButtons($buttons)
    {
        $innerhtml = '';
        $i         = 0;
        $count     = \count($buttons);

        foreach ($buttons ?? [] as $key => $button) {
            ++$i;
            $value       = '';
            $class       = '';
            $text        = '';
            $type        = '';
            $name        = '';
            $onclick     = '';
            $href        = '';
            $description = '';

            if ($button['onclick'] ?? false) {
                $onclick = 'onclick="' . $button['onclick'] . '"';
            }

            if ($button['name'] ?? false) {
                $name = 'name="' . $button['name'] . '"';
            }

            if ($button['type'] ?? false) {
                $type = $button['type'];
            }

            if (true === isset($button['text'][\Limepie\get_language()])) {
                $text = $button['text'][\Limepie\get_language()];
            } elseif (true === isset($button['text'])) {
                $text = $button['text'];
            }

            if ($button['class'] ?? false) {
                $class = $button['class'];
            }

            //    href: ../../?{=querystring}#additional

            if ($button['href'] ?? false) {
                $href = $button['href'];
                $flag = '';
                $qs   = '';

                if (false !== \strpos($href, '#')) {
                    [$href, $flag] = \explode('#', $href, 2);
                }

                if (false !== \strpos($href, '?')) {
                    [$href, $qs] = \explode('?', $href, 2);

                    if ($qs) {
                        $qs = '?' . $qs;
                    }
                } else {
                    $qs = $_SERVER['QSA'];
                }

                if ($qs) {
                    $href .= $qs;
                }

                if ($flag) {
                    $href .= '#' . $flag;
                }
            }

            if ($button['value'] ?? false) {
                $value = 'value="' . $button['value'] . '"';
            }

            if ($button['description'] ?? false) {
                if (true === isset($button['description'][\Limepie\get_language()])) {
                    $description = $button['description'][\Limepie\get_language()];
                } elseif (true === isset($button['description'])) {
                    $description = $button['description'];
                }

                $description = 'data-description="' . \htmlspecialchars($description) . '"';
            }

            if ('delete' === $button['type']) {
                if (true === isset($button['string'])) {
                    $string = $button['string'];
                } else {
                    $string = \Limepie\genRandomString(6);
                }

                $innerhtml .= '<a href="" data-method="delete" data-value="' . $string . '" ' . \str_replace('{=string}', $string, $description) . ' class="btn ' . $class . '">' . $text . '</a>';
            } elseif ('a' === $button['type']) {
                $innerhtml .= '<a href="' . $href . '" class="btn ' . $class . '">' . $text . '</a>';
            } elseif ('open' === $button['type']) {
                if (isset($button['name'])) {
                    $name = $button['name'];
                } else {
                    $name = 'PopupButton';
                }
                $onclick = 'window.open("' . $href . '", "' . $name . '", "width=500,height=600")';

                $innerhtml .= '<button type="' . $type . '" ' . $name . ' class="btn ' . $class . '" ' . $value . ' ' . $onclick . ' >' . $text . '</button>';
            } else {
                $innerhtml .= '<button type="' . $type . '" ' . $name . ' class="btn ' . $class . '" ' . $value . ' ' . $onclick . ' >' . $text . '</button>';
            }

            if ($i === $count) {
            }
            // $innerhtml .= ' ';
        }

        return $innerhtml;
    }

    public function read(array $spec, array $data = []) : string
    {
        $method = __NAMESPACE__ . '\Generation\Fields\\' . \Limepie\camelize($spec['type']);

        if (true === isset($spec['label'][\Limepie\get_language()])) {
            $title = $spec['label'][\Limepie\get_language()];
        } elseif (true === isset($spec['label'])) {
            $title = $spec['label'];
        } else {
            $title = 'Form';
        }

        $elements = $method::read($spec['key'] ?? '', $spec, $data);

        return <<<EOT
<div>
<label>{$title}</label>
{$elements}
</div>
EOT;
    }

    public function list(array $spec, array $data = []) : string
    {
        $method = __NAMESPACE__ . '\Generation\Fields\\' . \Limepie\camelize($spec['type']);

        return $method::list($spec['key'] ?? '', $spec, $data);
    }
}
