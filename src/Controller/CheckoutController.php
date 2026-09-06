<?php

namespace App\Controller;

use App\Form\CheckoutType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'checkout_index')]
    public function index(): Response
    {
        return $this->render('chekout.html.twig', [
            'form' => $this->createForm(CheckoutType::class)->createView(),
        ]);
    }

    #[Route('/checkout/{reference}/confirm', name: 'checkout_confirm')]
    public function confirm(string $reference): Response
    {
        return $this->redirectToRoute('checkout_indx');
    }
}
