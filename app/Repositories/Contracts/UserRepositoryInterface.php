<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * ユーザー（users）のデータアクセス。
 *
 * 名前が ...ForUpdate で終わるメソッドは、必ずトランザクション内で使うこと。
 * トランザクション外で呼ぶとロックがすぐ解放され、ロックの意味がなくなる。
 */
interface UserRepositoryInterface
{
  /**
   * ユーザーを1件取得する。存在しなければ null。
   */
  public function find(int $id): ?User;

  /**
   * ユーザーを作成する。role は fillable に含まれないため、このメソッドが直接設定する。
   *
   * @param  array{name: string, email: string, password: string, role: \App\Enums\UserRole, is_active?: bool}  $attributes  password はハッシュ済みの値
   */
  public function create(array $attributes): User;

  /**
   * ユーザーを更新する（渡した項目のみ）。role も更新できる。
   *
   * @param  array<string, mixed>  $attributes  password を含める場合はハッシュ済みの値
   */
  public function update(User $user, array $attributes): void;

  /**
   * 有効なadminの行を、id 昇順でロックして返す。
   * 最後の有効なadminを守る判定（複数adminの同時操作への対策）に使う。
   *
   * トランザクション内で使うこと。
   *
   * @return Collection<int, User>
   */
  public function lockActiveAdminsForUpdate(): Collection;
}