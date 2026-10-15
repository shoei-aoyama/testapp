<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Todo App</title>

    <style>
        body {
            font-family: sans-serif;
            max-width: 700px;
            margin: 40px auto;
            padding: 0 20px;
        }

        h1 {
            margin-bottom: 30px;
        }

        form {
            margin: 0;
        }

        .add-form {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }

        .add-form input {
            flex: 1;
            padding: 10px;
            font-size: 16px;
        }

        button {
            padding: 8px 12px;
            cursor: pointer;
        }

        .error {
            color: red;
            margin-bottom: 20px;
        }

        .todo-list {
            padding: 0;
            list-style: none;
        }

        .todo-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;

            padding: 12px 0;
            border-bottom: 1px solid #ddd;
        }

        .todo-title {
            flex: 1;
        }

        .completed {
            text-decoration: line-through;
            color: #888;
        }

        .actions {
            display: flex;
            gap: 8px;
        }
    </style>
</head>

<body>

    <h1>Todo App</h1>

    @if ($errors->any())
        <div class="error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form action="{{ route('todos.store') }}" method="POST" class="add-form">
        @csrf

        <input type="text" name="title" value="{{ old('title') }}" placeholder="Todoを入力">

        <button type="submit">
            追加
        </button>
    </form>

    <ul class="todo-list">

        @forelse ($todos as $todo)
            <li class="todo-item">

                <span class="todo-title {{ $todo->is_completed ? 'completed' : '' }}">
                    {{ $todo->title }}
                </span>

                <div class="actions">

                    <form action="{{ route('todos.toggle', $todo) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <button type="submit">
                            {{ $todo->is_completed ? '未完了' : '完了' }}
                        </button>
                    </form>

                    <form action="{{ route('todos.destroy', $todo) }}" method="POST">
                        @csrf
                        @method('DELETE')

                        <button type="submit">
                            削除
                        </button>
                    </form>

                </div>

            </li>

        @empty

            <li>
                Todoはありません。
            </li>
        @endforelse

    </ul>

</body>

</html>
