<?php
/**
 * includes/db.php — tiny prepared-statement helpers (every query uses bound parameters).
 */
function db_run(mysqli $c, string $sql, string $types = '', array $params = []): mysqli_stmt
{
    $st = $c->prepare($sql);
    if ($types !== '') $st->bind_param($types, ...$params);
    $st->execute();
    return $st;
}

/** @return array<int,array<string,mixed>> */
function db_all(mysqli $c, string $sql, string $types = '', array $params = []): array
{
    $st  = db_run($c, $sql, $types, $params);
    $res = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    return $res;
}

function db_one(mysqli $c, string $sql, string $types = '', array $params = []): ?array
{
    $rows = db_all($c, $sql, $types, $params);
    return $rows[0] ?? null;
}

/** Runs INSERT/UPDATE/DELETE and returns the number of affected rows. */
function db_exec(mysqli $c, string $sql, string $types = '', array $params = []): int
{
    $st = db_run($c, $sql, $types, $params);
    $n  = $st->affected_rows;
    $st->close();
    return (int)$n;
}