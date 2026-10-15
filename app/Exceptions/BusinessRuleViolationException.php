<?php

namespace App\Exceptions\Business;

/**
 * 他の専用例外に当てはまらない、業務ルール違反。
 * 違反の種類は、理由コード（定数）で区別する。
 */
class BusinessRuleViolationException extends BusinessException
{
  /** 明細が空 */
  public const EMPTY_ITEMS = 'empty_items';

  /** 明細の形式が不正（商品IDがない・整数でない） */
  public const INVALID_ITEM = 'invalid_item';

  /** 数量が不正（整数でない、範囲外） */
  public const INVALID_QUANTITY = 'invalid_quantity';

  /** 1回の処理内で同一商品が重複している */
  public const DUPLICATE_PRODUCT = 'duplicate_product';

  /** 倉庫間移動の移動元と移動先が同じ */
  public const SAME_WAREHOUSE = 'same_warehouse';

  /** 最後の有効なadminの無効化・降格 */
  public const LAST_ADMIN = 'last_admin';

  /** 自分自身の無効化・権限変更 */
  public const SELF_OPERATION = 'self_operation';

  /**
   * @param  string  $errorCode  上記の定数のいずれか
   * @param  string  $message  画面に表示できる説明
   */
  public function __construct(
    private readonly string $errorCode,
    string $message,
  ) {
    parent::__construct($message);
  }

  public function errorCode(): string
  {
    return $this->errorCode;
  }
}