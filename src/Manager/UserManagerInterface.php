<?php

namespace App\Manager;

use App\Entity\User;

interface UserManagerInterface
{
    public function find(int $id): ?User;
    public function findAll(): array;
    public function create(User $user, bool $flush = true): void;
    public function remove(User $user, bool $flush = true): void;
}
