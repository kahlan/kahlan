<?php
namespace kahlan\jit\patcher;

class Pointcut
{
    /**
     * Class dependencies.
     *
     * @var array
     */
    protected $_classes = [
        'node' => 'jit\node\NodeDef',
    ];

    /**
     * Prefix to use for custom variable name
     *
     * @var string
     */
    protected $_prefix = '';


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
            'prefix'   => 'KPOINTCUT'
        ];
        $config += $defaults;

        $this->_classes += $config['classes'];
        $this->_prefix   = $config['prefix'];
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
     * Helper for `Pointcut::process()`.
     *
     * @param array $nodes A node array to patch.
     */
    protected function _processTree($nodes)
    {
        foreach ($nodes as $node) {
            if ($node->hasMethods && $node->type !== 'interface') {
                $this->_processMethods($node->tree);
            } elseif (count($node->tree)) {
                $this->_processTree($node->tree);
            }
        }
    }

    /**
     * Helper for `Pointcut::process()`.
     *
     * @param object The node instance to patch.
     */
    protected function _processMethods($node)
    {
        foreach ($node as $child) {
            if (!$child->processable) {
                continue;
            }
            $process = (
                $child->type === 'function' &&
                $child->isMethod &&
                !isset($child->visibility['abstract'])
            );
            if ($process) {
                $code = $this->_classes['node'];
                $before = new $code($this->_before(), 'code');
                $before->parent = $child;
                $before->function = $child;
                $before->processable = false;
                $before->namespace = $child->namespace;
                array_unshift($child->tree, $before);
            }
        }
    }

    /**
     * Before closure pattern.
     *
     * @return string.
     */
    protected function _before()
    {
        $prefix = $this->_prefix;
        return "\$__{$prefix}_ARGS__ = func_get_args(); \$__{$prefix}_SELF__ = isset(\$this) ? \$this : get_called_class(); if (\$__{$prefix}__ = \kahlan\plugin\Pointcut::before(__METHOD__, \$__{$prefix}_SELF__, \$__{$prefix}_ARGS__)) { \$r = \$__{$prefix}__(\$__{$prefix}_SELF__, \$__{$prefix}_ARGS__); return \$r; }";
    }

}
