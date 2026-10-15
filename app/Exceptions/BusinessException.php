<?php

namespace App\Exceptions\Business;

use RuntimeException;

/**
 * 業務ルール違反を表す例外の親クラス。
 *
 * UseCaseが失敗理由を伝えるために投げる。Controllerの手前で一括して処理する
 * （画面ではエラーメッセージ付きで元の画面へ戻し、JSONではステータスコードとエラー内容を返す）。
 */
abstract class BusinessException extends RuntimeException
{
    /**
     * 画面・JSON向けの理由コード。
     */
    abstract public function errorCode(): string;

    /**
     * HTTPステータス。既定は 422（入力や状態が業務ルールを満たさない）。
     */
    public function status(): int
    {
        return 422;
    }
}
