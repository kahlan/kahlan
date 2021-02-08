<?php
namespace Kahlan\Spec\Fixture\Analysis;

use DateTime;

class SampleReturnTypeHintsClass {

    public function noReturnTypeHint() {
    }

    public function intReturnTypeHint() : int {
        return 1;
    }

    public function boolIntReturnTypeHint() : bool|int {
        return true;
    }

    public function selfReturnTypeHint() : self {
        return new self;
    }

    public function staticReturnTypeHint() : static {
        return new static;
    }

    public function sampleReturnTypeHintsClassReturnTypeHint() : SampleReturnTypeHintsClass {
        return new SampleReturnTypeHintsClass;
    }
}
