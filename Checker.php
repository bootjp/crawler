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
    public function __construct($auth = null, $contentSize = 500, $doubleCheck = true)
    {
        $this->contentsSize = (int) $contentSize;
        $this->doubleCheck = (bool) $doubleCheck;
        $this->client = new \GuzzleHttp\Client(
            ['defaults' =>
                ['exceptions' => false],
                ['headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_2) '
                  . 'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.111 Safari/537.36']
                ]
            ]
        );
        if (!is_null($auth)) {
            list($username, $password) = explode(':', $auth, 2);
            $this->client->setDefaultOption('auth', [$username, $password]);
        }
    }
    /**
     * Wrapper
     * @param  mixed $url      [require]
     * @param  bool $getFlag   [optional] true when fetch content on the $url
     * @param  bool $recursion [optional] true when fetch content on the link recursion.
     * @throws ReflectionException
     * @return array URLLIST
     */
    public function start($url, $flag = 'true:false')
    {
        $urlList = [];
        $result['white'] = [];
        $result['black'] = [];
        list($getFlag, $recursion) = explode(':', $flag, 2);

        if ((bool) $getFlag) {
            echo 'Contents fetching..';
            $url = $this->fetchByContents($url);

            if ((bool) $recursion) {
                $urlList = array_map(function($uri) {
                    return $this->fetchByContents($uri);
                }, $url);

                $url = $this->urlFilter($urlList);
            }
        }

        if (is_null($url)) {
            throw new \ReflectionException('Start URL is not null.');
        } else if (is_array($url)) {
            $urlList = $this->urlFilter($url);
        } else if (is_string($value)) {
            $urlList[] = $url;
        } else if (is_object($value)) {
            $urlList[] = (string) $url;
        }

        echo "\n";
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

            usleep(500000);
            echo '.';
        }
        $result['UnknownLinks'] = $this->garbage;

        return $result;
    }

    /**
     * Fetch Page Contents Links
     * @param  mixed $baseUrl
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
            } else if (preg_match('{https?:\/\/[\w/:%#\$&\?\(\)~\.=\+\-]+}i', $baseUrl . $url)) {
                if (preg_match("{(^#[A-Z0-9].+?$)}i", $url)) {
                    $this->garbage[] = $url;
                } else {
                    $urlList[] = $baseUrl . $url;
                }
            } else {
                $this->garbage[] = $url;
            }

            usleep(500000);
            echo '.';
        }

        return array_unique($urlList);
    }

    /**
     * Error check by header
     * @param \GuzzleHttp\Message\Response $metaData
     * @return array
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
     * @return array
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
     * @return array Result
     */
    private function softCheckByContentsWords(\GuzzleHttp\Message\Response $metaData)
    {
        foreach (self::getSoftErrorWords() as $word) {
            if (mb_stripos($metaData->getBody()->getContents(), $word) !== false) {
                return [
                    'result' => false,
                    'status' => 'NG WORD : ' . $word
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

    /**
     * multidimensional array to single arry comvert.
     * @param array $urlList
     * @return array URLLIST
     */
    private function urlFilter(array $urlList)
    {
        $result = [];
        array_walk_recursive($urlList, function($v) use (&$result) {
            $result[] = $v;
        });

        return array_values(array_unique($result));
    }
}
