<?php

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation\Since;

/**
 * @Hateoas\Relation(
 *      "self",
 *      href = @Hateoas\Route(
 *          "bookDetail",
 *          parameters = { "id" = "expr(object.getId())" }
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="getBooks")
 * )
 *
 * @Hateoas\Relation(
 *      "delete",
 *      href = @Hateoas\Route(
 *          "deleteBook",
 *          parameters = { "id" = "expr(object.getId())" },
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="getBooks", excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
 * )
 *
 * @Hateoas\Relation(
 *      "update",
 *      href = @Hateoas\Route(
 *          "updateBook",
 *          parameters = { "id" = "expr(object.getId())" },
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="getBooks", excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
 * )
 *
 */

#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(["getBooks"])]
    private ?int $id = null;
    
    #[ORM\Column(length: 255)]
    #[Groups(["getBooks"])]
    #[Assert\NotBlank(message: "A book must have a title")]
    #[Assert\Length(min: 1, max: 255, minMessage: "Title must be greater than {{ limit }} chars", maxMessage: "Title must be less than {{ limit }} chars")]
    private ?string $title = null;
    
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["getBooks"])]
    private ?string $coverText = null;
    
    #[ORM\ManyToOne(inversedBy: 'books')]
    #[Groups(["getBooks"])]
    private ?Author $author = null;
    
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["getBooks"])]
    #[Since("2.0")]
    private ?string $comment = null;

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

    public function getCoverText(): ?string
    {
        return $this->coverText;
    }

    public function setCoverText(?string $coverText): self
    {
        $this->coverText = $coverText;

        return $this;
    }

    public function getAuthor(): ?Author
    {
        return $this->author;
    }

    public function setAuthor(?Author $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }
}
