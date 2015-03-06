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
        require_once(__DIR__ . '/vendor/autoload.php');
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
            if ($this->UrlValidation($url)) {
                $result['white'][] = $url;
            } else {
                $result['black'][] = $url;
            }
        }

        return $result;
    }
    /**
     * DataValidation Check 404 Error
     * @param string $url validationData
     * @return bool Soft404 or normalContents
     */
    private function UrlValidation($url)
    {
        $metaData = $this->client->get($url)->getHeaders();
        switch ($this->doubleCheck) {
        case false:

            if (!isset($metaData)) {
                return false;
            }

            if (isset($metaData['Status']) && $metaData['Status'][0] === 200 ||
                isset($metaData['Status']) && $metaData['Status'][0] === 304) {
                return true;
            }

            if (isset($metaData['Content-Length']) && $metaData['Content-Length'] >= $this->contentsSize) {
                return true;
            }
            break;
        case true:
            break;
        }
    }

}
