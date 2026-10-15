<?php

namespace Database\Seeders;

use App\Enums\StockMovementType;
use App\Enums\StockTransferStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * ランダムなダミーデータ: 商品の追加、在庫、在庫変動履歴、倉庫間移動。
 *
 * 在庫と履歴の整合性を保つため、次の順序で「時系列に積み上げて」作る。
 *   1. 商品×倉庫ごとに、通常の履歴（入庫・出庫・棚卸調整）を古い順に生成し、最後の数量を現在庫にする
 *   2. すべての通常履歴が終わったあとのイベントとして、完了済みの倉庫間移動を時系列順に追加する
 *      （TRANSFER_OUT / TRANSFER_IN の履歴を作り、在庫を更新する）
 *   3. pending / cancelled の倉庫間移動は、ヘッダと明細だけを作る（在庫は動かさない）
 *
 * 履歴の日時は、通常履歴が「90日前〜10日前」、完了済み移動が「9日前〜1日前」になる。
 * UseCaseは使わず、Modelを直接使う。
 * 固定データ（CategorySeeder / WarehouseSeeder / UserSeeder / ProductSeeder）を先に実行しておくこと。
 */
class DummyDataSeeder extends Seeder
{
    private const PRODUCT_COUNT = 40;

    private const INACTIVE_PRODUCT_COUNT = 4;

    /** 商品×有効倉庫のうち、在庫を持たせる割合（%） */
    private const STOCK_PERCENT = 70;

    private const MIN_EVENTS = 5;

    private const MAX_EVENTS = 15;

    private const PENDING_TRANSFERS = 5;

    private const CANCELLED_TRANSFERS = 3;

    private const COMPLETED_TRANSFERS = 5;

    public function run(): void
    {
        $categoryIds = Category::where('is_active', true)->pluck('id');
        $warehouses = Warehouse::where('is_active', true)->get();
        $executorIds = User::where('is_active', true)
            ->whereIn('role', [UserRole::Manager->value, UserRole::Admin->value])
            ->pluck('id');
        $requesterIds = User::where('is_active', true)->pluck('id');

        if ($categoryIds->isEmpty() || $warehouses->count() < 2 || $executorIds->isEmpty()) {
            throw new RuntimeException('固定データ（カテゴリ・倉庫・ユーザー）が不足しています。先に固定Seederを実行してください。');
        }

        DB::transaction(function () use ($categoryIds, $warehouses, $executorIds, $requesterIds) {
            $this->createProducts($categoryIds);

            // 固定商品とダミー商品の両方を対象にする
            $products = Product::all();

            $stock = $this->seedMovementsAndInventories($products, $warehouses, $executorIds);

            $this->seedCompletedTransfers($products, $warehouses, $requesterIds, $executorIds, $stock);
            $this->seedPendingTransfers($products, $warehouses, $requesterIds);
            $this->seedCancelledTransfers($products, $warehouses, $requesterIds, $executorIds);
        });
    }

    /**
     * ダミー商品を作る。先頭の数件は無効商品にする。
     */
    private function createProducts(Collection $categoryIds): void
    {
        for ($i = 0; $i < self::PRODUCT_COUNT; $i++) {
            $factory = Product::factory();

            if ($i < self::INACTIVE_PRODUCT_COUNT) {
                $factory = $factory->inactive();
            }

            $factory->create(['category_id' => $categoryIds->random()]);
        }
    }

    /**
     * 通常の履歴（入庫・出庫・棚卸調整）を時系列に生成し、最後の数量を在庫として保存する。
     *
     * @return array<int, array<int, int>> 商品ID => 倉庫ID => 現在庫
     */
    private function seedMovementsAndInventories(Collection $products, Collection $warehouses, Collection $executorIds): array
    {
        $now = CarbonImmutable::now(config('app.timezone'));
        $start = $now->subDays(90);
        $end = $now->subDays(10);

        $stock = [];
        $movements = [];
        $inventories = [];

        foreach ($products as $product) {
            foreach ($warehouses as $warehouse) {
                if (random_int(1, 100) > self::STOCK_PERCENT) {
                    continue;
                }

                $timestamps = collect(range(1, random_int(self::MIN_EVENTS, self::MAX_EVENTS)))
                    ->map(fn () => $this->randomTimestamp($start, $end))
                    ->sort()
                    ->values();

                $quantity = 0;

                foreach ($timestamps as $index => $timestamp) {
                    [$type, $amount, $reason] = $this->nextEvent($index === 0, $quantity);

                    $before = $quantity;
                    $after = $type->isIncrease() ? $before + $amount : $before - $amount;

                    $movements[] = [
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouse->id,
                        'type' => $type->value,
                        'quantity' => $amount,
                        'quantity_before' => $before,
                        'quantity_after' => $after,
                        'reason' => $reason,
                        'user_id' => $executorIds->random(),
                        'stock_transfer_item_id' => null,
                        'created_at' => $this->at($timestamp)->format('Y-m-d H:i:s'),
                    ];

                    $quantity = $after;
                }

                $stock[$product->id][$warehouse->id] = $quantity;
                $inventories[] = [
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'quantity' => $quantity,
                    'created_at' => $now->format('Y-m-d H:i:s'),
                    'updated_at' => $now->format('Y-m-d H:i:s'),
                ];
            }
        }

        // 全体を日時順に並べてから登録する（idの順序と日時の順序を一致させる）。
        // usort は安定ソートなので、同じ秒の履歴は生成した順（数量の連鎖の順）のまま残る。
        usort($movements, fn (array $a, array $b) => strcmp($a['created_at'], $b['created_at']));

        foreach (array_chunk($movements, 500) as $chunk) {
            StockMovement::insert($chunk);
        }

        foreach (array_chunk($inventories, 500) as $chunk) {
            Inventory::insert($chunk);
        }

        return $stock;
    }

