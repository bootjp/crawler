# OreTokuCrawler

[![Build Status](https://travis-ci.org/bootjp/crawler.svg?branch=master)](https://travis-ci.org/bootjp/crawler)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/bootjp/crawler/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/bootjp/crawler/?branch=master)

## Now Beta2 Version relese.
* https://github.com/bootjp/crawler/releases

### Makeing now...

### How To Use

* Use composer
  + You do not know composer?
    - OK, GO to page => [https://getcomposer.org/](https://getcomposer.org/)
  
### Introduction
```shell
$ curl -s http://getcomposer.org/installer | php
$ php composer.phar install
or
$ composer install
```
### Use Ex.
```bash
$ php wrapper.php --url=https://bootjp.me
or
$  php wrapper.php --url=https://bootjp.me --recursion=false
```
-> https://bootjp.me/ is root on the contents link check
```bash
$ php wrapper.php --url=https://bootjp.me --recursion=true
```
-> https://bootjp.me/ is on the contents link all check  

### Use docker images 

dockerImage is hire -> https://hub.docker.com/r/bootjp/crawler


```bash
docker pull bootjp/crawler
docker run bootjp/crawler php wrapper.php --url=https://bootjp.me --recursion=false --auth=username:password
```

### Basic Auth
```shell
$ php wrapper.php --url=https://bootjp.me --recursion=false --auth=username:password
```
[LICENSE](https://github.com/bootjp/crawler/blob/master/LICENSE)

[日本語での解説記事(ちょっと古い)](https://bootjp.me/2015/03/14/%E5%8D%98%E8%AA%9E-or-404%E3%82%A8%E3%83%A9%E3%83%BCsoft%E5%90%AB%E3%82%80%E3%83%81%E3%82%A7%E3%83%83%E3%82%AB%E3%83%BC%E4%BD%9C%E3%81%A3%E3%81%9F%E3%81%9E-%E4%BB%96%E5%A0%B1%E5%91%8A%E7%AD%89/)
