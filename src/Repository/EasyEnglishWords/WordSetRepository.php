<?php

namespace App\Repository\EasyEnglishWords;

use App\Command\EasyEnglishWords\EasyEnglishWords;
use App\Entity\EasyEnglishWords\WordSet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * @method WordSet|null find($id, $lockMode = null, $lockVersion = null)
 * @method WordSet|null findOneBy(array $criteria, array $orderBy = null)
 * @method WordSet[]    findAll()
 * @method WordSet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WordSetRepository extends ServiceEntityRepository
{

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    public function __construct(ManagerRegistry $registry, LoggerInterface $logger)
    {
        parent::__construct($registry, WordSet::class);
        $this->logger = $logger;
    }
    
    public function saveWordsetImage(int $wordsetId, \Zanzara\Telegram\Type\File\File $file): bool
    {
        $parts = explode('.', $file->getFilePath());
        $imageFormat = end($parts);
        $newFilePath = 'file://' . EasyEnglishWords::IMAGES_DIR . "{$file->getFileId()}.{$imageFormat}";
        if (copy($file->getFilePath(), EasyEnglishWords::IMAGES_DIR . "{$file->getFileId()}.{$imageFormat}")) {
            $entityManager = $this->getEntityManager();
            $wordSet = $this->find($wordsetId);
            $wordSet->setImage($newFilePath);
            $entityManager->persist($wordSet);
            $entityManager->flush();
            $entityManager->refresh($wordSet);

            return true;
        }
        
        $this->logger->error("Error on copying image from: {$file->getFilePath()} to {$newFilePath}");
        
        return false;
    }

    // /**
    //  * @return WordSet[] Returns an array of WordSet objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('w.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?WordSet
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
