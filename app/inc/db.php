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

declare(strict_types=1);

$filePath = realpath(__DIR__ . '/../commons/commons.php');
if ($filePath === false) {
    throw new RuntimeException('Invalid file path.');
}
require_once $filePath;

/**
 * For uploading a video into the database
 *
 * @return array<int, string>
 * @since  0.0.0
 */
function upload(): array
{
    // Usually you would do a lot of error checking here for
    // e.g sting length or file size/type etc.
    // create external function in future
    $errors = [];
    if (isset($_FILES['file'])
        && is_array($_FILES['file'])
        && $_FILES['file']['error'] != 0
    ) {
          $errors[] = 'Video upload failed.';
    }
    if (empty($_FILES['video'])
        || !is_array($_FILES['video'])
        || !isset($_FILES['video']['name'])
        || !is_string($_FILES['video']['name'])
        || !isset($_FILES['video']['type'])
        || !is_string($_FILES['video']['type'])
        || !isset($_FILES['video']['size'])
        || !is_int($_FILES['video']['size'])
        || !isset($_FILES['video']['tmp_name'])
        || !is_string($_FILES['video']['tmp_name'])
    ) {
        $errors[] = 'No Video attached.';
    }
    if (empty($_POST['title'])
        || !is_string($_POST['title'])
        || trim($_POST['title']) === ''
    ) {
          $errors[] = 'Video Title cannot be determined.';
    }
    if (empty($_POST['duration'])
        || !is_string($_POST['duration'])
        || trim($_POST['duration']) === ''
    ) {
          $errors[] = 'Video Duration cannot be determined.';
    }
    if (empty($_POST['actors'])
        || !is_string($_POST['actors'])
        || trim($_POST['actors']) === ''
    ) {
          $errors[] = 'Video Actors cannot be determined.';
    }

    // soft error for UI.
    if (!empty($errors)) {
        return $errors;
    }

    // make compiler happy. It doesnt recognize the soft error
    // maybe rather use exception instead of custom error handling...
    if (empty($_FILES['video'])
        || !is_array($_FILES['video'])
        || !isset($_FILES['video']['name'])
        || !is_string($_FILES['video']['name'])
        || !isset($_FILES['video']['type'])
        || !is_string($_FILES['video']['type'])
        || !isset($_FILES['video']['size'])
        || !is_int($_FILES['video']['size'])
        || !isset($_FILES['video']['tmp_name'])
        || !is_string($_FILES['video']['tmp_name'])
        || empty($_POST['title'])
        || !is_string($_POST['title'])
        || trim($_POST['title']) === ''
        || empty($_POST['duration'])
        || !is_string($_POST['duration'])
        || trim($_POST['duration']) === ''
        || empty($_POST['actors'])
        || !is_string($_POST['actors'])
        || trim($_POST['actors']) === ''
    ) {
        throw new InvalidArgumentException('No Video attached on upload.');
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

/**
 * For streaming a video from the database in chunks
 *
 * @param int $videoId id of the video to stream
 *
 * @return array<int, string>
 * @since  0.0.0
 */
function stream(int $videoId): array
{
    $errors = [];

    $conn = openConnection();
    // could also get filesize, filetype handed over by jquery
    // but since we need to either way fetch the content
    // we might as well just get them here, too
    // For proper production app, we would actually
    // split the content in a separate table for performance
    // boosts, for this low profile poc, there is no need
    $sql = <<<EOF
        SELECT content, filesize, filetype
        FROM videos WHERE
        id=?
        LIMIT 1
    EOF;
    $result = queryConnection(
        $conn,
        $sql,
        [$videoId],
        "i"
    )->get_result();
    if ($result) {
        $row = $result->fetch_assoc();
        if (isset($row['content'])) {
            $totalSize = $row['filesize'] ??
              (is_string($row['content']) ?
              strlen($row['content']) : 0);
            $fileType = $row['filetype'] ?? 'video/mp4';
            header('Content-Type: ' . $fileType);
            header('Content-Length: ' . $totalSize);
            $chunkSize = 1024 * 1024; // 1MB chunks
            $bytesSent = 0;
            while ($bytesSent < $totalSize) {
                $chunk = is_string($row['content']) ?
                  substr($row['content'], $bytesSent, $chunkSize) : '';
                echo $chunk;
                flush();
                $bytesSent += strlen($chunk);
            }
            $result->free_result();
        } else {
            $errors[] = 'Database table malformed.';
        }
    } else {
        $errors[] = 'Video cannot be found.';
    }
    closeConnection($conn);
    return $errors;
}

/**
 * For returning video metadata
 *
 * @return array<string, array<string|mixed>>
 * @since  0.0.0
 */
function gather(): array
{
    $data = [];
    $data['videos'] = [];

    $conn = openConnection();
    $sql = <<<EOF
        SELECT
            id,
            title,
            filename,
            filetype,
            filesize,
            duration,
            actors
        FROM videos
        WHERE id > ?
    EOF;
    $result = queryConnection(
        $conn,
        $sql,
        [0],
        "i"
    )->get_result();
    if ($result) {
        $data['videos'] = $result->fetch_all(MYSQLI_ASSOC);
    }
    closeConnection($conn);
    return $data;
}

/**
 * For deleting a Video
 *
 * @param int $videoId The id of the Video to delete
 *
 * @return array<string>
 * @since  0.0.0
 */
function prune(int $videoId): array
{
    $errors = [];
    if (empty($videoId)) {
        $errors['videoId'] = "No todo id submitted.";
        return $errors;
    }
    $conn = openConnection();
    // If this query fails to delete, the error will be caught by
    // MYSQLI_OPT_INT_AND_FLOAT_NATIVE and SPA will display failure statement
    $sql = <<<EOF
        DELETE FROM videos WHERE
        id=?
    EOF;
    queryConnection(
        $conn,
        $sql,
        [$videoId],
        "i"
    );
    closeConnection($conn);
    return $errors;
}

/**
 * For editing a video into the database
 *
 * @return array<int, string>
 * @since  0.0.0
 */
function edit(): array
{
    // Usually you would do a lot of error checking here for
    // e.g sting length or file size/type etc.
    $errors = [];
    $types = "";
    $sets = [];
    $payload = [];

    if (!empty($_POST['title']) && is_string($_POST['title'])) {
        $types .= "s";
        $sets[] = 'title =?';
        $payload[] = $_POST['title'];
    }
    if (!empty($_POST['actors']) && is_string($_POST['actors'])) {
        $types .= "s";
        $sets[] = 'actors =?';
        $payload[] = $_POST['actors'];
    }
    if (!empty($_POST['duration']) && is_int($_POST['duration'])) {
        $types .= "i";
        $sets[] = 'duration =?';
        $payload[] = (int)$_POST['duration'];
    }
    if (!empty($_FILES['video'])) {
        if (isset($_FILES['file'])
            && is_array($_FILES['file'])
            && $_FILES['file']['error'] != 0
        ) {
              $errors[] = 'Video upload failed.';
              return $errors;
        }
        if (empty($_FILES['video'])
            || !is_array($_FILES['video'])
            || !isset($_FILES['video']['name'])
            || !is_string($_FILES['video']['name'])
            || !isset($_FILES['video']['type'])
            || !is_string($_FILES['video']['type'])
            || !isset($_FILES['video']['size'])
            || !is_int($_FILES['video']['size'])
            || !isset($_FILES['video']['tmp_name'])
            || !is_string($_FILES['video']['tmp_name'])
        ) {
            throw new InvalidArgumentException(
                'No Video attached on upload.'
            );
        }
        $types .= "ssib";
        $sets[] = 'filename =?';
        $payload[] = $_FILES['video']['name'];
        $sets[] = 'filetype =?';
        $payload[] = $_FILES['video']['type'];
        $sets[] = 'filesize =?';
        $payload[] = $_FILES['video']['size'];
        $sets[] = 'content =?';
        $payload[] = $_FILES['video']['tmp_name'];
    }

    if (isset($_POST['videoId']) && is_string($_POST['videoId'])) {
        $types .= "i";
        $payload[] = $_POST['videoId'];
    } else {
        $errors[] = 'No videoId in payload.';
        return $errors;
    }

    if (empty($sets)) {
        $errors[] = 'No data received.';
        return $errors;
    }

    $set = implode(', ', $sets);
    $conn = openConnection();
    // If this query fails to insert, the error will be caught by
    // MYSQLI_OPT_INT_AND_FLOAT_NATIVE and SPA will display failure statement
    $sql = <<<EOF
        UPDATE videos
        SET {$set}
        WHERE id=?
    EOF;
    queryConnection(
        $conn,
        $sql,
        $payload,
        $types
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
    $submit_errors = upload();
    if (!empty($submit_errors)) {
        $errors['submit'] = $submit_errors;
    }
}

if ($_POST['edit']) {
    $edit_errors = edit();
    if (!empty($edit_errors)) {
        $errors['edit'] = $edit_errors;
    }
}

if ($_POST['stream']) {
    if (isset($_POST['videoId']) && is_string($_POST['videoId'])) {
        $errors = stream((int)$_POST['videoId']);
    } else {
        $errors['stream'] = 'No videoId in payload.';
    }
}

if ($_POST['gather']) {
    $data = array_merge($data, gather());
}

if ($_POST['delete']) {
    if (isset($_POST['videoId']) && is_string($_POST['videoId'])) {
        $errors = array_merge($errors, prune((int)$_POST['videoId']));
    } else {
        $errors['delete'] = 'No videoId in payload.';
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
