<?php

require_once (__DIR__ . '/vendor/autoload.php');
require_once (__DIR__ . '/Checker.php');

if ($argc < 2 ){
    echo "Use ex. $ php test.php https://bootjp.me/ \n";
    exit;
}
echo "\n";
print_r((new Error\Checker(isset($argv[3])? $argv[3] : null))
        ->start($argv[1], isset($argv[2]) ? $argv[2] : 'true:false'));
