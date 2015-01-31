<?php
namespace kahlan\spec\suite;

use kahlan\reporter\Verbose;

describe("Versbose", function() {

    beforeEach(function() {
        $this->reporter = new Verbose();
    });

});
