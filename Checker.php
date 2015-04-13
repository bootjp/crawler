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

    protected $garbage = [];

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
        $this->client->setDefaultOption(
              'headers', ['User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_2) '
            . 'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.111 Safari/537.36']);
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

        echo 'Cheking..';

        foreach ($urlList as $key => $url) {

            $metaData = $this->client->get($url);
            $hardCheck = (array) $this->hardCheckByHeader($metaData);
            $softCheck = (array) $this->softCheckByContents($metaData);

            if ($hardCheck['result'] && $softCheck['result']) {
                $result['white'][$key]['url'] = $url;
                $result['white'][$key]['status'] = 'OK';
            } else {
                $result['black'][$key]['url'] = $url;
                $result['black'][$key]['status'] = array_key_exists('status', $hardCheck) ? $hardCheck['status'] : $softCheck['status'];
            }

            sleep(1);
            echo '.';
        }

        $result['UnknownLinks'] = $this->garbage;

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

            if (preg_match('{https?://[\w/:%#\$&\?\(\)~\.=\+\-]+}i', $url)) {
                $urlList[] = $url;
            } else if (preg_match('{https?://[\w/:%#\$&\?\(\)~\.=\+\-]+}i', $baseUrl . $url)) {
                $urlList[] = $baseUrl . $url;
            } else {
                $this->garbage[] = $url;
            }
        }

        return array_unique($urlList);
    }

    /**
     * Error check by header
     * @param \GuzzleHttp\Message\Response $metaData
     * @return bool Soft404 or normalContents
     */
    private function hardCheckByHeader(\GuzzleHttp\Message\Response $metaData)
    {
        $head = array_change_key_case($metaData->getHeaders());

        if (is_int($metaData->getStatusCode() && $metaData->getStatusCode() === 404) ||
            is_int($metaData->getStatusCode() && $metaData->getStatusCode() === 403) ||
            is_int($metaData->getStatusCode() && $metaData->getStatusCode() === 401) ||
            is_int($metaData->getStatusCode() && $metaData->getStatusCode() === 503) ||
            is_int($metaData->getStatusCode() && $metaData->getStatusCode() === 502) ||
            is_int($metaData->getStatusCode() && $metaData->getStatusCode() === 500)) {
            return [
                'result' => false,
                'status' => 'NG : status code 40X or 50X'
            ];
        }

        if (is_int($metaData->getStatusCode() && $metaData->getStatusCode() === 301) ||
            is_int($metaData->getStatusCode() && $metaData->getStatusCode() === 302) ||
            is_int($metaData->getStatusCode() && $metaData->getStatusCode() === 308)) {
            return [
                'result' => false,
                'status' => 'NG : status code 30X'
            ];
        }

        if (is_int($metaData->getStatusCode() && $metaData->getStatusCode() === 200) ||
            is_int($metaData->getStatusCode() && $metaData->getStatusCode() === 304)) {
            return [
                'result' => true
            ];
        }

        if (array_key_exists('content-length', $head) && $head['content-length'][0] >= $this->contentsSize) {
            return [
                'result' => true
            ];

        }

        return [
            'result' => true
        ];
    }

    /**
     * Soft404 check by contents Length
     * @param \GuzzleHttp\Message\Response $metaData
     * @return bool
     */
    public function softCheckByContents(\GuzzleHttp\Message\Response $metaData)
    {
        if ($metaData->getBody()->getSize() <= $this->contentsSize) {
            return [
                'result' => false,
                'status' => 'NG : contentsSize'
            ];
        }

        if ($this->doubleCheck) {
            $result = $this->softCheckByContentsWords($metaData);
            if (!$result['result']) {
                return [
                    'result' => $result['result'],
                    'status' => $result['status']
                ];
            }
        }

        return [
            'result' => true
        ];

    }

    /**
     * Soft404 Error check by words
     * @param \GuzzleHttp\Message\Response $metaData
     * @return bool Result
     */
    private function softCheckByContentsWords(\GuzzleHttp\Message\Response $metaData)
    {
        foreach (self::getSoftErrorWords() as $word) {
            if (mb_stripos($metaData->getBody()->getContents(), $word) !== false) {
                return [
                    'result' => false,
                    'status' => 'NG WORD : ' .$word
                ];
            }
        }

        return [
            'result' => true
        ];

    }

    /**
     * Return soft404 Page on Words.
     * @param  none
     * @return array
     */
    private static function getSoftErrorWords()
    {
        return file('ErrorPageWords.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }
}
