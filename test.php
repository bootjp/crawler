<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once (__DIR__ . '/vendor/autoload.php');
require_once (__DIR__ . '/Main.php');
require_once (__DIR__ . '/Start.php');

$obj = new Main(500, true);
print_r($obj->start((new Start())->layerStart('https://github.com/bootjp/crawler/blob/master/Main.php')));
