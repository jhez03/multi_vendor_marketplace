<?php

namespace App\Controller\Seller;

use App\Entity\Product;
use App\Entity\ProductImage;
use App\Form\ProductType;
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
        $user    = $this->getUser();
        $shop    = $user->getSellerProfile()?->getStore();
        $products = $shop
            ? $em->getRepository(Product::class)->findBy(['shop' => $shop], ['createdAt' => 'DESC'])
            : [];

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
            $imageFile = $form->get('imageFile')->getData();
            $image     = new ProductImage();

            $product->setShop($user->getSellerProfile()->getStore());

            if ($imageFile) {
                $safeFilename = $slugger->slug(pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME));
                $newFilename  = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                $image->setUrl($newFilename);
                $image->setIsPrimary(true);
                $storeName = $slugger->slug($user->getSellerProfile()->getStore()->getStoreName());


                try {
                    $imageFile->move(
                        $this->getParameter('products_images_directory') . '/' . $storeName,
                        $newFilename
                    );
                } catch (FileException) {
                    $image->setUrl('placeholder.png');
                }
            } else {
                $image->setUrl('placeholder.png');
                $image->setIsPrimary(true);
            }

            $product->addProductImage($image);
            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Product "' . $product->getName() . '" has been listed successfully.');

            return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
        }

        return $this->render('seller/product/new.html.twig', [
            'user'    => $user,
            'profile' => $user->getSellerProfile(),
            'form'    => $form,
            'product' => $product,
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', requirements: ['id' => '\d+'])]
    public function show(Product $product, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user    = $this->getUser();
        $profile = $user->getSellerProfile();

        // Ensure the product belongs to this seller's shop
        if ($product->getShop()?->getId() !== $profile?->getStore()?->getId()) {
            throw $this->createAccessDeniedException('You do not own this product.');
        }

        return $this->render('seller/product/show.html.twig', [
            'user'    => $user,
            'profile' => $profile,
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', requirements: ['id' => '\d+'])]
    public function edit(
        Product $product,
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
    ): Response {
        /** @var \App\Entity\User $user */
        $user    = $this->getUser();
        $profile = $user->getSellerProfile();

        if ($product->getShop()?->getId() !== $profile?->getStore()?->getId()) {
            throw $this->createAccessDeniedException('You do not own this product.');
        }


        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        //current image
        $primaryImage = $product->getProductImages()->filter(function ($image) {
            return $image->isPrimary();
        })->first();
        //add primaryImage to product object

        $product->primaryImage = $primaryImage->getUrl();



        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $safeFilename = $slugger->slug(pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME));
                $newFilename  = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                $image        = new ProductImage();
                $image->setUrl($newFilename);
                $image->setIsPrimary(true);
                $storeName = $slugger->slug($user->getSellerProfile()->getStore()->getStoreName());


                try {
                    $imageFile->move(
                        $this->getParameter('products_images_directory') . '/' . $storeName,
                        $newFilename
                    );
                    $product->addProductImage($image);
                } catch (FileException) {
                    // keep existing images
                }
            }

            $em->flush();
            $this->addFlash('success', 'Product updated successfully.');

            return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
        }

        return $this->render('seller/product/new.html.twig', [
            'user'    => $user,
            'profile' => $profile,
            'form'    => $form,
            'product' => $product,
            'editing' => true,
        ]);
    }
    #[Route('/{id}/del', name: 'app_product_del', requirements: ['id' => '\d+'])]
    public function delete(
        Product $product,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
    ): Response {
        /** @var \App\Entity\User $user */
        $user    = $this->getUser();
        $profile = $user->getSellerProfile();

        if ($product->getShop()?->getId() !== $profile?->getStore()?->getId()) {
            throw $this->createAccessDeniedException('You do not own this product.');
        }

        //remove image
        $storeName = $slugger->slug($user->getSellerProfile()->getStore()->getStoreName());
        $imagePath = $this->getParameter('products_images_directory');
        foreach ($product->getProductImages() as $image) {
            $imageFilePath = $imagePath . '/' . $storeName . '/' . $image->getUrl();
            if (file_exists($imageFilePath)) {
                unlink($imageFilePath);
            }
        }

        $em->remove($product);
        $em->flush();

        return $this->redirectToRoute('app_product_index');
    }
}
