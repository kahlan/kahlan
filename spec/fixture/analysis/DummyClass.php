<?php

namespace kahlan\spec\fixture\analysis;

class DummyClass {

    public function rand($min = 100, $max = 200) {
        return rand($min, $max);
    }

    public function getTrace(\Exception $e) {
        return $e->getTraceAsString();
    }

    public function max(array $values) {
        return max($values);
    }

    public function is_number(callable $i) {
        return is_numeric($i);
    }

}
