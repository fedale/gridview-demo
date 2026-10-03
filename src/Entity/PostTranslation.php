<?php

namespace App\Entity;

use App\Repository\PostTranslationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A post's title and summary in one language.
 *
 * Keyed by the pair (post, locale) rather than by a surrogate id: a post has one
 * translation per language and that pair already names it. The demo's proof that
 * a grid can address records by a composite key — the edit link carries the two
 * parts as a single token, `7~it`.
 */
#[ORM\Entity(repositoryClass: PostTranslationRepository::class)]
#[ORM\Table(name: 'post_translation')]
class PostTranslation
{
    #[ORM\Id]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Post $post = null;

    #[ORM\Id]
    #[ORM\Column(length: 5)]
    private ?string $locale = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $summary = null;

    #[ORM\Column]
    private bool $isReviewed = false;

    public function __toString(): string
    {
        return ($this->title ?? '') . ' (' . ($this->locale ?? '') . ')';
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(?Post $post): static
    {
        $this->post = $post;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): static
    {
        $this->summary = $summary;

        return $this;
    }

    public function isReviewed(): bool
    {
        return $this->isReviewed;
    }

    public function setIsReviewed(bool $isReviewed): static
    {
        $this->isReviewed = $isReviewed;

        return $this;
    }
}
