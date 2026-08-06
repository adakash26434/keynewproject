<?php
class SearchController
{
    public static function search(): void
    {
        Auth::requireAuth();
        $uid = userId();
        $q   = trim(input('q', ''));

        if (strlen($q) < 2) {
            json(['results' => []]);
        }

        $like = '%' . $q . '%';

        $passwords = Database::fetchAll(
            'SELECT id, title, username, url, category FROM passwords
             WHERE user_id = ? AND (title LIKE ? OR category LIKE ? OR url LIKE ?)',
            [$uid, $like, $like, $like]
        );

        $needle = strtolower($q);
        $passwords = array_values(array_filter(array_map(function (array $p): array {
            $p['username_plain'] = $p['username'] ? (Crypto::decrypt($p['username']) ?? '') : '';
            return $p;
        }, $passwords), function (array $p) use ($needle): bool {
            $haystack = strtolower(
                ($p['title'] ?? '') . ' ' . ($p['username_plain'] ?? '') . ' ' . ($p['category'] ?? '') . ' ' . ($p['url'] ?? '')
            );
            return str_contains($haystack, $needle);
        }));

        $documents = Database::fetchAll(
            'SELECT id, title, type, issued_by FROM documents
             WHERE user_id = ? AND (title LIKE ? OR type LIKE ? OR issued_by LIKE ?)',
            [$uid, $like, $like, $like]
        );

        $tasks = Database::fetchAll(
            'SELECT id, title, category, priority FROM tasks
             WHERE user_id = ? AND (title LIKE ? OR category LIKE ?)',
            [$uid, $like, $like]
        );

        $results = [];

        foreach ($passwords as $p) {
            $results[] = [
                'type'     => 'password',
                'id'       => $p['id'],
                'title'    => $p['title'],
                'subtitle' => $p['username_plain'] ?: $p['category'],
                'url'      => '/passwords',
                'icon'     => 'key',
            ];
        }
        foreach ($documents as $d) {
            $results[] = [
                'type'     => 'document',
                'id'       => $d['id'],
                'title'    => $d['title'],
                'subtitle' => $d['type'],
                'url'      => '/documents',
                'icon'     => 'file',
            ];
        }
        foreach ($tasks as $t) {
            $results[] = [
                'type'     => 'task',
                'id'       => $t['id'],
                'title'    => $t['title'],
                'subtitle' => $t['priority'] . ' priority',
                'url'      => '/tasks',
                'icon'     => 'check',
            ];
        }

        json(['results' => array_slice($results, 0, 10), 'query' => $q]);
    }
}
