<?php

declare(strict_types=1);

namespace Limepie\Template;

use Limepie\Exception;

class Compiler
{
    private $debug = true;

    /**
     * @var array
     */
    private $brace = [];

    /**
     * @var string
     */
    private $loopkey = 'A';

    /**
     * @var int
     */
    private $permission = 0777;

    /**
     * @var bool
     */
    private $phpengine = false;

    private $filename;

    private $basepath;

    private $tpl_path;

    private $obj_plugins_flip;

    private $func_plugins_flip;

    private $postfilters_flip;

    private $prefilters_flip;

    private $prefilter;

    private $postfilter;

    private $prefilters;

    private $postfilters;

    private $plugin_dir;

    private $plugins;

    private $func_plugins;

    private $obj_plugins;

    private $func_list;

    private $obj_list;

    private $method_list;

    private $on_ms;

    private $pluginExtension;

    private $functions = [];

    public function __construct()
    {
        $functions       = \get_defined_functions();
        $this->functions = \array_merge(
            $functions['internal'],
            $functions['user'],
            ['isset', 'empty', 'eval', 'list', 'array', 'include', 'require', 'include_once', 'require_once']
        );
    }

    /**
     * @param mixed $tpl
     * @param mixed $fid
     * @param mixed $tplPath
     * @param mixed $cplPath
     * @param mixed $cplHead
     *
     * @return mixed
     */
    public function execute($tpl, $fid, $tplPath, $cplPath, $cplHead)
    {
        $this->permission = $tpl->permission;
        $this->phpengine  = $tpl->phpengine;
        $this->debug      = $tpl->debug;

        $this->filename        = $tplPath;
        $this->basepath        = \dirname($tplPath);
        $this->tpl_path        = $tplPath;
        $this->prefilter       = $tpl->prefilter;
        $this->postfilter      = $tpl->postfilter;
        $this->prefilters      = [];
        $this->postfilters     = [];
        $this->plugin_dir      = $tpl->plugin_dir;
        $this->plugins         = [];
        $this->func_plugins    = [];
        $this->obj_plugins     = [];
        $this->func_list       = ['' => []];
        $this->obj_list        = ['' => []];
        $this->method_list     = [];
        $this->on_ms           = '/' !== \substr(__FILE__, 0, 1);
        $this->pluginExtension = $tpl->pluginExtension;

        if (false === \is_file($cplPath)) {
            $dirs = \explode('/', $cplPath);
            $path = '';

            for ($i = 0, $s = \count($dirs) - 1; $i < $s; ++$i) {
                $path .= $dirs[$i] . '/';

                if (false === \is_dir($path)) {
                    if (false === \mkdir($path)) {
                        throw new Compiler\Exception('cannot create compile directory <b>' . $path . '</b>');
                    }

                    \chmod($path, $this->permission);
                }
            }
        }

        // get plugin file info
        $plugins = [];
        $match   = [];

        if ($this->plugin_dir) {
            $d = \dir($this->plugin_dir);

            if (false === $d) {
                throw new Compiler\Exception('cannot access plugin directory ' . $this->plugin_dir . '');
            }

            while ($plugin_file = $d->read()) {
                $plugin_path = $this->plugin_dir . '/' . $plugin_file;

                if (!\is_file($plugin_path) || !\preg_match('/^(object|function|prefilter|postfilter)\.([^.]+)\.' . $this->pluginExtension . '$/i', $plugin_file, $match)) {
                    continue;
                }
                $plugin = \strtolower($match[2]);

                if ('object' === $match[1]) {
                    if (true === \in_array($plugin, $this->obj_plugins, true)) {
                        throw new Compiler\Exception('plugin file object.' . $match[2] . '.php is overlapped');
                    }
                    $this->obj_plugins[$match[2]] = $plugin;
                } else {
                    switch ($match[1]) {
                        case 'function':
                            $this->func_plugins[$match[2]] = $plugin;

                            break;
                        case 'prefilter':
                            $this->prefilters[$match[2]] = $plugin;

                            break;
                        case 'postfilter':
                            $this->postfilters[$match[2]] = $plugin;

                            break;
                    }

                    if (true === \in_array($plugin, $plugins, true)) {
                        throw new Compiler\Exception('plugin function ' . $plugin . ' is overlapped');
                    }
                    $plugins[] = $plugin;
                }
            }
        }
        $this->obj_plugins_flip  = \array_flip($this->obj_plugins);
        $this->func_plugins_flip = \array_flip($this->func_plugins);
        $this->prefilters_flip   = \array_flip($this->prefilters);
        $this->postfilters_flip  = \array_flip($this->postfilters);

        // get template
        $source = '';

        if ($sourceSize = \filesize($tplPath)) {
            $fpTpl  = \fopen($tplPath, 'rb');
            $source = \fread($fpTpl, $sourceSize);
            \fclose($fpTpl);
        }

        if (\trim((string) $this->prefilter)) {
            $source = $this->filter($source, 'pre');
        }

        $verLow54 = \defined('PHP_MAJOR_VERSION') and 5.4 <= (float) (\PHP_MAJOR_VERSION . '.' . \PHP_MINOR_VERSION);
        $phpTag   = '<\?php|(?<!`)\?>';

        if (\ini_get('short_open_tag')) {
            $phpTag .= '|<\?(?!`)';
        } elseif ($verLow54) {
            $phpTag .= '|<\?=';
        }

        if (\ini_get('asp_tags')) {
            $phpTag .= '|<%(?!`)|(?<!`)%>';
        }

        //        $phpTag .= '|';

        $tokens = \preg_split('/(' . \implode('|', [
            $phpTag,
            '\$\{(?!`)',
            '<!--{(?!`)',
            '\{\*\*',
            '\{\*',
            '{{!--',
            '"{{',
            '`{',
            '\'{{',
            '\/\*{(?!`)',
            '(?<!`)}-->',
            '(?<!`)}\*\/',
            '}}"',
            '}`;',
            '}`',
            '\*\*\}',
            '\*\}',
            '--}}',
            '}}\'',
            '(?:\.\.\.\.)*{',
            '{(?!`)',
            '(?<!`)})',
        ]) . '/i', $source, -1, \PREG_SPLIT_DELIM_CAPTURE);

        $line      = 0;
        $isOpen    = 0;
        $newTokens = [];

        for ($_index = 0, $s = \count($tokens); $_index < $s; ++$_index) {
            $line = \substr_count(\implode('', $newTokens), \chr(10)) + 1;

            $newTokens[$_index] = $tokens[$_index];

            switch (\strtolower($tokens[$_index])) {
                case '${': // pass
                    $newTokens[$_index] = $tokens[$_index];

                    break;
                case '<?php':
                case '<?=':
                case '<?':
                case '<%':
                    if (false === $this->phpengine) {
                        $newTokens[$_index] = \str_replace('<', '&lt;', $tokens[$_index]);
                    } else {
                        $newTokens[$_index] = $tokens[$_index];
                    }

                    break;
                case '?>':
                case '%>':
                    if (false === $this->phpengine) {
                        $newTokens[$_index] = \str_replace('>', '&gt', $tokens[$_index]);
                    } else {
                        $newTokens[$_index] = $tokens[$_index];
                    }

                    break;
                case '{*':
                case '{**':
                case '{{!--':
                    $newTokens[$_index] = '<?php /*';

                    break;
                case '<!--{':
                case '/*{':
                case '"{{':
                case '`{':
                case "'{{":
                case '{':
                    $isOpen = $_index;

                    break;
                case '**}':
                case '*}':
                case '--}}':
                    $newTokens[$_index] = '*/ ?>';

                    break;
                case '}-->':
                case '}*/':
                case '}}"':
                case "}}'":
                case '}`;':
                case '}`':
                case '}':
                    if ($_index - 2 !== $isOpen) {
                        break; // switch exit
                    }

                    $result = $this->compileStatement($tokens[$_index - 1], $line);

                    if ($result) {
                        if (1 === $result[0] || false === $result[1]) {
                            // \로 이스케이프 하는 등 원본에서 가공된 스트링을 돌려주기 위함
                            $newTokens[$_index - 1] = $result[1]; // $tokens[$_index - 1];
                        } elseif (2 === $result[0]) {
                            $newTokens[$isOpen]     = '<?php ';
                            $newTokens[$_index - 1] = $result[1];
                            $newTokens[$_index]     = '?>';
                        }

                        $isOpen = 0;
                    }

                    break;

                default:
                    if (\preg_match('/(\.\.\.\.)*\{/', $tokens[$_index])) {
                        $isOpen = $_index;
                    }
            }
        }

        if (0 < \count($this->brace)) {
            \array_pop($this->brace);
            $c = \end($this->brace);

            throw new Compiler\Exception($this->filename . ' not close brace, error line ' . $c[1]);
        }

        $source = \implode('', $newTokens);
        $this->saveResult($cplPath . '.original.php', $source, $cplHead, '*/ ?>');

        if ($this->postfilter) {
            $source = $this->filter($source, 'post');
        }

        $this->saveResult($cplPath, $source, $cplHead, '*/ ?>');
    }

