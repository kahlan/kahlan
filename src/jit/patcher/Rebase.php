<?php
namespace kahlan\jit\patcher;

class Rebase {

    /**
     * The JIT find file patcher.
     *
     * @param  object $loader The autloader instance.
     * @param  string $class  The fully-namespaced class name.
     * @param  string $file   The correponding finded file path.
     * @return string The patched file path.
     */
    public function findFile($loader, $class, $file)
    {
        return $file;
    }

    /**
     * The JIT patcher.
     *
     * @param  NodeDef $node The node to patch.
     * @param  string  $path The file path of the source code.
     * @return NodeDef       The patched node.
     */
    public function process($node, $path = null)
    {
        $this->_processTree($node->tree, $path);
        return $node;
    }

    /**
     * Helper for `Monkey::process()`.
     *
     * @param array  $nodes A node array to patch.
     * @param string $path  The file path of the source code.
     */
    protected function _processTree($nodes, $path)
    {
        $path = addcslashes($path, "'");
        $dir = "'" . dirname($path) . "'";
        $file = "'" . $path . "'";

        $alphanum = '[\\\a-zA-Z0-9_\\x7f-\\xff]';
        $dirRegex = "/(?<!\:|\\\$|\>|{$alphanum})(\s*)(__DIR__)/";
        $fileRegex = "/(?<!\:|\\\$|\>|{$alphanum})(\s*)(__FILE__)/";

        foreach ($nodes as $node) {
            if ($node->processable && $node->type === 'code') {
                $node->body = preg_replace($dirRegex, $dir, $node->body);
                $node->body = preg_replace($fileRegex, $file, $node->body);
            }
            if (count($node->tree)) {
                $this->_processTree($node->tree, $path);
            }
        }
    }

}
