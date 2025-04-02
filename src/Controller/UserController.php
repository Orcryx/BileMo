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
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class UserController extends AbstractController
{

    public function __construct(private readonly UserManagerInterface $userManager, private readonly SerializerInterface $serializer, private readonly TagAwareCacheInterface $cache) {}

    /**
     * This method returns the list of all users.
     *
     * @OA\Response(
     *     response=200,
     *     description="Return the list of all user",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"usersList"}))
     *     )
     * )
     * @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="The page you want to recover",
     *     @OA\Schema(type="int", default=1)
     * )
     * @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="The number of elements you want to retrieve",
     *     @OA\Schema(type="int", default=3)
     * )
     * @OA\Tag(name="Users")
     * @return JsonResponse
     **/
    #[Route('/api/users/{page}/{limit}', name: 'users_list', methods: ['GET'])]
    public function getUsersList(Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        //Id qui représente la requête reçue par le controller
        $idCache = sprintf('getUsersList-%d-%d-%d', $currentUser->getId(), $page, $limit);

        $users = $this->cache->get($idCache, function (ItemInterface $item) use ($currentUser, $page, $limit) {

            $item->tag(['usersCache']); // Tag utilisateur unique

            $usersList = $this->userManager->findAll($currentUser, $page, $limit);
            $context = SerializationContext::create()->setGroups(["usersList"]);
            return $this->serializer->serialize($usersList, 'json',  $context);
        });


        return new JsonResponse($users, Response::HTTP_OK, [], true);
    }

    /**
     *
     * This method return the detail of a user.
     *
     * @OA\Response(
     *     response=200,
     *     description="Return the detail of user",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"userDetails"}))
     *     )
     * )
     *
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="The identifiant of a user",
     *     @OA\Schema(type="integer")
     * )
     * @OA\Tag(name="Users")
     *
     * @param int $id
     * @return JsonResponse
     */
    #[Route('/api/users/{id}', name: 'userDetails', methods: ['GET'])]
    public function getUserById(int $id): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        //Id qui représente la requête reçue par le controller
        $idCache = sprintf('getUserById-%d-%d', $currentUser->getId(), $id);

        //Mise en cache
        $jsonUser = $this->cache->get($idCache, function (ItemInterface $item) use ($currentUser, $id) {

            $item->tag(['usersCache']);

            $user = $this->userManager->find($id, $currentUser);

            if (!$user) {
                // Code erreur et message
                $statusCode = Response::HTTP_NOT_FOUND; // 404
                $data = [
                    'status' => $statusCode,
                    'message' => 'Utilisateur non trouvé',
                ];
                return new JsonResponse($data);
            }
            //variable context est nécessaire pour le serialiser JMS, il prendra le groups
            $context = SerializationContext::create()->setGroups(["userDetails"]);
            return $this->serializer->serialize($user, 'json', $context);
        });

        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    /**
     * This method create a user.
     *
     * @OA\Response(
     *      response=200,
     *     description="Return the list of all user",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"userDetails"}))
     *     )
     * )
     * @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *              @OA\Schema(
     *               type="object",
     *          @OA\Property(
     *              property="email",
     *              type="string",
     *              example="utilisateur@bile.mo"
     *          ),
     *          @OA\Property(
     *              property="password",
     *              type="string",
     *              example="password"
     *          ),
     *          @OA\Property(
     *              property="role",
     *              type="array",
     *              example="["ROLE_USER"]"
     *          ),
     *          @OA\Property(
     *              property="customer",
     *              type="array",
     *              example=" "id": "3""
     *          ),
     *      )
     *  )
     * @OA\Tag(name="Users")
     * @param Request $request
     * @param UrlGeneratorInterface $urlGenerator
     * @param CustomerManagerInterface $customerManager
     * @param ValidatorInterface $validator
     * @return JsonResponse
     **/
    #[Route('/api/users', name: 'userCreate', methods: ['POST'])]
    #[IsGranted('ROLE_CLIENT', message: "Vous n'avez pas les droits suffisants pour créer un utilisateur")]
    public function createUser(Request $request, UrlGeneratorInterface $urlGenerator,  CustomerManagerInterface $customerManager, ValidatorInterface $validator): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Désérialisation du JSON en objet User
        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');
        $content = $request->toArray();
        $roles = $content['roles'] ?? ['ROLE_USER'];  // Si roles est absent, mettre ROLE_USER par défaut : A REVOIR
        $user->setRoles($roles);
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

        //Effacer le cache
        $this->cache->invalidateTags(['usersCache']);

        // Sauvegarde du nouvel utilisateur
        $this->userManager->create($user, $currentUser);

        //variable context est nécessaire pour le serialiser JMS, il prendra le groups
        $context = SerializationContext::create()->setGroups(["userDetails"]);

        // Sérialisation de la réponse
        $jsonUser = $this->serializer->serialize($user, 'json', $context);

        $location = $urlGenerator->generate('userCreate', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ['Location' => $location], true);
    }

    /**
     *   This method update a user.
     * @OA\Put(
     *     path="/api/users/{id}",
     *     summary="Update a user",
     *     description="This method allows updating an existing user's details.",
     *     operationId="updateUser",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="The identifier of the user to update",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="User object that needs to be updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", example="user@example.com"),
     *             @OA\Property(property="firstName", type="string", example="John"),
     *             @OA\Property(property="lastName", type="string", example="Doe"),
     *             @OA\Property(property="address", type="string", example="123 Main St"),
     *             @OA\Property(property="customer", type="integer", example=1)  // customer is optional, but shown here
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="email", type="string", example="user@example.com"),
     *             @OA\Property(property="firstName", type="string", example="John"),
     *             @OA\Property(property="lastName", type="string", example="Doe"),
     *             @OA\Property(property="address", type="string", example="123 Main St"),
     *             @OA\Property(
     *                 property="_links",
     *                 type="object",
     *                 @OA\Property(property="self", type="string", example="/api/users/1")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid data provided",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="field", type="string", example="email"),
     *                 @OA\Property(property="message", type="string", example="This value is not valid")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User or customer not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Utilisateur non trouvé")
     *         )
     *     ),
     *     @OA\Tag(name="Users")
     * )
     * 
     * @param int $id
     * @param Request $request
     * @param UrlGeneratorInterface $urlGenerator
     * @param CustomerManagerInterface $customerManager
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */

    #[Route('/api/users/{id}', name: 'userUpdate', methods: ['PUT'])]
    #[IsGranted('ROLE_CLIENT', message: "Vous n'avez pas les droits suffisants pour modifier un utilisateur")]
    public function updateUser(Request $request, UrlGeneratorInterface $urlGenerator, int $id, CustomerManagerInterface $customerManager, ValidatorInterface $validator): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Vérifier si l'utilisateur cible existe
        $user = $this->userManager->find($id, $currentUser);
        if (!$user) {
            // Code erreur et message
            $statusCode = Response::HTTP_NOT_FOUND; // 404
            $data = [
                'status' => $statusCode,
                'message' => 'Utilisateur non trouvé',
            ];
            return new JsonResponse($data, $statusCode);
        }

        // Désérialisation et mise à jour des données manuellement (A CAUSE DE JMS : nul !)
        $newUser = $this->serializer->deserialize($request->getContent(), User::class, 'json');
        // Mettre à jour uniquement les champs présents dans la requête
        if ($newUser->getEmail()) {
            $user->setEmail($newUser->getEmail());
        }
        if ($newUser->getFirstName()) {
            $user->setFirstName($newUser->getFirstName());
        }
        if ($newUser->getLastName()) {
            $user->setLastName($newUser->getLastName());
        }
        if ($newUser->getAddress()) {
            $user->setAddress($newUser->getAddress());
        }
        $user->setUpdateAt(new \DateTimeImmutable());

        // Mise à jour du Customer si présent dans la requête
        $content = $request->toArray();
        if (isset($content['customer'])) {
            $customer = $customerManager->find($content['customer']);
            if (!$customer) {
                // Code erreur et message
                $statusCode = Response::HTTP_NOT_FOUND; // 404
                $data = [
                    'status' => $statusCode,
                    'message' => 'Client non trouvé',
                ];
                return new JsonResponse($data);
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

        //variable context est nécessaire pour le serialiser JMS, il prendra le groups
        $context = SerializationContext::create()->setGroups(["userDetails"]);

        // Générer la réponse JSON
        $jsonUser =  $this->serializer->serialize($user, 'json', $context);
        $this->cache->invalidateTags(['usersCache']);
        $location = $urlGenerator->generate('userCreate', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonUser, Response::HTTP_OK, ['Location' => $location], true);
    }


    /**
     *   This method delete a user.
     *
     * @OA\Response(
     *     response=200,
     *     description="Return delete a user",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items()
     *     )
     * )
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="The identifiant of a user",
     *     @OA\Schema(type="int")
     * )
     * @OA\Tag(name="Users")
     * @param int $id
     * @return JsonResponse
     */
    #[Route('/api/users/{id}', name: 'userDelete', methods: ['DELETE'])]
    #[IsGranted('ROLE_CLIENT', message: "Vous n'avez pas les droits suffisants pour supprimer un utilisateur")]
    public function deleteUser(int $id): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $user = $this->userManager->find($id, $currentUser);

        if (!$user) {
            // Code erreur et message
            $statusCode = Response::HTTP_NOT_FOUND; // 404
            $data = [
                'status' => $statusCode,
                'message' => 'Utilisateur non trouvé',
            ];
            return new JsonResponse($data);
        }
        $this->userManager->remove($user);
        $this->cache->invalidateTags(['usersCache']);

        return new JsonResponse(['message' => 'Utilisateur supprimé'], Response::HTTP_NO_CONTENT);
    }
}
