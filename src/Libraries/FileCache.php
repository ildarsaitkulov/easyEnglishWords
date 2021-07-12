<?php

namespace App\Libraries;

class FileCache
{
    protected $cachePath = '/tmp/fileCache';
    protected $expire = 172800;

    public function setCachePath($path)
    {
        $this->cachePath = $path;
    }

    public function getCachePath()
    {
        return $this->cachePath;
    }

    protected function getPath($key)
    {
        return $this->cachePath . "/{$key}.cache";
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    public function get($key)
    {
        $cachePath = $this->getPath($key);
        if(!file_exists($cachePath)) {
            return null;
        }

        $expired = false;

        try {
            $storedData = unserialize(file_get_contents($cachePath));
            $now = time();
            if(($storedData['e'] > 0 && $storedData['e'] < $now) || ($now - filemtime($cachePath) > $this->expire)) {
                $expired = true;
            }
        } catch (\ErrorException $errorException) {
            $expired = true;
        }

        if($expired) {
            @unlink($cachePath);

            return null;
        }

        return $storedData['v'];
    }

    public function set($key, $value, $expire = 0)
    {
        $cachePath = $this->getPath($key);
        $dirname = dirname($cachePath);
        if (file_exists($cachePath) && !@chmod($cachePath, 0777)) {
            @unlink($cachePath);
        } elseif(!file_exists($dirname)) {
            @mkdir($dirname, 0777, true);
        }

        $storeData = [
            'e' => $expire > 0 ? (time() + $expire) : 0,
            'v' => $value
        ];

        $attempts = 5;
        do {
            $result = file_put_contents($cachePath, serialize($storeData));
            if($result !== false) {
                break;
            }
            usleep(10000);
        } while($result === false && --$attempts > 0);
    }

    public function delete($key)
    {
        $cachePath = $this->getPath($key);
        @unlink($cachePath);

        return true;
    }

    public function clear()
    {
        shell_exec("rm -rf {$this->cachePath}");

        return true;
    }
}