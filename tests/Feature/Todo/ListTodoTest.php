<?php

namespace Tests\Feature\Todo;

use App\Models\Todo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListTodoTest extends TestCase
{
  use RefreshDatabase;

  // 登録されているTodoが一覧画面に表示されることを確認する
  public function test_todos_can_be_displayed(): void
  {
    Todo::factory()->create([
      'title' => 'SQLを勉強する',
      'is_completed' => false,
    ]);

    $response = $this->get('/');

    $response->assertStatus(200);

    $response->assertSee('SQLを勉強する');
  }

  // 複数のTodoが登録されている場合、すべて一覧画面に表示されることを確認する
  public function test_multiple_todos_can_be_displayed(): void
  {
    Todo::factory()->create([
      'title' => 'SQLを勉強する',
      'is_completed' => false,
    ]);

    Todo::factory()->create([
      'title' => 'Laravelを勉強する',
      'is_completed' => false,
    ]);

    Todo::factory()->create([
      'title' => 'テストを勉強する',
      'is_completed' => true,
    ]);

    $response = $this->get('/');

    $response->assertStatus(200);

    $response->assertSee('SQLを勉強する');
    $response->assertSee('Laravelを勉強する');
    $response->assertSee('テストを勉強する');
  }

  // Todoが1件も登録されていなくても一覧画面を正常に表示できることを確認する
  public function test_todo_list_can_be_displayed_when_no_todos_exist(): void
  {
    $response = $this->get('/');

    $response->assertStatus(200);

    $this->assertDatabaseCount('todos', 0);
  }
}