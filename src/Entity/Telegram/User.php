<?php

namespace App\Entity\Telegram;

use App\Entity\EasyEnglishWords\WordSet;
use App\Entity\EasyEnglishWords\WordSetList;
use App\Entity\Telegram\Chat as TelegramChat;
use App\Repository\Telegram\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(name="`tg_user`")
 */
class User
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     */
    private $id;

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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $languageCode;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\User", inversedBy="telegramUser", cascade={"persist", "remove"})
     */
    private $user;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Telegram\Chat", mappedBy="initiator", orphanRemoval=true)
     */
    private $telegramChats;

    /**
     * @ORM\OneToMany(targetEntity=WordSet::class, mappedBy="telegramUser")
     */
    private $wordSets;
    
    
    public function __construct()
    {
        $this->telegramChats = new ArrayCollection();
        $this->wordSets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function setId(int $id): self
    {
        $this->id = $id;
        
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

    public function getUser(): ?\App\Entity\User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection|TelegramChat[]
     */
    public function getTelegramChats(): Collection
    {
        return $this->telegramChats;
    }

    public function addTelegramChat(TelegramChat $telegramChat): self
    {
        if (!$this->telegramChats->contains($telegramChat)) {
            $this->telegramChats[] = $telegramChat;
            $telegramChat->setInitiator($this);
        }

        return $this;
    }

    public function removeTelegramChat(TelegramChat $telegramChat): self
    {
        if ($this->telegramChats->contains($telegramChat)) {
            $this->telegramChats->removeElement($telegramChat);
            // set the owning side to null (unless already changed)
            if ($telegramChat->getInitiator() === $this) {
                $telegramChat->setInitiator(null);
            }
        }

        return $this;
    }

    public function getLanguageCode(): ?string
    {
        return $this->languageCode;
    }

    public function setLanguageCode(?string $languageCode): self
    {
        $this->languageCode = $languageCode;

        return $this;
    }

    /**
     * @return Collection|WordSet[]
     */
    public function getWordSets(): Collection
    {
        return $this->wordSets;
    }

    public function addWordSet(WordSet $wordSet): self
    {
        if (!$this->wordSets->contains($wordSet)) {
            $this->wordSets[] = $wordSet;
            $wordSet->setTelegramUser($this);
        }

        return $this;
    }

    public function removeWordSet(WordSet $wordSet): self
    {
        if ($this->wordSets->removeElement($wordSet)) {
            // set the owning side to null (unless already changed)
            if ($wordSet->getTelegramUser() === $this) {
                $wordSet->setTelegramUser(null);
            }
        }

        return $this;
    }
}
