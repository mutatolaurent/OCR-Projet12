<?php

namespace App\Entity;

use App\Repository\ConseilRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ConseilRepository::class)]
class Conseil
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Le texte est obligatoire")]
    #[Assert\Length(min: 10, minMessage: "Le texte doit faire au moins {{ limit }} caractères")]
    private ?string $texte = null;

    #[ORM\Column]
    #[Assert\Type('array', message: "Le champ 'mois' doit être un tableau.")]
    #[Assert\Count(min: 1, minMessage: "Le tableau de mois ne peut pas être vide.")]
    #[Assert\All([
        new Assert\Range(
            min: 1,
            max: 12,
            notInRangeMessage: "Chaque mois doit être compris entre {{ min }} et {{ max }}."
        )
    ])]
    private array $mois = [];

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
}
