<?php

namespace App\Libraries\Skyeng;

use App\Libraries\Net\Curl;
use Psr\Log\LoggerInterface;

class DictionaryApi
{
    protected $version = '1';
    protected $apiUrl = 'https://dictionary.skyeng.ru/';
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $apiMethod
     * @param array  $params
     * @param string $method
     *
     * @return mixed
     * @throws \App\Libraries\Net\Exceptions\CurlException
     */
    public function call(string $apiMethod, array $params = [], string $method = 'GET')
    {
        $curl = $this->curl();

        $url = "{$this->apiUrl}api/public/v{$this->version}/{$apiMethod}";
        $this->logger->debug("Calling {$url}?"  . http_build_query($params));
        $curl->prepare($url, $params, $method);

        return json_decode($curl->execute(), true);
    }

    /**
     * @param string $word
     *
     * @return mixed
     * @throws \App\Libraries\Net\Exceptions\CurlException
     */
    public function searchWords(string $word)
    {
        return $this->call('words/search', ['search' => $word]);
    }

    /**
     * @param array $meaningsIds
     *
     * @return mixed
     * @throws \App\Libraries\Net\Exceptions\CurlException
     */
    public function getMeanings(array $meaningsIds)
    {
        return $this->call('meanings', ['ids' => $meaningsIds]);
    }

    /**
     * @return Curl
     */
    protected function curl(): Curl
    {
        static $curl;
        if ($curl === null) {
            $curl = new Curl();
            $curl->reset();
            $curl->setCookieFile('');
        }

        return $curl;
    }
}