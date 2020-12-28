<?php

class ConstructorPromotion {
    public function __construct(
        public string|null $name,
        public bool $bool1 = false,
        public bool $bool2 = false
    ) {
    }
}
