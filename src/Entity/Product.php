<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation as Hateoas;


#[Hateoas\Relation(
    name: "self",
    href: new Hateoas\Route(
        name: "productDetails",
        parameters: ["id" => "expr(object.getId())"]
    ),
    exclusion: new Hateoas\Exclusion(groups: ["productList", "productDetails"])
)]
#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["productList", "productDetails"])]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Le champs "name" du produit est obligatoire.')]
    #[Assert\Regex('/^[a-zA-Z]{5,}[a-zA-Z0-9\- ]*$/', message: 'Le titre doit contenir au moins 5 lettres et peut inclure des chiffres et des tirets.')]
    #[ORM\Column(length: 255)]
    #[Groups(["productList", "productDetails"])]
    private ?string $name = null;

    #[Assert\NotBlank(message: 'Le champs "price" du produit est obligatoire.')]
    #[ORM\Column]
    #[Groups(["productDetails"])]
    private ?float $price = null;

    #[Assert\NotBlank(message: 'Le champs "screenSize" est obligatoire.')]
    #[ORM\Column]
    #[Groups(["productDetails"])]
    private ?int $screenSize = null;

    #[Assert\NotBlank(message: 'Le champs "ram" est obligatoire.')]
    #[ORM\Column]
    #[Groups(["productDetails"])]
    private ?int $ram = null;

    #[Assert\NotBlank(message: 'Le champs "storage" est obligatoire.')]
    #[ORM\Column]
    #[Groups(["productDetails"])]
    private ?int $storage = null;

    #[Assert\NotBlank(message: 'Le champs "color" est obligatoire.')]
    #[ORM\Column(length: 255)]
    #[Groups(["productDetails"])]
    private ?string $color = null;

    #[Assert\NotBlank(message: 'Le champs "createAt" est obligatoire.')]
    #[ORM\Column]
    #[Groups(["productList", "productDetails"])]
    private ?\DateTimeImmutable $createAt = null;

    #[Assert\NotBlank(message: 'Le champs "updateAt" est obligatoire.')]
    #[ORM\Column]
    #[Groups(["productDetails"])]
    private ?\DateTimeImmutable $updateAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getScreenSize(): ?int
    {
        return $this->screenSize;
    }

    public function setScreenSize(int $screenSize): static
    {
        $this->screenSize = $screenSize;

        return $this;
    }

    public function getRam(): ?int
    {
        return $this->ram;
    }

    public function setRam(int $ram): static
    {
        $this->ram = $ram;

        return $this;
    }

    public function getStorage(): ?int
    {
        return $this->storage;
    }

    public function setStorage(int $storage): static
    {
        $this->storage = $storage;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getCreateAt(): ?\DateTimeImmutable
    {
        return $this->createAt;
    }

    public function setCreateAt(\DateTimeImmutable $createAt): static
    {
        $this->createAt = $createAt;

        return $this;
    }

    public function getUpdateAt(): ?\DateTimeImmutable
    {
        return $this->updateAt;
    }

    public function setUpdateAt(\DateTimeImmutable $updateAt): static
    {
        $this->updateAt = $updateAt;

        return $this;
    }
}
