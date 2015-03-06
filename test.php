<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once (__DIR__ . '/Main.php');
$url = [
   'http://php.net/manual/ja/control-structures.switch.php',
   'http://php.net/manual/ja/index.php'
];
$obj = new Main(500, false);
print_r($obj->start($url));
