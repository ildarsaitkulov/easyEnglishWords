<?php

namespace App\Repository\EasyEnglishWords;

use App\Entity\EasyEnglishWords\EnglishWord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EnglishWord|null find($id, $lockMode = null, $lockVersion = null)
 * @method EnglishWord|null findOneBy(array $criteria, array $orderBy = null)
 * @method EnglishWord[]    findAll()
 * @method EnglishWord[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EnglishWordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EnglishWord::class);
    }

    /**
     * @param array $wordData
     *
     * @return EnglishWord
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(array $wordData): EnglishWord
    {
        $word = $this->findOneBy(['external_id' => $wordData['id']]);
        if (!$word) {
            $word = new EnglishWord();
            $word->setExternalId($wordData['id']);
            $word->setText($wordData['text']);
            $entityManager = $this->getEntityManager();
            $entityManager->persist($word);
            $entityManager->flush();
        }
        
        return $word;
    }

    // /**
    //  * @return EnglishWord[] Returns an array of EnglishWord objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?EnglishWord
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
