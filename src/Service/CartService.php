<?php

namespace App\Service;

use App\Entity\Gallery;
use App\Entity\Event;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartService
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    private function getSession(): ?SessionInterface
    {
        if (!$this->requestStack->getCurrentRequest()) {
            return null;
        }
        
        try {
            return $this->requestStack->getSession();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Ajouter un élément au panier
     */
    public function addItem(string $itemType, int $itemId, string $itemName, string $price, int $quantity = 1, ?array $metadata = null): void
    {
        $session = $this->getSession();
        if (!$session) {
            return;
        }
        
        $cart = $this->getCart();
        $itemKey = $itemType . '_' . $itemId;

        if (isset($cart[$itemKey])) {
            // Mettre à jour la quantité si l'élément existe déjà
            $cart[$itemKey]['quantity'] += $quantity;
            $cart[$itemKey]['total_price'] = number_format(floatval($cart[$itemKey]['unit_price']) * $cart[$itemKey]['quantity'], 2, '.', '');
        } else {
            // Ajouter un nouvel élément
            $cart[$itemKey] = [
                'item_type' => $itemType,
                'item_id' => $itemId,
                'item_name' => $itemName,
                'unit_price' => $price,
                'quantity' => $quantity,
                'total_price' => number_format(floatval($price) * $quantity, 2, '.', ''),
                'metadata' => $metadata
            ];
        }

        $session->set('cart', $cart);
    }

    /**
     * Supprimer un élément du panier
     */
    public function removeItem(string $itemType, int $itemId): void
    {
        $session = $this->getSession();
        if (!$session) {
            return;
        }
        
        $cart = $this->getCart();
        $itemKey = $itemType . '_' . $itemId;
        
        if (isset($cart[$itemKey])) {
            unset($cart[$itemKey]);
            $session->set('cart', $cart);
        }
    }

    /**
     * Mettre à jour la quantité d'un élément
     */
    public function updateQuantity(string $itemType, int $itemId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->removeItem($itemType, $itemId);
            return;
        }

        $session = $this->getSession();
        if (!$session) {
            return;
        }

        $cart = $this->getCart();
        $itemKey = $itemType . '_' . $itemId;
        
        if (isset($cart[$itemKey])) {
            $cart[$itemKey]['quantity'] = $quantity;
            $cart[$itemKey]['total_price'] = number_format(floatval($cart[$itemKey]['unit_price']) * $quantity, 2, '.', '');
            $session->set('cart', $cart);
        }
    }

    /**
     * Obtenir le contenu du panier
     */
    public function getCart(): array
    {
        $session = $this->getSession();
        if (!$session) {
            return [];
        }
        return $session->get('cart', []);
    }

    /**
     * Obtenir le nombre total d'éléments dans le panier
     */
    public function getItemCount(): int
    {
        $cart = $this->getCart();
        return array_sum(array_column($cart, 'quantity'));
    }

    /**
     * Obtenir le montant total du panier
     */
    public function getTotalAmount(): string
    {
        $cart = $this->getCart();
        $total = '0.00';
        
        foreach ($cart as $item) {
            $total = number_format(floatval($total) + floatval($item['total_price']), 2, '.', '');
        }
        
        return $total;
    }

    /**
     * Vider le panier
     */
    public function clearCart(): void
    {
        $session = $this->getSession();
        if (!$session) {
            return;
        }
        $session->remove('cart');
    }

    /**
     * Vérifier si le panier est vide
     */
    public function isEmpty(): bool
    {
        return empty($this->getCart());
    }

    /**
     * Ajouter une galerie au panier
     */
    public function addGallery(Gallery $gallery): void
    {
        // Utiliser le prix configuré de la galerie
        $price = $gallery->isPaid() ? $gallery->getAccessPrice() : '0.00';
        
        $this->addItem(
            'gallery_access',
            $gallery->getId(),
            $gallery->getTitle(),
            $price,
            1,
            [
                'gallery_slug' => $gallery->getSlug(),
                'gallery_type' => $gallery->isPaid() ? 'paid_access' : 'free_access',
                'pricing_type' => $gallery->getPricingType()
            ]
        );
    }

    /**
     * Ajouter un événement au panier (pour inscription payante)
     */
    public function addEvent(Event $event, string $price = '0.00'): void
    {
        $this->addItem(
            'event_registration',
            $event->getId(),
            $event->getTitle(),
            $price,
            1,
            [
                'event_slug' => $event->getSlug(),
                'event_date' => $event->getStartDate()?->format('Y-m-d H:i'),
                'registration_type' => 'paid'
            ]
        );
    }

    /**
     * Valider le panier avant checkout
     */
    public function validateCart(): array
    {
        $errors = [];
        $cart = $this->getCart();

        if (empty($cart)) {
            $errors[] = 'Le panier est vide.';
            return $errors;
        }

        foreach ($cart as $item) {
            // Validation spécifique par type d'élément
            switch ($item['item_type']) {
                case 'gallery_access':
                    // Vérifier que la galerie existe encore et est accessible
                    break;
                case 'event_registration':
                    // Vérifier que l'événement existe et que l'inscription est encore possible
                    break;
            }
        }

        return $errors;
    }
}