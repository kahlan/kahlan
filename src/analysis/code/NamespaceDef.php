<?php
namespace kahlan\analysis\code;

class NamespaceDef extends NodeDef
{
    public $type = 'namespace';

    public $uses = [];

    public $name = '';
}
