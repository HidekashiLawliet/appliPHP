<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Base class of every model: owns the PDO connection and the generic
 * data-access methods shared by all tables.
 *
 * Every query goes through a prepared statement. Column and table names come
 * from the code, never from user input.
 */
abstract class Model
{
    protected PDO $db;

    /**
     * @param PDO|null $db Connection to use; defaults to the Database singleton.
     *                     Tests inject an in-memory SQLite connection here.
     */
    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance()->getConnection();
    }

    /**
     * Name of the table managed by the model.
     */
    abstract protected function table(): string;

    /**
     * ORDER BY clause used by all().
     */
    protected function defaultOrder(): string
    {
        return 'id';
    }

    /**
     * Returns the row with the given primary key, or null.
     *
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        return $this->fetchOne(sprintf('SELECT * FROM %s WHERE id = :id', $this->table()), ['id' => $id]);
    }

    /**
     * Returns every row of the table, sorted by defaultOrder().
     *
     * @return list<array<string, mixed>>
     */
        public function all(): array
    {
        return $this->fetchAll(sprintf('SELECT * FROM %s ORDER BY %s', $this->table(), $this->defaultOrder()));
    }

    /**
     * Tells whether a row with this primary key exists.
     */
    public function exists(int $id): bool
    {
        return $this->find($id) !== null;
    }

    /**
     * Deletes the row with the given primary key.
     *
     * @return bool True when a row was deleted.
     */
    public function delete(int $id): bool
    {
        return $this->execute(sprintf('DELETE FROM %s WHERE id = :id', $this->table()), ['id' => $id]) > 0;
    }

    /**
     * Inserts a row and returns its generated id.
     *
     * @param array<string, scalar|null> $data Column => value.
     */
    protected function insert(array $data): int
    {
        $columns = array_keys($data);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table(),
            implode(', ', $columns),
            implode(', ', array_map(static fn (string $column): string => ':' . $column, $columns))
        );
        $this->execute($sql, $data);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Updates the columns of the row with the given primary key.
     *
     * @param array<string, scalar|null> $data Column => value.
     */
    protected function updateById(int $id, array $data): void
    {
        $assignments = array_map(static fn (string $column): string => $column . ' = :' . $column, array_keys($data));
        $sql = sprintf('UPDATE %s SET %s WHERE id = :id', $this->table(), implode(', ', $assignments));
        $this->execute($sql, $data + ['id' => $id]);
    }

    /**
     * Runs a query and returns its first row, or null.
     *
     * @param array<string, scalar|null> $params
     * @return array<string, mixed>|null
     */
    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * Runs a query and returns all its rows.
     *
     * @param array<string, scalar|null> $params
     * @return list<array<string, mixed>>
     */
    protected function fetchAll(string $sql, array $params = []): array
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    /**
     * Runs a write query and returns the number of affected rows.
     *
     * @param array<string, scalar|null> $params
     */
    protected function execute(string $sql, array $params = []): int
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return $statement->rowCount();
    }
}
