<?php
namespace kahlan\analysis\code;

class BlockDef extends NodeDef
{
    public $type = null;

    public $hasMethods = true;

    public $name = '';

    public $uses = [];

    public $extends = '';

}
