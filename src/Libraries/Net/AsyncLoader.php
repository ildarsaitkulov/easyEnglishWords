<?php

namespace App\Libraries\Net;

use App\Libraries\Io\Response;
use App\Libraries\Net\Exceptions\CurlException;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use parallel\{Channel,Runtime,Events,Events\Event};

class AsyncLoader
{
    use Response;

    protected $events;
    protected $tasksInQueue;

    /**
     * @var LoopInterface
     */
    protected $loop;

    protected LoggerInterface $logger;

    /**
     * @var \React\EventLoop\TimerInterface
     */
    protected $timer;

    public function __construct(LoopInterface $loop, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->loop = $loop;
        $this->events = new Events();
        $this->events->setBlocking(false);
        $this->timer = $this->addPeriodicTimer();
    }

    /**
     * @return \React\EventLoop\TimerInterface
     */
    public function addPeriodicTimer()
    {
        if ($this->timer) {
            return $this->timer;
        }
        return $this->loop->addPeriodicTimer(0.5, function () {
            $event = $this->events->poll();
            if ($event) {
                if (isset($event->value['status']) && $event->value['status']) {
                    $this->tasksInQueue[$event->source]['deferred']->resolve($event->value['data']);
                } else {
                    $this->tasksInQueue[$event->source]['deferred']->reject($event->value['data']);
                }
                $this->tasksInQueue[$event->source]['runtime']->kill();
                unset($this->tasksInQueue[$event->source]);
            }
        });
    }

    public function generateUniqueId($long = false)
    {
        $hash = md5(mt_rand(0, 1000000));

        return str_replace(".", '', microtime(true) . $hash[mt_rand(0, 31)]);
    }

    /**
     * @param callable $callback
     * @param          $params
     *
     * @return \React\Promise\Promise|\React\Promise\PromiseInterface
     */
    public function runInThread(callable $callback, $params)
    {
        $deferred = new Deferred();
        $runtime = new Runtime(APP_ROOT . '/config/bootstrap.php');
        $future = $runtime->run($callback, $params);

        $id = $this->generateUniqueId();
        $this->events->addFuture($id, $future);

        $this->tasksInQueue[$id] = [
            'deferred' => $deferred,
            'runtime' => $runtime
        ];

        return $deferred->promise();
    }

    /**
     * @param $url
     * @param $toFile
     *
     * @return \React\Promise\Promise|\React\Promise\PromiseInterface
     */
    public function downloadFileAsync($url, $toFile)
    {
        return $this->runInThread(function ($url, $toFile) {
            $downloaded = false;
            try {
                $downloaded = self::downloadFile($url, $toFile);
            } catch (\Exception $exception) {
                $errorMessage = "Error on downloadFileAsync {$url} {$exception->getMessage()}";
            }
            if ($downloaded) {
                return self::success($toFile);
            }
            $errorMessage = $errorMessage ?? "Error on downloadFileAsync {$url}";
            return self::error($errorMessage);
        }, [$url, $toFile]);
    }

    /**
     * @return Curl
     */
    protected static function curl(): Curl
    {
        $curl = new Curl();
        $curl->reset();
        $curl->setCookieFile('');

        return $curl;
    }

    protected static function downloadFile($url, $toFile)
    {
        $curl = self::curl();

        $fp = fopen($toFile, 'w');
        $curl->addOptions([
            CURLOPT_FILE => $fp,
            CURLOPT_HEADER => 0,
        ]);

        $curl->prepare($url);

        try {
            $curl->execute();
            fclose($fp);
        } catch (CurlException $e) {
            fclose($fp);

            return false;
        }

        return true;
    }
}