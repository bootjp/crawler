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
        $result = [];
        $result['white'] = [];
        $result['black'] = [];
        if (array_key_exists(0, $list)) {
            $getFlag = $list[0];
        }
        if (array_key_exists(1, $list)) {
            $this->recursion = $list[1];
        }

        if ((bool) $this->isContentsFetch) {
            echo 'Contents fetching..';
            $url = $this->fetchByContents($url);

            if ((bool) $this->recursion) {
                $url = $this->urlFilter($url);
            }
        }

        if (is_null($url)) {
            throw new \RuntimeException('Start URL is not null.');
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
            throw new \RuntimeException('Not match contents on url.');
        }

        foreach ($matches['url'] as $url) {

            if (preg_match('{https?://[\w/:%#\$&\?\(\)~\.=\+\-]+}i', $url)) {
                $urlList[] = $url;
            } else if (preg_match('{^https?:\/\/[\w/:%#\$&\?\(\)~\.=\+\-]+$}i', $baseUrl . $url)) {
                if (preg_match("{(^#[A-Z0-9].+?$)}i", $url)) {
                    $this->garbage[] = $url;
                } else if (preg_match("#javascript.*#i", $url)) {
                    $this->garbage[] = $url;
                } else {
                    // start slash ?
                    $startSlash = substr($url, 0, 1) === '/';
                    $secondSlash = substr($url, 1, 1) === '/';
                    if ($startSlash && $secondSlash) {
                        $parsedUrl = parse_url($baseUrl);
                        $urlList[] = $parsedUrl['scheme'] . ':' . $url;
                    } else if ($startSlash) {
                        // end is slash?
                        $parsedUrl = parse_url($baseUrl);
                        if (substr($baseUrl, -1, 1) === '/') {
                            // has slash
                            $root = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                        } else {
                            // add slash
                            $root = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . '/';
                        }
                        $urlList[] = $root . $url;
                    } else {
                        $urlList[] = $baseUrl . $url;
                    }
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
    private function hardCheckByHeader($metaData)
    {
        $headers = array_change_key_case($metaData->getHeaders());
        $statusCode = (int) $metaData->getStatusCode();

        $isErrorPageCode = [
            '40x' => [401, 403, 404],
            '50x' => [500, 502, 503],
            '30x' => [301, 302, 308]
        ];

        foreach($isErrorPageCode as $errorType => $statuses) {
            if (in_array($statusCode, $statuses)) {
                return [
                    'result' => false,
                    'status' => "NG : status code {$errorType}"
                ];
            }
        }

        if ($statusCode === 200 && $statusCode === 304) {
            return [
                'result' => true
            ];
        }

        if (array_key_exists('content-length', $headers) && $headers['content-length'][0] < $this->contentsSize) {
            return [
                'result' => false,
                'status' => 'NG : contentsSize'
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
    public function softCheckByContents($metaData)
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
    private function softCheckByContentsWords($metaData)
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
