<?php

declare(strict_types=1);

namespace Tests\DatabaseIntegration;

use App\Repository\UserRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class UserDeleteIntegrationTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            getenv('DB_HOST'),
            getenv('DB_PORT') ?: '3306',
            getenv('DB_DATABASE') ?: 'inventory_db'
        );
        $this->pdo = new PDO($dsn, (string) getenv('DB_USERNAME'), (string) getenv('DB_PASSWORD'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    public function testAssignedUserIsDeactivatedAndTaskHistoryIsPreserved(): void
    {
        $userId = $this->createUser('assigned-member');
        $projectId = (int) $this->pdo->query('SELECT id FROM projects ORDER BY id LIMIT 1')->fetchColumn();
        $statement = $this->pdo->prepare(
            "INSERT INTO tasks(project_id,title,description,assignee_id,status,priority,due_date)
             VALUES(?, 'Delete account integration test', '', ?, 'To Do', 'Low', CURDATE())"
        );
        $statement->execute([$projectId, $userId]);
        $taskId = (int) $this->pdo->lastInsertId();

        $deactivated = (new UserRepository($this->pdo))->delete($userId);

        $statement = $this->pdo->prepare('SELECT is_active FROM users WHERE id=?');
        $statement->execute([$userId]);
        self::assertTrue($deactivated);
        self::assertSame(0, (int) $statement->fetchColumn());
        $statement = $this->pdo->prepare('SELECT assignee_id FROM tasks WHERE id=?');
        $statement->execute([$taskId]);
        self::assertSame($userId, (int) $statement->fetchColumn());
    }

    public function testUnassignedUserIsPermanentlyDeleted(): void
    {
        $userId = $this->createUser('unassigned-member');

        $deactivated = (new UserRepository($this->pdo))->delete($userId);

        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE id=?');
        $statement->execute([$userId]);
        self::assertFalse($deactivated);
        self::assertSame(0, (int) $statement->fetchColumn());
    }

    private function createUser(string $suffix): int
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO users(username,password,name,email,role,is_active)
             VALUES(?, 'not-used-in-test', ?, ?, 'Sales', 1)"
        );
        $unique = bin2hex(random_bytes(5));
        $statement->execute([$suffix.'-'.$unique, 'Integration '.$suffix, $suffix.'-'.$unique.'@example.test']);
        return (int) $this->pdo->lastInsertId();
    }
}
