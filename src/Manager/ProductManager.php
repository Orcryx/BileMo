<?php

namespace App\Manager;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;

class ProductManager implements ProductManagerInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function save(Product $product, bool $flush = true): void
    {
        $this->entityManager->persist($product);
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    public function remove(Product $product, bool $flush = true): void
    {
        $this->entityManager->remove($product);
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    public function find(int $id): ?Product
    {
        return $this->entityManager->getRepository(Product::class)->find($id);
    }

    public function findAll(): array
    {
        return $this->entityManager->getRepository(Product::class)->findAll();
    }
}
