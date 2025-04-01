<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Manager\ProductManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
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
            // echo ("CET ELEMENT N'EST PAS ENCORE EN CACHE");

            $item->tag(['productsCache']); // Ajout du tag pour invalidation future

            // Récupération des produits via le manager
            $productsList = $this->productManager->findAll($page, $limit);
            return $this->serializer->serialize($productsList, 'json', ['groups' => 'productList']);
        });

        return new JsonResponse($products, Response::HTTP_OK, [], true);
    }

    #[Route('/api/products/{id}', name: 'product')]
    public function getProductDetails(int $id): JsonResponse
    {
        //Id qui représente la requête reçue par le controller
        $idCache = sprintf('getProductsList-%d', $id);

        //Mise en cache
        $jsonProductDetails = $this->cache->get($idCache, function (ItemInterface $item) use ($id) {

            // echo ("CET ELEMENT N'EST PAS ENCORE EN CACHE");

            $item->tag(['productsCache']);

            $productDetails = $this->productManager->find($id);
            if (!$productDetails) {
                return new JsonResponse(['message' => ' produit non trouvé'], Response::HTTP_NOT_FOUND);
            }
            return $this->serializer->serialize($productDetails, 'json', ['groups' => 'productDetails']);
        });


        return new JsonResponse($jsonProductDetails, Response::HTTP_OK, [], true);
    }
}
