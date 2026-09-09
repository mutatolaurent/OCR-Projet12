<?php

namespace App\Entity;

use App\Repository\ConseilRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ConseilRepository::class)]
class Conseil
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['conseil:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['conseil:read'])]
    #[Assert\NotBlank(message: "Le texte est obligatoire")]
    #[Assert\Length(min: 10, minMessage: "Le texte doit faire au moins {{ limit }} caractères")]
    private ?string $texte = null;

    #[ORM\Column]
    #[Groups(['conseil:read'])]
    #[Assert\Type('array', message: "Le champ 'mois' doit être un tableau.")]
    #[Assert\Count(min: 1, minMessage: "Le tableau de mois ne peut pas être vide.")]
    #[Assert\All([
        new Assert\Range(
            min: 1,
            max: 12,
            notInRangeMessage: "Chaque mois doit être compris entre {{ min }} et {{ max }}."
        )
    ])]
    #[Assert\Callback([self::class, 'validateMoisUnique'])]
    private array $mois = [];

    #[ORM\ManyToOne(inversedBy: 'conseils')]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTexte(): ?string
    {
        return $this->texte;
    }

    public function setTexte(string $texte): static
    {
        $this->texte = $texte;

        return $this;
    }

    public function getMois(): array
    {
        return $this->mois;
    }

    public function setMois(array $mois): static
    {
        $this->mois = $mois;

        return $this;
    }

    /**
     * Valide que le tableau 'mois' ne contient pas de doublons.
     */
    public static function validateMoisUnique(array $mois, ExecutionContextInterface $context): void
    {
        if (count($mois) !== count(array_unique($mois))) {
            $context->buildViolation('Le tableau de mois ne doit pas contenir de doublons.')
                ->atPath('mois')
                ->addViolation();
        }
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

}
