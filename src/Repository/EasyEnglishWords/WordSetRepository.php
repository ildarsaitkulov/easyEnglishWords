<?php

namespace App\Repository\EasyEnglishWords;

use App\Entity\EasyEnglishWords\WordSet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WordSet|null find($id, $lockMode = null, $lockVersion = null)
 * @method WordSet|null findOneBy(array $criteria, array $orderBy = null)
 * @method WordSet[]    findAll()
 * @method WordSet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WordSetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WordSet::class);
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
