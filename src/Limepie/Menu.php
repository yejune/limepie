<?php declare(strict_types=1);

namespace Limepie;

class Menu
{
    public $sequence = 0;

    public $menu = [];

    public $url = '';

    public $active = 0;

    public $finalize = false;

    public $prefix = '';

    public $start = 0;

    public $fullpath;

    public $query;

    public function __construct($url, $prefix = '')
    {
        // $url          = \rtrim($url, '/') ;
        // $this->url    = $url;
        $this->prefix = \rtrim($prefix, '/');

        // url에 ?가 있을 경우 querystring까지 확인한다.
        if (false !== \strpos($url, '?')) {
            $query       = \Limepie\Di::getRequest()->getQueryString();
            $this->query = '?' . \trim($query, '/');
        }
        $this->fullpath = \rtrim(\Limepie\Di::getRequest()->getPathByUrl(0), '/') . $this->query;
    }

    public function __invoke($name, $link = '', $parent = 0)
    {
        return $this->add($name, $link, $parent);
    }

    public function add($name, $url, int $parent = 0, $params = [])
    {
        $url    = \rtrim($url, '/');
        $newurl = $this->prefix . $url;
        ++$this->sequence;

        $this->menu[$this->sequence] = [
            'parent' => $parent,
            'seq'    => $this->sequence,
            'name'   => $name,
            'url'    => $newurl,
            'active' => false,
            'params' => $params,
        ];

        $target = \rtrim(\Limepie\Di::getRequest()->getPathByUrl(0, \substr_count($this->prefix . $url, '/')), '/') . $this->query;

        if ($this->prefix . $url) {
            if ($this->prefix . $url === $target) {
                if (false === $this->finalize || $this->prefix . $url === $this->fullpath) {
                    $this->active = $this->sequence;
                }
            }

            if (false === $this->finalize) {
                $this->finalize = $this->prefix . $url === $this->fullpath;
            }
        } else {
            if (!$this->active) {
                $this->active   = $this->sequence;
                $this->finalize = false;
            }
        }

        return $this->sequence;
    }

    public function addSeq($name, int $sequence, $url, int $parent = 0, $params = [])
    {
        $url    = \rtrim($url, '/');
        $newurl = $this->prefix . $url;

        $this->menu[$sequence] = [
            'parent' => $parent,
            'seq'    => $sequence,
            'name'   => $name,
            'url'    => $newurl,
            'active' => false,
            'params' => $params,
        ];

        $target = \rtrim(\Limepie\Di::getRequest()->getPathByUrl(0, \substr_count($this->prefix . $url, '/')), '/') . $this->query;

        if ($this->prefix . $url) {
            if ($this->prefix . $url === $target) {
                if (false === $this->finalize || $this->prefix . $url === $this->fullpath) {
                    $this->active = $sequence;
                }
            }

            if (false === $this->finalize) {
                $this->finalize = $this->prefix . $url === $this->fullpath;
            }
        } else {
            if (!$this->active) {
                $this->active   = $sequence;
                $this->finalize = false;
            }
        }

        return $sequence;
    }

    public function getActives()
    {
        $active  = $this->active;
        $parents = [];

        if ($this->start < $active) {
            $parents = [$active];
        }

        while (1) {
            if (true === isset($this->menu[$active]) && $this->start < $this->menu[$active]['parent']) {
                $item = $this->menu[$active];

                if (true === isset($this->menu[$item['parent']])) {
                    $parent = $this->menu[$item['parent']];
                    \array_push($parents, $item['parent']);
                }
                $active = $parent['seq'] ?? 0;
            } else {
                break;
            }
        }

        return $parents;
    }

    public function getActiveNames()
    {
        $active   = $this->active;
        $location = [];

        while (1) {
            if (true === isset($this->menu[$active]) && $this->start < $this->menu[$active]['parent']) {
                $item = $this->menu[$active];

                if (true === isset($this->menu[$item['parent']])) {
                    $parent = $this->menu[$item['parent']];
                    \array_unshift($location, $item);
                }
                $active = $parent['seq'] ?? 0;
            } else {
                break;
            }
        }

        if ($active) {
            \array_unshift($location, $this->menu[$active]);
        }

        return $location;
    }

