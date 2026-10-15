<?php

namespace App\UseCases\User;

use App\Enums\UserRole;
use App\Exceptions\Business\BusinessRuleViolationException;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * ユーザーの権限（role）の変更。
 *
 * ルール:
 *   - 自分自身の権限は変更できない
 *   - 最後の有効なadminは、admin以外に変更できない（降格できない）
 *
 * 有効なadminの行を先にロックしてから人数を確認する（SetUserActiveUseCase と同じ）。
 */
class ChangeUserRoleUseCase
{
    private const MAX_ATTEMPTS = 3;

    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * @param  User  $executor  実行者
     *
     * @throws ModelNotFoundException 対象が存在しない
     * @throws BusinessRuleViolationException 自分自身の変更、または最後の有効なadminの降格
     */
    public function execute(User $executor, int $targetId, UserRole $role): User
    {
        if ($executor->id === $targetId) {
            throw new BusinessRuleViolationException(
                BusinessRuleViolationException::SELF_OPERATION,
                '自分自身の権限は変更できません。',
            );
        }

        return DB::transaction(function () use ($targetId, $role) {
            $activeAdmins = $this->users->lockActiveAdminsForUpdate();

            $target = $this->users->find($targetId);

            if ($target === null) {
                throw (new ModelNotFoundException)->setModel(User::class, [$targetId]);
            }

            if ($target->role === UserRole::Admin
                && $target->is_active
                && $role !== UserRole::Admin
                && $activeAdmins->count() <= 1
            ) {
                throw new BusinessRuleViolationException(
                    BusinessRuleViolationException::LAST_ADMIN,
                    '最後の有効な管理者は降格できません。',
                );
            }

            $this->users->update($target, ['role' => $role]);

            return $target;
        }, self::MAX_ATTEMPTS);
    }
}