    /**
     * @param mixed $statement
     * @param mixed $line
     * @param mixed $add
     *
     * @return mixed
     */
    public function compileStatement($statement, $line, $add = ';')
    {
        $org       = $statement;
        $statement = \trim($statement);

        $match = [];
        \preg_match('/^(\\\*)\s*(:\?|\?#|:\?#|\/@|\/\?|[\|\-=#@?:\/+\*])?(.*)$/s', $statement, $match);

        if ($match[1]) {
            // escape
            $result = [1, \substr($org, 1)];
            // pr($match, $result);
        } else {
            switch ($match[2]) {
                case '+':
                    $result = [2, $this->compileInclude($statement, $line)];

                    break;
                case '@':
                    $this->brace[] = ['if', $line];
                    $this->brace[] = ['loop', $line];
                    $result        = [2, $this->compileLoop($statement, $line)];

                    break;
                case '?#':
                    $this->brace[] = ['if', $line];
                    $this->brace[] = ['if', $line];

                    if (1 === \preg_match('`^\?#([\s+])?([a-zA-Z0-9\-_\.]+)$`', $statement)) {
                        $result = [2, $this->compileIfDefine($statement, $line)];
                    } else {
                        $result = [1, $statement];
                    }

                    break;
                case '#':
                    // scope a b:c => $a, $b = $c;

                    // if (1 === \preg_match('`^#\s?(?P<filename>[^ ]+\.tpl)(\s+(?P<scope>.*))?$`', $statement, $tmp)) {
                    //     // #a.tpl scope variable
                    //     $result = [2, $this->compileInDefine('#*', $this->basepath . '/' . $tmp['filename'], $line, $tmp['scope'] ?? '', 333)];
                    // } elseif (1 === \preg_match('`^#([\s+])?(?P<define>[a-zA-Z0-9\-_\.]+)(([\s+])scope([\s+])(?P<scope>.*))?$`', $statement, $tmp)) {
                    //     // #define_id scope variable
                    //     $result = [2, $this->compileDefine('#' . $tmp['define'], $line, $tmp['scope'] ?? '')];
                    // } elseif (1 === \preg_match('`^#([\s+])?(?P<define>[a-zA-Z0-9\-_\.]+)([\s+])(?:\'|")(?P<filename>[^ ]+)(?:\'|")(([\s+])scope([\s+])(?P<scope>.*))?$`', $statement, $tmp)) {
                    //     // #define_id "a.tpl" scope variable
                    //     $result = [2, $this->compileInDefine('#' . $tmp['define'], $this->basepath . '/' . $tmp['filename'], $line, $tmp['scope'] ?? '', 444)];
                    // } elseif (1 === \preg_match('`^#([\s+])?(?P<define>[a-zA-Z0-9\-_\.]+)([\s+])(?P<filename>[^ ]+)(([\s+])scope([\s+])(?P<scope>.*))?$`', $statement, $tmp)) {
                    //     // #define_id a.tpl scope variable
                    //     $result = [2, $this->compileInDefine('#' . $tmp['define'], $this->basepath . '/' . $tmp['filename'], $line, $tmp['scope'] ?? '', 555)];
                    // } elseif (1 === \preg_match('`^#([\s+])?(?:\'|")(?P<filename>[^"\']+)(?:\'|")(([\s+])scope([\s+])(?P<scope>.*))?$`', $statement, $tmp)) {
                    //     // #"a.tpl" scope variable
                    //     $result = [2, $this->compileInDefine('#*', $this->basepath . '/' . $tmp['filename'], $line, $tmp['scope'] ?? '', 111)];
                    // } elseif (1 === \preg_match('`^#([\s+])?(?P<filename>[^ ]+)(([\s+])scope([\s+])(?P<scope>.*))?$`', $statement, $tmp)) {
                    //     // #a.tpl scope variable
                    //     $result = [2, $this->compileInDefine('#*', $this->basepath . '/' . $tmp['filename'], $line, $tmp['scope'] ?? '', 222)];
                    // } else {
                    //     $result = [1, $statement];
                    // }

                    // 공통 패턴 정의
                    $SCOPE = '(?:\s+scope\s+(?P<scope>.*))?';  // scope와 변수들
                    $ID    = '[a-zA-Z0-9\-_\.]+';                 // 식별자
                    $FILE  = '[^ ]+';                           // 파일명

                    if (1 === \preg_match("`^#\\s*(?P<filename>{$FILE}\\.tpl){$SCOPE}$`", $statement, $tmp)) {
                        // echo 1;
                        // #a.tpl scope variable
                        $result = [2, $this->compileInDefine('#*', $this->basepath . '/' . $tmp['filename'], $line, $tmp['scope'] ?? '', 333)];
                    } elseif (1 === \preg_match("`^#\\s*(?P<define>{$ID}){$SCOPE}$`", $statement, $tmp)) {
                        // echo 2;
                        // #define_id scope variable
                        $result = [2, $this->compileDefine('#' . $tmp['define'], $line, $tmp['scope'] ?? '')];
                    } elseif (1 === \preg_match("`^#\\s*(?P<define>{$ID})\\s+['\"](?P<filename>{$FILE})['\"]{$SCOPE}$`", $statement, $tmp)) {
                        // echo 3;
                        // #define_id "a.tpl" scope variable
                        $result = [2, $this->compileInDefine('#' . $tmp['define'], $this->basepath . '/' . $tmp['filename'], $line, $tmp['scope'] ?? '', 444)];
                    } elseif (1 === \preg_match("`^#\\s*(?P<define>{$ID})\\s+(?P<filename>{$FILE}){$SCOPE}$`", $statement, $tmp)) {
                        // echo 4;
                        // #define_id a.tpl scope variable
                        $result = [2, $this->compileInDefine('#' . $tmp['define'], $this->basepath . '/' . $tmp['filename'], $line, $tmp['scope'] ?? '', 555)];
                    } elseif (1 === \preg_match("`^#\\s*['\"](?P<filename>{$FILE})['\"]{$SCOPE}$`", $statement, $tmp)) {
                        // echo 5;
                        // #"a.tpl" scope variable
                        $result = [2, $this->compileInDefine('#*', $this->basepath . '/' . $tmp['filename'], $line, $tmp['scope'] ?? '', 111)];
                    } elseif (1 === \preg_match("`^#\\s*(?P<filename>{$FILE}){$SCOPE}$`", $statement, $tmp)) {
                        // echo 6;
                        // #a.tpl scope variable
                        $result = [2, $this->compileInDefine('#*', $this->basepath . '/' . $tmp['filename'], $line, $tmp['scope'] ?? '', 222)];
                    } else {
                        // echo 7;
                        $result = [1, $statement];
                    }

                    break;
                case '*':
                    $result = [2, '/*' . $statement . '*/'];

                    break;
                case ':':
                    if (!\count($this->brace)) {
                        throw new Compiler\Exception('error line ' . $line);
                    }

                    $result = [2, $this->compileElse($statement, $line)];

                    break;
                case '-':
                case '|':
                case '/':
                    if (0 === \strpos($match[3], $match[2])) {
                        $result = [1, $org];

                        break;
                    }

                    if (1 < \strlen($statement)) {
                        // if (true === $this->debug) {
                        //     \pr($xpr, $prev, $current, __LINE__);
                        // }

                        return false;
                    }

                    if (!\count($this->brace)) {
                        throw new Compiler\Exception($this->tpl_path . ' file not if/loop error line ' . $line);
                    }

                    \array_pop($this->brace);
                    \array_pop($this->brace);

                    $result = [2, $this->compileClose($statement, $line)];

                    break;
                case '=':
                    $result = [2, $this->compileEcho($statement, $line)];

                    break;
                case '?':
                    $this->brace[] = ['if', $line];
                    $this->brace[] = ['if', $line];
                    $result        = [2, $this->compileIf($statement, $line)];

                    break;
                case ':?':
                    if (!\count($this->brace)) {
                        throw new Compiler\Exception('error line ' . $line);
                    }

                    //    $this->brace[] = ['elseif', $line];
                    //    $this->brace[] = ['if', $line];
                    $result = [2, $this->compileElseif($statement, $line)];

                    break;

                default:
                    if (!$statement) {
                        $result = [1, $org];
                    } else {
                        $compileString = $this->compileDefault($statement, $line);

                        if (false === $compileString) {
                            $result = [1, $org];
                        } else {
                            $result = [2, $compileString . $add];
                        }
                    }

                    break;
            }
        }

        return $result;
    }

