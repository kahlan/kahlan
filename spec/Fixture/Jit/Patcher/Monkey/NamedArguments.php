<?php

$string = '<a href="test">Test</a>';
echo htmlspecialchars($string, double_encode: false);
