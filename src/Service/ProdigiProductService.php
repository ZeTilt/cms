<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

/**
 * Service pour récupérer les produits Prodigi par SKU
 * Utilise une liste de SKUs connus pour simuler un catalogue
 */
class ProdigiProductService
{
    // SKUs COMPLETS basés sur l'exploration exhaustive du site Prodigi
    private const COMMON_SKUS = [
        // ========== PRINTS & POSTERS ==========
        // Papier Photo Standard (PAP)
        'GLOBAL-PAP-4X6',    // 4x6" (10x15cm)
        'GLOBAL-PAP-5X7',    // 5x7" (13x18cm)
        'GLOBAL-PAP-8X10',   // 8x10" (20x25cm)
        'GLOBAL-PAP-8X12',   // 8x12" (20x30cm)
        'GLOBAL-PAP-10X10',  // 10x10" Square
        'GLOBAL-PAP-10X12',  // 10x12"
        'GLOBAL-PAP-10X20',  // 10x20"
        'GLOBAL-PAP-11X14',  // 11x14"
        'GLOBAL-PAP-11X17',  // 11x17"
        'GLOBAL-PAP-12X12',  // 12x12" Square
        'GLOBAL-PAP-12X16',  // 12x16"
        'GLOBAL-PAP-12X18',  // 12x18"
        
        // Papier Fine Art (FAP)
        'GLOBAL-FAP-8X10',   // 8x10"
        'GLOBAL-FAP-11X14',  // 11x14"
        'GLOBAL-FAP-12X16',  // 12x16"
        'GLOBAL-FAP-16X20',  // 16x20"
        'GLOBAL-FAP-18X24',  // 18x24"
        'GLOBAL-FAP-20X24',  // 20x24"
        
        // Canvas (CAN)
        'GLOBAL-CAN-8X8',    // 8x8" Square
        'GLOBAL-CAN-8X10',   // 8x10"
        'GLOBAL-CAN-10X10',  // 10x10" Square
        'GLOBAL-CAN-11X14',  // 11x14"
        'GLOBAL-CAN-12X12',  // 12x12" Square
        'GLOBAL-CAN-12X16',  // 12x16"
        'GLOBAL-CAN-16X16',  // 16x16" Square
        'GLOBAL-CAN-16X20',  // 16x20"
        'GLOBAL-CAN-18X24',  // 18x24"
        'GLOBAL-CAN-20X30',  // 20x30"
        
        // Metal Prints (MET) - Aluminium & Dibond
        'GLOBAL-ALU-8X10',   // 8x10" Aluminium Print
        'GLOBAL-ALU-11X14',  // 11x14" Aluminium Print
        'GLOBAL-ALU-12X16',  // 12x16" Aluminium Print
        'GLOBAL-ALU-16X20',  // 16x20" Aluminium Print
        'GLOBAL-DIB-8X10',   // 8x10" Dibond Print
        'GLOBAL-DIB-11X14',  // 11x14" Dibond Print
        'GLOBAL-DIB-12X16',  // 12x16" Dibond Print
        'GLOBAL-DIB-16X20',  // 16x20" Dibond Print
        
        // Wood Prints (CONFIRMÉS sur site Prodigi)
        'GLOBAL-WOOD-4X6-WHI-NOBDR',    // 102x152mm White No Border ✅
        'GLOBAL-WOOD-8X8-NAT-NOBDR',    // 203x203mm Natural No Border ✅
        'GLOBAL-WOOD-11X14-NAT-NOBDR',  // 279x356mm Natural No Border ✅
        'GLOBAL-WOOD-12X12-NAT-NOBDR',  // 305x305mm Natural No Border ✅
        'GLOBAL-WOOD-20X30-WHI-NOBDR',  // 508x762mm White No Border ✅
        
        // Photo Tiles (CONFIRMÉS sur site Prodigi)
        'PHOTIL-FRA-0507',   // 5x7" Framed Photo Tile ✅
        'PHOTIL-FRA-0808',   // 8x8" Framed Photo Tile ✅
        'PHOTIL-FRA-0810',   // 8x10" Framed Photo Tile ✅
        
        // ========== PUZZLES ==========
        'GLOBAL-JIG-PUZ-A4', // A4 Jigsaw puzzle
        'GLOBAL-JIG-PUZ-A3', // A3 Jigsaw puzzle
        'GLOBAL-PUZZLE-A4',  // Alternative naming
        'GLOBAL-PUZZLE-A3',  // Alternative naming
        
        // ========== MUGS & DRINKWARE ==========
        'GLOBAL-MUG-11OZ',   // 11oz photo mug
        'GLOBAL-MUG-COL',    // Coloured photo mug
        'GLOBAL-MUG-LATTE',  // Latte mug
        'GLOBAL-MUG-15OZ',   // 15oz ceramic mug (if exists)
        
        // ========== APPAREL ==========
        // T-Shirts (CONFIRMÉS sur site Prodigi)
        'GLOBAL-LS-TEE-GIL-2400', // Gildan 2400 Long Sleeve ✅
        
        // Men's Clothing
        'GLOBAL-TEE-BC-3200',     // Bella Canvas 3200 Baseball
        'GLOBAL-HOD-DELTA-99100', // Delta 99100 Hoodie
        'GLOBAL-SWE-DELTA-97100', // Delta 97100 Sweatshirt
        'GLOBAL-SWE-AWD-JH030',   // AWDis JH030 Sweatshirt
        
        // Women's Clothing (CONFIRMÉS)
        'GLOBAL-TEE-AS-4008',     // AS Colour 4008 Scoop T-shirt ✅
        'GLOBAL-TEE-GIL-64V00L',  // Gildan 64V00L V-neck T-shirt ✅
        'GLOBAL-HOD-AWD-JH001F',  // AWDis JH001F Women's Hoodie ✅
        
        // Kids Clothing (CONFIRMÉS)
        'GLOBAL-BODY-LAT-4411',   // LAT Apparel 4411 Baby Bodysuit ✅
        'GLOBAL-BODY-LAT-4424',   // LAT Apparel 4424 Baby Bodysuit ✅
        'GLOBAL-TEE-LAT-3322',    // LAT Apparel 3322 Baby T-shirt ✅
        'GLOBAL-TEE-GIL-64000B',  // Gildan 64000B Kids T-shirt ✅
        'GLOBAL-TEE-LAT-3321',    // LAT Apparel 3321 Kids T-shirt ✅
        'GLOBAL-TEE-LAT-6101',    // LAT Apparel 6101 Kids T-shirt ✅
        'GLOBAL-HOD-AWD-JH001J',  // AWDis JH001J Kids Hoodie ✅
        'GLOBAL-HOD-STA-STSK911', // Stanley/Stella Mini Cruiser 2.0 ✅
        'GLOBAL-SWE-AWD-JH030J',  // AWDis JH030J Kids Sweatshirt ✅
        'GLOBAL-SWE-STA-STSK913', // Stanley/Stella Mini Changer 2.0 ✅
        
        // ========== BAGS & ACCESSORIES ==========
        'GLOBAL-BAG-TOTE',       // Canvas tote bag
        'GLOBAL-BAG-STA-STAU773', // Stanley/Stella STAU773 woven tote ✅
        'GLOBAL-BAG-SHOP',       // Shopping bag
        'GLOBAL-MASK-PREM',      // Premium face mask
        'GLOBAL-MASK-PURE',      // Pure face mask
        'GLOBAL-FLIP-FLOP',      // Flip flops
        'GLOBAL-SOCK-ANKLE',     // Ankle socks
        'GLOBAL-SOCK-TUBE',      // Tube socks
        'GLOBAL-PATCH',          // Patches
        'GLOBAL-PIN-BADGE',      // Button pin badges
        'GLOBAL-KEYRING-PLA',    // Plastic keyrings
        'GLOBAL-PENDANT',        // Pendants
        
        // ========== CUSHIONS & HOME ==========
        'GLOBAL-CUSHION',     // Standard cushion
        'GLOBAL-CAN-CUSHION', // Canvas cushion
        'GLOBAL-WOV-PILLOW',  // Woven pillow
        'GLOBAL-APRON',       // Kitchen apron
        'GLOBAL-TEA-TOWEL',   // Tea towel
        'GLOBAL-WOOD-COAST',  // Wooden coaster
        'GLOBAL-TOWEL',       // Bath towel
        'GLOBAL-SHOW-CURT',   // Shower curtain
        'GLOBAL-BATH-MAT',    // Bath mat
        'GLOBAL-PILLOW-CASE', // Pillow case
        'GLOBAL-DUVET',       // Microfibre duvet cover
        'GLOBAL-BLANKET-JAC', // Jacquard blanket
        'GLOBAL-BLANKET-FLE', // Fleece blanket
        
        // ========== TECHNOLOGY ==========
        // Phone Cases
        'GLOBAL-CASE-SNAP',   // Snap case
        'GLOBAL-CASE-TOUGH',  // Tough case
        'GLOBAL-CASE-CLEAR',  // Clear case
        'GLOBAL-CASE-IPAD',   // iPad case
        
        // Tech Accessories
        'GLOBAL-MOUSE-MAT',   // Mouse mats
        'GLOBAL-LAPTOP-SLEEVE', // Laptop sleeves
        'GLOBAL-WATCH-STRAP', // Apple watch straps
        'GLOBAL-WATCH-ECO',   // Eco watch straps
        'GLOBAL-GAMING-MAT',  // Gaming mats
        
        // ========== STICKERS & TATTOOS ==========
        'GLOBAL-TATT-S',      // Small temporary tattoo (50x75mm) - confirmé
        'GLOBAL-TATT-M',      // Medium temporary tattoo (75x100mm) - confirmé  
        'GLOBAL-TATT-L',      // Large temporary tattoo (100x150mm) - confirmé
        'GLOBAL-TATT-XL',     // Extra Large temporary tattoo (200x200mm) - confirmé
        'GLOBAL-TATT-XXL',    // XXL temporary tattoo (300x300mm) - confirmé
        
        // ========== PHOTO BOOKS ==========
        'GLOBAL-BOOK-HARD',   // Hardcover Photo Book
        'GLOBAL-BOOK-SOFT',   // Softcover Photo Book  
        'GLOBAL-BOOK-LAYFLAT', // Layflat Photo Book
        
        // ========== CARDS & STATIONERY ==========
        'GLOBAL-CARD-FINE',   // Fine art greetings cards
        'GLOBAL-CARD-CLASSIC', // Classic greetings cards
        'GLOBAL-POST-FINE',   // Fine art postcards
        'GLOBAL-POST-CLASSIC', // Classic postcards
        'GLOBAL-NOTE-SPIRAL', // Spiral notebooks
        'GLOBAL-NOTE-PAPER',  // Paperback notebooks
        'GLOBAL-NOTE-HARD',   // Hardback notebooks
        'GLOBAL-DIARY-ACAD',  // Academic diaries
        'GLOBAL-DIARY-DAILY', // Daily diaries
        'GLOBAL-PLAN-FIN',    // Finance planners
        'GLOBAL-INVITATION',  // Invitations
        'GLOBAL-WRAP-PAPER',  // Wrapping paper
        
        // ========== CALENDARS ==========
        'GLOBAL-CAL-WALL',    // Wall calendar
        'GLOBAL-CAL-DESK',    // Desk calendar
        'CAL-WALL-11X17',     // Wall calendar 11x17"
        'CAL-DESK-5X7',       // Desk calendar 5x7"
    ];

