<?php

namespace App\Manager;

use App\Entity\Product;

interface ProductManagerInterface
{
    public function find(int $id): ?Product;
    public function findAll(): array;
}
