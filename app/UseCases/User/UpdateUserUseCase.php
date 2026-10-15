<?php

namespace App\UseCases\User;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;

/**
 * ユーザーの基本情報（名前・メールアドレス・パスワード）の更新。
 *
 * 権限（role）と有効・無効は、ここでは変更できない
 * （それぞれ ChangeUserRoleUseCase / SetUserActiveUseCase。最後のadmin保護などの規則があるため）。
 */
class UpdateUserUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * @param  array{name?: string, email?: string, password?: string|null}  $attributes  password は平文。null・空文字なら変更しない
     *
     * @throws ModelNotFoundException ユーザーが存在しない
     */
    public function execute(int $userId, array $attributes): User
    {
        $user = $this->users->find($userId);

        if ($user === null) {
            throw (new ModelNotFoundException)->setModel(User::class, [$userId]);
        }

        $changes = array_intersect_key($attributes, array_flip(['name', 'email']));

        if (isset($attributes['password']) && $attributes['password'] !== '') {
            $changes['password'] = Hash::make($attributes['password']);
        }

        $this->users->update($user, $changes);

        return $user;
    }
}