    public function __construct(
        private ProdigiApiService $prodigiApiService,
        private ?LoggerInterface $logger = null
    ) {}

    /**
     * Récupérer tous les produits disponibles via leurs SKUs
     */
    public function getAllProducts(): array
    {
        $products = [];
        $errors = [];

        foreach (self::COMMON_SKUS as $sku) {
            try {
                $product = $this->getProductBySku($sku);
                if ($product) {
                    $products[$sku] = $this->formatProductData($product, $sku);
                }
            } catch (\Exception $e) {
                $errors[] = "Erreur SKU {$sku}: " . $e->getMessage();
                $this->logger?->warning("Impossible de récupérer le produit {$sku}", [
                    'sku' => $sku,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->logger?->info('Récupération des produits Prodigi terminée', [
            'products_count' => count($products),
            'errors_count' => count($errors),
            'total_skus' => count(self::COMMON_SKUS)
        ]);

        return [
            'products' => $products,
            'errors' => $errors,
            'total_attempted' => count(self::COMMON_SKUS),
            'successful' => count($products)
        ];
    }

    /**
     * Récupérer un produit par son SKU
     */
    public function getProductBySku(string $sku): ?array
    {
        try {
            // Utiliser l'API directe pour un SKU spécifique
            return $this->prodigiApiService->getProduct($sku);
        } catch (\Exception $e) {
            $this->logger?->warning("Produit {$sku} non accessible", [
                'sku' => $sku,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Formatter les données produit pour notre système
     */
    private function formatProductData(array $productData, string $sku): array
    {
        $product = $productData['product'] ?? [];
        
        // Extraire les informations de base
        $name = $this->generateProductName($sku, $product);
        $dimensions = $product['productDimensions'] ?? [];
        $attributes = $product['attributes'] ?? [];
        
        // Déterminer la catégorie basée sur le SKU
        $category = $this->getCategoryFromSku($sku);
        
        // Prix de base (sera calculé via quotes dans une future version)
        $basePrice = $this->estimateBasePrice($sku, $dimensions);

        return [
            'sku' => $sku,
            'name' => $name,
            'description' => $product['description'] ?? '',
            'category' => $category,
            'base_price' => $basePrice,
            'dimensions' => $dimensions,
            'attributes' => $attributes,
            'paper_type' => $this->getPaperTypeFromSku($sku),
            'available' => true
        ];
    }

    /**
     * Générer un nom de produit lisible
     */
    private function generateProductName(string $sku, array $product): string
    {
        $dimensions = $product['productDimensions'] ?? [];
        
        if (isset($dimensions['width'], $dimensions['height'], $dimensions['units'])) {
            $width = $dimensions['width'];
            $height = $dimensions['height'];
            $units = $dimensions['units'] === 'in' ? '"' : $dimensions['units'];
            
            $paperType = $this->getPaperTypeFromSku($sku);
            
            return "{$width}x{$height}{$units} {$paperType}";
        }
        
        return $sku; // Fallback au SKU si pas de dimensions
    }

    /**
     * Déterminer la catégorie du produit basée sur le SKU
     */
    private function getCategoryFromSku(string $sku): string
    {
        // Puzzles
        if (strpos($sku, 'PUZ-') !== false || strpos($sku, '-PUZ-') !== false) {
            return 'puzzles';
        }
        
        // Mugs et drinkware  
        if (strpos($sku, 'MUG-') !== false || strpos($sku, '-MUG-') !== false || 
            strpos($sku, 'TUM-') !== false || strpos($sku, '-TUM-') !== false) {
            return 'cadeaux';
        }
        
        // T-shirts et vêtements
        if (strpos($sku, 'TEE-') !== false || strpos($sku, '-TEE-') !== false ||
            strpos($sku, 'HOD-') !== false || strpos($sku, '-HOD-') !== false) {
            return 'cadeaux';
        }
        
        // Sacs
        if (strpos($sku, 'BAG-') !== false || strpos($sku, '-BAG-') !== false) {
            return 'cadeaux';
        }
        
        // Coussins et maison
        if (strpos($sku, 'CSH-') !== false || strpos($sku, '-CSH-') !== false ||
            strpos($sku, 'PLW-') !== false || strpos($sku, '-PLW-') !== false) {
            return 'cadeaux';
        }
        
        // Étuis téléphone
        if (strpos($sku, 'CASE-') !== false || strpos($sku, '-CASE-') !== false) {
            return 'cadeaux';
        }
        
        // Stickers
        if (strpos($sku, 'STK-') !== false || strpos($sku, '-STK-') !== false) {
            return 'cadeaux';
        }
        
        // Livres photo
        if (strpos($sku, 'BOOK-') !== false || strpos($sku, '-BOOK-') !== false) {
            return 'livres';
        }
        
        // Cartes
        if (strpos($sku, 'CARD-') !== false || strpos($sku, '-CARD-') !== false ||
            strpos($sku, 'NOTE-') !== false || strpos($sku, '-NOTE-') !== false) {
            return 'cartes';
        }
        
        // Calendriers
        if (strpos($sku, 'CAL-') !== false || strpos($sku, '-CAL-') !== false) {
            return 'calendriers';
        }
        
        // Canvas = décoration
        if (strpos($sku, 'CAN-') !== false || strpos($sku, '-CAN-') !== false) {
            return 'decoration';
        }
        
        // Metal prints = décoration
        if (strpos($sku, 'MET-') !== false || strpos($sku, '-MET-') !== false) {
            return 'decoration';
        }
        
        // Fine Art Paper = grands formats
        if (strpos($sku, 'FAP-') !== false || strpos($sku, '-FAP-') !== false) {
            return 'grands_formats';
        }
        
        // Photo Paper = tirages classiques
        if (strpos($sku, 'PAP-') !== false || strpos($sku, '-PAP-') !== false) {
            return 'tirages';
        }
        
        return 'autres';
    }

    /**
     * Déterminer le type de papier/matériau basé sur le SKU
     */
    private function getPaperTypeFromSku(string $sku): string
    {
        // Canvas
        if (strpos($sku, 'CAN-') !== false || strpos($sku, '-CAN-') !== false) {
            return 'Canvas';
        }
        
        // Fine Art Paper
        if (strpos($sku, 'FAP-') !== false || strpos($sku, '-FAP-') !== false) {
            return 'Fine Art Paper';
        }
        
        // Photo Paper
        if (strpos($sku, 'PAP-') !== false || strpos($sku, '-PAP-') !== false) {
            return 'Photo Paper';
        }
        
        // Metal prints
        if (strpos($sku, 'MET-') !== false || strpos($sku, '-MET-') !== false) {
            return 'Metal';
        }
        
        // Puzzles
        if (strpos($sku, 'PUZ-') !== false || strpos($sku, '-PUZ-') !== false) {
            return 'Cardboard';
        }
        
        // Céramique (mugs)
        if (strpos($sku, 'MUG-') !== false || strpos($sku, '-MUG-') !== false) {
            return 'Ceramic';
        }
        
        // Textile (t-shirts, coussins, sacs)
        if (strpos($sku, 'TEE-') !== false || strpos($sku, '-TEE-') !== false ||
            strpos($sku, 'HOD-') !== false || strpos($sku, '-HOD-') !== false ||
            strpos($sku, 'CSH-') !== false || strpos($sku, '-CSH-') !== false ||
            strpos($sku, 'PLW-') !== false || strpos($sku, '-PLW-') !== false ||
            strpos($sku, 'BAG-') !== false || strpos($sku, '-BAG-') !== false) {
            return 'Fabric';
        }
        
        // Plastique/Silicone (phone cases)
        if (strpos($sku, 'CASE-') !== false || strpos($sku, '-CASE-') !== false) {
            return 'Plastic';
        }
        
        // Vinyl (stickers)
        if (strpos($sku, 'STK-') !== false || strpos($sku, '-STK-') !== false) {
            return 'Vinyl';
        }
        
        // Papier (livres, cartes, calendriers)
        if (strpos($sku, 'BOOK-') !== false || strpos($sku, '-BOOK-') !== false ||
            strpos($sku, 'CARD-') !== false || strpos($sku, '-CARD-') !== false ||
            strpos($sku, 'NOTE-') !== false || strpos($sku, '-NOTE-') !== false ||
            strpos($sku, 'CAL-') !== false || strpos($sku, '-CAL-') !== false) {
            return 'Paper';
        }
        
        return 'Standard';
    }

    /**
     * Estimer le prix de base selon le type de produit (sera remplacé par des quotes réels)
     */
    private function estimateBasePrice(string $sku, array $dimensions): float
    {
        // Prix fixes pour certains types de produits
        
        // Puzzles
        if (strpos($sku, 'PUZ-120') !== false) return 19.99;
        if (strpos($sku, 'PUZ-252') !== false) return 24.99;
        if (strpos($sku, 'PUZ-500') !== false) return 29.99;
        if (strpos($sku, 'PUZ-1000') !== false) return 34.99;
        if (strpos($sku, 'PUZ-') !== false) return 24.99; // Default puzzle price
        
        // Mugs
        if (strpos($sku, 'MUG-11OZ') !== false) return 12.99;
        if (strpos($sku, 'MUG-15OZ') !== false) return 15.99;
        if (strpos($sku, 'MUG-') !== false) return 12.99; // Default mug price
        if (strpos($sku, 'TUM-') !== false) return 18.99; // Tumbler
        
        // T-shirts et vêtements
        if (strpos($sku, 'TEE-') !== false) return 19.99;
        if (strpos($sku, 'HOD-') !== false) return 39.99; // Hoodies
        
        // Sacs
        if (strpos($sku, 'BAG-TOTE') !== false) return 14.99;
        if (strpos($sku, 'BAG-POUCH') !== false) return 12.99;
        if (strpos($sku, 'BAG-BACKPACK') !== false) return 49.99;
        if (strpos($sku, 'BAG-') !== false) return 16.99; // Default bag
        
        // Coussins
        if (strpos($sku, 'CSH-') !== false || strpos($sku, 'PLW-') !== false) return 24.99;
        
        // Phone cases
        if (strpos($sku, 'CASE-') !== false) return 24.99;
        
        // Stickers
        if (strpos($sku, 'STK-') !== false) return 3.99;
        
        // Livres photo - prix selon format approximatif
        if (strpos($sku, 'BOOK-') !== false) {
            if (strpos($sku, 'A4') !== false) return 19.99;
            if (strpos($sku, 'HARD') !== false) return 29.99;
            return 14.99; // Default book price
        }
        
        // Cartes
        if (strpos($sku, 'CARD-') !== false) return 2.99;
        if (strpos($sku, 'NOTE-') !== false) return 1.99;
        
        // Calendriers
        if (strpos($sku, 'CAL-WALL') !== false) return 14.99;
        if (strpos($sku, 'CAL-DESK') !== false) return 9.99;
        if (strpos($sku, 'CAL-') !== false) return 12.99;
        
        // Calcul basé sur les dimensions pour les impressions
        $width = $dimensions['width'] ?? 10;
        $height = $dimensions['height'] ?? 10;
        $area = $width * $height;
        
        // Tarifs par matériau et surface
        $baseRate = 0.02; // €/inch² de base
        
        if (strpos($sku, 'MET-') !== false) {
            $baseRate *= 4; // Metal prints très chers
        } elseif (strpos($sku, 'CAN-') !== false) {
            $baseRate *= 3; // Canvas cher
        } elseif (strpos($sku, 'FAP-') !== false) {
            $baseRate *= 2; // Fine Art plus cher
        } elseif (strpos($sku, 'PAP-') !== false) {
            $baseRate *= 1; // Photo paper standard
        }
        
        $calculatedPrice = $area * $baseRate;
        
        // Prix minimum selon le type
        $minPrice = 2.99; // Prix minimum général
        if (strpos($sku, 'MET-') !== false) $minPrice = 19.99;
        if (strpos($sku, 'CAN-') !== false) $minPrice = 14.99;
        if (strpos($sku, 'FAP-') !== false) $minPrice = 8.99;
        
        return round(max($calculatedPrice, $minPrice), 2);
    }

    /**
     * Obtenir la liste des SKUs disponibles
     */
    public function getAvailableSkus(): array
    {
        return self::COMMON_SKUS;
    }
}