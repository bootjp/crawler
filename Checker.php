<?php

/**
 * Description of Main
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
    public function __construct($contentSize = 1000, $doubleCheck = false)
    {
        $this->contentsSize = (int) $contentSize;
        $this->doubleCheck = (bool) $doubleCheck;
        $this->client = new \GuzzleHttp\Client();
    }
    /**
     * Wrapper
     * @param mixed $url
     * @param bool $getFlag true when get content on the $url
     * @throws \ReflectionException
     * @return arrat URLLIST
     */
    public function start($url, $getFlag = false)
    {
        $urlList = [];
        $result['white'] = [];
        $result['black'] = [];

        if ($getFlag) {
            $url = $this->layerStart($url);
        }

        if (is_null($url)) {
            throw new \ReflectionException('Start URL is not null.');
        } else if (is_array($url)) {
            foreach ($url as $value) {
                $urlList[] = $value;
            }
        } else {
            $urlList[] = $url;
        }

        foreach ($urlList as $url) {

            $this->client->setDefaultOption('exceptions', false);
            $metaData = $this->client->get($url);

            if ($this->hardCheckByHeader($metaData) ||
                $this->softCheckByContents($metaData)) {
                $result['white'][] = $url;
            } else {
                $result['black'][] = $url;
            }

            sleep(5);
        }

        return $result;
    }

    private function layerStart($baseUrl)
    {
        $urlList = [];
        $matches = [];
        $urlList['baseUrl'] = (string) $baseUrl;

        $contents = $this->client->get($baseUrl)->getBody()->getContents();

        preg_match_all('{<a.+?href=[\"|\'](?<url>.+?)[\"\|\'].*?>}is', $contents, $matches);

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

        return array_unique($urlList);
    }

    /**
     * DataValidation Check 404 Error
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

    }

    private function softCheckByContents(\GuzzleHttp\Message\Response $metaData)
    {
        if (!strlen($metaData->getBody()->getContents()) >= $this->contentsSize) {
            return false;
        }

        if ($this->doubleCheck) {
            return $this->softCheckByContentsWords($metaData);
        }
    }

    private function softCheckByContentsWords(\GuzzleHttp\Message\Response $metaData)
    {
        foreach (self::softErrorWords() as $word) {
            if (stripos($word, $metaData->getBody()->getContents()) !== false) {
                return false;
            }
        }
    }

    private static function softErrorWords()
    {
        return [
            'not found',
            'みつかりま',
            '見つかりま'
        ];
    }
}
