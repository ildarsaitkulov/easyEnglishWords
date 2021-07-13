<?php

namespace App\Command\EasyEnglishWords;

use App\Entity\EasyEnglishWords\WordInLearn;
use App\Entity\EasyEnglishWords\WordSet;
use App\Entity\EasyEnglishWords\WordsetCollection;
use App\Libraries\Skyeng\DictionaryApi;
use App\Libraries\Skyeng\WordsApi;
use App\Repository\EasyEnglishWords\EnglishWordRepository;
use App\Repository\EasyEnglishWords\MeaningRepository;
use App\Repository\EasyEnglishWords\WordsetCollectionRepository;
use App\Repository\EasyEnglishWords\WordSetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportWordSetCollections extends Command
{
    public const COMPILATION_TOP_ID = 47;
    
    protected static $defaultName = 'EasyEnglishWords:ImportWordSetCollections';
    protected static $defaultDescription = 'Add a short description for your command';
    
    protected EntityManagerInterface $entityManager;
    protected WordsetCollectionRepository $wordsetCollectionRepository;
    protected WordSetRepository $wordSetRepository;
    protected EnglishWordRepository $englishWordRepository;
    protected MeaningRepository $meaningRepository;
    protected LoggerInterface $logger;
    protected WordsApi $wordsApi;

    public function __construct(string $name = null, WordsetCollectionRepository $wordsetCollectionRepository, EntityManagerInterface $entityManager, WordSetRepository $wordSetRepository, LoggerInterface $logger, MeaningRepository $meaningRepository, EnglishWordRepository $englishWordRepository)
    {
        parent::__construct($name);

        $this->entityManager = $entityManager;
        $this->wordsetCollectionRepository = $wordsetCollectionRepository;
        $this->wordSetRepository = $wordSetRepository;
        $this->meaningRepository = $meaningRepository;
        $this->englishWordRepository = $englishWordRepository; 
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this->setDescription(self::$defaultDescription)
             ->addOption('login',null, InputOption::VALUE_REQUIRED)
             ->addOption('password', null, InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $login = $input->getOption('login');
        $password = $input->getOption('password');
        
        if (!$login || !$password) {
            throw new \InvalidArgumentException('please pass --login, --password');
        }
        
        $this->wordsApi = new WordsApi($login, $password);
        $this->wordsApi->login();
        if (!$this->wordsApi->authorized()) {
            $this->logger->error('Error on authorization!');
            exit;
        }
        $this->logger->info('Authorized!');
        
        $this->saveCompilationById(self::COMPILATION_TOP_ID);
        
        
        
        return 0;
    }

    protected function saveCompilationById(int $compilationId)
    {
        $compilation = $this->wordsApi->getCompilation($compilationId);

        $this->saveCompilation($compilation);
    }

    protected function saveCompilation(array $compilation)
    {
        $wordsetCollection = new WordsetCollection();
        $wordsetCollection->setTitle($compilation['compilation']['title']);
        $wordsetCollection->setDescription($compilation['compilation']['attributes']['subtitle']);
        $this->entityManager->persist($wordsetCollection);

        $dictionaryApi = new DictionaryApi($this->logger);
        
        $totalCount = count($compilation['wordsets']['data']);
        $this->logger->debug("Total word sets count {$totalCount}");

        $i = 0;
        foreach ($compilation['wordsets']['data'] as $wordset) {
            $i++;
            $this->logger->debug("Processed {$i}/$totalCount");
            
            $newWordSet = new WordSet();
            $newWordSet->setTitle($wordset['title']);
            $newWordSet->setImage($wordset['imageUrl']);
            $this->entityManager->persist($newWordSet);
            $wordsetCollection->addWordSet($newWordSet);

            $wordsetFull = $this->wordsApi->getWordset($wordset['id']);
            $meaningIds = array_column($wordsetFull['words'], 'meaningId');

            $result = $dictionaryApi->getMeanings($meaningIds);

            foreach ($result as $meaning) {
                $word = ['id' => $meaning['wordId'], 'text' => $meaning['text']];
                $wordEntity = $this->englishWordRepository->save($word);
                $meaning = $this->meaningRepository->save($meaning, $wordEntity);

                $wordInLearn = new WordInLearn();
                $wordInLearn->setMeaning($meaning);
                $wordInLearn->setWordSet($newWordSet);
                $this->entityManager->persist($wordInLearn);

                $newWordSet->addWordToLearn($wordInLearn);
                $this->entityManager->flush();
            }

        }
        $this->entityManager->flush();
    }
}
