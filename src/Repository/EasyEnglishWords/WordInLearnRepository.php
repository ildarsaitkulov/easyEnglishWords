<?php

namespace App\Repository\EasyEnglishWords;

use App\Entity\EasyEnglishWords\WordInLearn;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WordInLearn|null find($id, $lockMode = null, $lockVersion = null)
 * @method WordInLearn|null findOneBy(array $criteria, array $orderBy = null)
 * @method WordInLearn[]    findAll()
 * @method WordInLearn[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WordInLearnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WordInLearn::class);
    }

    // /**
    //  * @return WordInLearn[] Returns an array of WordInLearn objects
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
    public function findOneBySomeField($value): ?WordInLearn
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
