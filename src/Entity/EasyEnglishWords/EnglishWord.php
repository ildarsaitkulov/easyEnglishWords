<?php

namespace App\Entity\EasyEnglishWords;

use App\Repository\EasyEnglishWords\EnglishWordRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EnglishWordRepository::class)
 */
class EnglishWord
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $external_id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $text;

    /**
     * @ORM\OneToMany(targetEntity=Meaning::class, mappedBy="word")
     */
    private $meanings;

    public function __construct()
    {
        $this->meanings = new ArrayCollection();
    }
    
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExternalId(): ?int
    {
        return $this->external_id;
    }

    public function setExternalId(int $external_id): self
    {
        $this->external_id = $external_id;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @return Collection|Meaning[]
     */
    public function getMeanings(): Collection
    {
        return $this->meanings;
    }

    public function addMeaning(Meaning $meaning): self
    {
        if (!$this->meanings->contains($meaning)) {
            $this->meanings[] = $meaning;
            $meaning->setWord($this);
        }

        return $this;
    }

    public function removeMeaning(Meaning $meaning): self
    {
        if ($this->meanings->removeElement($meaning)) {
            // set the owning side to null (unless already changed)
            if ($meaning->getWord() === $this) {
                $meaning->setWord(null);
            }
        }

        return $this;
    }
}
