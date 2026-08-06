<?php
class Database
{
    private static ?PDO $instance = null;
    private static ?string $driver = null;

    public static function driver(): string
    {
        if (self::$driver === null) {
            $raw = strtolower(trim((string) getenv('DB_DRIVER')));
            self::$driver = $raw !== '' ? $raw : 'sqlite';
        }
        return self::$driver;
    }

    public static function isMySql(): bool
    {
        return self::driver() === 'mysql';
    }

    public static function isSqlite(): bool
    {
        return self::driver() === 'sqlite';
    }

    public static function nowExpression(): string
    {
        return self::isMySql() ? 'CURRENT_TIMESTAMP' : "datetime('now')";
    }

    public static function yearMonthExpression(string $column): string
    {
        $col = self::safeColumn($column);
        return self::isMySql()
            ? "DATE_FORMAT($col, '%Y-%m')"
            : "strftime('%Y-%m', $col)";
    }

    private static function safeColumn(string $column): string
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_\.]*$/', $column)) {
            throw new InvalidArgumentException('Unsafe SQL column name provided.');
        }
        return $column;
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            if (self::isMySql()) {
                $host = (string) (getenv('DB_HOST') ?: '127.0.0.1');
                $port = (string) (getenv('DB_PORT') ?: '3306');
                $name = (string) (getenv('DB_NAME') ?: 'key_wallet');
                $user = (string) (getenv('DB_USER') ?: 'root');
                $pass = (string) (getenv('DB_PASS') ?: '');
                $charset = (string) (getenv('DB_CHARSET') ?: 'utf8mb4');

                $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=$charset";
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } else {
                self::$instance = new PDO('sqlite:' . DB_PATH, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                self::$instance->exec('PRAGMA journal_mode=WAL');
                self::$instance->exec('PRAGMA synchronous=NORMAL');
                self::$instance->exec('PRAGMA busy_timeout=5000');
                self::$instance->exec('PRAGMA temp_store=MEMORY');
                self::$instance->exec('PRAGMA cache_size=-20000');
                self::$instance->exec('PRAGMA foreign_keys=ON');
            }

            self::migrate(self::$instance);
        }
        return self::$instance;
    }

    private static function migrate(PDO $db): void
    {
        if (self::isMySql()) {
            self::migrateMySql($db);
            return;
        }

        self::migrateSqlite($db);
    }

    private static function migrateSqlite(PDO $db): void
    {
        $db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT UNIQUE NOT NULL,
                name TEXT NOT NULL,
                password_hash TEXT NOT NULL,
                totp_secret TEXT,
                totp_verified INTEGER NOT NULL DEFAULT 0,
                bio TEXT,
                phone TEXT,
                location TEXT,
                created_at TEXT NOT NULL DEFAULT (datetime('now')),
                updated_at TEXT NOT NULL DEFAULT (datetime('now'))
            );

            CREATE TABLE IF NOT EXISTS passwords (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                title TEXT NOT NULL,
                username TEXT,
                password TEXT,
                url TEXT,
                category TEXT NOT NULL DEFAULT 'Other',
                notes TEXT,
                strength TEXT NOT NULL DEFAULT 'weak',
                created_at TEXT NOT NULL DEFAULT (datetime('now')),
                updated_at TEXT NOT NULL DEFAULT (datetime('now'))
            );

            CREATE TABLE IF NOT EXISTS documents (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                title TEXT NOT NULL,
                type TEXT NOT NULL,
                number TEXT,
                issued_by TEXT,
                issue_date TEXT,
                expiry_date TEXT,
                notes TEXT,
                created_at TEXT NOT NULL DEFAULT (datetime('now')),
                updated_at TEXT NOT NULL DEFAULT (datetime('now'))
            );

            CREATE TABLE IF NOT EXISTS finance_records (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                type TEXT NOT NULL CHECK(type IN ('income','expense')),
                amount REAL NOT NULL,
                category TEXT NOT NULL,
                description TEXT,
                record_date TEXT NOT NULL,
                created_at TEXT NOT NULL DEFAULT (datetime('now')),
                updated_at TEXT NOT NULL DEFAULT (datetime('now'))
            );

            CREATE TABLE IF NOT EXISTS tasks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                title TEXT NOT NULL,
                description TEXT,
                priority TEXT NOT NULL DEFAULT 'medium' CHECK(priority IN ('low','medium','high')),
                category TEXT NOT NULL DEFAULT 'Personal',
                due_date TEXT,
                completed INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL DEFAULT (datetime('now')),
                updated_at TEXT NOT NULL DEFAULT (datetime('now'))
            );

            CREATE TABLE IF NOT EXISTS cv_profiles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
                full_name TEXT NOT NULL DEFAULT '',
                job_title TEXT NOT NULL DEFAULT '',
                email TEXT NOT NULL DEFAULT '',
                phone TEXT NOT NULL DEFAULT '',
                address TEXT NOT NULL DEFAULT '',
                website TEXT,
                linkedin TEXT,
                summary TEXT,
                experience TEXT NOT NULL DEFAULT '[]',
                education TEXT NOT NULL DEFAULT '[]',
                skills TEXT NOT NULL DEFAULT '[]',
                languages TEXT NOT NULL DEFAULT '[]',
                template_color TEXT NOT NULL DEFAULT '#0078D4',
                created_at TEXT NOT NULL DEFAULT (datetime('now')),
                updated_at TEXT NOT NULL DEFAULT (datetime('now'))
            );

            CREATE TABLE IF NOT EXISTS login_sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                session_token TEXT NOT NULL UNIQUE,
                user_agent TEXT,
                ip_address TEXT,
                last_active TEXT NOT NULL DEFAULT (datetime('now')),
                created_at TEXT NOT NULL DEFAULT (datetime('now'))
            );

            CREATE TABLE IF NOT EXISTS password_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                password_hash TEXT NOT NULL,
                created_at TEXT NOT NULL DEFAULT (datetime('now'))
            );

            CREATE INDEX IF NOT EXISTS idx_passwords_user_title ON passwords(user_id, title);
            CREATE INDEX IF NOT EXISTS idx_passwords_user_category ON passwords(user_id, category);
            CREATE INDEX IF NOT EXISTS idx_documents_user_title ON documents(user_id, title);
            CREATE INDEX IF NOT EXISTS idx_documents_user_type ON documents(user_id, type);
            CREATE INDEX IF NOT EXISTS idx_finance_user_date ON finance_records(user_id, record_date);
            CREATE INDEX IF NOT EXISTS idx_finance_user_type_date ON finance_records(user_id, type, record_date);
            CREATE INDEX IF NOT EXISTS idx_tasks_user_completed_priority_due ON tasks(user_id, completed, priority, due_date);
            CREATE INDEX IF NOT EXISTS idx_tasks_user_category ON tasks(user_id, category);
            CREATE INDEX IF NOT EXISTS idx_login_sessions_user_active ON login_sessions(user_id, last_active);
            CREATE INDEX IF NOT EXISTS idx_password_history_user_created ON password_history(user_id, created_at);
        ");
    }

    private static function migrateMySql(PDO $db): void
    {
        $db->exec('SET NAMES utf8mb4');

        $db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                email VARCHAR(255) NOT NULL,
                name VARCHAR(255) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                totp_secret VARCHAR(255) DEFAULT NULL,
                totp_verified TINYINT(1) NOT NULL DEFAULT 0,
                bio TEXT,
                phone VARCHAR(100) DEFAULT NULL,
                location VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_users_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS passwords (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT UNSIGNED NOT NULL,
                title VARCHAR(255) NOT NULL,
                username TEXT,
                password TEXT,
                url TEXT,
                category VARCHAR(100) NOT NULL DEFAULT 'Other',
                notes TEXT,
                strength VARCHAR(32) NOT NULL DEFAULT 'weak',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_passwords_user_title (user_id, title),
                KEY idx_passwords_user_category (user_id, category),
                CONSTRAINT fk_passwords_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS documents (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT UNSIGNED NOT NULL,
                title VARCHAR(255) NOT NULL,
                type VARCHAR(100) NOT NULL,
                number TEXT,
                issued_by VARCHAR(255) DEFAULT NULL,
                issue_date DATE DEFAULT NULL,
                expiry_date DATE DEFAULT NULL,
                notes TEXT,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_documents_user_title (user_id, title),
                KEY idx_documents_user_type (user_id, type),
                CONSTRAINT fk_documents_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS finance_records (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT UNSIGNED NOT NULL,
                type VARCHAR(16) NOT NULL,
                amount DECIMAL(14,2) NOT NULL,
                category VARCHAR(100) NOT NULL,
                description TEXT,
                record_date DATE NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_finance_user_date (user_id, record_date),
                KEY idx_finance_user_type_date (user_id, type, record_date),
                CONSTRAINT fk_finance_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT chk_finance_type CHECK (type IN ('income', 'expense'))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS tasks (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT UNSIGNED NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                priority VARCHAR(16) NOT NULL DEFAULT 'medium',
                category VARCHAR(100) NOT NULL DEFAULT 'Personal',
                due_date DATE DEFAULT NULL,
                completed TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_tasks_user_completed_priority_due (user_id, completed, priority, due_date),
                KEY idx_tasks_user_category (user_id, category),
                CONSTRAINT fk_tasks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT chk_tasks_priority CHECK (priority IN ('low', 'medium', 'high'))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS cv_profiles (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT UNSIGNED NOT NULL,
                full_name VARCHAR(255) NOT NULL DEFAULT '',
                job_title VARCHAR(255) NOT NULL DEFAULT '',
                email VARCHAR(255) NOT NULL DEFAULT '',
                phone VARCHAR(100) NOT NULL DEFAULT '',
                address VARCHAR(255) NOT NULL DEFAULT '',
                website VARCHAR(255) DEFAULT NULL,
                linkedin VARCHAR(255) DEFAULT NULL,
                summary TEXT,
                experience LONGTEXT NOT NULL,
                education LONGTEXT NOT NULL,
                skills LONGTEXT NOT NULL,
                languages LONGTEXT NOT NULL,
                template_color VARCHAR(20) NOT NULL DEFAULT '#0078D4',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_cv_profiles_user (user_id),
                CONSTRAINT fk_cv_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS login_sessions (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT UNSIGNED NOT NULL,
                session_token VARCHAR(128) NOT NULL,
                user_agent VARCHAR(255) DEFAULT NULL,
                ip_address VARCHAR(100) DEFAULT NULL,
                last_active DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_login_sessions_token (session_token),
                KEY idx_login_sessions_user_active (user_id, last_active),
                CONSTRAINT fk_login_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS password_history (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT UNSIGNED NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_password_history_user_created (user_id, created_at),
                CONSTRAINT fk_password_history_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        return self::query($sql, $params)->fetch() ?: null;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function execute(string $sql, array $params = []): int
    {
        return self::query($sql, $params)->rowCount();
    }

    public static function lastInsertId(): string
    {
        return self::getInstance()->lastInsertId();
    }
}