    public function compileInDefine($statement, $file, $line, $scope = '', $echo = '')
    {
        if ($scope) {
            $scopeDefines = '';
            $scopeVars    = '';
            $parts        = [];

            foreach (\explode(' ', $scope) as $item) {
                if (false === \strpos($item, ':')) {
                    $key   = $item;
                    $value = $this->compileStatement($item, 0, '')[1];
                } else {
                    [$key, $value] = \explode(':', $item);
                    $value         = $this->compileStatement($value, 0, '')[1];
                }

                $scopeDefines .= 'self::setScope("' . $key . '", ' . $value . ');';
                $parts[] = "'" . $key . "'";
            }
            $scopeVars = '[' . \implode(',', $parts) . ']';

            return $scopeDefines . "self::define('" . \trim(\substr($statement, 1)) . "', '" . $file . "');/*" . $echo . "*/;self::printContents('" . \trim(\substr($statement, 1)) . "', [], " . $scopeVars . ')';
        }

        return "self::define('" . \trim(\substr($statement, 1)) . "', '" . $file . "');/*" . $echo . "*/;self::printContents('" . \trim(\substr($statement, 1)) . "')";
    }

    public function compileDefine($statement, $line, $scope = '')
    {
        if ($scope) {
            $scopeDefines = '';
            $scopeVars    = '';
            $parts        = [];

            /*
             * {order = payload.order} {#row Row.tpl scope order}
             * =>
             * {#row Row.tpl scope order:payload.order}
             */
            foreach (\explode(' ', $scope) as $item) {
                if (false === \strpos($item, ':')) {
                    $key   = $item;
                    $value = $this->compileStatement($item, 0, '')[1];
                } else {
                    [$key, $value] = \explode(':', $item);
                    $value         = $this->compileStatement($value, 0, '')[1];
                }

                $scopeDefines .= 'self::setScope("' . $key . '", ' . $value . ');';
                $parts[] = "'" . $key . "'";
            }
            $scopeVars = '[' . \implode(',', $parts) . ']';

            return $scopeDefines . "self::printContents('" . \trim(\substr($statement, 1)) . "', [], " . $scopeVars . ')';
        }

        return "self::printContents('" . \trim(\substr($statement, 1)) . "')";
    }

