<?php

namespace App\Entity\EasyEnglishWords;

use App\Repository\EasyEnglishWords\MeaningRepository;
use Doctrine\ORM\Mapping as ORM;
use \App\Entity\EasyEnglishWords\EnglishWord;

/**
 * @ORM\Entity(repositoryClass=MeaningRepository::class)
 */
class Meaning
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
    private $externalId;

    /**
     * @ORM\ManyToOne(targetEntity=EnglishWord::class, inversedBy="meanings")
     * @ORM\JoinColumn(nullable=false)
     */
    private $word;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $text;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $soundUrl;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $difficultyLevel;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $partOfSpeechCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $prefix;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $transcription;

    /**
     * @ORM\Column(type="jsonb", nullable=true)
     */
    private $properties;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $mnemonics;

    /**
     * @ORM\Column(type="jsonb")
     */
    private $translation;

    /**
     * @ORM\Column(type="jsonb", nullable=true)
     */
    private $images;

    /**
     * @ORM\Column(type="jsonb", nullable=true)
     */
    private $definition;

    /**
     * @ORM\Column(type="jsonb", nullable=true)
     */
    private $examples;

    /**
     * @ORM\Column(type="jsonb", nullable=true)
     */
    private $meaningsWithSimilarTranslation;

    /**
     * @ORM\Column(type="jsonb", nullable=true)
     */
    private $alternativeTranslations;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExternalId(): ?int
    {
        return $this->externalId;
    }

    public function setExternalId(int $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getWord(): ?EnglishWord
    {
        return $this->word;
    }

    public function setWord(?EnglishWord $word): self
    {
        $this->word = $word;

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

    public function getSoundUrl(): ?string
    {
        return $this->soundUrl;
    }

    public function setSoundUrl(?string $soundUrl): self
    {
        $this->soundUrl = $soundUrl;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDifficultyLevel(): ?int
    {
        return $this->difficultyLevel;
    }

    public function setDifficultyLevel(?int $difficultyLevel): self
    {
        $this->difficultyLevel = $difficultyLevel;

        return $this;
    }

    public function getPartOfSpeechCode(): ?string
    {
        return $this->partOfSpeechCode;
    }

    public function setPartOfSpeechCode(?string $partOfSpeechCode): self
    {
        $this->partOfSpeechCode = $partOfSpeechCode;

        return $this;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    public function setPrefix(?string $prefix): self
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function getTranscription(): ?string
    {
        return $this->transcription;
    }

    public function setTranscription(?string $transcription): self
    {
        $this->transcription = $transcription;

        return $this;
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function setProperties(array $properties): self
    {
        $this->properties = $properties;

        return $this;
    }

    public function getMnemonics(): ?string
    {
        return $this->mnemonics;
    }

    public function setMnemonics(?string $mnemonics): self
    {
        $this->mnemonics = $mnemonics;

        return $this;
    }

    public function getTranslation()
    {
        return $this->translation;
    }

    public function setTranslation(array $translation): self
    {
        $this->translation = $translation;

        return $this;
    }

    public function getImages()
    {
        return $this->images;
    }

    public function setImages(array $images): self
    {
        $this->images = $images;

        return $this;
    }

    public function getDefinition()
    {
        return $this->definition;
    }

    public function setDefinition(array $definition): self
    {
        $this->definition = $definition;

        return $this;
    }

    public function getExamples()
    {
        return $this->examples;
    }

    public function setExamples(array $examples): self
    {
        $this->examples = $examples;

        return $this;
    }

    public function getMeaningsWithSimilarTranslation()
    {
        return $this->meaningsWithSimilarTranslation;
    }

    public function setMeaningsWithSimilarTranslation(array $meaningsWithSimilarTranslation): self
    {
        $this->meaningsWithSimilarTranslation = $meaningsWithSimilarTranslation;

        return $this;
    }

    public function getAlternativeTranslations()
    {
        return $this->alternativeTranslations;
    }

    public function setAlternativeTranslations(array $alternativeTranslations): self
    {
        $this->alternativeTranslations = $alternativeTranslations;

        return $this;
    }
    
    public function getFirstImage(bool $cleanParams = true)
    {
        $images = $this->getImages();
        if (empty($images[0]['url'])) {
            return null;
        }
        
        $imageUrl = $images[0]['url'];
        
        if ($cleanParams) {
            $parsed = parse_url($imageUrl);
            return "https://{$parsed['host']}{$parsed['path']}";
        }

        return "https:{$imageUrl}";
    }

    public function getFirstImageByPrams(int $quality = 50, int $with = 400, int $height = 300)
    {
        $image = $this->getFirstImage(true);
        if (!$image) {
            return null;
        }
        
        return "{$image}?w={$with}&h={$height}&q={$quality}";
    }
}
