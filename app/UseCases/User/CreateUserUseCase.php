<?php

namespace App\UseCases\User;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

/**
 * ユーザーの作成。パスワードはハッシュ化して保存する。
 * 実行できるのは admin のみ（Gate `manage-users`）。
 */
class CreateUserUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * @param  array{name: string, email: string, password: string, role: UserRole, is_active?: bool}  $attributes  password は平文
     */
    public function execute(array $attributes): User
    {
        $attributes['password'] = Hash::make($attributes['password']);

        return $this->users->create($attributes);
    }
}
