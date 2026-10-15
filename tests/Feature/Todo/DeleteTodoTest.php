<?php

namespace Tests\Feature\Todo;

use App\Models\Todo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteTodoTest extends TestCase
{
  use RefreshDatabase;

  // 指定したTodoを正常に削除できることを確認する
  public function test_todo_can_be_deleted(): void
  {
    $todo = Todo::factory()->create([
      'title' => 'SQLを勉強する',
      'is_completed' => false,
    ]);

    $response = $this->delete("/todos/{$todo->id}");

    $response->assertRedirect('/');

    $this->assertDatabaseMissing('todos', [
      'id' => $todo->id,
    ]);
  }

  // 指定したTodoだけが削除され、他のTodoは残ることを確認する
  public function test_only_specified_todo_is_deleted(): void
  {
    $todoToDelete = Todo::factory()->create([
      'title' => 'SQLを勉強する',
      'is_completed' => false,
    ]);

    $todoToKeep = Todo::factory()->create([
      'title' => 'Laravelを勉強する',
      'is_completed' => false,
    ]);

    $response = $this->delete("/todos/{$todoToDelete->id}");

    $response->assertRedirect('/');

    $this->assertDatabaseMissing('todos', [
      'id' => $todoToDelete->id,
    ]);

    $this->assertDatabaseHas('todos', [
      'id' => $todoToKeep->id,
      'title' => 'Laravelを勉強する',
    ]);
  }

  // 存在しないTodoを削除しようとした場合は404になることを確認する
  public function test_nonexistent_todo_cannot_be_deleted(): void
  {
    $response = $this->delete('/todos/999999');

    $response->assertNotFound();

    $this->assertDatabaseCount('todos', 0);
  }
}