    public function compileIfDefine($statement, $line)
    {
        return "if(self::defined('" . \trim(\substr($statement, 2)) . "')) {{";
    }

    /**
     * @param mixed $statement
     * @param mixed $line
     *
     * @return mixed
     */
    public function compileDefault($statement, $line)
    {
        return $this->tokenizer($statement, $line);
    }

    public function compileLoop($statement, $line)
    {
        $result = $this->tokenizer(\substr($statement, 1), $line);

        if (!$result) {
            throw new Exception('Parse error: syntax error, loop는 {@row = array}...{/} 로 사용해주세요. 표현식은 안됩니다. file ' . $this->filename . ' line ' . $line);
        }
        $tokenizer = \explode('=', $result, 2);

        if (false === isset($tokenizer[0]) || false === isset($tokenizer[1])) {
            throw new Compiler\Exception('Parse error: syntax error, loop는 {@row = array}...{/} 로 사용해주세요. file ' . $this->filename . ' line ' . $line);
        }

        [$loop, $array] = $tokenizer;

        $loopValueName = \trim($loop);
        $loopKey       = $this->loopkey++;
        $loopArrayName = '$_a' . $loopKey;
        $loopIndexName = '$_i' . $loopKey;
        $loopSizeName  = '$_s' . $loopKey;
        $loopKeyName   = '$_k' . $loopKey;

        return $loopArrayName . '=' . $array . ';'
            . $loopIndexName . '=-1;'
            . 'if((true===is_array(' . $loopArrayName . ') || true===is_object(' . $loopArrayName . '))&&0<(' . $loopSizeName . '=\Limepie\count(' . $loopArrayName . '))){foreach(' . $loopArrayName . ' as ' . $loopKeyName . '=>' . $loopValueName . '){'
            . $loopIndexName . '++;'
            . $loopValueName . '__index=' . $loopValueName . '_index_=' . $loopIndexName . ';'
            . $loopValueName . '__size=' . $loopValueName . '_size_=' . $loopSizeName . ';'
            . $loopValueName . '__key=' . $loopValueName . '_key_=' . $loopKeyName . ';'
            . $loopValueName . '__value=' . $loopValueName . '_value_=' . $loopValueName . ';'
            . $loopValueName . '__last=' . $loopValueName . '_last_=(' . $loopValueName . '_size_==' . $loopValueName . '_index_+1);';
    }

    public function compileIf($statement, $line)
    {
        $result = $this->tokenizer(\substr($statement, 1), $line);

        if (false === $result) {
            return false;
        }

        return 'if(' . $result . '){{';
    }

    public function compileEcho($statement, $line)
    {
        $result = $this->tokenizer(\substr($statement, 1), $line);

        if (false === $result) {
            return false;
        }

        return 'echo ' . $result . ';';
    }

    public function compileInclude($statement, $line)
    {
        return 'include "' . \substr($statement, 1) . '"';
    }

    public function compileElse($statement, $line)
    {
        return '}}else{{' . $this->tokenizer(\substr($statement, 1), $line);
    }

    public function compileElseif($statement, $line)
    {
        return '}}else if(' . $this->tokenizer(\substr($statement, 2), $line) . '){{';
    }

    public function compileClose($statement, $line)
    {
        return '}}' . $this->tokenizer(\substr($statement, 1), $line);
    }

    public function compileCloseIf($statement, $line)
    {
        return '}}' . $this->tokenizer(\substr($statement, 2), $line);
    }

