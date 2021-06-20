<?php

namespace App\Repository\EasyEnglishWords;

use App\Entity\EasyEnglishWords\EnglishWord;
use App\Entity\EasyEnglishWords\Meaning;
use App\Libraries\SimpleHydrator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Meaning|null find($id, $lockMode = null, $lockVersion = null)
 * @method Meaning|null findOneBy(array $criteria, array $orderBy = null)
 * @method Meaning[]    findAll()
 * @method Meaning[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MeaningRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Meaning::class);
    }

    /**
     * @param array       $meaningData
     * @param EnglishWord $word
     *
     * @return Meaning
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(array $meaningData, EnglishWord $word): Meaning
    {
        $meaning = $this->findOneBy(['externalId' => $meaningData['id']]);
        if (!$meaning) {
            $meaning = new Meaning();
            SimpleHydrator::fillOut($meaning, $meaningData, [], ['updatedAt' => function($updatedAt) {
                return new \DateTime($updatedAt);
            }, 'soundUrl' => function ($soundUrl) {
                if (strpos($soundUrl, '//') === 0) {
                    return "https:{$soundUrl}";
                }

                return $soundUrl;
            }]);
            $meaning->setExternalId($meaningData['id']);
            $meaning->setWord($word);
            $entityManager = $this->getEntityManager();
            $entityManager->persist($meaning);
            $entityManager->flush();
        }
        
        return $meaning;
    }

    // /**
    //  * @return Meaning[] Returns an array of Meaning objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Meaning
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
