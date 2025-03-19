<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Manager\ProductManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

final class ProductController extends AbstractController
{

    public function __construct(private readonly ProductManagerInterface $productManager, private readonly SerializerInterface $serializer) {}

    #[Route('/api/products', name: 'products', methods: ['GET'])]
    public function getProductsList(): JsonResponse
    {
        $productList = $this->productManager->findAll();
        $jsonProductList = $this->serializer->serialize($productList, 'json', ['groups' => 'productList']);

        return new JsonResponse($jsonProductList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/products/{id}', name: 'product')]
    public function getProductDetails(int $id): JsonResponse
    {
        $productDetails = $this->productManager->find($id);
        if (!$productDetails) {
            return new JsonResponse(['message' => ' produit non trouvé'], Response::HTTP_NOT_FOUND);
        }
        $jsonProductDetail = $this->serializer->serialize($productDetails, 'json', ['groups' => 'productDetails']);

        return new JsonResponse($jsonProductDetail, Response::HTTP_OK, [], true);
    }
}
