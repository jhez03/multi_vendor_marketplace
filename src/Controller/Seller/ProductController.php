<?php

namespace App\Controller\Seller;

use App\Entity\Product;
use App\Entity\ProductImage;
use App\Form\ProductType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/seller/products')]
#[IsGranted('ROLE_SELLER')]
class ProductController extends AbstractController
{
    #[Route('', name: 'app_product_index')]
    public function index(EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user     = $this->getUser();
        $products = $em->getRepository(Product::class)->findAll();

        return $this->render('seller/product/index.html.twig', [
            'user'     => $user,
            'profile'  => $user->getSellerProfile(),
            'products' => $products,
        ]);
    }

    #[Route('/new', name: 'app_product_new')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
    ): Response {
        /** @var \App\Entity\User $user */
        $user    = $this->getUser();
        $product = new Product();
        $form    = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image upload
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename     = $slugger->slug($originalFilename);
                $newFilename      = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                $image = new ProductImage();
                $image->setUrl($newFilename);
                //set shop id
                $product->setShop($user->getSellerProfile()->getStore());


                try {
                    $imageFile->move(
                        $this->getParameter('products_images_directory'),
                        $newFilename
                    );
                    $product->addProductImage($image);
                } catch (FileException) {
                    $image->setUrl('placeholder.png');
                    $product->addProductImage(image);
                }
            } else {
                $image->setUrl('placeholder.png');
                $product->addProductImage($image);
            }

            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Product "' . $product->getName() . '" has been listed successfully.');

            return $this->redirectToRoute('app_product_index');
        }


        return $this->render('seller/product/new.html.twig', [
            'user'    => $user,
            'profile' => $user->getSellerProfile(),
            'form'    => $form,
            'product' => $product,
        ]);
    }
}
