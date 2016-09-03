<?php
namespace Kahlan\Jit\Patcher;

class Monkey
{
    /**
     * Class dependencies.
     *
     * @var array
     */
    protected $_classes = [
        'node' => 'Kahlan\Jit\Node\NodeDef',
    ];

    /**
     * Prefix to use for custom variable name.
     *
     * @var string
     */
    protected $_prefix = '';

    /**
     * Counter for building unique variable name.
     *
     * @var integer
     */
    protected $_counter = 0;

    /**
     * Ignoring the following statements which are not valid function or class names.
     *
     * @var array
     */
    protected $_blacklist = [
        '__halt_compiler' => true,
        'and'             => true,
        'array'           => true,
        'catch'           => true,
        'case'            => true,
        'compact'         => true,
        'declare'         => true,
        'die'             => true,
        'echo'            => true,
        'elseif'          => true,
        'empty'           => true,
        'eval'            => true,
        'exit'            => true,
        'extract'         => true,
        'for'             => true,
        'foreach'         => true,
        'function'        => true,
        'if'              => true,
        'include'         => true,
        'include_once'    => true,
        'isset'           => true,
        'list'            => true,
        'or'              => true,
        'parent'          => true,
        'print'           => true,
        'require'         => true,
        'require_once'    => true,
        'return'          => true,
        'self'            => true,
        'static'          => true,
        'switch'          => true,
        'unset'           => true,
        'while'           => true,
        'xor'             => true
    ];

    /**
     * Uses for the parsed node's namespace.
     *
     * @var array
     */
    protected $_uses = [];

    /**
     * Variables for the parsed node.
     *
     * @var array
     */
    protected $_variables = [];

    /**
     * The regex.
     *
     * @var string
     */
    protected $_regex = null;

    /**
     * The constructor.
     *
     * @var array $config The config array. Possible values are:
     *                    - `'prefix'` _string_: prefix to use for custom variable name..
     */
    public function __construct($config = [])
    {
        $defaults = [
            'classes'  => [],
            'prefix'   => 'KMONKEY'
        ];
        $config += $defaults;

        $this->_classes += $config['classes'];
        $this->_prefix   = $config['prefix'];

        $alpha = '[\\\a-zA-Z_\\x7f-\\xff]';
        $alphanum = '[\\\a-zA-Z0-9_\\x7f-\\xff]';
        $this->_regex = "/(new\s+)?(?<!\:|\\\$|\>|{$alphanum})(\s*)({$alpha}{$alphanum}*)(\s*)(\(|;|::{$alpha}{$alphanum}*\s*\()/m";

    }

    /**
     * The JIT find file patcher.
     *
     * @param  object $loader The autloader instance.
     * @param  string $class  The fully-namespaced class name.
     * @param  string $file   The correponding finded file path.
     * @return string         The patched file path.
     */
    public function findFile($loader, $class, $file)
    {
        return $file;
    }

    /**
     * The JIT patchable checker.
     *
     * @param  string  $class The fully-namespaced class name to check.
     * @return boolean
     */
    public function patchable($class)
    {
        return true;
    }

    /**
     * The JIT patcher.
     *
     * @param  object $node The node instance to patch.
     * @param  string $path The file path of the source code.
     * @return object       The patched node.
     */
    public function process($node, $path = null)
    {
        $this->_processTree($node->tree);
        return $node;
    }

    /**
     * Helper for `Monkey::process()`.
     *
     * @param array $nodes A array of nodes to patch.
     */
    protected function _processTree($nodes)
    {
        foreach ($nodes as $index => $node) {
            $this->_variables = [];
            if ($node->processable && $node->type === 'code') {
                $this->_uses = $node->namespace ? $node->namespace->uses : [];

                $this->_monkeyPatch($node, $nodes, $index);
                $code = $this->_classes['node'];
                $body = '';

                if ($this->_variables) {
                    foreach ($this->_variables as $variable) {
                        if ($variable['isInstance']) {
                            $body .= $variable['name'] . '__=null;';
                        }
                        $body .= $variable['name'] . $variable['patch'];
                    }
                    $parent = $node->function ?: $node->parent;
                    if (!$parent->inPhp) {
                        $body = '<?php ' . $body . ' ?>';
                    }

                    $patch = new $code($body, 'code');
                    $patch->parent = $parent;
                    $patch->function = $node->function;
                    $patch->namespace = $node->namespace;
                    array_unshift($parent->tree, $patch);
                }
            }
            if (count($node->tree)) {
                $this->_processTree($node->tree);
            }
        }
    }

