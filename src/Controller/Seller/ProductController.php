<?php

namespace App\Controller\Seller;

use App\Entity\Product;
use App\Entity\ProductImage;
use App\Form\ProductType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

final class ProductController extends AbstractController
{
    #[Route('/seller/product/new', name: 'new_seller_product')]
    #[IsGranted('SELLER_ACCESS')]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {


        $product = new Product();
        $product_image = new ProductImage();


        $form = $this->createForm(ProductType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imagefile')->getData();

            if ($imageFile) {
                $originalName = pathinfo(
                    $imageFile->getClientOriginalName(),
                    PATHINFO_FILENAME
                );
                $safeName = $slugger->slug($originalName);

                $fileName = $safeName . '-' . uniqid() . '.' . $imageFile->guessExtension();

                $imageFile->move(
                    $this->getParameter('product_images_directory'),
                    $fileName
                );

                $product_image->setUrl($this->getUser());
            }

            $product->setShopId($this->getUser());

            $product->setCreatedAt(new \DateTimeImmutable());

            $em->persist($product);
            $em->persist($product_image);
            $em->flush();
        }

        return $this->render('seller/product/new.html.twig', [
            'controller_name' => 'Seller/ProductController',
            'form' => $form,
        ]);
    }
}
