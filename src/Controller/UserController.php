<?php

namespace App\Controller;

use App\Entity\User;
use App\Manager\UserManagerInterface;
use App\Manager\CustomerManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class UserController extends AbstractController
{

    public function __construct(private readonly UserManagerInterface $userManager, private readonly SerializerInterface $serializer, private readonly TagAwareCacheInterface $cache) {}

    #[Route('/api/users', name: 'users_list', methods: ['GET'])]
    public function getUsersList(Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        //Id qui représente la requête reçue par le controller
        $idCache = sprintf('getUsersList-%d-%d-%d', $currentUser->getId(), $page, $limit);

        $users = $this->cache->get($idCache, function (ItemInterface $item) use ($currentUser, $page, $limit) {
            // echo ("CET ELEMENT N'EST PAS ENCORE EN CACHE");

            $item->tag(['usersCache']); // Tag utilisateur unique

            $usersList = $this->userManager->findAll($currentUser, $page, $limit);
            return $this->serializer->serialize($usersList, 'json', ['groups' => 'usersList']);
        });


        return new JsonResponse($users, Response::HTTP_OK, [], true);
    }

    #[Route('/api/users/{id}', name: 'user_details', methods: ['GET'])]
    public function getUserById(int $id): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        //Id qui représente la requête reçue par le controller
        $idCache = sprintf('getUserById-%d-%d', $currentUser->getId(), $id);

        //Mise en cache
        $jsonUser = $this->cache->get($idCache, function (ItemInterface $item) use ($currentUser, $id) {
            // echo ("CET ELEMENT N'EST PAS ENCORE EN CACHE");

            $item->tag(['usersCache']);

            $user = $this->userManager->find($id, $currentUser);

            if (!$user) {
                return new JsonResponse(['message' => 'Utilisateur(s) non trouvé(s)'], Response::HTTP_NOT_FOUND);
            }

            return $this->serializer->serialize($user, 'json', ['groups' => 'userDetails']);
        });

        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    #[Route('/api/users', name: 'user_create', methods: ['POST'])]
    #[IsGranted('ROLE_CLIENT', message: "Vous n'avez pas les droits suffisants pour créer un utilisateur")]
    public function createUser(Request $request, UrlGeneratorInterface $urlGenerator,  CustomerManagerInterface $customerManager, ValidatorInterface $validator): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Désérialisation du JSON en objet User
        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');
        $content = $request->toArray();
        $isCustomer = $content['customer']['id'] ?? -1;

        //Gérer les erreurs de validation
        $errors = $validator->validate($user);
        if ($errors->count() > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = [
                    'field' => $error->getPropertyPath(),
                    'message' => $error->getMessage()
                ];
            }
            return new JsonResponse($this->serializer->serialize($errorMessages, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $customer = $customerManager->find($isCustomer);
        $user->setCustomer($customer);

        // Sauvegarde du nouvel utilisateur
        $this->userManager->create($user, $currentUser);

        // Sérialisation de la réponse
        $jsonUser = $this->serializer->serialize($user, 'json', ['groups' => 'userDetails']);
        $this->cache->invalidateTags(['usersCache']);

        $location = $urlGenerator->generate('user_details', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ['Location' => $location], true);
    }

    #[Route('/api/users/{id}', name: 'user_update', methods: ['PUT'])]
    #[IsGranted('ROLE_CLIENT', message: "Vous n'avez pas les droits suffisants pour modifier un utilisateur")]
    public function updateUser(Request $request, UrlGeneratorInterface $urlGenerator, int $id, CustomerManagerInterface $customerManager, ValidatorInterface $validator): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Vérifier si l'utilisateur cible existe
        $user = $this->userManager->find($id, $currentUser);
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        // Désérialisation et mise à jour des données
        $this->serializer->deserialize(
            $request->getContent(),
            User::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $user]
        );

        // Mise à jour du Customer si présent dans la requête
        $content = $request->toArray();
        if (isset($content['customer'])) {
            $customer = $customerManager->find($content['customer']);
            if (!$customer) {
                return new JsonResponse(['message' => 'Client non trouvé.'], Response::HTTP_BAD_REQUEST);
            }
            $user->setCustomer($customer);
        }

        // Validation des données mises à jour
        $errors = $validator->validate($user);
        if ($errors->count() > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = [
                    'field' => $error->getPropertyPath(),
                    'message' => $error->getMessage()
                ];
            }
            return new JsonResponse($this->serializer->serialize($errorMessages, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        // Enregistrer les modifications
        $this->userManager->edit($user);

        // Générer la réponse JSON
        $jsonUser = $this->serializer->serialize($user, 'json', ['groups' => 'userDetails']);
        $this->cache->invalidateTags(['usersCache']);
        $location = $urlGenerator->generate('user_details', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonUser, Response::HTTP_OK, ['Location' => $location], true);
    }



    #[Route('/api/users/{id}', name: 'user_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_CLIENT', message: "Vous n'avez pas les droits suffisants pour supprimer un utilisateur")]
    public function deleteUser(int $id): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $user = $this->userManager->find($id, $currentUser);

        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }
        $this->cache->invalidateTags(['usersCache']);
        $this->userManager->remove($user);
        return new JsonResponse(['message' => 'Utilisateur supprimé'], Response::HTTP_NO_CONTENT);
    }
}
