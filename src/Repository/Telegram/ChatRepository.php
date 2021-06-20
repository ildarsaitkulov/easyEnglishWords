<?php

namespace App\Repository\Telegram;

use App\Entity\Telegram\Chat;
use App\Entity\Telegram\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * @method Chat|null find($id, $lockMode = null, $lockVersion = null)
 * @method Chat|null findOneBy(array $criteria, array $orderBy = null)
 * @method Chat[]    findAll()
 * @method Chat[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChatRepository extends ServiceEntityRepository
{

    /**
     * @var LoggerInterface 
     */
    protected $logger;
    
    public function __construct(ManagerRegistry $registry, LoggerInterface $logger)
    {
        parent::__construct($registry, Chat::class);
        $this->logger = $logger;
    }

    public function saveChat(\Zanzara\Telegram\Type\Chat $chat, User $telegramUser)
    {
        $telegramChat = $this->find($chat->getId());
        if ($telegramChat) {
            return $telegramChat;
        }
        
        $telegramChat = new Chat();
        $telegramChat->setInitiator($telegramUser);
        $telegramChat->setId($chat->getId());
        $telegramChat->setType($chat->getType());
        $telegramChat->setTitle($chat->getTitle());
        $telegramChat->setUsername($chat->getUsername());
        $telegramChat->setFirstName($chat->getFirstName());
        $telegramChat->setLastName($chat->getLastName());
        $telegramChat->setChatIdUpdated(false);
        $telegramChat->setRemoved(false);
        $telegramChat->setRemoveReason('');

        $em = $this->getEntityManager();
        $em->persist($telegramChat);
        $em->flush();
        
        $this->logger->info("Chat {$telegramChat->getId()} saved to db");

        return $telegramChat;
    }

    public function removeChatByChatId(int $chatId, string $reason, bool $onlyMark = true)
    {
        $telegramChat = $this->find($chatId);
        if (!$telegramChat) {
            return false;
        }
        $em = $this->getEntityManager();
        $em->persist($telegramChat);
        try {
            if ($onlyMark) {
                $telegramChat->setRemoved(true);
                $telegramChat->setRemoveReason($reason);
                $em->flush();
                
            } else {
                $em->remove($telegramChat);
            }

        } catch (\Exception $exception) {
            $this->logger->error("[Error] on removing telegramChat from db {$exception->getMessage()}");

            return false;
        }
        $this->logger->info("Chat {$chatId} removed from database");

        return true;
    }

    // /**
    //  * @return Chat[] Returns an array of Chat objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Chat
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}


