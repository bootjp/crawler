<?php

namespace Error;

/**
 * Description of Checker Main
 *
 * @author bootjp
 */
class Checker
{
    protected $client;

    protected $contentsSize;

    protected $doubleCheck;

    /**
     * initialisation.
     * @param int  $contentSize [optional]
     * @param bool $doubleCheck [optional]
     */
    public function __construct($contentSize = 500, $doubleCheck = true)
    {
        $this->contentsSize = (int) $contentSize;
        $this->doubleCheck = (bool) $doubleCheck;
        $this->client = new \GuzzleHttp\Client();
        $this->client->setDefaultOption('exceptions', false);
    }
    /**
     * Wrapper
     * @param mixed $url    [require]
     * @param bool $getFlag [optional] true when fetch content on the $url
     * @throws \ReflectionException
     * @return array URLLIST
     */
    public function start($url, $getFlag = false)
    {
        $urlList = [];
        $result['white'] = [];
        $result['black'] = [];

        if ($getFlag) {
            $url = $this->fetchByContents($url);
        }

        if (is_null($url)) {
            throw new \ReflectionException('Start URL is not null.');
        } else if (is_array($url)) {
            foreach ($url as $value) {
                $urlList[] = $value;
            }
        } else {
            $urlList[] = (string) $url;
        }

        foreach ($urlList as $url) {

            $metaData = $this->client->get($url);

            if ($this->hardCheckByHeader($metaData) &&
                $this->softCheckByContents($metaData)) {
                $result['white'][] = $url;
            } else {
                $result['black'][] = $url;
            }

            sleep(5);
        }

        return $result;
    }

    /**
     * Fetch Page Contents Links
     * @param mixed $baseUrl
     * @return array URllist
     */
    private function fetchByContents($baseUrl)
    {
        $urlList = [];
        $matches = [];
        $urlList['baseUrl'] = (string) $baseUrl;

        $contents = $this->client->get($baseUrl)->getBody()->getContents();

        preg_match_all('{<a.+?href=[\"|\'](?<url>.+?)[\"\|\'].*?>}is', $contents, $matches);

        if (!array_key_exists('url', $matches)) {
            throw new \ErrorException('Not match contents on url.');
        }

        foreach ($matches['url'] as $url) {

            if (preg_match('{https?://[\w/:%#\$&\?\(\)~\.=\+\-]+}', $url)) {
                $urlList[] = $url;
            } else if (preg_match('{https?://[\w/:%#\$&\?\(\)~\.=\+\-]+}', $baseUrl . $url)) {
                $urlList[] = $baseUrl . $url;
            } else {
                $urlList['unknown'] = $url;
            }
        }

        return array_unique($urlList);
    }

    /**
     * Error check by header
     * @param string $metaData validationData
     * @return bool Soft404 or normalContents
     */
    private function hardCheckByHeader(\GuzzleHttp\Message\Response $metaData)
    {
        $head = array_change_key_case($metaData->getHeaders());

        if (array_key_exists('status', $head) && array_search(404, $head['status']) !== false ||
            array_key_exists('status', $head) && array_search(403, $head['status']) !== false) {
            return false;
        }

        if (array_key_exists('status', $head) && array_search(200, $head['status']) !== false ||
            array_key_exists('status', $head) && array_search(304, $head['status']) !== false) {
            return true;
        }

        if (array_key_exists('content-length', $head) && $head['content-length'][0] >= $this->contentsSize) {
            return true;
        }

        return true;
    }

    /**
     * Soft404 check by contents Length
     * @param \GuzzleHttp\Message\Response $metaData
     * @return bool
     */
    public function softCheckByContents(\GuzzleHttp\Message\Response $metaData)
    {
        if (!$metaData->getBody()->getSize() >= $this->contentsSize) {
            return false;
        }

        if ($this->doubleCheck) {
            if (!$this->softCheckByContentsWords($metaData)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Soft404 Error check by words
     * @param \GuzzleHttp\Message\Response $metaData
     * @return bool Result
     */
    private function softCheckByContentsWords($metaData)
    {
        return array_filter(self::getSoftErrorWords(), function($word) use ($metaData) {
            if (stripos(strip_tags($metaData->getBody()->getContents()), $word) !== false) {
                return false;
            }
        });
    }

    /**
     * Return soft404 Page on Words.
     * @param  none
     * @return array
     */
    private function getSoftErrorWords()
    {
        return file('ErrorPageWords.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }
}
