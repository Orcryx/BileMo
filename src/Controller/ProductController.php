<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Manager\ProductManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class ProductController extends AbstractController
{

    public function __construct(private readonly ProductManagerInterface $productManager, private readonly SerializerInterface $serializer, private readonly TagAwareCacheInterface $cache) {}

    #[Route('/api/products', name: 'products', methods: ['GET'])]
    public function getProductsList(Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        //Id qui représente la requête reçue par le controller
        $idCache = sprintf('getProductsList-%d-%d', $page, $limit);

        //item = stocker en cache, echo pour le debug, tag pour permettre de supprimer tous les éléments associés en une fois.
        //créer une condition qui verifie si ce qui est retourné par la fonction est déjà dans le cache
        $products = $this->cache->get($idCache, function (ItemInterface $item) use ($page, $limit) {

            $item->tag(['productsCache']); // Ajout du tag pour invalidation future

            // Récupération des produits via le manager
            $productsList = $this->productManager->findAll($page, $limit);

            //variable context est nécessaire pour le serialiser JMS, il prendra le groups
            $context = SerializationContext::create()->setGroups(["productList"]);

            return $this->serializer->serialize($productsList, 'json', $context);
        });

        return new JsonResponse($products, Response::HTTP_OK, [], true);
    }

    #[Route('/api/products/{id}', name: 'productDetails', methods: ['GET'])]
    public function getProductDetails(int $id): JsonResponse
    {
        //Id qui représente la requête reçue par le controller
        $idCache = sprintf('getProductsList-%d', $id);

        //Mise en cache
        $jsonProductDetails = $this->cache->get($idCache, function (ItemInterface $item) use ($id) {

            $item->tag(['productsCache']);

            $productDetails = $this->productManager->find($id);
            if (!$productDetails) {
                // Code erreur et message
                $statusCode = Response::HTTP_NOT_FOUND; // 404
                $data = [
                    'status' => $statusCode,
                    'message' => 'Produit non trouvé',
                ];
                return new JsonResponse($data);
            }
            //variable context est nécessaire pour le serialiser JMS, il prendra le groups
            $context = SerializationContext::create()->setGroups(["productDetails"]);
            return $this->serializer->serialize($productDetails, 'json', $context);
        });


        return new JsonResponse($jsonProductDetails, Response::HTTP_OK, [], true);
    }
}
