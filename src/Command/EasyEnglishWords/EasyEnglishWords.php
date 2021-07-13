<?php

namespace App\Command\EasyEnglishWords;

use App\Entity\EasyEnglishWords\EnglishWord;
use App\Entity\EasyEnglishWords\Meaning;
use App\Entity\EasyEnglishWords\WordInLearn;
use App\Entity\EasyEnglishWords\WordSet;
use App\Libraries\Skyeng\DictionaryApi;
use App\Command\TelegramBotBase;
use App\Libraries\Telegram\TelegramFileCache;
use App\Repository\EasyEnglishWords\EnglishWordRepository;
use App\Repository\EasyEnglishWords\MeaningRepository;
use App\Repository\EasyEnglishWords\WordInLearnRepository;
use App\Repository\EasyEnglishWords\WordsetCollectionRepository;
use App\Repository\EasyEnglishWords\WordSetRepository;
use App\Repository\Telegram\ChatRepository;
use App\Repository\Telegram\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Zanzara\Context;
use Zanzara\Telegram\Type\Input\InputFile;
use Zanzara\Telegram\Type\Message;

class EasyEnglishWords extends TelegramBotBase
{
    protected static $defaultName = 'EasyEnglishWords:EasyEnglishWords';
    protected static $defaultDescription = 'Add a short description for your command';

    protected EntityManagerInterface $entityManager;
    protected EnglishWordRepository $englishWordRepository;
    protected MeaningRepository $meaningRepository;
    protected WordSetRepository $wordSetRepository;
    protected WordInLearnRepository $wordInLearnRepository;
    protected UserRepository $telegramUserRepository;
    protected ChatRepository $telegramChatRepository;
    protected DictionaryApi $dictionaryApi;
    protected TelegramBotBase $telegramBotHelper;
    protected WordsetCollectionRepository $wordsetCollectionRepository;

    public const IMAGES_DIR = APP_ROOT . '/public/EasyEnglishWords/images/';
    public const NO_IMAGE_FILE_PATH =  self::IMAGES_DIR  . 'no-image-icon.png';


    protected array $config = [
        'telegramApiUrl' => 'http://127.0.0.1:8081',
        'telegramCachePath' => '/tmp/easyEnglishBotCache',
        'callback_ttl' => 86400 * 7,
        'maxMeanings' => 5
    ];
    
    protected array $menuCommands = [
        '/start' => 'Старт',
        '/create_wordset' => 'Создать словарь',
        '/list_wordsets' => 'Мои словари',
        '/popular_wordsets' => 'Популярные словари',
        '/learn' => 'Учить',
        '/search' => 'Поиск',
    ];
    
    /**
     * @var TelegramFileCache
     */
    protected TelegramFileCache $telegramFileCache;

