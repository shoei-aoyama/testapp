<?php

namespace Tests\Unit;

use App\Models\Todo;
use PHPUnit\Framework\TestCase;

class TodoTest extends TestCase
{
  public function test_incomplete_todo_can_be_toggled_to_completed(): void
  {
    $todo = new Todo();
    $todo->is_completed = false;

    $todo->toggleCompletion();

    $this->assertTrue($todo->is_completed);
  }

  public function test_completed_todo_can_be_toggled_to_incomplete(): void
  {
    $todo = new Todo();
    $todo->is_completed = true;

    $todo->toggleCompletion();

    $this->assertFalse($todo->is_completed);
  }
}