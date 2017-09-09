<?php
function parent() {
    return nested();

    function nested() {
        return true;
    }
}