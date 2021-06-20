<?php

namespace App\Entity\Telegram;

use App\Entity\Telegram\User as TelegramUser;
use App\Repository\Telegram\ChatRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ChatRepository::class)
 * @ORM\Table(name="`tg_chat`")
 */
class Chat
{
    public const TYPE_CHANNEL = 'channel';
    public const TYPE_GROUP = 'group';
    public const TYPE_SUPERGROUP = 'supergroup';
    public const TYPE_PRIVATE = 'private';

    public static $types = [
        self::TYPE_CHANNEL => self::TYPE_CHANNEL,
        self::TYPE_GROUP => self::TYPE_GROUP,
        self::TYPE_PRIVATE => self::TYPE_PRIVATE,
        self::TYPE_SUPERGROUP => self::TYPE_SUPERGROUP
    ];

    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Telegram\User", inversedBy="telegramChats")
     * @ORM\JoinColumn(nullable=false)
     */
    private $initiator;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lastName;
    


    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $removed;
    
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $chatIdUpdated;

    /**
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    private $removeReason;
    
    
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInitiator(): ?TelegramUser
    {
        return $this->initiator;
    }

    public function setInitiator(?TelegramUser $initiator): self
    {
        $this->initiator = $initiator;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        if (!isset(self::$types[$type])) {
            throw new \InvalidArgumentException("Invalid type {$type}");
        }

        $this->type = $type;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }
    
    public function getRemoved(): ?bool
    {
        return $this->removed;
    }

    public function setRemoved(?bool $removed): self
    {
        $this->removed = $removed;

        return $this;
    }

    public function getRemoveReason(): ?string
    {
        return $this->removeReason;
    }

    public function setRemoveReason(?string $removeReason): self
    {
        $this->removeReason = $removeReason;

        return $this;
    }

    public function getChatIdUpdated(): ?bool
    {
        return $this->chatIdUpdated;
    }

    public function setChatIdUpdated(?bool $chatIdUpdated): self
    {
        $this->chatIdUpdated = $chatIdUpdated;

        return $this;
    }
}