    public function compileCloseLoop($statement, $line)
    {
        return '}}' . $this->tokenizer(\substr($statement, 2), $line);
    }

    /**
     * @param mixed $source
     * @param mixed $line
     *
     * @return mixed
     */
    public function tokenizer($source, $line = 1)
    {
        $expression = $source;
        $token      = [];
        $m          = [0 => ''];

        for ($i = 0; \strlen($expression); $expression = \substr($expression, \strlen($m[0])), $i++) {
            \preg_match('/^
            (:P<unknown>(?:\.\s*)+)
            |(?P<number>(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+\-]?\d+)?)
            |(?P<assoc_array>=\>)
            |(?P<object_sign>-\>)
            |(?P<clone_sign>clone )
            |(?P<namespace_sigh>\\\)
            |(?P<static_object_sign>::)
            |(?P<compare>===|!==|<<|>>|<=|>=|==|!=|&&|\|\||<|>)
            |(?P<sam>\?\?|\?\:)
            |(?P<sam2>\?|\:)
            |(?P<assign>\=)
            |(?P<string_concat>\.)
            |(?P<left_parenthesis>\()
            |(?P<right_parenthesis>\))
            |(?P<left_bracket>\[)
            |(?P<right_bracket>\])
            |(?P<comma>,)
            |(?:(?P<string>[A-Z_a-z\x7f-\xff][\w\x7f-\xff]*)\s*)
            |(?<quote>(?:"(?:\\\.|[^"])*")|(?:\'(?:\\\.|[^\'])*\'))
            |(?P<double_operator>\+\+|--)
            |(?P<operator>\+|\-|\*|\/|%|&|\^|~|\!|\|)
            |(?P<not_support>\?|:)
            |(?P<whitespace>\s+)
            |(?P<dollar>\$)
            |(?P<semi_colon>;)
            |(?P<not_match>.+)
            /ix', $expression, $m);

            $r = ['org' => '', 'name' => '', 'value' => ''];

            foreach ($m as $key => $value) {
                if (\is_numeric($key)) {
                    continue;
                }

                if (\strlen($value)) {
                    $v = \trim($value);

                    if ('number' === $key && '.' === $v[0]) {
                        $token[] = ['org' => '.', 'name' => 'number_concat', 'value' => '.'];
                        $r       = ['org' => \substr($v, 1), 'name' => 'string_number', 'value' => \substr($v, 1)];
                    } else {
                        $r = ['org' => $m[0], 'name' => $key, 'value' => $v];
                    }

                    break;
                }
            }

            if ('whitespace' !== $r['name'] && 'enter' !== $r['name']) {
                $token[] = $r;
            }
        }
        // \pr($token);
        $xpr    = '';
        $stat   = [];
        $assign = 0;
        $org    = '';
        $prev   = $next = [];

        foreach ($token as $key => &$current) {
            if ('semi_colon' === $current['name']) {
                if (true === $this->debug) {
                    \pr($xpr, $prev, $current, __LINE__);
                }

                return $this->empty($prev, $current, $xpr, __LINE__);
            }
            $current['value'] = \strtr($current['value'], [
                '{`' => '{',
                '`}' => '}',
            ]);
            $current['org'] = \strtr($current['org'], [
                '{`' => '{',
                '`}' => '}',
            ]);

            $current['key'] = $key;

            if (true === isset($token[$key - 1])) {
                $prev = $token[$key - 1];
            } else {
                $prev = ['org' => '', 'name' => '', 'value' => ''];
            }

            $org .= $current['org'];

            if (true === isset($token[$key + 1])) {
                $next = $token[$key + 1];
            } else {
                $next = ['org' => '', 'name' => '', 'value' => ''];
            }

            // 마지막이 종결되지 않음
            if (!$next['name'] && false === \in_array($current['name'], ['string', 'number', 'string_number', 'right_bracket', 'right_parenthesis', 'double_operator', 'quote'], true)) {
                if ('sam' === $current['name']) {
                    $xpr .= $current['value'] . 'null';

                    continue;
                }

                // pr($current);
                if (true === $this->debug) {
                    \pr($xpr, $prev, $current, __LINE__);
                }

                return $this->empty($prev, $current, $xpr, __LINE__);

                throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $current['org']);
            }

            switch ($current['name']) {
                case 'string':
                    if (false === \in_array($prev['name'], ['', 'clone_sign', 'right_parenthesis', 'left_parenthesis', 'left_bracket', 'assign', 'object_sign', 'static_object_sign', 'namespace_sigh', 'double_operator', 'operator', 'assoc_array', 'compare', 'quote_number_concat', 'assign', 'string_concat', 'comma', 'sam', 'sam2'], true)) {
                        if (true === $this->debug) {
                            \pr($xpr, $prev, $current, __LINE__);
                        }

                        return $this->empty($prev, $current, $xpr, __LINE__);

                        throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);
                    }

                    if (true               === \in_array($current['value'], ['int', 'string', 'float'], true)
                    && 'left_parenthesis'  === $prev['name']
                    && 'right_parenthesis' === $next['name']
                    ) {
                        $xpr .= $current['value'];
                    } elseif ('new' === $current['value'] && 'namespace_sigh' === $next['name']) {
                        $xpr .= 'new ';
                        // 클로저를 허용하지 않음. 그래서 string_concat 비교 보다 우선순위가 높음
                    } elseif (true === \in_array($next['name'], ['left_parenthesis', 'static_object_sign', 'namespace_sigh'], true)) {
                        if ('string_concat' === $prev['name']) {
                            if (true === $this->debug) {
                                \pr($xpr, $prev, $current, __LINE__);
                            }

                            return $this->empty($prev, $current, $xpr, __LINE__);

                            throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org'] . $next['org']);
                        }

                        if ('_' === $current['value']) {
                            // $xpr .= '\\limepie\\'.$current['value'];
                            $xpr .= $current['value'];
                        } else {
                            $xpr .= $current['value'];
                        }
                    } elseif ('object_sign' === $prev['name']) {
                        $xpr .= $current['value'];
                    } elseif ('static_object_sign' === $prev['name']) {
                        $xpr .= '$' . $current['value'];
                    } elseif ('namespace_sigh' === $prev['name']) {
                        $xpr .= $current['value'];
                    } elseif ('string_concat' === $prev['name']) {
                        if (true === \in_array($current['value'], ['index_', 'key_', 'value_', 'last_', 'size_', '_index', '_key', '_value', '_last', '_size'], true)) {
                            if (0 === \strrpos($xpr, ']')) {
                                $xpr .= '[\'' . $current['value'] . '\']';
                            } else {
                                $xpr .= '_' . $current['value'] . '';
                            }
                        } else {
                            $xpr .= '[\'' . $current['value'] . '\']';
                        }
                    } else {
                        if (true === \in_array(\strtolower($current['value']), ['true', 'false', 'null'], true)) {
                            $xpr .= $current['value'];
                        } elseif (\preg_match('#__([a-zA-Z_]+)__#', $current['value'])) {
                            $xpr .= $current['value']; // 처음
                        } else {
                            $xpr .= '$' . $current['value']; // 처음
                        }
                    }

                    break;
                case 'dollar':
                    if (true === $this->debug) {
                        \pr($xpr, $prev, $current, __LINE__);
                    }

                    return $this->empty($prev, $current, $xpr, __LINE__);

                    if (false === \in_array($prev['name'], ['left_bracket', 'assign', 'object_sign', 'static_object_sign', 'namespace_sigh', 'double_operator', 'operator', 'assoc_array', 'compare', 'quote_number_concat', 'assign', 'string_concat', 'comma'], true)) {
                        if (true === $this->debug) {
                            \pr($xpr, $prev, $current, __LINE__);
                        }

                        return $this->empty($prev, $current, $xpr, __LINE__); // 원본 출력(javascript)
                    }

                    throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);

