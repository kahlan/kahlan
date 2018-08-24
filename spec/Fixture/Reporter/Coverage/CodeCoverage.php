<?php
namespace Kahlan\Spec\Fixture\Reporter\Coverage;

class CodeCoverage
{
    public function shallNotPass()
    {
        $shallNotPass = false;
        if (false) {
            $shallNotPass = true;
        }
        return $shallNotPass;
        $shallNotPass = true;
    }

    public function shallPass()
    {
        $shallPass = false;
        if (true) {
            $shallPass = true;
        }
        return $shallPass;
    }

    public function multilineArrays()
    {
        $array = [
            'a',
            'b',
            'c',
            true || false,
            (
                true && (false && false)
            )
        ];

        $array =
        [
            'hello'
        ];

        $array = array(
            'a',
            'b',
            'c',
            true || false,
            (
                true && (false && false)
            )
        );

        $array = array
        (
            'hello'
        );
    }

    public function multilineExpressions()
    {
        $bool = (
            (true && true)
            ||
            false
            &&
            (
                true || false
            )
        );

        $bool = (
            (false && $bool)
            ||
            empty($bool)
            &&
            (
                isset($bool) || false
            )
        );
    }

    public function multilineStrings()
    {
        $quote = '
            quoted string
        ';

        $doubleQuote = '
            double quoted string
        ';
    }
}
