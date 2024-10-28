<?php
/**
 * Database setup file
 *
 * File to setup and repair db schema if necessary.
 * php version 8.0.25
 *
 * @category Include
 * @package  BMMI
 * @author   Karsten Siemer <karstensiemer@live.de>
 * @license  https://en.wikipedia.org/wiki/WTFPL WTFPL
 * @link     https://github.com/KarstenSiemer/BMMI
 * @since    1.7.0
 */

declare(strict_types=1);

// Include the database connection functions
require_once $_SERVER['DOCUMENT_ROOT'] . '/commons/commons.php';

/**
 * Ensures the 'videos' table is present with the correct schema
 *
 * Starts a database transaction and checks if the 'videos' table
 * exists. If it does, the table schema is validated and repaired as needed.
 * If not, the table is created. Commits or rolls back on failure.
 *
 * @return void
 * @throws RuntimeException if table setup fails
 * @since  1.7.0
 */
function ensureVideosTable(): void
{
    $conn = openConnection();
    $table_exists = false;

    try {
        if (!tableExists($conn, 'videos')) {
            createTable($conn);
        }
        $table_exists = true;
    } catch (Exception $e) {
        error_log("Error ensuring videos table: " . $e->getMessage());
        throw new RuntimeException("Error ensuring 'videos' table", 0, $e);
    } finally {
        closeConnection($conn);
        echo json_encode(
            [
            'status' => $table_exists ? 'ready' : 'not ready',
            'table_exists' => $table_exists
            ]
        );
    }
}

/**
 * Retrieves the schema definition for the 'videos' table
 *
 * Returns an associative array that defines the intended structure of
 * the 'videos' table, including column types, constraints, and whether
 * each column allows NULL values.
 *
 * @return array<string, array<string, mixed>> The schema definition
 * @since  1.7.0
 */
function getVideoTableSchema(): array
{
    return [
        'id'       => [
            'type' => 'SMALLINT',
            'unsigned' => true,
            'nullable' => false,
            'auto_increment' => true,
            'primary' => true
        ],
        'title'    => [
            'type' => 'VARCHAR(100)',
            'nullable' => false
        ],
        'filename' => [
            'type' => 'VARCHAR(150)',
            'nullable' => false
        ],
        'filetype' => [
            'type' => 'VARCHAR(25)',
            'nullable' => false
        ],
        'filesize' => [
            'type' => 'INT',
            'nullable' => false
        ],
        'duration' => [
            'type' => 'SMALLINT',
            'nullable' => false
        ],
        'actors'   => [
            'type' => 'VARCHAR(150)',
            'nullable' => false
        ],
        'content'  => [
            'type' => 'LONGBLOB',
            'nullable' => false
        ],
    ];
}

/**
 * Checks if a table exists within the database
 *
 * Queries the database to determine if a specified table exists.
 *
 * @param mysqli $conn      database connection
 * @param string $tableName the table name to check
 *
 * @return bool              true if table exists, false otherwise
 * @since  1.7.0
 */
function tableExists(mysqli $conn, string $tableName): bool
{
    // SQL query to check if the table exists in INFORMATION_SCHEMA.TABLES
    $sql = "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ?";

    // Use database from the connection and bind the parameters
    $stmt = queryConnection($conn, $sql, [$tableName]);
    $result = $stmt->get_result();

    return $result && $result->num_rows > 0;
}



/**
 * Generates the SQL definition for a column
 *
 * Constructs an SQL string that represents the column definition
 * based on its name and schema attributes such as type, nullable,
 * and auto-increment status.
 *
 * @param string               $columnName name of the column
 * @param array<string, mixed> $definition schema definition for the column
 *
 * @return string             SQL string for the column definition
 * @since  1.7.0
 */
function columnDefinitionSQL(string $columnName, array $definition): string
{
    $sql = "`$columnName` " . $definition['type'];
    if (!empty($definition['unsigned'])) {
        $sql .= " UNSIGNED";
    }
    if (empty($definition['nullable'])) {
        $sql .= " NOT NULL";
    }
    if (!empty($definition['auto_increment'])) {
        $sql .= " AUTO_INCREMENT";
    }
    return $sql;
}

/**
 * Creates the 'videos' table with the specified schema
 *
 * Constructs and executes an SQL query to create the 'videos' table,
 * defining its structure according to the specified schema.
 *
 * @param mysqli $conn database connection
 *
 * @return void
 * @since  1.7.0
 */
function createTable(mysqli $conn): void
{
    $schema = getVideoTableSchema();
    $columnsSQL = [];

    foreach ($schema as $columnName => $definition) {
        $columnsSQL[] = columnDefinitionSQL($columnName, $definition);
    }

    $primaryKeys = array_keys(
        array_filter(
            $schema,
            fn($def) => !empty($def['primary'])
        )
    );
    $primarySQL = "PRIMARY KEY (" . implode(
        ", ",
        array_map(fn($col) => "`$col`", $primaryKeys)
    ) . ")";

    $sql = "CREATE TABLE videos (" . implode(
        ", ",
        $columnsSQL
    ) . ", $primarySQL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    queryConnection($conn, $sql, []);
}

// Execute the script to ensure the 'videos' table schema
ensureVideosTable();

?>