    /**
     * Monkey patch a node body.
     *
     * @param object  $node  The node to monkey patch.
     * @param array   $nodes The nodes array.
     * @param integer $index The index of node in nodes.
     */
    protected function _monkeyPatch($node, $nodes, $index)
    {
        if (!preg_match_all($this->_regex, $node->body, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            return;
        }
        $offset = 0;
        $buffer = $node->body;
        foreach (array_reverse($matches) as $match) {
            $len = strlen($match[0][0]);
            $pos = $match[0][1];
            $name = $match[3][0];

            $isInstance = !!$match[1][0];
            $isStatic = preg_match('/^::/', $match[5][0]);

            if (!isset($this->_blacklist[strtolower($name)]) && ($isInstance || $match[5][0] === '(' || $isStatic)) {
                $tokens = explode('\\', $name, 2);

                if ($name[0] === '\\') {
                    $name = substr($name, 1);
                    $args = "null , '{$name}'";
                } elseif (isset($this->_uses[$tokens[0]])) {
                    $ns = $this->_uses[$tokens[0]];
                    if (count($tokens) === 2) {
                        $ns .= '\\' . $tokens[1];
                    }
                    $args = "null, '" . $ns . "'";
                } else {
                    $args = "__NAMESPACE__ , '{$name}'";
                }

                if (!isset($this->_variables[$name])) {
                    $variable = '$__' . $this->_prefix . '__' . $this->_counter++;
                    $this->_variables[$name]['name'] = $variable;
                    $this->_variables[$name]['isInstance'] = $isInstance;
                    if ($isInstance) {
                        $args .= ', false, ' . $variable . '__';
                    } elseif ($isStatic) {
                        $args .= ', false';
                    }

                    $this->_variables[$name]['patch'] = "=\Kahlan\Plugin\Monkey::patched({$args});";
                } else {
                    $variable = $this->_variables[$name]['name'];
                }
                $substitute = $variable . '__';
                if (!$isInstance) {
                    $replace = $match[2][0] . $variable . $match[4][0] . $match[5][0];
                } else {
                    $p = $pos + $len;
                    $count = 0;
                    $total = count($nodes);

                    if ($match[5][0][strlen($match[5][0]) - 1] === ';') {
                        $match[5][0] = substr($match[5][0], 0, -1) . ');';
                        $count--;
                    } else {
                        for ($i = $index; $i < $total; $i++) {
                            $n = $nodes[$i];
                            if ($n->processable && $n->type === 'code') {
                                $code = $n->body;
                                $l = strlen($code);
                                while ($p < $l) {
                                    if ($count === 0 && $code[$p] === ';') {
                                        $n->body = substr_replace($code, ');', $p, 1);
                                        $count--;
                                        break 2;
                                    } elseif ($code[$p] === '(' || $code[$p] === '{') {
                                        $count++;
                                    } elseif ($code[$p] === ')' || $code[$p] === '}') {
                                        $count--;
                                    }
                                    if ($count < 0) {
                                        if ($i === $index) {
                                            $buffer = substr_replace($code, $code[$p] . ')', $p, 1);
                                        } else {
                                            $n->body = substr_replace($code, $code[$p] . ')', $p, 1);
                                        }
                                        break 2;
                                    }
                                    $p++;
                                }
                            }
                            $p = 0;
                        }
                    }
                    if ($count < 0) {
                        $replace = '(' . $substitute . '?' . $substitute . ':' . $match[1][0] . $match[2][0] . $variable . $match[4][0] . $match[5][0];
                    } else {
                        $replace = $match[1][0] . $match[2][0] . $variable . $match[4][0] . $match[5][0];
                    }
                }
                $buffer = substr_replace($buffer, $replace, $pos, $len);
                $offset = $pos + strlen($replace);
            } else {
                $offset = $pos + $len;
            }
        }
        return $node->body = $buffer;
    }
}
