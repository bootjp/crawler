<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Start
 *
 * @author bootjp
 */
class Start
{

    protected $client;

    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client;
    }

    public function layerStart($baseUrl)
    {
        $urlList = [];
        $matches = [];
        $urlList['baseUrl'] = (string) $baseUrl;

        $contens = $this->client->get($baseUrl)->getBody()->getContents();

        preg_match_all('{<a.+?href=[\"|\'](?<url>.+?)[\"\|\'].*?>}is', $contens, $matches);

        if (!array_key_exists('url', $matches)) {
            return null;
        }

        foreach ($matches['url'] as $key => $url) {

            if (preg_match('{https?://[\w/:%#\$&\?\(\)~\.=\+\-]+}', $url)) {
                $urlList[] = $url;
            } else if (preg_match('{https?://[\w/:%#\$&\?\(\)~\.=\+\-]+}', $baseUrl . $url)) {
                $urlList[] = $baseUrl . $url;
            } else {
                $urlList['unknown'] = $url;
            }
        }

        return $urlList;
    }
}
