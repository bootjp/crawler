<?php

$url = '/';
if (substr($url, 0) === '/') {
    $urlList[] = substr($url, 1);
} else {
    $urlList[] = $baseUrl . $url;
}

