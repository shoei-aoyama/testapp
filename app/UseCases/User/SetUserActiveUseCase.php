<?php

namespace App\UseCases\User;

use App\Enums\UserRole;
use App\Exceptions\Business\BusinessRuleViolationException;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * ユーザーの有効化・無効化。
 *
 * 無効化のルール:
 *   - 自分自身は無効化できない
 *   - 最後の有効なadminは無効化できない
 *
 * 有効なadminの行を先にロックしてから人数を確認する。
 * 複数のadminが同時に互いを無効化しても、有効なadminが0人にならない。
 */
class SetUserActiveUseCase
{
    private const MAX_ATTEMPTS = 3;

    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * @param  User  $executor  実行者
     *
     * @throws ModelNotFoundException 対象が存在しない
     * @throws BusinessRuleViolationException 自分自身の無効化、または最後の有効なadminの無効化
     */
    public function execute(User $executor, int $targetId, bool $active): User
    {
        // 自分自身の判定は、DBを読む前にできる
        if (! $active && $executor->id === $targetId) {
            throw new BusinessRuleViolationException(
                BusinessRuleViolationException::SELF_OPERATION,
                '自分自身を無効化することはできません。',
            );
        }

        return DB::transaction(function () use ($targetId, $active) {
            // 無効化するときだけ、先に有効なadminをロックする（ロックしたあとに対象を読む）
            $activeAdmins = $active ? null : $this->users->lockActiveAdminsForUpdate();

            $target = $this->users->find($targetId);

            if ($target === null) {
                throw (new ModelNotFoundException)->setModel(User::class, [$targetId]);
            }

            if (! $active
                && $target->role === UserRole::Admin
                && $target->is_active
                && $activeAdmins->count() <= 1
            ) {
                throw new BusinessRuleViolationException(
                    BusinessRuleViolationException::LAST_ADMIN,
                    '最後の有効な管理者は無効化できません。',
                );
            }

            $this->users->update($target, ['is_active' => $active]);

            return $target;
        }, self::MAX_ATTEMPTS);
    }
}
