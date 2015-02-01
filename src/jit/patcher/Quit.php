<?php
namespace kahlan\jit\patcher;

class Quit {

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
     * Helper for `Quit::process()`.
     *
     * @param array $nodes A array of nodes to patch.
     */
    protected function _processTree($nodes)
    {
        $alphanum = '[\\\a-zA-Z0-9_\\x7f-\\xff]';
        $regex = "/(?<!\:|\\\$|\>|{$alphanum})(\s*)((?:exit|die)\s*\()/m";

        foreach ($nodes as $node) {
            if ($node->processable && $node->type === 'code') {
                $node->body = preg_replace($regex, '\1\kahlan\plugin\Quit::quit(', $node->body);
            }
            if (count($node->tree)) {
                $this->_processTree($node->tree);
            }
        }
    }

}
