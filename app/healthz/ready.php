<?php
/**
 * Kubernetes readiness check script.
 *
 * Checks if the PHP environment, Apache server, and the database are ready.
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

// Include the database connection functions
require_once $_SERVER['DOCUMENT_ROOT'] . '/commons/commons.php';

/**
 * Check if the database is reachable and has available connections.
 *
 * Opens a connection to the database and checks if the connection is valid.
 *
 * @since 1.5.0
 *
 * @return bool
 */
function checkDatabase(): bool
{
    try {
        $conn = openConnection();
        closeConnection($conn);
        return true;
    } catch (Exception $e) {
        // Log the error for debugging (can be adapted to your logging setup)
        error_log("Database connection error: " . $e->getMessage());
        return false;
    }
}

/**
 * Main readiness check logic.
 *
 * @since 1.5.0
 *
 * @return void
 */
function readinessCheck(): void
{
    $phpReady = checkPHPFunctional();
    $apacheReady = checkApacheFunctional();
    $dbReady = checkDatabase();

    $status = [
        'php' => $phpReady ? 'ready' : 'not ready',
        'apache' => $apacheReady ? 'ready' : 'not ready',
        'database' => $dbReady ? 'ready' : 'not ready'
    ];

    // If all components are ready, return HTTP 200. Otherwise, return HTTP 503.
    if ($phpReady && $apacheReady && $dbReady) {
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode(
            [
            'status' => 'ready',
            'components' => $status
            ]
        );
    } else {
        http_response_code(503); // Service Unavailable
        header('Content-Type: application/json');
        echo json_encode(
            [
            'status' => 'not ready',
            'components' => $status
            ]
        );
    }
}

// Execute readiness check
readinessCheck();
?>
