<?php
class SearchController
{
    public static function search(): void
    {
        Auth::requireAuth();
        $uid = userId();
        $q   = trim(input('q', ''));

        $searchKey = Auth::throttleKey('search', (string) $uid);
        if (Auth::tooManyAttempts($searchKey, 90, 60)) {
            json(['results' => [], 'error' => 'Too many search requests.'], 429);
        }
        Auth::recordAttempt($searchKey);

        if (strlen($q) < 2) {
            json(['results' => []]);
        }

        $needle = strtolower($q);
        $cacheKey = 'search:' . $uid . ':' . hash('sha256', $needle);
        $results = cacheRemember($cacheKey, 8, function () use ($uid, $q, $needle): array {
            $like = '%' . $q . '%';

            $passwordCandidates = Database::fetchAll(
                'SELECT id, title, username, url, category FROM passwords
                 WHERE user_id = ? AND (title LIKE ? OR category LIKE ? OR url LIKE ?)
                 ORDER BY updated_at DESC LIMIT 40',
                [$uid, $like, $like, $like]
            );

            $passwords = array_values(array_filter(array_map(function (array $p): array {
                $p['username_plain'] = $p['username'] ? (Crypto::decrypt($p['username']) ?? '') : '';
                return $p;
            }, $passwordCandidates), function (array $p) use ($needle): bool {
                $haystack = strtolower(
                    ($p['title'] ?? '') . ' ' . ($p['username_plain'] ?? '') . ' ' . ($p['category'] ?? '') . ' ' . ($p['url'] ?? '')
                );
                return str_contains($haystack, $needle);
            }));

            $documents = Database::fetchAll(
                'SELECT id, title, type, issued_by FROM documents
                 WHERE user_id = ? AND (title LIKE ? OR type LIKE ? OR issued_by LIKE ?)
                 ORDER BY updated_at DESC LIMIT 20',
                [$uid, $like, $like, $like]
            );

            $tasks = Database::fetchAll(
                'SELECT id, title, category, priority FROM tasks
                 WHERE user_id = ? AND (title LIKE ? OR category LIKE ?)
                 ORDER BY updated_at DESC LIMIT 20',
                [$uid, $like, $like]
            );

            $items = [];

            foreach ($passwords as $p) {
                $items[] = [
                    'type'     => 'password',
                    'id'       => $p['id'],
                    'title'    => $p['title'],
                    'subtitle' => $p['username_plain'] ?: $p['category'],
                    'url'      => '/passwords',
                    'icon'     => 'key',
                ];
            }
            foreach ($documents as $d) {
                $items[] = [
                    'type'     => 'document',
                    'id'       => $d['id'],
                    'title'    => $d['title'],
                    'subtitle' => $d['type'],
                    'url'      => '/documents',
                    'icon'     => 'file',
                ];
            }
            foreach ($tasks as $t) {
                $items[] = [
                    'type'     => 'task',
                    'id'       => $t['id'],
                    'title'    => $t['title'],
                    'subtitle' => $t['priority'] . ' priority',
                    'url'      => '/tasks',
                    'icon'     => 'check',
                ];
            }

            return array_slice($items, 0, 10);
        });

        json(['results' => $results, 'query' => $q]);
    }
}
