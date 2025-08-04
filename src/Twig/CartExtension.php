<?php

namespace App\Twig;

use App\Service\CartService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CartExtension extends AbstractExtension
{
    public function __construct(private CartService $cartService)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('cart_count', [$this, 'getCartCount']),
            new TwigFunction('cart_total', [$this, 'getCartTotal']),
            new TwigFunction('cart_items', [$this, 'getCartItems']),
        ];
    }

    public function getCartCount(): int
    {
        return $this->cartService->getItemCount();
    }

    public function getCartTotal(): string
    {
        return $this->cartService->getTotalAmount();
    }

    public function getCartItems(): array
    {
        return $this->cartService->getCart();
    }
}