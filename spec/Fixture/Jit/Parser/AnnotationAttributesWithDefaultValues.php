<?php

#[Attribute(Attribute::TARGET_PROPERTY)]
class TestAttribute {}

class Test
{
    #[TestAttribute] public int $intPropertyWithNoDefault;
    #[TestAttribute] public int $intPropertyWithZeroAsDefault = 0;
    #[TestAttribute]
    public int $anotherIntPropertyWithZeroAsDefault = 0;
    #[TestAttribute] public int $intPropertyWithOneAsDefault = 1;
    #[TestAttribute] public string $stringPropertyWithEmptyAsDefault = '';
}