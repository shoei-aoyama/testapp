<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * 固定データ: ユーザー。
 * admin / manager / staff 各1件と、無効ユーザー1件。
 * パスワードはすべて開発用の「password」（保存時に自動でハッシュ化される）。
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => '管理者', 'email' => 'admin@example.com', 'role' => UserRole::Admin, 'is_active' => true],
            ['name' => '在庫担当', 'email' => 'manager@example.com', 'role' => UserRole::Manager, 'is_active' => true],
            ['name' => '一般ユーザー', 'email' => 'staff@example.com', 'role' => UserRole::Staff, 'is_active' => true],
            ['name' => '無効ユーザー', 'email' => 'disabled@example.com', 'role' => UserRole::Staff, 'is_active' => false],
        ];

        foreach ($users as $attributes) {
            // role は $fillable に含めていないため、forceFill で明示的に設定する
            User::firstOrNew(['email' => $attributes['email']])
                ->forceFill($attributes + [
                    'password' => 'password',
                    'email_verified_at' => now(),
                ])
                ->save();
        }
    }
}
