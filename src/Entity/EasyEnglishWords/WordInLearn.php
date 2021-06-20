<?php

namespace App\Entity\EasyEnglishWords;

use App\Repository\EasyEnglishWords\WordInLearnRepository;
use Doctrine\ORM\Mapping as ORM;
use \App\Entity\EasyEnglishWords\EnglishWord;
use \App\Entity\EasyEnglishWords\WordSet;

/**
 * @ORM\Entity(repositoryClass=WordInLearnRepository::class)
 */
class WordInLearn
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Meaning::class, inversedBy="wordInLearns")
     * @ORM\JoinColumn(nullable=false)
     */
    private $meaning;

    /**
     * @ORM\ManyToOne(targetEntity=WordSet::class, inversedBy="wordInLearns")
     * @ORM\JoinColumn(nullable=false)
     */
    private $wordSet;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $score;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMeaning(): ?Meaning
    {
        return $this->meaning;
    }

    public function setMeaning(?Meaning $meaning): self
    {
        $this->meaning = $meaning;

        return $this;
    }

    public function getWordSet(): ?WordSet
    {
        return $this->wordSet;
    }

    public function setWordSet(?WordSet $wordSet): self
    {
        $this->wordSet = $wordSet;

        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): self
    {
        $this->score = $score;

        return $this;
    }
}
