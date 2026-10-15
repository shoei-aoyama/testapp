<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Todo extends Model
{
  use HasFactory;
  protected $fillable = [
    'title',
    'is_completed',
  ];

  protected $casts = [
    'is_completed' => 'boolean',
  ];

  public function toggleCompletion(): void
  {
    $this->is_completed = ! $this->is_completed;
  }
}