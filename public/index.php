<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable('../');
$dotenv->load();

use ConnectingOfThings\Classes\Database\DB;


try {
    $db = DB::getInstance();
    $pdo = $db->getConnection();

    // Get the requested URI and clean it
    $uri = $_SERVER['REQUEST_URI'];
    $uri = parse_url($uri, PHP_URL_PATH); // Extract the path
    $uri = trim($uri, '/'); // Remove leading/trailing slashes.  Important!

    // Route the request
    if ($uri === 'rfms/vehicles') {
        require 'rfms/vehicles.php';
    } elseif ($uri === 'rfms/vehiclestatuses') {
        require 'rfms/vehiclestatuses.php';
    } elseif ($uri === 'api/token') {
        require 'api/token.php';
    } elseif ($uri === 'mock/init') {
        require 'mock/create.php';
    } else {
        // Handle unmatched routes with a JSON response
        http_response_code(400); // Use 400 Bad Request for invalid requests
        echo json_encode([
            'error' => 'Invalid endpoint',
            'message' => 'Please refer to the SDK documentation for valid API endpoints.',
            'documentation_url' => 'https://api.rfmsconnect.nl:9123/sdk'
        ]);
        exit; // Important: Stop further execution
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred: ' . $e->getMessage()]);
    error_log("Critical error in index.php: " . $e->getMessage());
}