    /**
     * 次の通常履歴（種別・数量・理由）を決める。在庫は0未満にならない。
     *
     * @return array{0: StockMovementType, 1: int, 2: string|null}
     */
    private function nextEvent(bool $isFirst, int $current): array
    {
        if ($isFirst) {
            return [StockMovementType::In, random_int(10, 100), $this->inReason()];
        }

        $roll = random_int(1, 100);

        // 入庫（在庫0のときは、出庫の代わりに入庫にする）
        if ($roll <= 40 || ($current === 0 && $roll <= 70)) {
            return [StockMovementType::In, random_int(1, 100), $this->inReason()];
        }

        // 出庫
        if ($roll <= 70) {
            return [StockMovementType::Out, random_int(1, $current), $this->outReason()];
        }

        // 棚卸調整（実在庫との差分を履歴にする）
        $actual = random_int(0, $current + 30);

        if ($actual === $current) {
            return [StockMovementType::In, random_int(1, 10), $this->inReason()];
        }

        if ($actual > $current) {
            return [StockMovementType::AdjustmentIn, $actual - $current, null];
        }

        return [StockMovementType::AdjustmentOut, $current - $actual, null];
    }

    private function inReason(): ?string
    {
        return fake()->optional(0.7)->randomElement(['仕入れ', '返品', 'その他']);
    }

    private function outReason(): ?string
    {
        return fake()->optional(0.7)->randomElement(['販売', '廃棄', '破損', 'その他']);
    }

    /**
     * 完了済みの倉庫間移動を、日時の古い順に作る。
     * 通常の履歴がすべて終わったあとのイベントなので、数量の時系列が壊れない。
     *
     * @param  array<int, array<int, int>>  $stock  現在庫（更新される）
     */
    private function seedCompletedTransfers(
        Collection $products,
        Collection $warehouses,
        Collection $requesterIds,
        Collection $executorIds,
        array &$stock,
    ): void {
        $now = CarbonImmutable::now(config('app.timezone'));

        $timestamps = collect(range(1, self::COMPLETED_TRANSFERS))
            ->map(fn () => $this->randomTimestamp($now->subDays(9), $now->subDays(1)))
            ->sort()
            ->values();

        $activeProducts = $products->where('is_active', true);

        foreach ($timestamps as $timestamp) {
            $completedAt = $this->at($timestamp);

            [$from, $to] = $warehouses->random(2)->values()->all();

            // 移動元に在庫がある有効な商品だけを対象にする
            $candidates = $activeProducts->filter(
                fn (Product $product) => ($stock[$product->id][$from->id] ?? 0) > 0
            );

            if ($candidates->isEmpty()) {
                continue;
            }

            $picked = $candidates
                ->random(min(random_int(1, 3), $candidates->count()))
                ->sortBy('id');

            $executorId = $executorIds->random();
            $requestedAt = $completedAt->subHours(random_int(1, 48));

            $transfer = new StockTransfer([
                'from_warehouse_id' => $from->id,
                'to_warehouse_id' => $to->id,
                'status' => StockTransferStatus::Completed,
                'requested_by' => $requesterIds->random(),
                'completed_by' => $executorId,
                'completed_at' => $completedAt,
            ]);
            $this->saveAt($transfer, $requestedAt);

            foreach ($picked as $product) {
                $amount = random_int(1, min($stock[$product->id][$from->id], 30));

                $item = new StockTransferItem([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $product->id,
                    'quantity' => $amount,
                ]);
                $this->saveAt($item, $requestedAt);

                $fromBefore = $stock[$product->id][$from->id];
                $toBefore = $stock[$product->id][$to->id] ?? 0;

                $this->createMovement([
                    'product_id' => $product->id,
                    'warehouse_id' => $from->id,
                    'type' => StockMovementType::TransferOut,
                    'quantity' => $amount,
                    'quantity_before' => $fromBefore,
                    'quantity_after' => $fromBefore - $amount,
                    'reason' => null,
                    'user_id' => $executorId,
                    'stock_transfer_item_id' => $item->id,
                ], $completedAt);

                $this->createMovement([
                    'product_id' => $product->id,
                    'warehouse_id' => $to->id,
                    'type' => StockMovementType::TransferIn,
                    'quantity' => $amount,
                    'quantity_before' => $toBefore,
                    'quantity_after' => $toBefore + $amount,
                    'reason' => null,
                    'user_id' => $executorId,
                    'stock_transfer_item_id' => $item->id,
                ], $completedAt);

                $stock[$product->id][$from->id] = $fromBefore - $amount;
                $stock[$product->id][$to->id] = $toBefore + $amount;

                Inventory::updateOrCreate(
                    ['product_id' => $product->id, 'warehouse_id' => $from->id],
                    ['quantity' => $stock[$product->id][$from->id]]
                );
                Inventory::updateOrCreate(
                    ['product_id' => $product->id, 'warehouse_id' => $to->id],
                    ['quantity' => $stock[$product->id][$to->id]]
                );
            }
        }
    }

