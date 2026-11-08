<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Validation\ValidationException;

/** Builds/syncs a product's variant matrix from up to 3 option groups (e.g. Color: Red,Blue). */
class VariantGenerator
{
    private const MAX_COMBINATIONS = 60;

    /** @return array<string, string[]> option name => values, read back from the product's own variants (so the generator form repopulates itself). */
    public static function groupsFor(Product $product): array
    {
        $groups = [];
        foreach ($product->variants as $variant) {
            foreach ($variant->options ?? [] as $name => $value) {
                $groups[$name][] = $value;
            }
        }

        return collect($groups)->map(fn ($values) => array_values(array_unique($values)))->all();
    }

    /** Regenerates the variant matrix from submitted option1_name/option1_values (...2, ...3). Existing combinations keep their price/stock/sku; removed ones are deleted. */
    public function generate(Product $product, array $input): void
    {
        $groups = [];
        for ($i = 1; $i <= 3; $i++) {
            $name = trim((string) ($input["option{$i}_name"] ?? ''));
            $values = collect(explode(',', (string) ($input["option{$i}_values"] ?? '')))->map(fn ($v) => trim($v))->filter()->unique()->values()->all();
            if ($name && $values) {
                $groups[$name] = $values;
            }
        }

        if (! $groups) {
            $product->variants()->delete();

            return;
        }

        $combos = [[]];
        foreach ($groups as $name => $values) {
            $next = [];
            foreach ($combos as $combo) {
                foreach ($values as $value) {
                    $next[] = $combo + [$name => $value];
                }
            }
            $combos = $next;
        }
        if (count($combos) > self::MAX_COMBINATIONS) {
            throw ValidationException::withMessages(['option1_name' => 'Too many combinations ('.count($combos).') - max '.self::MAX_COMBINATIONS.'. Use fewer values.']);
        }

        $existing = $product->variants->keyBy(fn ($v) => json_encode($v->options));
        $keep = [];
        foreach ($combos as $i => $options) {
            $variant = $existing->get(json_encode($options));
            if (! $variant) {
                $variant = $product->variants()->create(['options' => $options, 'stock' => 0, 'sort_order' => $i]);
            } else {
                $variant->update(['sort_order' => $i]);
            }
            $keep[] = $variant->id;
        }
        $product->variants()->whereNotIn('id', $keep)->delete();
    }
}
