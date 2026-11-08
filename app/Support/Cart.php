<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

/**
 * Session cart, one per store. Each line is keyed by "{productId}" or, when a variant was
 * chosen, "{productId}:{variantId}" - prices and stock always come from the database, never
 * the session, so a line's value is just the quantity.
 */
class Cart
{
    public function __construct(private int $storeId) {}

    private function key(): string
    {
        return 'cart.'.$this->storeId;
    }

    public function raw(): array
    {
        return session($this->key(), []);
    }

    private function lineKey(int $productId, ?int $variantId): string
    {
        return $variantId ? "$productId:$variantId" : (string) $productId;
    }

    /** @return array{0: int, 1: ?int} [productId, variantId] */
    public static function parseKey(string $key): array
    {
        [$productId, $variantId] = array_pad(explode(':', $key, 2), 2, null);

        return [(int) $productId, $variantId !== null ? (int) $variantId : null];
    }

    public function set(int $productId, int $qty, ?int $variantId = null): void
    {
        $cart = $this->raw();
        $key = $this->lineKey($productId, $variantId);
        if ($qty <= 0) {
            unset($cart[$key]);
        } else {
            $cart[$key] = min($qty, 99);
        }
        session([$this->key() => $cart]);
    }

    public function add(int $productId, int $qty, ?int $variantId = null): void
    {
        $key = $this->lineKey($productId, $variantId);
        $this->set($productId, ($this->raw()[$key] ?? 0) + $qty, $variantId);
    }

    public function clear(): void
    {
        session()->forget($this->key());
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    /** @return Collection<int, array{product: Product, variant: ?ProductVariant, qty: int, price: float, total: float}> */
    public function lines(): Collection
    {
        $raw = $this->raw();
        if (! $raw) {
            return collect();
        }
        $parsed = collect(array_keys($raw))->map(fn ($k) => self::parseKey((string) $k));
        $products = Product::with('images')->where('is_active', true)->whereIn('id', $parsed->pluck(0)->unique())->get()->keyBy('id');
        $variantIds = $parsed->pluck(1)->filter()->unique();
        $variants = $variantIds->isNotEmpty() ? ProductVariant::whereIn('id', $variantIds)->get()->keyBy('id') : collect();

        return collect($raw)->map(function ($qty, $key) use ($products, $variants) {
            [$productId, $variantId] = self::parseKey((string) $key);
            $p = $products->get($productId);
            $variant = $variantId ? $variants->get($variantId) : null;
            if (! $p || ($variantId && ! $variant)) {
                return null;
            }
            $stock = $p->stockFor($variant);
            if ($stock < 1) {
                return null;
            }
            $qty = min($qty, $stock);
            $price = (float) $p->priceFor($variant);

            return ['product' => $p, 'variant' => $variant, 'qty' => $qty, 'price' => $price, 'total' => round($price * $qty, 2)];
        })->filter()->values();
    }

    public function subtotal(): float
    {
        return round($this->lines()->sum('total'), 2);
    }
}
