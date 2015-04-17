<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once (__DIR__ . '/vendor/autoload.php');
require_once (__DIR__ . '/Checker.php');

if ($argc < 2 ){
    echo "Use ex. $ php test.php https://bootjp.me/ \n";
    exit;
}
echo $argv[4];
echo "\n";
print_r((new Error\Checker(isset($argv[3])? $argv[3] : null, isset($argv[4])? $argv[4] : null))
        ->start($argv[1], isset($argv[2]) ? (bool) $argv[2] : true));
