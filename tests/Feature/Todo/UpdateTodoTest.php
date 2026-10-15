<?php

namespace Tests\Feature\Todo;

use App\Models\Todo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToggleTodoTest extends TestCase
{
  use RefreshDatabase;

  // 未完了のTodoを完了状態に切り替えられることを確認する
  public function test_todo_can_be_toggled_to_completed(): void
  {
    $todo = Todo::factory()->create([
      'title' => 'SQLを勉強する',
      'is_completed' => false,
    ]);

    $response = $this->patch("/todos/{$todo->id}/toggle");

    $response->assertRedirect('/');

    $this->assertDatabaseHas('todos', [
      'id' => $todo->id,
      'title' => 'SQLを勉強する',
      'is_completed' => true,
    ]);
  }

  // 完了済みのTodoを未完了状態に戻せることを確認する
  public function test_completed_todo_can_be_toggled_to_incomplete(): void
  {
    $todo = Todo::factory()->create([
      'title' => 'SQLを勉強する',
      'is_completed' => true,
    ]);

    $response = $this->patch("/todos/{$todo->id}/toggle");

    $response->assertRedirect('/');

    $this->assertDatabaseHas('todos', [
      'id' => $todo->id,
      'title' => 'SQLを勉強する',
      'is_completed' => false,
    ]);
  }

  // 指定したTodoだけが切り替わり、他のTodoの状態は変更されないことを確認する
  public function test_only_specified_todo_is_toggled(): void
  {
    $todoToToggle = Todo::factory()->create([
      'title' => 'SQLを勉強する',
      'is_completed' => false,
    ]);

    $todoToKeep = Todo::factory()->create([
      'title' => 'Laravelを勉強する',
      'is_completed' => false,
    ]);

    $response = $this->patch("/todos/{$todoToToggle->id}/toggle");

    $response->assertRedirect('/');

    $this->assertDatabaseHas('todos', [
      'id' => $todoToToggle->id,
      'is_completed' => true,
    ]);

    $this->assertDatabaseHas('todos', [
      'id' => $todoToKeep->id,
      'is_completed' => false,
    ]);
  }

  // 存在しないTodoの状態を切り替えようとした場合は404になることを確認する
  public function test_nonexistent_todo_cannot_be_toggled(): void
  {
    $response = $this->patch('/todos/999999/toggle');

    $response->assertNotFound();

    $this->assertDatabaseCount('todos', 0);
  }
}