<?php

namespace App\Repository\EasyEnglishWords;

use App\Entity\EasyEnglishWords\WordsetCollection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WordsetCollection|null find($id, $lockMode = null, $lockVersion = null)
 * @method WordsetCollection|null findOneBy(array $criteria, array $orderBy = null)
 * @method WordsetCollection[]    findAll()
 * @method WordsetCollection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WordsetCollectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WordsetCollection::class);
    }

    // /**
    //  * @return WordsetCollection[] Returns an array of WordsetCollection objects
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
    public function findOneBySomeField($value): ?WordsetCollection
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
