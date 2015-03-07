<?php

/**
 * Description of Main
 *
 * @author bootjp
 */
class Main
{
    protected $client;

    protected $contentsSize;

    protected $doubleCheck;

    /**
     * initialisation.
     * @param int  $contentSize
     * @param bool $doubleCheck
     */
    public function __construct($contentSize, $doubleCheck)
    {
        $this->contentsSize = (int) $contentSize;
        $this->doubleCheck = (bool) $doubleCheck;
        $this->client = new \GuzzleHttp\Client();
    }
    /**
     * Wrapper
     * @param mixed $url
     * @throws \ReflectionException
     * @return arrat URLLIST
     */
    public function start($url)
    {
        $urlList = [];
        $result['white'] = [];
        $result['black'] = [];

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
    /**
     * DataValidation Check 404 Error
     * @param string $url validationData
     * @return bool Soft404 or normalContents
     */
    private function hardCheckByHeader(\GuzzleHttp\Message\Response $metaData)
    {
        $head = array_change_key_case($metaData->getHeaders());

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
        if (strlen($metaData->getBody()->getContents()) >= $this->contentsSize) {
            if ($this->doubleCheck){
                foreach (self::softErrorWords() as $word) {
                    var_dump(($metaData->getBody()->getContents()));exit;
                    if (strpos($word, strtolower($metaData->getBody()->getContents())) !== false){
                        return false;
                    }
                }
            }

            return true;
        } else {
            return false;
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
