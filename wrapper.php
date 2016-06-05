<?php

require_once (__DIR__ . '/vendor/autoload.php');
require_once (__DIR__ . '/Checker.php');

if ($argc < 2 ){
    echo "Use ex. $ php wrapper.php https://bootjp.me/ \n";
    exit;
}

$options = array_merge([
    'url' => null,
    'recursion' => false,
    'doubleCheck' => true
], getopt('', [
    'url:',
    'recursion:',
    'doubleCheck::',
    'auth::'
]));

if (in_array(null, $options, true)) {
    throw new InvalidArgumentException('Invalid args');
}


echo "\n";


print_r((new Checker($options))->start($options['url']));
