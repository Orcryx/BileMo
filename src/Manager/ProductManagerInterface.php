<?php

namespace App\Manager;

use App\Entity\Product;

interface ProductManagerInterface
{
    public function save(Product $product, bool $flush = true): void;
    public function remove(Product $product, bool $flush = true): void;
    public function find(int $id): ?Product;
    public function findAll(): array;
}
