<?php
namespace Kahlan\Box;

use RuntimeException;

class BoxException extends RuntimeException
{
    protected $code = 500;
}
