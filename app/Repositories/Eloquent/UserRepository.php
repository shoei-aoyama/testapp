<?php

namespace App\Repositories\Eloquent;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
  public function find(int $id): ?User
  {
    return User::find($id);
  }

  public function create(array $attributes): User
  {
    // role は fillable に含めていない（一括代入で権限が変わらないようにするため）ので、forceFill で設定する
    $user = new User;
    $user->forceFill($attributes)->save();

    return $user;
  }

  public function update(User $user, array $attributes): void
  {
    $user->forceFill($attributes)->save();
  }

  public function lockActiveAdminsForUpdate(): Collection
  {
    return User::query()
      ->where('role', UserRole::Admin->value)
      ->where('is_active', true)
      ->orderBy('id')
      ->lockForUpdate()
      ->get();
  }
}