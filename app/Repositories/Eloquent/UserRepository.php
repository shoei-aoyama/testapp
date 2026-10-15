<?php

namespace App\Repositories\Eloquent;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
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
