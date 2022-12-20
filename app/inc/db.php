<?php
/**
 * Database connection file
 *
 * File to require in SPA for making database connections.
 * php version 8.0.25
 *
 * @category Include
 * @package  BMMI
 * @author   Karsten Siemer <karstensiemer@live.de>
 * @license  https://en.wikipedia.org/wiki/WTFPL WTFPL
 * @link     https://github.com/KarstenSiemer/BMMI
 * @since    0.0.0
 */

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
    $conn->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
    return $conn;
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
    array  $params,
    string $types = ""
): mysqli_stmt {
    $types = $types ?: str_repeat("s", count($params));
    if ($stmt = $conn->prepare($sql)) {
        $pos = strpos($types, "b");
        if ($pos) {
            // Blobs need to be sent to the database before the query gets executed
            // Thus forcing this branch
            // Usually we'd need to do a lot more error handling, but
            // I will just shamelessly cast to string out of pure laziness
            $tmp_name = (string)$params[$pos];
            $params[$pos] = null;
            $stmt->bind_param($types, ...$params);
            $stmt->send_long_data($pos, (string)file_get_contents($tmp_name));
        } else {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
    } else {
        throw new RuntimeException(
            $conn->errno . ' ' . $conn->error
        );
    }
    return $stmt;
}

/**
 * For uploading a video into the database
 *
 * @return array<int, array<int, string>>.
 * @since  0.0.0
 */
function upload(): array
{
    $errors = [];
    if ($_FILES['file']['error'] != 0) {
        $errors[] = ['Video upload failed.'];
    }
    if (empty($_FILES['video'])) {
        $errors[] = ['No Video attached.'];
    }
    if (empty($_POST['title'])) {
        $errors[] = ['Video Title cannot be determined.'];
    }
    if (empty($_POST['duration'])) {
        $errors[] = ['Video Duration cannot be determined.'];
    }
    if (empty($_POST['actors'])) {
        $errors[] = ['Video Actors cannot be determined.'];
    }

    if (!empty($errors)) {
        return $errors;
    }

    $conn = openConnection();
    // If this query fails to insert, the error will be caught by
    // MYSQLI_OPT_INT_AND_FLOAT_NATIVE and SPA will display failure statement
    $sql = <<<EOF
    INSERT INTO videos SET
       title =?,
       filename =?,
       actors =?,
       filetype =?,
       duration =?,
       filesize =?,
       content =?
    EOF;
    queryConnection(
        $conn,
        $sql,
        [
            $_POST['title'],
            $_FILES['video']['name'],
            $_POST['actors'],
            $_FILES['video']['type'],
            $_POST['duration'],
            $_FILES['video']['size'],
            $_FILES['video']['tmp_name']
        ],
        "ssssiib"
    );
    closeConnection($conn);
    return $errors;
}

if (!$_SERVER["REQUEST_METHOD"] == "POST") {
    // Only support POST
    // Return gets caught by fail directive in SPA
    return;
}

$errors = [];
$data = [];

if ($_POST['upload']) {
    $submit_erros = upload();
    if (!empty($submit_erros)) {
        $errors['submit'] = $submit_erros;
    }
}

if (!empty($errors)) {
    $data['success'] = false;
    $data['errors'] = $errors;
} else {
    $data['success'] = true;
}
echo json_encode($data);
?>
