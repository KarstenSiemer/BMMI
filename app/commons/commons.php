<?php
/**
 * Commons
 *
 * Some common functions outsourced to stay DRY.
 *
 * php version 8.0.25
 *
 * @category Include
 * @package  BMMI
 * @author   Karsten Siemer <karstensiemer@live.de>
 * @license  https://en.wikipedia.org/wiki/WTFPL WTFPL
 * @link     https://github.com/KarstenSiemer/BMMI
 * @since    1.5.0
 */

declare(strict_types=1);

/**
 * Check if PHP is functional.
 *
 * This will always return true if PHP is able to execute this function.
 *
 * @since 1.5.0
 *
 * @return bool
 */
function checkPHPFunctional(): bool
{
    return true; // If PHP runs this function, it is functional
}

/**
 * Check if Apache is functional by looking for a specific server variable.
 *
 * Apache should pass a 'SERVER_SOFTWARE' key in the $_SERVER array.
 *
 * @since 1.5.0
 *
 * @return bool
 */
function checkApacheFunctional(): bool
{
    return isset($_SERVER['SERVER_SOFTWARE']) !== false;
}

/**
 * For opening a database connection
 *
 * Reads from environment and uses this data to connect to the database.
 *
 * @since 0.0.0
 *
 * @return mysqli database connection.
 */
function openConnection(): mysqli
{
    $dbhost  = getenv('DATABASE_HOST')      ?: null;
    $dbuser  = getenv('DATABASE_USER')      ?: null;
    $dbpass  = getenv('DATABASE_PASSWORD')  ?: null;
    $dbport  = (int)getenv('DATABASE_PORT') ?: null;
    $db      = getenv('DATABASE')           ?: null;
    $charset = 'utf8mb4';

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = new mysqli(
        $dbhost,
        $dbuser,
        $dbpass,
        $db,
        $dbport
    );
    if ($conn->connect_errno) {
        throw new RuntimeException(
            'mysqli connection error: '
            . $conn->connect_error
        );
    }
    $conn->set_charset($charset);
    if ($conn->errno) {
        throw new RuntimeException(
            'mysqli error: '
            . $conn->error
        );
    }
    // This will do most of the Error handling as
    // everything not binary or json will trigger the error
    // function in JQuery, to not leak any database data.
    // Currently, for checking the work, there
    // are still console.logs in JQuery to ease finding errors, those
    // should be removed in a real world scenario
    $conn->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
    return $conn;
}

/**
 * For querying database connection
 *
 * Utilizes a prepared statement to query the database
 *
 * @param mysqli            $conn   database connection
 * @param string            $sql    SQL Query without params
 * @param array<string|int> $params parameters for prepared statements
 * @param string            $types  the types for expanding params
 *                                  s<string>,d<double>,i<int>,b<blob>
 *
 * @return mysqli_stmt
 * @since  0.0.0
 */
function queryConnection(
    mysqli $conn,
    string $sql,
    array $params = [],
    string $types = ""
): mysqli_stmt {
    if (!($stmt = $conn->prepare($sql))) {
        throw new RuntimeException($conn->errno . ' ' . $conn->error);
    }

    if (!empty($params)) {
        $types = $types ?: str_repeat("s", count($params));
        $stmt->bind_param($types, ...$params);

        if (($pos = strpos($types, "b")) !== false) {
            $stmt->send_long_data(
                $pos,
                (string)file_get_contents((string)$params[$pos])
            );
        }
    }

    $stmt->execute();
    return $stmt;
}

/**
 * For closing a database connection
 *
 * Closes passed db connection.
 *
 * @param mysqli $conn database connection
 *
 * @since 0.0.0
 *
 * @return void
 */
function closeConnection(mysqli $conn): void
{
    $conn -> close();
}
?>
