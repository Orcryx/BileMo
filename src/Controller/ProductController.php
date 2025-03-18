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

    public function __construct(private readonly ProductManagerInterface $productManager) {}

    #[Route('/api/products', name: 'products', methods: ['GET'])]
    public function getProductsList(SerializerInterface $serializer): JsonResponse
    {
        $productList = $this->productManager->findAll();
        $jsonProductList = $serializer->serialize($productList, 'json');

        return new JsonResponse([
            'products' => $jsonProductList,
            Response::HTTP_OK,
            [],
            true
        ]);
    }

    #[Route('/api/products', name: 'product')]
    public function getProductDetails(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Les détails du produit',
            'path' => 'src/Controller/ProductController.php',
        ]);
    }
}
