<?php

namespace App\Services;

use App\Exceptions\Business\BusinessRuleViolationException;

/**
 * 入庫・出庫・棚卸・移動申請に共通する、明細の入力チェック。
 *
 * FormRequestでも同じ確認をするが、HTTP以外から呼ばれても守れるよう、UseCaseでも確認する。
 * 入庫・出庫には、同一商品の重複を防ぐDB制約がないため、特に必要。
 */
class StockItemGuard
{
    /**
     * 明細を確認し、商品IDと数量を整数にそろえて返す（入力の順序は保つ）。
     *
     * 確認する内容:
     *   - 明細が空でない
     *   - 商品IDが1以上の整数
     *   - 数量が整数で、$minQuantity 以上
     *   - 同一商品が重複していない
     *
     * 整数として扱えるのは、整数と、整数の形の文字列（例: "5"）のみ。小数（1.5）や数値以外は不正とする。
     *
     * @param  array<int, mixed>  $items  各要素は ['product_id' => ..., $quantityKey => ...]
     * @param  string  $quantityKey  数量のキー名（入庫・出庫・移動は 'quantity'、棚卸は 'actual_quantity'）
     * @param  int  $minQuantity  数量の最小値（入庫・出庫・移動は 1、棚卸の実在庫は 0）
     * @return list<array<string, int>>  ['product_id' => int, $quantityKey => int]
     *
     * @throws BusinessRuleViolationException
     */
    public function normalize(array $items, string $quantityKey = 'quantity', int $minQuantity = 1): array
    {
        if ($items === []) {
            throw new BusinessRuleViolationException(
                BusinessRuleViolationException::EMPTY_ITEMS,
                '明細が空です。1件以上指定してください。',
            );
        }

        $normalized = [];
        $seen = [];

        foreach ($items as $item) {
            $productId = is_array($item) ? $this->toInt($item['product_id'] ?? null) : null;

            if ($productId === null || $productId < 1) {
                throw new BusinessRuleViolationException(
                    BusinessRuleViolationException::INVALID_ITEM,
                    '商品IDが不正です。',
                );
            }

            $quantity = $this->toInt($item[$quantityKey] ?? null);

            if ($quantity === null || $quantity < $minQuantity) {
                throw new BusinessRuleViolationException(
                    BusinessRuleViolationException::INVALID_QUANTITY,
                    sprintf('商品ID %d の数量は、%d以上の整数で指定してください。', $productId, $minQuantity),
                );
            }

            if (isset($seen[$productId])) {
                throw new BusinessRuleViolationException(
                    BusinessRuleViolationException::DUPLICATE_PRODUCT,
                    sprintf('商品ID %d が重複して指定されています。', $productId),
                );
            }

            $seen[$productId] = true;
            $normalized[] = ['product_id' => $productId, $quantityKey => $quantity];
        }

        return $normalized;
    }

    /**
     * 整数、または整数の形の文字列だけを整数にする。それ以外は null。
     */
    private function toInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }

        return null;
    }
}
