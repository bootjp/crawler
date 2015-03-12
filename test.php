<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once (__DIR__ . '/vendor/autoload.php');
require_once (__DIR__ . '/Checker.php');

if (count($argv) < 2){
    echo "Use ex. $ php test.php https://bootjp.me/ \n";
    exit;
}

print_r((new Error\Checker())->start($argv[1], false));
