<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * ユーザー（users）のデータアクセス。
 * 登録・更新のメソッドは、ユーザー管理のUseCaseを実装するときに追加する。
 *
 * 名前が ...ForUpdate で終わるメソッドは、必ずトランザクション内で使うこと。
 * トランザクション外で呼ぶとロックがすぐ解放され、ロックの意味がなくなる。
 */
interface UserRepositoryInterface
{
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
