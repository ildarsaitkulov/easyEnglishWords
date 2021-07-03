<?php

namespace App\Libraries\Telegram;

use App\Libraries\FileCache;

class TelegramFileCache
{

    /**
     * @var FileCache
     */
    protected FileCache $cache;
    
    protected string $telegramBotToken;

    public function __construct(string $telegramBotToken)
    {
        $this->telegramBotToken = $telegramBotToken;
        $this->cache = new FileCache();
        $this->cache->setCachePath('/tmp/telegramFilesCache');
    }

    public function storeFileId(string $key, string $value): void
    {
        $this->cache->set($this->getKeyHash($key), $value);
    }

    public function getFileId(string $key)
    {
        return $this->cache->get($this->getKeyHash($key));
    }

    protected function getKeyHash(string $key): string
    {
        return md5($this->telegramBotToken . $key);
    }
}