<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{

    public function __construct(private readonly UserPasswordHasherInterface $userPasswordHasher) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // Création de 100 produits
        for ($i = 0; $i < 100; ++$i) {
            $product = new Product();
            $product->setName('Product ' . $i);
            $product->setPrice($faker->randomFloat(2, 100, 2000));
            $product->setScreenSize($faker->numberBetween(10, 20));
            $product->setRam($faker->numberBetween(4, 64));
            $product->setStorage($faker->numberBetween(64, 2000));
            $product->setColor($faker->safeColorName());
            $product->setCreateAt(new \DateTimeImmutable());
            $product->setUpdateAt(new \DateTimeImmutable());

            $manager->persist($product);
        }

        // Création de 10 clients
        $customers = [];
        for ($i = 0; $i < 10; ++$i) {
            $customer = new Customer();
            $customer->setName($faker->company());
            $customer->setEmail('customer_' . $i . '@bilemo.com');
            $customer->setAddress($faker->address());
            $customer->setCreateAt(new \DateTimeImmutable());
            $customer->setUpdateAt(new \DateTimeImmutable());

            $manager->persist($customer);
            $customers[] = $customer;

            // Création d'un utilisateur associé au client avec le rôle ROLE_CLIENT
            $user = new User();
            $user->setEmail($customer->getEmail());
            $user->setRoles(['ROLE_CLIENT']);
            $user->setPassword($this->userPasswordHasher->hashPassword($user, 'password'));
            $user->setAddress($customer->getAddress());
            $user->setCreateAt(new \DateTimeImmutable());
            $user->setUpdateAt(new \DateTimeImmutable());
            $user->setCustomer($customer);

            $manager->persist($user);
        }

        // Création de 90 utilisateurs sans le rôle ROLE_CLIENT
        for ($i = 0; $i < 90; ++$i) {
            $user = new User();
            $user->setEmail($faker->unique()->email);
            $user->setFirstName($faker->firstName());
            $user->setLastName($faker->lastName());
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($this->userPasswordHasher->hashPassword($user, 'password'));
            $user->setAddress($faker->address());
            $user->setCreateAt(new \DateTimeImmutable());
            $user->setUpdateAt(new \DateTimeImmutable());
            $user->setCustomer($customers[array_rand($customers)]); // Attribution aléatoire d'un customer

            $manager->persist($user);
        }

        $manager->flush();
    }
}
