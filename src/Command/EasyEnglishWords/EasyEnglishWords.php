<?php

namespace App\Command\EasyEnglishWords;

use App\Entity\EasyEnglishWords\EnglishWord;
use App\Entity\EasyEnglishWords\Meaning;
use App\Entity\EasyEnglishWords\WordInLearn;
use App\Entity\EasyEnglishWords\WordSet;
use App\Libraries\Skyeng\DictionaryApi;
use App\Command\TelegramBotBase;
use App\Repository\EasyEnglishWords\EnglishWordRepository;
use App\Repository\EasyEnglishWords\MeaningRepository;
use App\Repository\EasyEnglishWords\WordInLearnRepository;
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

    protected const IMAGES_DIR = APP_ROOT . '/public/EasyEnglishWords/images/';
    protected const NO_IMAGE_FILE_PATH =  self::IMAGES_DIR  . 'no-image-icon.png';


    protected array $config = [
        'telegramApiUrl' => 'http://127.0.0.1:8081',
        'telegramCachePath' => '/tmp/easyEnglishBotCache',
        'callback_ttl' => 86400 * 7,
    ];

    public function __construct(string $name = null, 
                                EntityManagerInterface $entityManager, 
                                LoggerInterface $logger,
                                EnglishWordRepository $englishWordRepository,
                                WordSetRepository $wordSetRepository,
                                MeaningRepository $meaningRepository,
                                WordInLearnRepository $wordInLearnRepository,
                                UserRepository $telegramUserRepository,
                                ChatRepository $chatRepository
    )
    {
        $this->config['botToken'] = $_ENV['EASY_ENGLISH_BOT_TOKEN'];
        $this->config['bot_username'] = $_ENV['EASY_ENGLISH_BOT_USERNAME'];
        
        $this->entityManager = $entityManager;
        $this->meaningRepository = $meaningRepository;
        $this->englishWordRepository = $englishWordRepository;
        $this->telegramUserRepository = $telegramUserRepository;
        $this->wordSetRepository = $wordSetRepository;
        $this->wordInLearnRepository = $wordInLearnRepository;
        $this->telegramChatRepository = $chatRepository;
        
        parent::__construct($logger, $name);
        $this->dictionaryApi = new DictionaryApi($this->logger);
    }

    protected function configure()
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->bot->getTelegram()->setMyCommands([
            [
                'command' => '/start',
                'description' => 'Старт'
            ],
            [
                'command' => '/create_wordset',
                'description' => 'Создать список слов'
            ],
            [
                'command' => '/list_wordset',
                'description' => 'Показать список слов'
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

            $context->sendMessage('Hi!hoho');

        });
        
        $this->bot->onCommand('create_wordset', function (Context $context) {
            $context->sendMessage('Введите название списка слов:');
            $context->nextStep(function (Context $context) {
                $title = $context->getMessage()->getText();
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
        });
        
        $this->bot->onCommand('list_wordset', function (Context $context) {
            $inlineKeyboard = [];
            $wordSetList = $this->wordSetRepository->findBy(['telegramUser' => $user = $context->getEffectiveUser()->getId()]);
            foreach ($wordSetList as $wordSet) {
                $inlineKeyboard[] = [
                    [
                        'callback_data' => $this->prepareCallbackData('onWordSet', ['wordsetId' => $wordSet->getId()]),
                        'text' => "{$wordSet->getTitle()}: {$wordSet->getWordInLearns()->count()}",
                    ]
                ];
            }
            $context->sendMessage('Наборы слов:', [
                'reply_markup' => [
                    'inline_keyboard' => $inlineKeyboard
                ],
                'parse_mode' => 'HTML',
            ]);
        });
        
        $this->bot->run();
        
        return 0;
    }

    public function onWordSet(Context $context, array $params)
    {
        $wordSet = $this->wordSetRepository->find($params['wordsetId']);
        
        $file = $wordSet->getFileId() ?: new InputFile(self::NO_IMAGE_FILE_PATH);
        
        $context->sendPhoto($file, [
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
                            'callback_data' => $this->prepareCallbackData('learnWordset', $params),
                            'text' => "Учить слова",
                        ],
                    ],
                ]
            ],
        ]);
    }

    public function learnWordset(Context $context, array $params)
    {
        $wordSet = $this->wordSetRepository->find($params['wordsetId']);
        $wordsInLearn = $wordSet->getWordInLearns();
        $offsetKey = "learnWordset_{$params['wordsetId']}";
        $offset = $this->cache->get($offsetKey) ?? 0;
        $wordsInLearnChunk = $wordsInLearn->slice($offset);

        if (count($wordsInLearnChunk) === 0) {
            $wordsInLearnChunk = $wordsInLearn->slice(0);
        }
        
        /** @var $wordInLearn WordInLearn */
        foreach ($wordsInLearnChunk as $key => $wordInLearn) {
            $meaning = $wordInLearn->getMeaning();
            $inlineKeyboard = [
                [
                    [
                        'callback_data' => $this->prepareCallbackData('learnWordset', $params),
                        'text' => 'Помню',
                    ],
                    [
                        'callback_data' => $this->prepareCallbackData('onForgot', ['wordInLearnId' => $wordInLearn->getId(), 'wordsetId' => $params['wordsetId']]),
                        'text' => 'Не помню',
                    ],
                ],
            ];
            $caption = "<b>{$meaning->getTranslation()['text']}</b>
Вспомните перевод";
            $this->sendMeaningPhoto($context, $meaning, $inlineKeyboard, $caption, true);
            $this->cache->set($offsetKey, ++$key);
            break;
        }
    }

    public function onForgot(Context $context, array $params)
    {
        $wordInLearn = $this->wordInLearnRepository->find($params['wordInLearnId']);
        $meaning = $wordInLearn->getMeaning();
        $oldScore = (int) $wordInLearn->getScore();
        $wordInLearn->setScore(--$oldScore);
        $this->entityManager->persist($wordInLearn);
        $this->entityManager->flush();
        $inlineKeyboard = [
            [
                [
                    'callback_data' => $this->prepareCallbackData('learnWordset', ['wordsetId' => $params['wordsetId']]),
                    'text' => 'Дальше',
                ],
            ],
        ];
        $this->sendMeaningPhoto($context, $meaning, $inlineKeyboard, null, true);
        //$this->sendMeaningAudio($context, $meaning);
        $context->endConversation();
    }

    public function showWordSetList(Context $context, array $params)
    {
        $wordSet = $this->wordSetRepository->find($params['wordsetId']);
        $wordsInLearn = $wordSet->getWordInLearns();

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

    public function onMeaning(Context $context, array $params)
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

    public function onAddWords(Context $context, array $params)
    {
        $context->sendMessage('Введите слово');
        $context->nextStep([$this, 'searchWord']);
    }

    public function searchWord(Context $context)
    {
        $word = $context->getMessage()->getText();

        $searchResponse = $this->dictionaryApi->searchWords($word);
        
        if (empty($searchResponse)) {
            $context->sendMessage("По запросу <i>{$word}</i> ничего не найдено!", ['parse_mode' => 'HTML']);
            $context->endConversation();
            
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

    public function onWord(Context $context, array $params)
    {
        $words = $params['words'];
        $word = $words[$params['word_external_id']];
        $wordEntity = $this->englishWordRepository->save($word);
        $meaningIds  = array_column($word['meanings'], 'id');
        
        $meanings = $this->dictionaryApi->getMeanings($meaningIds);
        
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

    protected function sendMeaningPhoto(Context $context, Meaning $meaning, array $inlineKeyboard, string $caption = null, bool $edit = false)
    {
        $photoUrl = $meaning->getFirstImage();
        $caption = $caption ?? $this->getMeaningCaption($meaning);
        $photoUrlLowerSize = $photoUrl . '?w=400&h=300&q=1';
        $this->sendPhoto($context, $inlineKeyboard, $photoUrl, $photoUrlLowerSize, $caption, $edit);
    }

    protected function sendMeaningAudio(Context $context, Meaning $meaning)
    {
        $this->sendAudio($context, $meaning->getSoundUrl(), [
            'caption' => $meaning->getTranscription(),
            'performer' => $meaning->getText(),
            'title' => $meaning->getTranslation()['text'],
            'parse_mode' => 'HTML',
        ]);
    }

    protected function getMeaningCaption(Meaning $meaning)
    {
        return "<b>{$meaning->getText()}</b> - <i>{$meaning->getTranslation()['text']}</i>
[{$meaning->getTranscription()}]

{$meaning->getDefinition()['text']}
";
    }

    public function showWordSetsToAdd(Context $context, array $params)
    {
        $inlineKeyboard = [];
        $wordSetList = $this->wordSetRepository->findBy(['telegramUser' => $user = $context->getEffectiveUser()->getId()]);
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

    public function addMeaningToWordSet(Context $context, array $params)
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
        
        $context->sendMessage("{$meaning->getText()} Добавлено в <i>{$wordSet->getTitle()}</i>", ['parse_mode' => 'HTML']);
        $context->endConversation();
    }

    public function onChangeWordSetImage(Context $context, array $params)
    {
        $context->sendMessage('Отправьте новую обложку');
        
        $context->nextStep(function (Context $context) use ($params) {
            $photos = $context->getMessage()->getPhoto();
            if ($photos) {
                $largesPhoto = end($photos);
                $context->getFile($largesPhoto->getFileId())->then(function (\Zanzara\Telegram\Type\File\File $file) use ($context, $params) {
                    $parts = explode('.', $file->getFilePath());
                    $imageFormat = end($parts);
                    $newFilePath = 'file://' . self::IMAGES_DIR . "{$file->getFileId()}.{$imageFormat}";
                    if (copy($file->getFilePath(), self::IMAGES_DIR . "{$file->getFileId()}.{$imageFormat}")) {
                        $wordSet = $this->wordSetRepository->find($params['wordsetId']);
                        $wordSet->setImage($newFilePath);
                        $this->entityManager->persist($wordSet);
                        $this->entityManager->flush();
                        $context->sendMessage('Обложка успешно обновлена');
                    } else {
                        $this->logger->error("Error on copying image from: {$file->getFilePath()} to {$newFilePath}");
                        
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