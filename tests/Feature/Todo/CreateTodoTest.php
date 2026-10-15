<?php

namespace Tests\Feature\Todo;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTodoTest extends TestCase
{
  use RefreshDatabase;

  // 正常なタイトルでTodoを作成できることを確認する
  public function test_todo_can_be_created(): void
  {
    $response = $this->post('/todos', [
      'title' => 'SQLを勉強する',
    ]);

    $response->assertRedirect('/');

    $this->assertDatabaseHas('todos', [
      'title' => 'SQLを勉強する',
      'is_completed' => false,
    ]);
  }

  // タイトルが最大文字数の255文字でもTodoを作成できることを確認する
  public function test_todo_can_be_created_with_255_character_title(): void
  {
    $title = str_repeat('a', 255);

    $response = $this->post('/todos', [
      'title' => $title,
    ]);

    $response->assertRedirect('/');

    $this->assertDatabaseHas('todos', [
      'title' => $title,
      'is_completed' => false,
    ]);
  }

  // titleパラメータ自体がない場合はTodoを作成できないことを確認する
  public function test_todo_cannot_be_created_without_title(): void
  {
    $response = $this->post('/todos', []);

    $response->assertSessionHasErrors('title');

    $this->assertDatabaseCount('todos', 0);
  }

  // タイトルが空文字の場合はTodoを作成できないことを確認する
  public function test_todo_cannot_be_created_with_empty_title(): void
  {
    $response = $this->post('/todos', [
      'title' => '',
    ]);

    $response->assertSessionHasErrors('title');

    $this->assertDatabaseCount('todos', 0);
  }

  // タイトルが空白文字だけの場合はTodoを作成できないことを確認する
  public function test_todo_cannot_be_created_with_whitespace_only_title(): void
  {
    $response = $this->post('/todos', [
      'title' => '   ',
    ]);

    $response->assertSessionHasErrors('title');

    $this->assertDatabaseCount('todos', 0);
  }

  // タイトルが最大文字数の255文字を超えた場合はTodoを作成できないことを確認する
  public function test_todo_cannot_be_created_with_title_longer_than_255_characters(): void
  {
    $response = $this->post('/todos', [
      'title' => str_repeat('a', 256),
    ]);

    $response->assertSessionHasErrors('title');

    $this->assertDatabaseCount('todos', 0);
  }
}