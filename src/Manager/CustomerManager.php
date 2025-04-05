<?php

namespace App\Manager;

use App\Entity\Customer;
use Doctrine\ORM\EntityManagerInterface;

class CustomerManager implements CustomerManagerInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function find(int $id): ?Customer
    {
        return $this->entityManager->getRepository(Customer::class)->find($id);
    }
}
