<?php

namespace App\Repository\Podcast;

use App\Entity\Podcast\EpisodeSendingJournal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EpisodeSendingJournal|null find($id, $lockMode = null, $lockVersion = null)
 * @method EpisodeSendingJournal|null findOneBy(array $criteria, array $orderBy = null)
 * @method EpisodeSendingJournal[]    findAll()
 * @method EpisodeSendingJournal[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EpisodeSendingJournalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EpisodeSendingJournal::class);
    }

    // /**
    //  * @return EpisodeSendingJournal[] Returns an array of EpisodeSendingJournal objects
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
    public function findOneBySomeField($value): ?EpisodeSendingJournal
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
