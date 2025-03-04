<?php

namespace App\Service;

use Stripe\Price;
use Stripe\Product;
use Stripe\Stripe;

class StripeService
{
    public function __construct(private string $stripeSecretKey)
    {
        Stripe::setApiKey($this->stripeSecretKey);
    }

    /**
     * Méthode pour créer un plan d'abonnement
     * @param string $name Nom du plan
     * @param int $price Prix du plan
     * @param string $interval Période de facturation (month, year)
     * @return array Retourne les ID du produit et du prix Stripe
     */
    public function createSubscriptionPlan(string $name, int $price, string $interval): array
    {
        // Création du produit Stripe
        $product = Product::create(
            [
                'name' => $name,
                'type' => 'service'
            ]
        );

        // Création du prix Stripe
        $priceData = Price::create(
            [
                'unit_amount' => $price, // Prix en centimes
                'currency' => 'eur',
                'recurring' => ['interval' => $interval],
                'product' => $product->id
            ]
        );

        // Retourne les ID du produit et du prix
        return [
            'productId' => $product->id,
            'priceId' => $priceData->id
        ];
    }
}