<?php

namespace App\Entity\EasyEnglishWords;

use App\Repository\EasyEnglishWords\WordSetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use \App\Entity\Telegram\User;

/**
 * @ORM\Entity(repositoryClass=WordSetRepository::class)
 */
class WordSet
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $image;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="wordSets")
     */
    private $telegramUser;

    /**
     * @ORM\OneToMany(targetEntity=WordInLearn::class, mappedBy="wordSet")
     */
    private $wordInLearns;

    public function __construct()
    {
        $this->wordInLearns = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getFileId()
    {
        if ($this->image && strpos($this->image, 'file://') === 0) {
            $parts = explode('/', $this->image);
            $last = end($parts);
            
            return explode('.', $last)[0];
        }
        
        return false;
    }

    public function getTelegramUser(): ?User
    {
        return $this->telegramUser;
    }

    public function setTelegramUser(?User $telegramUser): self
    {
        $this->telegramUser = $telegramUser;

        return $this;
    }

    /**
     * @return Collection|WordInLearn[]
     */
    public function getWordInLearns(): Collection
    {
        return $this->wordInLearns;
    }

    /**
     * @return Collection|WordInLearn[]
     */
    public function getWordsInLearnProgress(): Collection
    {
        $wordsInLearn = $this->getWordInLearns();
        return $wordsInLearn->filter(function (WordInLearn $wordInLearn) {
            return !$wordInLearn->learned();
        });
    }

    public function addWordToLearn(WordInLearn $wordInLearn): self
    {
        if (!$this->wordInLearns->contains($wordInLearn)) {
            $this->wordInLearns[] = $wordInLearn;
            $wordInLearn->setWordSet($this);
        }

        return $this;
    }

    public function removeWordInLearn(WordInLearn $wordInLearn): self
    {
        if ($this->wordInLearns->removeElement($wordInLearn)) {
            // set the owning side to null (unless already changed)
            if ($wordInLearn->getWordSet() === $this) {
                $wordInLearn->setWordSet(null);
            }
        }

        return $this;
    }
}
