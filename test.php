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

echo "\n";
print_r((new Error\Checker())->start($argv[1], isset($argv[2]) ? (bool) $argv[2] : true));