    public function __construct(string $name = null, 
                                EntityManagerInterface $entityManager, 
                                LoggerInterface $logger,
                                EnglishWordRepository $englishWordRepository,
                                WordSetRepository $wordSetRepository,
                                MeaningRepository $meaningRepository,
                                WordInLearnRepository $wordInLearnRepository,
                                UserRepository $telegramUserRepository,
                                ChatRepository $chatRepository,
                                WordsetCollectionRepository $wordsetCollectionRepository
    )
    {
        $this->config['botToken'] = $_ENV['EASY_ENGLISH_BOT_TOKEN'];
        $this->config['bot_username'] = $_ENV['EASY_ENGLISH_BOT_USERNAME'];
        
        $this->wordsetCollectionRepository = $wordsetCollectionRepository;
        $this->entityManager = $entityManager;
        $this->meaningRepository = $meaningRepository;
        $this->englishWordRepository = $englishWordRepository;
        $this->telegramUserRepository = $telegramUserRepository;
        $this->wordSetRepository = $wordSetRepository;
        $this->wordInLearnRepository = $wordInLearnRepository;
        $this->telegramChatRepository = $chatRepository;
        $this->telegramFileCache = new TelegramFileCache($this->config['botToken']);
        
        parent::__construct($logger, $name);
        $this->dictionaryApi = new DictionaryApi($this->logger);
    }

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription)
             ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
             ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->bot->getTelegram()->setMyCommands([
            [
                'command' => '/start',
                'description' => $this->menuCommands['/start']
            ],
            [
                'command' => '/create_wordset',
                'description' => $this->menuCommands['/create_wordset']
            ],
            [
                'command' => '/list_wordsets',
                'description' => $this->menuCommands['/list_wordsets']
            ],
        ]);
        

        $this->bot->onCommand('start', function (Context $context){
            $user = $context->getEffectiveUser();
            try {
                $telegramUser = $this->telegramUserRepository->saveTelegramUser($user);
                $this->telegramChatRepository->saveChat($context->getEffectiveChat(), $telegramUser);
            } catch (\Exception $exception) {
                $this->logger->error("on start {$exception->getMessage()}");
            }

            $context->sendMessage('Привет! Я помогу тебе выучить английские слова.', [
                'reply_markup' => $this->getMenuReplyMarkup(),
            ]);

        });
        
        $this->bot->onCommand('create_wordset', [$this, 'createWordSet']);
        $this->bot->onText($this->menuCommands['/create_wordset'], [$this, 'createWordSet']);
        
        $this->bot->onCommand('list_wordsets', [$this, 'listWordsets']);
        $this->bot->onText($this->menuCommands['/list_wordsets'], [$this, 'listWordsets']);

        $this->bot->onCommand('popular_wordsets', [$this, 'popularWordSets']);
        $this->bot->onText($this->menuCommands['/popular_wordsets'], [$this, 'popularWordSets']);
        
        $this->bot->onCommand('search', [$this, 'onAddWords']);
        $this->bot->onText($this->menuCommands['/search'], [$this, 'onAddWords']);
        
        $this->bot->onCommand('learn', [$this, 'learn']);
        $this->bot->onText($this->menuCommands['/learn'], [$this, 'learn']);
        
        $this->bot->run();
        
        return 0;
    }

    /**
     * @param Context $context
     */
    public function learn(Context $context): void
    {
        $inlineKeyboard = [];
        $wordSetList = $this->wordSetRepository->findBy(['telegramUser' => $user = $context->getEffectiveUser()->getId()]);
        if (empty($wordSetList)) {
            $this->onEmptyWordset($context);

            return;
        }
        
        foreach ($wordSetList as $wordSet) {
            $inlineKeyboard[] = [
                [
                    'callback_data' => $this->prepareCallbackData('learnWordset', ['wordsetId' => $wordSet->getId()]),
                    'text' => "{$wordSet->getTitle()}: {$wordSet->getWordInLearns()->count()}",
                ]
            ];
        }
        $context->sendMessage('Что будем учить?', [
            'reply_markup' => [
                'inline_keyboard' => $inlineKeyboard
            ],
            'parse_mode' => 'HTML',
        ]);
    }

    /**
     * @param Context $context
     */
    public function listWordsets(Context $context): void
    {
        $inlineKeyboard = [];
        $wordSetList = $this->wordSetRepository->findBy(['telegramUser' => $user = $context->getEffectiveUser()->getId()]);
        if (empty($wordSetList)) {
            $this->onEmptyWordset($context);

            return;
        }
        
        foreach ($wordSetList as $wordSet) {
            $inlineKeyboard[] = [
                [
                    'callback_data' => $this->prepareCallbackData('onWordSet', ['wordsetId' => $wordSet->getId()]),
                    'text' => "{$wordSet->getTitle()}: {$wordSet->getWordInLearns()->count()}",
                ]
            ];
        }
        $context->sendMessage('Мои словари:', [
            'reply_markup' => [
                'inline_keyboard' => $inlineKeyboard
            ],
            'parse_mode' => 'HTML',
        ]);
    }
    
    /**
     * @param Context $context
     */
    public function popularWordSets(Context $context): void
    {
        $inlineKeyboard = [];
        $collections = $this->wordsetCollectionRepository->findAll();
        
        foreach ($collections as $wordSetTopCollection) {
            foreach ($wordSetTopCollection->getWordSets() as $wordSet) {
                
                $inlineKeyboard[] = [
                    [
                        'callback_data' => $this->prepareCallbackData('onPopularWordSet', ['wordsetId' => $wordSet->getId()]),
                        'text' => "{$wordSet->getTitle()}: {$wordSet->getWordInLearns()->count()}",
                    ]
                ];
            }
            $context->sendMessage("{$wordSetTopCollection->getTitle()}:", [
                'reply_markup' => [
                    'inline_keyboard' => $inlineKeyboard
                ],
                'parse_mode' => 'HTML',
            ]);
        }
        
    }

    /**
     * @param Context $context
     *
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function createWordSet(Context $context): void
    {
        $context->sendMessage('Введите название для нового словаря:');
        $context->nextStep(function (Context $context) {
            $title = $context->getMessage()->getText();
            if ($this->isCommand($title)) {
                $context->endConversation();
                
                return;
            }
            $wordSet = $this->wordSetRepository->findOneBy(['title' => $title, 'telegramUser' => $user = $context->getEffectiveUser()->getId()]);
            if ($wordSet) {
                $context->sendMessage("Список слов: <i>{$title}</i> уже существует!", ['parse_mode' => 'HTML']);
            } else {
                $wordSet = new WordSet();
                $wordSet->setTitle($title);
                $user = $context->getEffectiveUser();
                $telegramUser = $this->telegramUserRepository->saveTelegramUser($user);
                $wordSet->setTelegramUser($telegramUser);
                $this->entityManager->persist($wordSet);
                $this->entityManager->flush();
                $context->sendMessage("Список слов: <i>{$wordSet->getTitle()}</i> создан!", ['parse_mode' => 'HTML']);
            }
            $context->endConversation();
        });
    }

    /**
     * @return array{keyboard:array, resize_keyboard:bool}
     */
    protected function getMenuReplyMarkup(): array
    {
        return [
            'keyboard' => [
                [
                    ['text' => $this->menuCommands['/search']],
                    ['text' => $this->menuCommands['/learn']],
                ],
                [
                    ['text' => $this->menuCommands['/list_wordsets']],
                    ['text' => $this->menuCommands['/create_wordset']],
                ],
                [
                    ['text' => $this->menuCommands['/popular_wordsets']],
                ],
            ],
            'resize_keyboard' => true
        ];
    }

    /**
     * @param string $text
     *
     * @return bool
     */
    protected function isCommand(string $text): bool
    {   
        return isset($this->menuCommands[$text]) || in_array($text, $this->menuCommands, true);
    }

    /**
     * @param Context $context
     * @param array   $params
     *
     * @psalm-param array{wordsetId:int} $params
     */
    public function onWordSet(Context $context, array $params)
    {
        $wordSet = $this->wordSetRepository->find($params['wordsetId']);
        $options = [
            'parse_mode' => 'HTML',
            'caption' => $wordSet->getTitle(),
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        [
                            'callback_data' => $this->prepareCallbackData('onChangeWordSetImage', $params),
                            'text' => "Редактировать обложку",
                        ],
                    ],
                    [

                        [
                            'callback_data' => $this->prepareCallbackData('onAddWords', $params),
                            'text' => "Добавить слова",
                        ]
                    ],
                    [

                        [
                            'callback_data' => $this->prepareCallbackData('showWordSetList', $params),
                            'text' => "Показать слова",
                        ]
                    ],
                    [
                        [
                            'callback_data' => $this->prepareCallbackData('learnWordset', array_merge($params, ['editMedia' => true])),
                            'text' => "Учить слова",
                        ],
                    ],
                ]
            ],
        ];
        
        $this->sendWordsetPhoto($context, $wordSet, $options);
    }    
    
    
    /**
     * @param Context $context
     * @param array   $params
     *
     * @psalm-param array{wordsetId:int} $params
     */
    public function onPopularWordSet(Context $context, array $params)
    {
        $wordSet = $this->wordSetRepository->find($params['wordsetId']);
        $options = [
            'parse_mode' => 'HTML',
            'caption' => $wordSet->getTitle(),
            'reply_markup' => [
                'inline_keyboard' => [
                    [

                        [
                            'callback_data' => $this->prepareCallbackData('showWordSetList', $params),
                            'text' => "Показать слова",
                        ]
                    ],
                    [
                        [
                            'callback_data' => $this->prepareCallbackData('savePopularWordset', ['wordsetId' => $params['wordsetId']]),
                            'text' => "Сохранить к себе",
                        ],
                    ],
                ]
            ],
        ];
        
        $this->sendWordsetPhoto($context, $wordSet, $options);
    }

    public function savePopularWordset(Context $context, array $params)
    {
        $topWordSet = $this->wordSetRepository->find($params['wordsetId']);

        /** @var \Zanzara\Telegram\Type\User $user */
        $user = $context->getEffectiveUser();
        
        $alreadySavedWordset = $this->wordSetRepository->findOneBy(['telegramUser' => $user->getId(), 'title' => $topWordSet->getTitle()]);
        if ($alreadySavedWordset) {
            $context->sendMessage("Словарь: <i>{$alreadySavedWordset->getTitle()}</i> уже существует!", ['parse_mode' => 'HTML']);
            $context->endConversation();
            
            return;
        }
        
        $wordSet = new WordSet();
        $wordSet->setTitle($topWordSet->getTitle());
        $wordSet->setDescription($topWordSet->getDescription());
        $wordSet->setImage($topWordSet->getImage());
        $this->entityManager->persist($wordSet);
        
        $wordsInLearn = $topWordSet->getWordInLearns();
        foreach ($wordsInLearn as $wordInLearn) {
            $newWordInLearn = new WordInLearn();
            $newWordInLearn->setMeaning($wordInLearn->getMeaning());
            $newWordInLearn->setWordSet($wordSet);
            $newWordInLearn->setScore(0);
            $this->entityManager->persist($newWordInLearn);
            $wordSet->addWordToLearn($newWordInLearn);
        }
        
        
        $telegramUser = $this->telegramUserRepository->saveTelegramUser($user);
        $wordSet->setTelegramUser($telegramUser);
        $this->entityManager->persist($wordSet);
        $this->entityManager->flush();
        $context->sendMessage("Словарь: <i>{$wordSet->getTitle()}</i> успешно сохранён!", ['parse_mode' => 'HTML']);
    }

    protected function sendWordsetPhoto(Context $context, WordSet $wordSet, array $options)
    {
        $file = $wordSet->getFileId();
        $saveFileId = false;
        if (!$file) {
            $saveFileId = true;
            $file = $wordSet->getImage() ?: new InputFile(self::NO_IMAGE_FILE_PATH);
        }
        
        $context->sendPhoto($file, $options)->then(function (Message $message) use ($context, $wordSet, $saveFileId) {
            if ($saveFileId) {
                $this->saveFileIdFromMessage($context, $message, $wordSet);
            }
        }, function ($error) use ($context, $options, $wordSet) {
            $this->logger->error($error);
            $context->sendPhoto($wordSet->getImage(), $options)->then(function (Message $message) use ($context, $wordSet) {
                $this->saveFileIdFromMessage($context, $message, $wordSet);
            });
        });
    }

    protected function saveFileIdFromMessage(Context $context, Message $message, WordSet $wordSet)
    {
        $photos = $message->getPhoto();
        if ($photos) {
            $largesPhoto = end($photos);
            $context->getFile($largesPhoto->getFileId())->then(function (\Zanzara\Telegram\Type\File\File $file) use ($wordSet) {
                $this->wordSetRepository->saveWordsetImage($wordSet->getId(), $file);
            });
        }
    }

    /**
     * @param Context $context
     * @param array   $params
     *
     * @psalm-param array{wordsetId:int, editMedia?:bool} $params
     */
    public function learnWordset(Context $context, array $params)
    {
        $wordSet = $this->wordSetRepository->find($params['wordsetId']);
        if ($wordSet->getWordInLearns()->count() === 0) {
            $context->sendMessage('В словаре нет слов!');
            $context->endConversation();

            return;
        }
        $wordsInLearn = $wordSet->getWordsInLearnProgress();
        $totalCount = $wordsInLearn->count();
        if ($totalCount === 0) {
            $context->sendMessage("Молодец, все слова из списка <i>{$wordSet->getTitle()}</i> выучены!", ['parse_mode' => 'HTML']);
            
            return;
        }
        $offsetKey = "learnWordset_{$params['wordsetId']}";
        $offset = $this->cache->get($offsetKey);
        if (!$offset || $offset >= $totalCount) {
            $offset = 0;
        }
        
        $wordsInLearnChunk = $wordsInLearn->slice($offset);
        
        $editMedia = $params['editMedia'] ?? false;
        
        /** @var $wordInLearn WordInLearn */
        foreach ($wordsInLearnChunk as $wordInLearn) {
            $meaning = $wordInLearn->getMeaning();
            $inlineKeyboard = [
                [
                    [
                        'callback_data' => $this->prepareCallbackData('onRemember', ['wordInLearnId' => $wordInLearn->getId(), 'wordsetId' => $params['wordsetId']]),
                        'text' => 'Помню',
                    ],
                    [
                        'callback_data' => $this->prepareCallbackData('onForgot', ['wordInLearnId' => $wordInLearn->getId(), 'wordsetId' => $params['wordsetId']]),
                        'text' => 'Не помню',
                    ],
                ],
            ];
            $page = $offset + 1;
            $caption = "{$page}/{$totalCount}

<b>{$meaning->getText()}</b>";
            $this->sendMeaningPhoto($context, $meaning, $inlineKeyboard, $caption, $editMedia);
            $this->cache->set($offsetKey, ++$offset);
            break;
        }
    }

    /**
     * @param Context $context
     * @param array   $params
     *
     * @psalm-param array{wordInLearnId:int, wordsetId:int, editMedia?:bool} $params
     */
    public function onRemember(Context $context, array $params)
    {
        $this->wordInLearnRepository->increaseScore($params['wordInLearnId'], +1);
        $params['editMedia'] = true;
        $this->learnWordset($context, $params);
    }

    /**
     * @param Context $context
     * @param array   $params
     *
     * @psalm-param array{wordInLearnId:int, wordsetId:int} $params
     */
    public function onForgot(Context $context, array $params)
    {
        $wordInLearn = $this->wordInLearnRepository->increaseScore($params['wordInLearnId'], -1);
        $meaning = $wordInLearn->getMeaning();
        $inlineKeyboard = [
            [
                [
                    'callback_data' => $this->prepareCallbackData('learnWordset', ['wordsetId' => $params['wordsetId'], 'editMedia' => true]),
                    'text' => 'Дальше',
                ],
            ],
        ];
        $this->sendMeaningPhoto($context, $meaning, $inlineKeyboard, null, true);
        //$this->sendMeaningAudio($context, $meaning);
        $context->endConversation();
    }

    /**
     * @param Context $context
     * @param array   $params
     *
     * @psalm-param array{wordsetId:int} $params
     */
    public function showWordSetList(Context $context, array $params): void
    {
        $wordSet = $this->wordSetRepository->find($params['wordsetId']);
        $wordsInLearn = $wordSet->getWordInLearns();
        
        if ($wordsInLearn->count() === 0) {
            $context->sendMessage('В словаре нет слов!');
            $context->endConversation();
            
            return;
        }

        $inlineKeyBoard = [];
        foreach ($wordsInLearn as $wordInLearn) {
            $meaning = $wordInLearn->getMeaning();
            $inlineKeyBoard[] = [
                [
                    'callback_data' => $this->prepareCallbackData('onMeaning', ['meaningId' => $meaning->getId()]),
                    'text' => $meaning->getText(),
                ]
            ];
        }
        $context->endConversation();

        $this->showPaging($context, ['data' => $inlineKeyBoard]);
    }

    /**
     * @param Context $context
     * @param array   $params
     *
     * @psalm-param array{meaningId:int} $params
     */
    public function onMeaning(Context $context, array $params): void
    {
        $meaning = $this->meaningRepository->find($params['meaningId']);
        $inlineKeyboard = [
            [
                [
                    'callback_data' => $this->prepareCallbackData('showWordSetsToAdd', ['meaningId' => $meaning->getId()]),
                    'text' => "Добавить в словарь",
                ],
            ],
        ];
        $this->sendMeaningPhoto($context, $meaning, $inlineKeyboard);
        $this->sendMeaningAudio($context, $meaning);
        $context->endConversation();
    }

    /**
     * @param Context $context
     *
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function onAddWords(Context $context): void
    {
        $context->sendMessage('Введите слово');
        $context->nextStep([$this, 'searchWord']);
    }

    /**
     * @param Context $context
     *
     * @throws \App\Libraries\Net\Exceptions\CurlException
     */
    public function searchWord(Context $context): void
    {
        $message = $context->getMessage();
        if (!$message) {
            $context->endConversation();

            return;
        }
        $word = $message->getText();

        $searchResponse = $this->dictionaryApi->searchWords($word);
        
        if (empty($searchResponse)) {
            $context->sendMessage("По запросу <i>{$word}</i> ничего не найдено!", ['parse_mode' => 'HTML']);
            
            return;
        }
        
        $wordsByIds = [];
        foreach ($searchResponse as $word) {
            $wordsByIds[$word['id']] = $word;
        }
        
        $searchResult = [];
        foreach ($wordsByIds as $wordId => $word) {
            $searchResult[] = [
                [
                    'callback_data' => $this->prepareCallbackData('onWord', ['word_external_id' => $wordId, 'words' => $wordsByIds]),
                    'text' => $word['text'],
                ]
            ];
        }
        
        $context->endConversation();
        
        $this->showPaging($context, ['data' => $searchResult]);
    }

    /**
     * @param Context $context
     * @param array   $params
     *
     * @throws \App\Libraries\Net\Exceptions\CurlException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @psalm-param array{words:array,word_external_id:int} $params
     */
    public function onWord(Context $context, array $params): void
    {
        $words = $params['words'];
        $word = $words[$params['word_external_id']];
        $wordEntity = $this->englishWordRepository->save($word);
        $meaningIds  = array_column($word['meanings'], 'id');
        
        $meanings = $this->dictionaryApi->getMeanings($meaningIds);
        $meanings = array_slice($meanings, 0, $this->config['maxMeanings']);
        
        foreach ($meanings as $meaningData) {
            $meaning = $this->meaningRepository->save($meaningData, $wordEntity);
            $inlineKeyboard = [
                [
                    [
                        'callback_data' => $this->prepareCallbackData('showWordSetsToAdd', ['meaningId' => $meaning->getId()]),
                        'text' => "Добавить в словарь",
                    ],
                ],
            ];
            $this->sendMeaningPhoto($context, $meaning, $inlineKeyboard);
            $this->sendMeaningAudio($context, $meaning);
        }
        
        $context->endConversation();
    }

    /**
     * @param Context     $context
     * @param Meaning     $meaning
     * @param array       $inlineKeyboard
     * @param string|null $caption
     * @param bool        $edit
     */
    protected function sendMeaningPhoto(Context $context, Meaning $meaning, array $inlineKeyboard, string $caption = null, bool $edit = false): void
    {
        $photoUrl = $meaning->getFirstImageByPrams(50);
        $fileId = $this->telegramFileCache->getFileId($photoUrl);
        $photo = $fileId ?: $photoUrl;
        $caption = $caption ?? $this->getMeaningCaption($meaning);
        
        $this->sendPhoto($context, $inlineKeyboard, $photo, $caption, $edit)->then(function (Message $message) use ($photoUrl) {
            $this->storeFileIdByMessage($message, $photoUrl);
        }, function ($error) use ($context, $inlineKeyboard, $meaning, $caption, $edit) {
            $this->logger->error($error);
            $photoUrlLowerSize = $meaning->getFirstImageByPrams(1);
             $this->sendPhoto($context, $inlineKeyboard, $photoUrlLowerSize, $caption, $edit)->then(function (Message $message) use ($photoUrlLowerSize) {
                 $this->storeFileIdByMessage($message, $photoUrlLowerSize);
            }, function ($error) {
                $this->logger->error($error);
            });
        });
    }

    /**
     * @param Message $message
     * @param string  $photoUrl
     */
    protected function storeFileIdByMessage(Message $message, string $photoUrl): void
    {
        $photos = $message->getPhoto();
        $largestPhoto = end($photos);
        $this->telegramFileCache->storeFileId($photoUrl, $largestPhoto->getFileId());
    }

    /**
     * @param Context $context
     * @param Meaning $meaning
     */
    protected function sendMeaningAudio(Context $context, Meaning $meaning): void
    {
        $this->sendAudio($context, $meaning->getSoundUrl(), [
            'caption' => $meaning->getTranscription(),
            'performer' => $meaning->getText(),
            'title' => $meaning->getTranslation()['text'],
            'parse_mode' => 'HTML',
        ]);
    }

    /**
     * @param Meaning $meaning
     *
     * @return string
     */
    protected function getMeaningCaption(Meaning $meaning): string
    {
        return "<b>{$meaning->getText()}</b> - <i>{$meaning->getTranslation()['text']}</i>
[{$meaning->getTranscription()}]

{$meaning->getDefinition()['text']}
";
    }

    /**
     * @param Context $context
     * @param array   $params
     */
    public function showWordSetsToAdd(Context $context, array $params): void
    {
        $wordSetList = $this->wordSetRepository->findBy(['telegramUser' => $user = $context->getEffectiveUser()->getId()]);
        if (empty($wordSetList)) {
            $this->onEmptyWordset($context);
            
            return;
        }

        $inlineKeyboard = [];
        foreach ($wordSetList as $wordSet) {
            $inlineKeyboard[] = [
                [
                    'callback_data' => $this->prepareCallbackData('addMeaningToWordSet', ['meaningId' => $params['meaningId'], 'wordSetId' => $wordSet->getId()]),
                    'text' => "{$wordSet->getTitle()}: {$wordSet->getWordInLearns()->count()}",
                ]
            ];
        }
        $context->sendMessage('Куда добавить?', [
            'reply_markup' => [
                'inline_keyboard' => $inlineKeyboard
            ],
            'parse_mode' => 'HTML',
        ]);
    }

    public function onEmptyWordset(Context $context)
    {
        $inlineKeyboard = [
            [
                [
                    'callback_data' => $this->prepareCallbackData('createWordSet'),
                    'text' => 'Да',
                ],
                [
                    'callback_data' => $this->prepareCallbackData('endConversation', ['message' => 'Ok']),
                    'text' => 'Не надо',
                ],
            ],
            [
                [
                    'callback_data' => $this->prepareCallbackData('popularWordSets'),
                    'text' => 'Популярные словари',
                ],
            ]

        ];
        $context->sendMessage('У вас нет словарей, создать?', [
            'reply_markup' => [
                'inline_keyboard' => $inlineKeyboard
            ],
            'parse_mode' => 'HTML',
        ]);
    }

    public function endConversation(Context $context, array $params)
    {
        if (!empty($params['message'])) {
            $context->sendMessage($params['message']);
        }
        $context->endConversation();
    }

    /**
     * @param Context $context
     * @param array   $params
     *
     * @psalm-param array{meaningId:int, wordSetId:int} $params
     */
    public function addMeaningToWordSet(Context $context, array $params): void
    {
        $wordInLearn = $this->wordInLearnRepository->findOneBy(['meaning' => $params['meaningId'], 'wordSet' => $params['wordSetId']]);
        
        if ($wordInLearn) {
            $meaning = $wordInLearn->getMeaning();
            $wordSet = $wordInLearn->getWordSet();
            $context->sendMessage("Слово {$meaning->getText()} уже есть в <i>{$wordSet->getTitle()}</i>", ['parse_mode' => 'HTML']);
            $context->endConversation();
            
            return;
        }
        
        $wordSet = $this->wordSetRepository->find($params['wordSetId']);
        $meaning = $this->meaningRepository->find($params['meaningId']);
        $wordInLearn = new WordInLearn();
        $wordInLearn->setWordSet($wordSet);
        $wordInLearn->setMeaning($meaning);
        $this->entityManager->persist($wordInLearn);
        $this->entityManager->flush();
        $this->entityManager->refresh($wordSet);
        
        $context->sendMessage("{$meaning->getText()} Добавлено в <i>{$wordSet->getTitle()}</i>", ['parse_mode' => 'HTML']);
        $context->endConversation();
    }

    /**
     * @param Context $context
     * @param array   $params
     *
     * @psalm-param array{wordsetId:int} $params
     *                       
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function onChangeWordSetImage(Context $context, array $params): void
    {
        $context->sendMessage('Отправьте новую обложку');
        
        $context->nextStep(function (Context $context) use ($params) {
            $photos = $context->getMessage()->getPhoto();
            if ($photos) {
                $largesPhoto = end($photos);
                $context->getFile($largesPhoto->getFileId())->then(function (\Zanzara\Telegram\Type\File\File $file) use ($context, $params) {
                    $saved = $this->wordSetRepository->saveWordsetImage($params['wordsetId'], $file);
                    if ($saved) {
                        $context->sendMessage('Обложка успешно обновлена');
                    } else {
                        $context->sendMessage('Ошибка, попробуйте позже');
                    }
                    $context->endConversation();
                });
                
            } else {
                $context->sendMessage('Не поддерживаемый формат!');
                $context->endConversation();
            }
        });
    }
}