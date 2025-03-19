<?php

namespace App\Controller;

use App\Entity\User;
use App\Manager\UserManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserController extends AbstractController
{
    public function __construct(private readonly UserManagerInterface $userManager, private readonly SerializerInterface $serializer) {}

    #[Route('/api/users', name: 'users_list', methods: ['GET'])]
    public function getUsersList(): JsonResponse
    {
        $users = $this->userManager->findAll();
        $jsonUsers = $this->serializer->serialize($users, 'json', ['groups' => 'usersList']);

        return new JsonResponse($jsonUsers, Response::HTTP_OK, [], true);
    }

    #[Route('/api/user/{id}', name: 'user_details', methods: ['GET'])]
    public function getUserById(int $id): JsonResponse
    {
        $user = $this->userManager->find($id);

        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $jsonUser = $this->serializer->serialize($user, 'json', ['groups' => 'userDetails']);

        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    #[Route('/api/user', name: 'user_create', methods: ['POST'])]
    public function createUser(Request $request, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        // Désérialisation du JSON en objet User
        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');

        // Vérification des champs obligatoires
        if (!$user->getEmail() || !$user->getPassword()) {
            return new JsonResponse(['message' => 'Email et mot de passe sont requis.'], Response::HTTP_BAD_REQUEST);
        }

        // Sauvegarde du nouvel utilisateur
        $this->userManager->create($user);

        // Sérialisation de la réponse
        $jsonUser = $this->serializer->serialize($user, 'json', ['groups' => 'userDetails']);

        $location = $urlGenerator->generate('user_details', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ['Location' => $location], true);
    }


    #[Route('/api/user/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function deleteUser(int $id): JsonResponse
    {
        $user = $this->userManager->find($id);

        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $this->userManager->remove($user);

        return new JsonResponse(['message' => 'Utilisateur supprimé'], Response::HTTP_NO_CONTENT);
    }
}
