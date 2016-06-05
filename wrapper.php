<?php

require_once (__DIR__ . '/vendor/autoload.php');
require_once (__DIR__ . '/Checker.php');

if ($argc < 2 ){
    throw new InvalidArgumentException('Use ex. $ php wrapper.php https://bootjp.me/');
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


print_r((new Error\Checker($options))->start($options['url']));
