<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Currency;
use App\Models\Account;
use App\Models\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SimilarListingsService
{
    /**
     * Find similar listings for a given product
     */
    public function findSimilar(string $type, int $productId, int $limit = 10): Collection
    {
        $product = $this->getProduct($type, $productId);

        if (!$product) {
            return collect();
        }

        $model = $this->getModel($type);

        // Get candidates from same game
        $candidates = $model::where('game_id', $product->game_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->with(['user', 'game'])
            ->get();

        // For currencies, use much lower threshold since they're commodities
        // For items/accounts/services, use higher threshold
        $threshold = $type === 'currency' ? 0.35 : 0.6;

        // Score and filter similar products
        $similarProducts = $candidates->map(function ($candidate) use ($product, $type) {
            $score = $this->calculateSimilarityScore($product, $candidate, $type);

            return [
                'product' => $candidate,
                'similarity_score' => $score,
            ];
        })
        ->filter(fn($item) => $item['similarity_score'] >= $threshold)
        ->sortByDesc('similarity_score')
        ->take($limit)
        ->pluck('product');

        return $similarProducts;
    }

    /**
     * Find all listings with the same normalized title (for comparison view)
     */
    public function findExactMatches(string $type, int $productId): Collection
    {
        $product = $this->getProduct($type, $productId);

        if (!$product) {
            return collect();
        }

        $normalizedTitle = $this->normalizeTitle($product->title);
        $model = $this->getModel($type);

        return $model::where('game_id', $product->game_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->get()
            ->filter(function ($item) use ($normalizedTitle) {
                return $this->normalizeTitle($item->title) === $normalizedTitle;
            })
            ->values();
    }

    /**
     * Calculate similarity score between two products
     */
    private function calculateSimilarityScore($product1, $product2, string $type = 'item'): float
    {
        $score = 0;

        // For currencies, be much more aggressive with matching
        if ($type === 'currency') {
            $normalized1 = $this->normalizeCurrencyTitle($product1->title);
            $normalized2 = $this->normalizeCurrencyTitle($product2->title);

            // If both contain same currency type (gold, gp, coins, etc.), very high score
            $currencyKeywords = ['gold', 'gp', 'coins', 'coin', 'credits', 'money', 'currency', 'gil', 'zeny', 'mesos'];
            $hasCommonCurrency = false;

            foreach ($currencyKeywords as $keyword) {
                if (str_contains($normalized1, $keyword) && str_contains($normalized2, $keyword)) {
                    $score += 0.6; // Big boost for same currency type
                    $hasCommonCurrency = true;
                    break;
                }
            }

            // Title similarity (0-0.3 points for currencies)
            $titleSimilarity = $this->stringSimilarity($normalized1, $normalized2);
            $score += $titleSimilarity * 0.3;

            // Exact normalized match
            if ($normalized1 === $normalized2) {
                $score += 0.2;
            }

            return min($score, 1.0);
        }

        // Regular scoring for items/accounts/services
        // Title similarity (0-0.5 points)
        $titleSimilarity = $this->stringSimilarity($product1->title, $product2->title);
        $score += $titleSimilarity * 0.5;

        // Exact normalized title match (bonus 0.3 points)
        if ($this->normalizeTitle($product1->title) === $this->normalizeTitle($product2->title)) {
            $score += 0.3;
        }

        // Tag overlap (0-0.2 points)
        if (!empty($product1->tags) && !empty($product2->tags)) {
            $tags1 = is_array($product1->tags) ? $product1->tags : [];
            $tags2 = is_array($product2->tags) ? $product2->tags : [];

            $commonTags = count(array_intersect($tags1, $tags2));
            $totalTags = count(array_unique(array_merge($tags1, $tags2)));

            if ($totalTags > 0) {
                $score += ($commonTags / $totalTags) * 0.2;
            }
        }

        return min($score, 1.0); // Cap at 1.0
    }

    /**
     * Calculate string similarity using Levenshtein distance
     */
    private function stringSimilarity(string $str1, string $str2): float
    {
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));

        $maxLen = max(strlen($str1), strlen($str2));

        if ($maxLen === 0) {
            return 1.0;
        }

        $distance = levenshtein($str1, $str2);

        return 1 - ($distance / $maxLen);
    }

    /**
     * Normalize title for exact matching
     * Removes special characters, extra spaces, common words
     */
    private function normalizeTitle(string $title): string
    {
        $normalized = strtolower($title);

        // Remove common filler words
        $fillerWords = ['the', 'a', 'an', 'x', '+', '-', 'new', 'used', 'rare', 'epic', 'legendary'];
        foreach ($fillerWords as $word) {
            $normalized = preg_replace('/\b' . preg_quote($word, '/') . '\b/i', '', $normalized);
        }

        // Remove special characters except alphanumeric and spaces
        $normalized = preg_replace('/[^a-z0-9\s]/', '', $normalized);

        // Remove extra spaces
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return trim($normalized);
    }

    /**
     * Normalize currency title - more aggressive than regular normalization
     * Removes amounts, numbers, quantity indicators to get core currency name
     */
    private function normalizeCurrencyTitle(string $title): string
    {
        $normalized = strtolower($title);

        // Remove common quantity/amount words
        $quantityWords = [
            'million', 'mil', 'm', 'billion', 'bil', 'b', 'thousand', 'k',
            'amount', 'qty', 'quantity', 'units', 'unit', 'stock',
            'fast', 'instant', 'quick', 'cheap', 'safe', 'trusted'
        ];

        foreach ($quantityWords as $word) {
            $normalized = preg_replace('/\b' . preg_quote($word, '/') . '\b/i', '', $normalized);
        }

        // Remove all numbers and their common separators (1,000,000 or 1.5m, etc.)
        $normalized = preg_replace('/[\d,\.]+/', '', $normalized);

        // Remove special characters except alphanumeric and spaces
        $normalized = preg_replace('/[^a-z0-9\s]/', '', $normalized);

        // Remove extra spaces
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return trim($normalized);
    }

    /**
     * Get product model instance
     */
    private function getProduct(string $type, int $id)
    {
        return match($type) {
            'item' => Item::find($id),
            'currency' => Currency::find($id),
            'account' => Account::find($id),
            'service' => Service::find($id),
            default => null,
        };
    }

    /**
     * Get model class for type
     */
    private function getModel(string $type): string
    {
        return match($type) {
            'item' => Item::class,
            'currency' => Currency::class,
            'account' => Account::class,
            'service' => Service::class,
            default => Item::class,
        };
    }

    /**
     * Group search results by similar products
     */
    public function groupSearchResults(Collection $products, string $type): Collection
    {
        $grouped = [];
        $processed = [];

        foreach ($products as $product) {
            if (in_array($product->id, $processed)) {
                continue;
            }

            $normalizedTitle = $this->normalizeTitle($product->title);

            // Find exact matches in the current result set
            $matches = $products->filter(function ($p) use ($product, $normalizedTitle, $processed) {
                return !in_array($p->id, $processed)
                    && $p->game_id === $product->game_id
                    && $this->normalizeTitle($p->title) === $normalizedTitle;
            });

            if ($matches->count() > 1) {
                // Multiple sellers for same item
                $grouped[] = [
                    'type' => 'group',
                    'title' => $product->title,
                    'game_id' => $product->game_id,
                    'product_type' => $type,
                    'listings' => $matches->sortBy('price')->values()->all(),
                    'min_price' => $matches->min('price'),
                    'seller_count' => $matches->count(),
                ];

                // Mark as processed
                foreach ($matches as $m) {
                    $processed[] = $m->id;
                }
            } else {
                // Single unique listing
                $grouped[] = [
                    'type' => 'single',
                    'product' => $product,
                ];
                $processed[] = $product->id;
            }
        }

        return collect($grouped);
    }
}
