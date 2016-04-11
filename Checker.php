<?php

namespace Error;

require_once (__DIR__ . '/vendor/autoload.php');

/**
 * Description of Checker Main
 *
 * @author bootjp
 */
class Checker
{
    protected $client;

    protected $contentsSize = 500;

    protected $doubleCheck = true;

    protected $recursion = false;

    protected $garbage = [];

    protected $isContentsFetch = true;


    /**
     * initialisation.
     * @param array $args
     */
    public function __construct(array $args)
    {
        $this->client = new \GuzzleHttp\Client([
                'defaults' => [
                    'exceptions' => false,
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_2) ' .
                        'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.111 Safari/537.36'
                    ]
                ]
            ]
        );
        if (array_key_exists('contentSize', $args)) {
            $this->contentsSize = (int) $args['contentSize'];
        }

        if (array_key_exists('doubleCheck', $args)) {
            $this->doubleCheck = (bool) $args['doubleCheck'];
        }

        if (array_key_exists('isContentsFetch', $args)) {
            $this->isContentsFetch = (bool) $args['isContentsFetch'];
        }

        if (array_key_exists('recursion', $args)) {
            $this->recursion = (bool) $args['recursion'];
        }

        if (array_key_exists('auth', $args)) {
            list($username, $password) = explode(':', $args['auth'], 2);
            $this->client->setDefaultOption('auth', [$username, $password]);
        }

    }

    /**
     * Wrapper
     * @param  mixed $url [require]
     * @return array
     * @throws \ErrorException
     * @throws \ReflectionException
     */
    public function start($url)
    {
        $urlList = [];
        $result['white'] = [];
        $result['black'] = [];

        if ((bool) $this->isContentsFetch) {
            echo 'Contents fetching..';
            $url = $this->fetchByContents($url);

            if ((bool) $this->recursion) {
                $url = $this->urlFilter($url);
            }
        }

        if (is_null($url)) {
            throw new \ReflectionException('Start URL is not null.');
        } else if (is_array($url)) {
            $urlList = $this->urlFilter($url);
        } else if (is_string($url)) {
            $urlList[] = $url;
        } else if (is_object($url)) {
            $urlList[] = (string) $url;
        }

        echo "\n";
        echo 'Cheking..';

        foreach ($urlList as $key => $url) {
            try {
                $metaData = $this->client->get($url);
            } catch (\Exception $e) {
                echo "\n {$url}\t {$e->getMessage()}";
            }
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
     * @return array URlList
     * @throws \ErrorException
     */
    private function fetchByContents($baseUrl)
    {
        $urlList = [];
        $matches = [];
        $urlList['baseUrl'] = (string) $baseUrl;
        try {
            $contents = $this->client->get($baseUrl)->getBody()->getContents();
        } catch (\Exception $e) {
            echo "\n {$baseUrl}\t {$e->getMessage()}";
        }

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
                } else if (preg_match("#javascript.*#i", $url)) {
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
        return file(__DIR__ . '/ErrorPageWords.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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
