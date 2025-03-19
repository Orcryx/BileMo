<?php

namespace App\Manager;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class UserManager implements UserManagerInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function create(User $user, bool $flush = true): void
    {
        if (!$user->getEmail() || !$user->getPassword()) {
            throw new \InvalidArgumentException("Email et mot de passe sont requis.");
        }

        // Hachage du mot de passe
        $user->setPassword(password_hash($user->getPassword(), PASSWORD_BCRYPT));

        // Définition de la date de création
        if (!$user->getCreateAt()) {
            $user->setCreateAt(new \DateTimeImmutable());
        }

        // Définition de la date de modification
        if (!$user->getUpdateAt()) {
            $user->setUpdateAt(new \DateTimeImmutable());
        }

        $this->entityManager->persist($user);
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    public function remove(User $user, bool $flush = true): void
    {
        $this->entityManager->remove($user);
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    public function find(int $id): ?User
    {
        return $this->entityManager->getRepository(User::class)->find($id);
    }

    public function findAll(): array
    {
        return $this->entityManager->getRepository(User::class)->findAll();
    }
}
