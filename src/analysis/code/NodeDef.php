<?php
namespace kahlan\analysis\code;

class NodeDef
{
    public $processable = true;

    public $coverable = false;

    public $type = 'none';

    public $namespace = null;

    public $parent = null;

    public $function = null;

    public $inPhp = false;

    public $hasMethods = false;

    public $body = '';

    public $close = '';

    public $tree = [];

    public $lines = [
        'content' => [],
        'start' => null,
        'stop'  => 0
    ];

    public function __construct($body = '', $type = null)
    {
        if ($type) {
            $this->type = $type;
        }
        $this->body = $body;
    }

    public function __toString()
    {
        $childs = '';
        foreach ($this->tree as $node) {
            $childs .= (string) $node;
        }
        return $this->body . $childs . $this->close;
    }
}