    /**
     * pending の倉庫間移動を作る。ヘッダと明細だけで、在庫は動かさない。
     */
    private function seedPendingTransfers(Collection $products, Collection $warehouses, Collection $requesterIds): void
    {
        $now = CarbonImmutable::now(config('app.timezone'));
        $activeProducts = $products->where('is_active', true);

        for ($i = 0; $i < self::PENDING_TRANSFERS; $i++) {
            [$from, $to] = $warehouses->random(2)->values()->all();
            $createdAt = $now->subHours(random_int(1, 72));

            $transfer = new StockTransfer([
                'from_warehouse_id' => $from->id,
                'to_warehouse_id' => $to->id,
                'status' => StockTransferStatus::Pending,
                'requested_by' => $requesterIds->random(),
            ]);
            $this->saveAt($transfer, $createdAt);

            $this->createItems($transfer, $activeProducts, $createdAt);
        }
    }

    /**
     * cancelled の倉庫間移動を作る。ヘッダと明細だけで、在庫は動かさない。
     */
    private function seedCancelledTransfers(
        Collection $products,
        Collection $warehouses,
        Collection $requesterIds,
        Collection $executorIds,
    ): void {
        $now = CarbonImmutable::now(config('app.timezone'));
        $activeProducts = $products->where('is_active', true);

        for ($i = 0; $i < self::CANCELLED_TRANSFERS; $i++) {
            [$from, $to] = $warehouses->random(2)->values()->all();
            $createdAt = $now->subDays(random_int(2, 8));

            $transfer = new StockTransfer([
                'from_warehouse_id' => $from->id,
                'to_warehouse_id' => $to->id,
                'status' => StockTransferStatus::Cancelled,
                'requested_by' => $requesterIds->random(),
                'cancelled_by' => $executorIds->random(),
                'cancelled_at' => $createdAt->addHours(random_int(1, 24)),
            ]);
            $this->saveAt($transfer, $createdAt);

            $this->createItems($transfer, $activeProducts, $createdAt);
        }
    }

    /**
     * 移動に、重複しない商品の明細を1〜3件作る（数量は1〜30）。
     */
    private function createItems(StockTransfer $transfer, Collection $activeProducts, CarbonImmutable $createdAt): void
    {
        $picked = $activeProducts->random(random_int(1, 3))->sortBy('id');

        foreach ($picked as $product) {
            $item = new StockTransferItem([
                'stock_transfer_id' => $transfer->id,
                'product_id' => $product->id,
                'quantity' => random_int(1, 30),
            ]);
            $this->saveAt($item, $createdAt);
        }
    }

    /**
     * 履歴を1件作る。履歴は updated_at を持たないため、created_at だけを設定する。
     *
     * @param  array<string, mixed>  $attributes
     */
    private function createMovement(array $attributes, CarbonImmutable $time): void
    {
        $movement = new StockMovement($attributes);
        $movement->created_at = $time;
        $movement->save();
    }

    /**
     * created_at / updated_at を指定の日時にして保存する（updated_at を持つModel用）。
     */
    private function saveAt(Model $model, CarbonImmutable $time): void
    {
        $model->created_at = $time;
        $model->updated_at = $time;
        $model->save();
    }

    private function randomTimestamp(CarbonImmutable $start, CarbonImmutable $end): int
    {
        return random_int($start->getTimestamp(), $end->getTimestamp());
    }

    private function at(int $timestamp): CarbonImmutable
    {
        return CarbonImmutable::createFromTimestamp($timestamp, config('app.timezone'));
    }
}
