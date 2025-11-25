<?php

convert(hex2bin('22222222222222222222222222222222'));
convert(hex2bin('33333333333333333333333333333333'));


function convert($item)
{
    if (strlen($item) === 16 && (!ctype_print($item) || !ctype_xdigit($item))) {
        echo "Converted: ";
        $item = bin2hex($item);
    }

    echo $item . "\n";
}