    public function get($parent = 0)
    {
        $this->start = $parent;

        foreach ($this->getActives() as $seq) {
            $this->menu[$seq]['active'] = 1;
        }

        return $this->parseTree($this->menu, $parent);
    }

    public function relation($items, $parent = 0)
    {
        $childs = [];

        foreach ($items as &$item) {
            $childs[$item['parent']][] = &$item;
        }
        unset($item);

        foreach ($items as &$item) {
            $item['children'] = $childs[$item['seq']] ?? [];
        }

        return \current($childs);
    }

    public function parseTree($tree, $parent = 0, $depth = 0)
    {
        ++$depth;
        $return = [];
        // Traverse the tree and search for direct children of the parent
        foreach ($tree as $child => $value) {
            // A direct child is found
            if ($value['parent'] === $parent) {
                // Remove item from tree (we don't need to traverse this again)
                unset($tree[$child]);
                // Append the child into result array and parse its children

                $item = [
                    'seq'    => $value['seq'],
                    'name'   => $value['name'],
                    'url'    => $value['url'],
                    'active' => $value['active'],
                    'params' => $value['params'],
                ];
                $item['children'] = $this->parseTree($tree, $child, $depth);
                // $item['depth']    = $depth;
                $return[$value['url']] = $item;
            }
        }

        return empty($return) ? null : $return;
    }

    public function getIterator()
    {
        foreach ($this->getActives() as $seq) {
            $this->menu[$seq]['active'] = 1;
        }

        return new \RecursiveIteratorIterator(
            new \Limepie\RecursiveIterator\AdjacencyList($this->menu),
            \RecursiveIteratorIterator::SELF_FIRST
        );
    }

    public function flatten($source_arr, &$target_arr, $depth = 0, $endInfo = [])
    {
        $i = 0;
        $s = \count($source_arr);

        \array_unshift($endInfo, $depth);
        $currentEndInfo = [];

        foreach ($source_arr as $k => $v) {
            $target_arr[$k]            = $v;
            $target_arr[$k]['depth']   = $depth;
            $target_arr[$k]['isFirst'] = (0 === $i);
            $children                  = $target_arr[$k]['children'];
            unset($target_arr[$k]['children']);

            if (++$i === $s) {
                $currentEndInfo = $endInfo;
            }

            if (empty($children)) {
                $target_arr[$k]['endInfo'] = $currentEndInfo;
            } else {
                $target_arr[$k]['hasChild'] = true; // 추가된 부분
                $this->flatten($children, $target_arr, $depth + 1, $currentEndInfo);
            }
        }
    }
}

class UlRecursiveIteratorIterator extends \RecursiveIteratorIterator
{
    public function beginIteration() : void
    {
        echo '<ul>', \PHP_EOL;
    }

    public function endIteration() : void
    {
        echo '</ul>', \PHP_EOL;
    }

    public function beginChildren() : void
    {
        echo \str_repeat("\t", $this->getDepth()), '<ul>', \PHP_EOL;
    }

    public function endChildren() : void
    {
        echo \str_repeat("\t", $this->getDepth()), '</ul>', \PHP_EOL;
        echo \str_repeat("\t", $this->getDepth()), '</li>', \PHP_EOL;
    }
}

class TabRecursiveIteratorIterator extends \RecursiveIteratorIterator
{
    public function beginIteration() : void
    {
        // echo '<ul>', \PHP_EOL;
    }

    public function endIteration() : void
    {
        // echo '</ul>', \PHP_EOL;
    }

    public function beginChildren() : void
    {
        // echo \str_repeat("\t", $this->getDepth()), '<ul>', \PHP_EOL;
    }

    public function endChildren() : void
    {
        // echo \str_repeat("\t", $this->getDepth()), '</ul>', \PHP_EOL;
        // echo \str_repeat("\t", $this->getDepth()), '</li>', \PHP_EOL;
    }
}
