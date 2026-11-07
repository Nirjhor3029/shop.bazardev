<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;

/** Session cart, one per store. Holds only product_id => quantity. Prices always come from the database. */
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

    public function set(int $productId, int $qty): void
    {
        $cart = $this->raw();
        if ($qty <= 0) {
            unset($cart[$productId]);
        } else {
            $cart[$productId] = min($qty, 99);
        }
        session([$this->key() => $cart]);
    }

    public function add(int $productId, int $qty): void
    {
        $this->set($productId, ($this->raw()[$productId] ?? 0) + $qty);
    }

    public function clear(): void
    {
        session()->forget($this->key());
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    /** @return Collection<int, array{product: Product, qty: int, price: float, total: float}> */
    public function lines(): Collection
    {
        $raw = $this->raw();
        if (! $raw) {
            return collect();
        }
        $products = Product::with('images')->where('is_active', true)->whereIn('id', array_keys($raw))->get()->keyBy('id');

        return collect($raw)->map(function ($qty, $id) use ($products) {
            $p = $products->get($id);
            if (! $p || $p->stock < 1) {
                return null;
            }
            $qty = min($qty, $p->stock);
            $price = (float) $p->currentPrice();

            return ['product' => $p, 'qty' => $qty, 'price' => $price, 'total' => round($price * $qty, 2)];
        })->filter()->values();
    }

    public function subtotal(): float
    {
        return round($this->lines()->sum('total'), 2);
    }
}
