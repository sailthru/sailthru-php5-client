<?php
// The same script acts as a loopback-only HTTP fixture in the server process.
if (PHP_SAPI === 'cli-server') {
    header('Content-Type: application/json');
    $payload = json_decode($_POST['json'] ?? '', true);
    $valid = $_SERVER['REQUEST_METHOD'] === 'POST'
        && $_SERVER['REQUEST_URI'] === '/user'
        && ($_POST['api_key'] ?? '') === 'smoke-key'
        && preg_match('/^[a-f0-9]{32}$/', $_POST['sig'] ?? '')
        && ($payload['email'] ?? '') === 'smoke@example.invalid';
    http_response_code($valid ? 200 : 400);
    echo json_encode($valid ? ['email' => $payload['email'], 'ok' => true]
        : ['error' => 9, 'errormsg' => 'Unexpected smoke request']);
    return;
}

function check(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$vendor = realpath($argv[1]);
require $vendor . '/autoload.php';
foreach (['Sailthru_Client', 'Sailthru_Util', 'Sailthru_Client_Exception'] as $class) {
    $file = realpath((new ReflectionClass($class))->getFileName());
    check(str_starts_with($file, $vendor . DIRECTORY_SEPARATOR), 'Class was not loaded from the installed package');
}

$socket = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
check($socket !== false, 'Could not allocate a local test port: ' . $errorMessage);
$address = stream_socket_get_name($socket, false);
fclose($socket);
$log = tempnam(sys_get_temp_dir(), 'sailthru-http-');
$server = proc_open([PHP_BINARY, '-S', $address, __FILE__],
    [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes);
check(is_resource($server), 'Could not start the loopback test server');
fclose($pipes[0]);

try {
    $ready = false;
    for ($attempt = 0; $attempt < 100; $attempt++) {
        $connection = @stream_socket_client('tcp://' . $address, $errorCode, $errorMessage, 0.05);
        if ($connection !== false) {
            fclose($connection);
            $ready = true;
            break;
        }
        usleep(50000);
    }
    check($ready, 'Local test server did not start: ' . file_get_contents($log));
    $client = new Sailthru_Client('smoke-key', 'smoke-secret', 'http://' . $address,
        ['timeout' => 3000, 'connect_timeout' => 3000]);
    $response = $client->apiPost('user', ['email' => 'smoke@example.invalid']);
    check(($response['ok'] ?? false) === true, 'Request did not succeed');
    check(($response['email'] ?? '') === 'smoke@example.invalid', 'Unexpected response payload');
} finally {
    proc_terminate($server);
    proc_close($server);
    unlink($log);
}
