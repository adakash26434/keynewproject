<?php
class TaskController
{
    public static function index(): void
    {
        Auth::requireAuth();
        $uid      = userId();
        $filter   = sanitize(input('filter', 'all'));
        $priority = sanitize(input('priority', ''));
        $category = sanitize(input('category', ''));

        $sql    = 'SELECT * FROM tasks WHERE user_id = ?';
        $params = [$uid];

        if ($filter === 'active')    { $sql .= ' AND completed = 0'; }
        if ($filter === 'done')      { $sql .= ' AND completed = 1'; }
        if ($priority)               { $sql .= ' AND priority = ?'; $params[] = $priority; }
        if ($category)               { $sql .= ' AND category = ?'; $params[] = $category; }

        $sql .= ' ORDER BY completed ASC, CASE priority WHEN "high" THEN 1 WHEN "medium" THEN 2 ELSE 3 END, due_date ASC NULLS LAST';

        $tasks      = Database::fetchAll($sql, $params);
        $categories = Database::fetchAll(
            'SELECT DISTINCT category FROM tasks WHERE user_id = ? ORDER BY category', [$uid]
        );

        $counts = [
            'all'    => (int) Database::fetch('SELECT COUNT(*) as c FROM tasks WHERE user_id = ?', [$uid])['c'],
            'active' => (int) Database::fetch('SELECT COUNT(*) as c FROM tasks WHERE user_id = ? AND completed = 0', [$uid])['c'],
            'done'   => (int) Database::fetch('SELECT COUNT(*) as c FROM tasks WHERE user_id = ? AND completed = 1', [$uid])['c'],
        ];

        $taskCategories = ['Personal', 'Work', 'Health', 'Finance', 'Learning', 'Home', 'Other'];
        $pageTitle      = 'Tasks';

        view('pages/tasks', compact('tasks', 'categories', 'taskCategories', 'counts', 'filter', 'priority', 'category', 'pageTitle'));
    }

    public static function store(): void
    {
        Auth::requireAuth();
        Auth::verifyCsrf();
        $uid = userId();

        $title       = sanitize(input('title', ''));
        $description = sanitize(input('description', ''));
        $priority    = sanitize(input('priority', 'medium'));
        $category    = sanitize(input('category', 'Personal'));
        $due_date    = sanitize(input('due_date', ''));

        if (!$title) {
            flash('error', 'Task title is required.');
            redirect('/tasks');
        }

        if (!in_array($priority, ['low', 'medium', 'high'])) $priority = 'medium';

        Database::execute(
            "INSERT INTO tasks (user_id, title, description, priority, category, due_date) VALUES (?,?,?,?,?,?)",
            [$uid, $title, $description, $priority, $category, $due_date ?: null]
        );

        flash('success', 'Task added successfully.');
        redirect('/tasks');
    }

    public static function update(array $params): void
    {
        Auth::requireAuth();
        Auth::verifyCsrf();
        $uid = userId();
        $id  = (int) $params['id'];

        $row = Database::fetch('SELECT id FROM tasks WHERE id = ? AND user_id = ?', [$id, $uid]);
        if (!$row) abort(404);

        $title       = sanitize(input('title', ''));
        $description = sanitize(input('description', ''));
        $priority    = sanitize(input('priority', 'medium'));
        $category    = sanitize(input('category', 'Personal'));
        $due_date    = sanitize(input('due_date', ''));

        if (!$title) {
            flash('error', 'Task title is required.');
            redirect('/tasks');
        }

        if (!in_array($priority, ['low', 'medium', 'high'], true)) {
            $priority = 'medium';
        }

        Database::execute(
            "UPDATE tasks SET title=?, description=?, priority=?, category=?, due_date=?, updated_at=datetime('now') WHERE id=? AND user_id=?",
            [$title, $description, $priority, $category, $due_date ?: null, $id, $uid]
        );

        flash('success', 'Task updated.');
        redirect('/tasks');
    }

    public static function delete(array $params): void
    {
        Auth::requireAuth();
        Auth::verifyCsrf();
        $uid = userId();
        $id  = (int) $params['id'];

        Database::execute('DELETE FROM tasks WHERE id = ? AND user_id = ?', [$id, $uid]);
        flash('success', 'Task deleted.');
        redirect('/tasks');
    }

    public static function toggle(array $params): void
    {
        Auth::requireAuth();
        Auth::verifyCsrf();
        $uid = userId();
        $id  = (int) $params['id'];

        $row = Database::fetch('SELECT completed FROM tasks WHERE id = ? AND user_id = ?', [$id, $uid]);
        if (!$row) abort(404);

        $newState = $row['completed'] ? 0 : 1;
        Database::execute(
            "UPDATE tasks SET completed = ?, updated_at=datetime('now') WHERE id = ? AND user_id = ?",
            [$newState, $id, $uid]
        );

        redirect('/tasks');
    }
}
