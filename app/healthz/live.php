<?php
/**
 * Kubernetes liveness check script.
 *
 * Checks if the PHP environment, Apache server are live.
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

$filePath = realpath(__DIR__ . '/../commons/commons.php');
if ($filePath === false) {
    throw new RuntimeException('Invalid file path.');
}
require_once $filePath;

/**
 * Main liveness check logic.
 *
 * @since 1.5.0
 *
 * @return void
 */
function livenessCheck(): void
{
    $phplive = checkPHPFunctional();
    $apachelive = checkApacheFunctional();

    $status = [
        'php' => $phplive ? 'live' : 'not live',
        'apache' => $apachelive ? 'live' : 'not live',
    ];

    // If all components are live, return HTTP 200. Otherwise, return HTTP 503.
    if ($phplive && $apachelive) {
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode(
            [
            'status' => 'live',
            'components' => $status
            ]
        );
    } else {
        http_response_code(503);
        header('Content-Type: application/json');
        echo json_encode(
            [
            'status' => 'not live',
            'components' => $status
            ]
        );
    }
}

livenessCheck();
?>
