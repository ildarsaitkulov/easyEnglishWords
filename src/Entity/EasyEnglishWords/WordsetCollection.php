<?php

namespace App\Entity\EasyEnglishWords;

use App\Repository\EasyEnglishWords\WordsetCollectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=WordsetCollectionRepository::class)
 */
class WordsetCollection
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
     * @ORM\Column(type="string", length=255)
     */
    private $alias;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @ORM\ManyToMany(targetEntity=WordSet::class, inversedBy="wordsetCollections")
     */
    private $wordSets;

    public function __construct()
    {
        $this->wordSets = new ArrayCollection();
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

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): self
    {
        $this->alias = $alias;

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
        }

        return $this;
    }

    public function removeWordSet(WordSet $wordSet): self
    {
        $this->wordSets->removeElement($wordSet);

        return $this;
    }
}
