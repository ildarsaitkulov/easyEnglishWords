<?php

namespace App\Command;

use App\Libraries\FileCache;
use App\Libraries\Net\AsyncLoader;
use App\Libraries\Net\Curl;
use Psr\Log\LoggerInterface;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zanzara\Config;
use Zanzara\Context;
use Zanzara\Telegram\Type\Input\InputFile;
use Zanzara\Telegram\Type\Message;
use Zanzara\Zanzara;

class TelegramBotBase extends Command
{
    protected int $maxPageSize = 10;
    protected FileCache $cache;
    protected LoggerInterface $logger;
    protected AsyncLoader $asyncLoader;

    /**
     * @var LoopInterface
     */
    protected LoopInterface $loop;

    protected array $config = [
        'botToken' => '',
        'bot_username' => '',
        'telegramApiUrl' => 'http://127.0.0.1:8081',
        'telegramCachePath' => '',
        'callback_ttl' => 86400 * 7,
    ];
    /**
     * @var Zanzara
     */
    protected Zanzara $bot;

    /**
     * TelegramBotBase constructor.
     *
     * @param LoggerInterface $logger
     * @param string|null     $name
     *
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function __construct(LoggerInterface $logger, string $name = null)
    {
        $this->logger = $logger;
        $this->loop = Factory::create();
        
        $this->cache = new FileCache();
        $this->cache->setCachePath($this->config['telegramCachePath']);
        
        $this->asyncLoader = new AsyncLoader($this->loop, $this->logger);
        
        $config = new Config();
        $config->setLoop($this->loop);
        $config->setApiTelegramUrl($this->config['telegramApiUrl']);

        $this->bot = new Zanzara($this->config['botToken'], $config);
        $this->bot->onCbQuery(function (Context $context) {
            $callbackData = $context->getCallbackQuery()->getData();
            [$methodName, $cacheId] = explode('@', $callbackData);
            $methodParams = $this->cache->get($cacheId);
            if ($methodParams === null) {
                $this->sendButtonExpired($context);
                $context->endConversation();

                return;
            }
            if (empty($methodParams)) {
                call_user_func([$this, $methodName], $context);
            } else {
                call_user_func([$this, $methodName], $context, $methodParams);
            }
            $context->answerCallbackQuery();
        });

        if (!$name) {
            $name = 'TelegramBotBase';
        }
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        throw new LogicException('Implement it in your telegram bot');
    }

    /**
     * @param Context $context
     * @param array   $params
     *
     * @psalm-param array{data:array, size?:int, currentPage?:int, edit?:bool} $params
     */
    public function showPaging(Context $context, array $params)
    {
        if (empty($params['size'])) {
            $params['size'] = $this->maxPageSize;
        }
        if (empty($params['currentPage'])) {
            $params['currentPage'] = 0;
        }
        $size = $params['size'];
        $currentPage = $params['currentPage'];

        $chunks = array_chunk($params['data'], $size);
        $totalPages = count($chunks);

        $nextPageParams = $params;
        $previousPageParams = $params;

        $previousPageParams['edit'] = $nextPageParams['edit'] = true;
        $previousPageParams['currentPage']--;
        $nextPageParams['currentPage']++;

        if (!isset($chunks[$currentPage])) {
            $this->logger->info('Out of range');
            $context->answerCallbackQuery([]);

            return;
        }
        $searchResultChunk = $chunks[$currentPage];

        $searchResultChunk[] = [
            [
                'callback_data' => $this->prepareCallbackData('showPaging', $previousPageParams),
                'text' => '??????',
            ],
            [
                'callback_data' => $this->prepareCallbackData('showPaging', $nextPageParams),
                'text' => '??????',
            ],
        ];

        
        $currentPageToDisplay = $currentPage + 1;
        $text = "????????????????: {$currentPageToDisplay}/{$totalPages}";

        if (isset($params['edit'])) {
            $context->editMessageText($text, [
                'reply_markup' => [
                    'inline_keyboard' => $searchResultChunk
                ],
            ]);
        } else {
            $context->sendMessage($text, [
                'reply_markup' => [
                    'inline_keyboard' => $searchResultChunk
                ],
                'parse_mode' => 'HTML',
            ]);
        }

        if ($context->getCallbackQuery()) {
            $context->answerCallbackQuery([]);
        }
    }

    /**
     * @param string $method
     * @param        $params
     *
     * @return string
     */
    public function prepareCallbackData(string $method, array $params = [])
    {
        $id = $this->generateUniqueId();
        $this->cache->set($id, $params, $this->config['callback_ttl']);
        
        return "{$method}@{$id}";
    }

    /**
     * @param Context $context
     */
    public function sendButtonExpired(Context $context)
    {
        $context->answerCallbackQuery(['text' => '?????????? ???????????????? ???????????? ??????????????!']);
    }

    /**
     * @return string
     */
    public function generateUniqueId()
    {
        $hash = md5(mt_rand(0, 1000000));

        return str_replace(".", '', microtime(true) . $hash[1]);
    }

    /**
     * @param string $url
     *
     * @return int
     * @throws \App\Libraries\Net\Exceptions\CurlException
     */
    protected function getFileSizeByUrl(string $url)
    {
        $fileSize = 0;
        $curl = new Curl();
        $curl->setCookieFile('');
        $curl->addOptions([
            CURLOPT_HEADERFUNCTION => function($ch, $headerLine) use (&$fileSize) {
                if (preg_match('/Content-Length:\s*(?<size>\d+)/', $headerLine, $matches)) {
                    $fileSize = (int) $matches['size'];
                }
                return strlen($headerLine);
            },
        ]);
        $curl->prepare($url);
        $curl->execute();

        return $fileSize;
    }

    /**
     * @param Context     $context
     * @param array       $inlineKeyboard
     * @param string      $photoUrl
     * @param string|null $caption
     * @param bool        $edit
     *
     * @return \React\Promise\PromiseInterface
     */
    protected function sendPhoto(Context $context, array $inlineKeyboard, string $photoUrl, string $caption = null, bool $edit = false)
    {
        $options = [
            'parse_mode' => 'HTML',
            'caption' => $caption,
            'reply_markup' => [
                'inline_keyboard' => $inlineKeyboard
            ],
        ];

        if ($edit) {
            $media = [
                'media' => $photoUrl,
                'type' => 'photo',
                'caption' => $caption,
                'parse_mode' => 'HTML'
            ];
            
            return $context->editMessageMedia($media, $options);
        }
        
        return $context->sendPhoto($photoUrl, $options);
    }

    /**
     * @param Context $context
     * @param string  $soundUrl
     * @param array   $options
     */
    protected function sendAudio(Context $context, string $soundUrl, array $options)
    {
        $filePath = '/tmp/' . md5($soundUrl);
        $this->asyncLoader->downloadFileAsync($soundUrl, $filePath)->then(function ($filePath) use ($context, $options) {
            $context->sendAudio(new InputFile($filePath), $options)->then(function (Message $message) use ($filePath){
                @unlink($filePath);
            });
        });
    }
}