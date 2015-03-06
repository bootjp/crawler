<?php

/**
 * Description of Main
 *
 * @author bootjp
 */
class Main
{
    protected $client;

    public function __construct($contentSize)
    {
        require_once (__DIR__ . './vender/autoload.php');

        $this->client = new \GuzzleHttp\Client();
    }
    /**
      *
      * @param mixed $url
      * @throws \ReflectionException
      */
    public function start($url = null)
    {
        $urlList = [];
        $whileList = [];

        if (is_null($url)) {
            throw new \ReflectionException("Start URL is not null.");
        } else if (is_array($url)) {

            foreach ($url as $value) {
                $urlList += $value;
            }

        }

        foreach ($urlList as $url) {
            if ($this->client->get($url)->getStatusCode() === 200 || $this->client->get($url)->getStatusCode() === 304) {
                $this->
                $whileList += $url;
            }

        }
    }
    /**
     * DataValidation Check Soft404 Error
     * @param string $data validationData
     * @return bool Soft404 or normalContents
     */
    private function dataValidation($data)
    {

    }

}
