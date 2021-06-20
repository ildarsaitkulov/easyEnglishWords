<?php


namespace App\Libraries;

use Doctrine\ORM\EntityManager;

class EntityManagerFactory
{

    public static function createEntityManager(EntityManager $entityManager): EntityManager
    {
        if (!$entityManager->isOpen()) {
            $entityManager = $entityManager::create($entityManager->getConnection(), $entityManager->getConfiguration());
        }
        
        
        return $entityManager;
    }

}