                    break;
                case 'not_support':
                    if (true === $this->debug) {
                        \pr($xpr, $prev, $current, __LINE__);
                    }

                    return $this->empty($prev, $current, $xpr, __LINE__); // 원본 출력(javascript)

                    throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);

                    break;
                case 'not_match':
                    if (true === \in_array($prev['name'], ['sam', 'sam2'], true)) {
                        if (true === $this->debug) {
                            \pr($xpr, $prev, $current, __LINE__);
                        }

                        return $this->empty($prev, $current, $xpr, __LINE__); // 원본 출력
                    }

                    throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $current['org']);

                    break;
                case 'assoc_array':
                    $last_stat = \array_pop($stat);

                    if ($last_stat
                        && 0 < $last_stat['key']
                        && true === \in_array($token[$last_stat['key'] - 1]['name'], ['string'], true)
                    ) {
                        throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);
                    }

                    $stat[] = $last_stat;

                    if (false === \in_array($prev['name'], ['number', 'string', 'quote', 'right_parenthesis', 'right_bracket'], true)) {
                        throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);
                    }
                    $xpr .= $current['value'];

                    break;
                case 'sam':
                    if (false === \in_array($prev['name'], ['string', 'number', 'right_bracket', 'right_parenthesis', 'string_number'], true)) {
                        if (true === $this->debug) {
                            \pr($xpr, $prev, $current, __LINE__);
                        }

                        return $this->empty($prev, $current, $xpr, __LINE__);
                    }
                    $xpr .= $current['value'];

                    break;
                case 'sam2':
                    if (false === \in_array($prev['name'], ['string', 'number', 'quote', 'right_parenthesis'], true)) {
                        if (true === $this->debug) {
                            \pr($xpr, $prev, $current, __LINE__);
                        }

                        return $this->empty($prev, $current, $xpr, __LINE__);
                    }

                    if ('?' === $current['value']) {
                        $stat[] = $current;
                    } elseif (':' === $current['value']) {
                        $last_stat = \array_pop($stat);

                        if (!$last_stat || 'sam2' !== $last_stat['name'] || !$next['name']) {
                            if (true === $this->debug) {
                                \pr($xpr, $prev, $current, __LINE__);
                            }

                            return $this->empty($prev, $current, $xpr, __LINE__);
                        }
                    }
                    $xpr .= $current['value'];

                    break;
                case 'quote':
                    if (true === \in_array($prev['name'], ['string'], true)) {
                        if (true === $this->debug) {
                            \pr($xpr, $prev, $current, __LINE__);
                        }

                        return $this->empty($prev, $current, $xpr, __LINE__);
                    }

                    if (false === \in_array($prev['name'], ['', 'left_parenthesis', 'left_bracket', 'comma', 'compare', 'assoc_array', 'operator', 'quote_number_concat', 'assign', 'sam', 'sam2'], true)) {
                        throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);
                    }
                    $xpr .= $current['value'];

                    break;
                case 'number':
                    if ($current['value'] === $source) {
                        return $this->empty($prev, $current, $xpr, __LINE__);
                    }
                    $last_stat = \array_pop($stat);

                    if ('assoc_array' === $prev['name']) {
                    } elseif ($last_stat
                        && 1 < $last_stat['key']
                        && 'assoc_array' === $prev['name'] && false === \in_array($token[$last_stat['key'] - 1]['name'], ['left_bracket'], true)
                    ) {
                        throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);
                    }

                    $stat[] = $last_stat;

                    if (false === \in_array($prev['name'], ['', 'left_bracket', 'left_parenthesis', 'comma', 'compare', 'operator', 'assign', 'assoc_array', 'string', 'right_bracket', 'number_concat', 'string_concat', 'quote_number_concat', 'sam', 'sam2'], true)) {
                        throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);
                    }

                    if ('quote_number_concat' === $prev['name']) {
                        $xpr .= "'" . $current['value'] . "'";
                        $current['name'] = 'quote';
                    } elseif (true === \in_array($prev['name'], ['string', 'right_bracket', 'number_concat'], true)) {
                        $xpr .= '[' . $current['value'] . ']';
                    } else {
                        $xpr .= $current['value'];
                    }

                    break;
                case 'string_number':
                    if (false === \in_array($prev['name'], ['right_bracket', 'number_concat'], true)) {
                        // 'string',
                        throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);
                    }
                    $xpr .= '[' . $current['value'] . ']';

                    break;
                case 'number_concat':
                    if (false === \in_array($prev['name'], ['string', 'string_number', 'right_bracket'], true)) {
                        throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);
                    }

                    break;
                case 'double_operator':
                    if (false === \in_array($prev['name'], ['string', 'number', 'string_number', 'assign', 'sam', 'sam2'], true)) {
                        if (true === $this->debug) {
                            \pr($xpr, $prev, $current, __LINE__);
                        }

                        return $this->empty($prev, $current, $xpr, __LINE__);

                        throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);
                    }
                    $xpr .= $current['value'];

                    break;
                case 'object_sign':
                    if (false === \in_array($prev['name'], ['right_bracket', 'string', 'right_parenthesis'], true)) {
                        throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);
                    }

                    $xpr .= $current['value'];

                    break;
                case 'clone_sign':
                    if (false === \in_array($prev['name'], ['left_parenthesis'], true)) {
                        throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);
                    }

                    $xpr .= $current['value'];

                    break;
                case 'namespace_sigh':
                    if (false === \in_array($prev['name'], ['compare', 'static_object_sign', 'quote_number_concat', 'left_bracket', 'left_parenthesis', 'string', 'assign', 'comma', 'operator', 'sam2', 'sam', ''], true)) {
                        if (true === $this->debug) {
                            \pr($xpr, $prev, $current, __LINE__);
                        }

                        // return $this->empty($prev, $current, $xpr, __LINE__);

                        throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . '(' . $prev['name'] . ')' . $current['org']);
                    }

                    if ('static_object_sign' === $prev['name']) {
                        $xpr .= \substr($current['value'], 1);
                    } else {
                        $xpr .= $current['value'];
                    }

                    break;
                case 'static_object_sign':
                    if (false === \in_array($prev['name'], ['right_parenthesis', 'string', ''], true)) {
                        throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);
                    }
                    $xpr .= $current['value'];

                    break;
                case 'operator':
                    if (false === \in_array($prev['name'], ['', 'compare', 'right_parenthesis', 'right_bracket', 'number', 'string', 'string_number', 'quote', 'assign', 'comma', 'sam', 'sam2', 'left_parenthesis'], true)) {
                        if (true === $this->debug) {
                            \pr($xpr, $prev, $current, __LINE__);
                        }

                        return $this->empty($prev, $current, $xpr, __LINE__);

                        throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);
                    }

                    // + 이지만 앞이나 뒤가 quote라면 + -> .으로 바꾼다. 지금의 name또한 변경한다.
                    // || false !== \strpos($current['value'], "'"), 변수가 포함되면 무조건 concat이 되므로 삭제
                    if ('+' === $current['value'] && ('quote' === $prev['name'] || 'quote' === $next['name'])) {
                        $xpr .= '.';
                        $current['name'] = 'quote_number_concat';
                    } else {
                        $xpr .= $current['value'];
                    }

                    break;
                case 'compare':
                    if (false === \in_array($prev['name'], ['', 'number', 'string', 'string_number', 'assign', 'left_parenthesis', 'left_bracket', 'quote', 'right_parenthesis', 'right_bracket', 'double_operator'], true)) {
                        throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);
                    }
                    $xpr .= $current['value'];

                    break;
                case 'assign':
                    $assign++;

                    if (2 < $assign) {
                        // $test = $ret = ... 와 같이 여러 변수를 사용하지 못하는 제약 조건
                        throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);
                    }

                    if (false === \in_array($prev['name'], ['sam', 'right_bracket', 'string', 'operator'], true)) {
                        // \pr($prev, $current, $next);
                        // exit;
                        throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);
                    }

                    // = 앞에는 일부의 연산자만 허용된다. +=, -=...
                    if ('operator' === $prev['name'] && false === \in_array($prev['value'], ['+', '-', '*', '/', '%', '^', '!'], true)) {
                        throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);
                    }

                    $xpr .= $current['value'];

                    break;
                case 'left_bracket':
                    $stat[] = $current;

                    if (false === \in_array($prev['name'], ['', 'assign', 'left_bracket', 'right_bracket', 'comma', 'left_parenthesis', 'right_parenthesis', 'assoc_array', 'string', 'string_number', 'sam'], true)) {
                        throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);
                    }
                    $xpr .= $current['value'];

                    break;
                case 'right_bracket':
                    $last_stat = \array_pop($stat);

                    if ('left_bracket' !== $last_stat['name']) {
                        throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);
                    }

                    if (false === \in_array($prev['name'], ['quote', 'left_bracket', 'right_parenthesis', 'comma', 'string', 'number', 'string_number', 'right_bracket'], true)) {
                        throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);
                    }
                    $xpr .= $current['value'];

                    break;
                case 'array_keyword': // number next             |(?P<array_keyword>array)
                    if (false === \in_array($prev['name'], ['', 'compare', 'operator', 'left_parenthesis', 'left_bracket', 'comma', 'assign'], true)) {
                        throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);
                    }
                    $xpr .= $current['value'];

                    break;
                case 'left_parenthesis': // ()
                    $stat[] = $current;

                    if (false === \in_array($prev['name'], ['', 'quote_number_concat', 'operator', 'compare', 'assoc_array', 'left_parenthesis', 'comma', 'left_bracket', 'array_keyword', 'string', 'assign', 'right_bracket', 'sam2'], true)) {
                        // , 'string_number' ->d.3.a() -> ->d[3]['a']() 제외
                        throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);
                    }
                    $xpr .= $current['value'];

                    break;
                case 'right_parenthesis':
                    $last_stat = \array_pop($stat);

                    if (!$last_stat) {
                        \pr($org);
                        \pr($last_stat);

                        exit;
                    }

                    if ('left_parenthesis' !== $last_stat['name']) {
                        throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);
                    }

                    if (false === \in_array($prev['name'], ['left_parenthesis', 'right_bracket', 'right_parenthesis', 'string', 'number', 'string_number', 'quote'], true)) {
                        //                        pr($prev);
                        if (true === $this->debug) {
                            \pr($xpr, $prev, $current, __LINE__);
                        }

                        return $this->empty($prev, $current, $xpr, __LINE__);

                        throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);
                    }
                    $xpr .= $current['value'];

                    break;
                case 'comma':
                    $last_stat = \array_pop($stat);

                    if ($last_stat) {
                        if ($last_stat['name'] && 'left_bracket' === $last_stat['name'] && 0 < $last_stat['key']) {
                            // ][ ,] 면 배열키이므로 ,가 있으면 안됨
                            if (\in_array($token[$last_stat['key'] - 1]['name'], ['right_bracket', 'string'], true)) {
                                throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);
                            }
                        }

                        // 배열이나 인자 속이 아니면 오류
                        if (false === \in_array($last_stat['name'], ['left_parenthesis', 'left_bracket'], true)) {
                            if (true === $this->debug) {
                                \pr($xpr, $prev, $current, __LINE__);
                            }

                            return $this->empty($prev, $current, $xpr, __LINE__);

                            throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);
                        }

                        $stat[] = $last_stat;

                        if (false === \in_array($prev['name'], ['quote', 'string', 'number', 'string_number', 'right_parenthesis', 'right_bracket'], true)) {
                            throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $prev['org'] . $current['org']);
                        }

                        $xpr .= $current['value'];
                    } else {
                        return $this->empty($prev, $current, $xpr, __LINE__);
                    }

                    break;
            }
        }

        if (0 < \count($stat)) {
            $last_stat = \array_pop($stat);

            if ($last_stat) {
                if ('left_parenthesis' === $last_stat['name']) {
                    throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $current['org']);
                }

                if ('left_bracket' === $last_stat['name']) {
                    throw new Compiler\Exception(__LINE__ . ' parse error(' . $prev['name'] . ') : file ' . $this->filename . ' line ' . $line . ' ' . $current['org']);
                }
            }
        }

        return $xpr;
    }

    private function empty($prev, $current, $xpr, $line)
    {
        // \pr($prev, $current, $xpr, $line);

        // if(false !== strpos($xpr, "guest")) {
        //     exit;
        // }
        return false;
    }

    private function saveResult($cplPath, $source, $cplHead, $initCode)
    {
        $sourceSize = \strlen($cplHead) + 9 + \strlen($initCode) + \strlen($source);
        $source     = $cplHead . \str_pad((string) $sourceSize, 9, '0', \STR_PAD_LEFT) . $initCode . $source;

        \file_put_contents($cplPath, $source, \LOCK_EX);

        // 파일 시스템 동기화
        \system('sync');

        $retries  = 5;
        $fileSize = 0;

        while ($retries > 0 && $fileSize !== \strlen($source)) {
            \clearstatcache(true, $cplPath);
            $fileSize = \filesize($cplPath);

            if ($fileSize !== \strlen($source)) {
                \usleep(100000); // 100ms 대기
                --$retries;
            }
        }

        // echo $retries;

        if ($fileSize !== \strlen($source)) {
            \unlink($cplPath);

            throw new Compiler\Exception($fileSize . ' | ' . \strlen($source) . ' Problem by concurrent access. Just retry after some seconds. "<b>' . $cplPath . '</b>"');
        }
    }

    private function filter($source, $type)
    {
        $func_split    = \preg_split('/\s*(?<!\\\)\|\s*/', \trim($this->{$type . 'filter'}));
        $func_sequence = [];

        for ($i = 0,$s = \count($func_split); $i < $s; ++$i) {
            if ($func_split[$i]) {
                $func_sequence[] = \str_replace('\|', '|', $func_split[$i]);
            }
        }

        if (!empty($func_sequence)) {
            for ($i = 0,$s = \count($func_sequence); $i < $s; ++$i) {
                $func_args = \preg_split('/\s*(?<!\\\)\&\s*/', $func_sequence[$i]);

                for ($j = 1,$k = \count($func_args); $j < $k; ++$j) {
                    $func_args[$j] = \str_replace('\&', '&', \trim($func_args[$j]));
                }
                $func      = \strtolower(\array_shift($func_args));
                $func_name = $this->{$type . 'filters_flip'}[$func];
                \array_unshift($func_args, $source, $this);
                $func_file = $this->plugin_dir . '/' . $type . 'filter.' . $func_name . '.' . $this->pluginExtension;

                if (!\in_array($func, $this->{$type . 'filters'}, true)) {
                    throw new Compiler\Exception('cannot find ' . $type . 'filter file ' . $func_file . '');
                }

                if (!\function_exists($func_name)) {
                    if (false === include_once $func_file) {
                        throw new Compiler\Exception('error in ' . $type . 'filter ' . $func_file . '');
                    }

                    if (!\function_exists($func_name)) {
                        throw new Compiler\Exception('filter function ' . $func_name . '() is not found in ' . $func_file . '');
                    }
                }
                $source = \call_user_func_array($func_name, $func_args);
            }
        }

        return $source;
    }
}
