<?php

namespace App\Manager;

use App\Entity\Customer;

interface CustomerManagerInterface
{
    public function find(int $id): ?Customer;
}
