<?php

// include our OAuth2 Server object
require_once 'api/rfms_api_server.php';

use ConnectingOfThings\Classes\Database\DB; // Add this use statement

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Handle a request to a resource and authenticate the access token
if (!$server->verifyResourceRequest(OAuth2\Request::createFromGlobals())) {
    $server->getResponse()->send();
    die;
}
$token = $server->getAccessTokenData(OAuth2\Request::createFromGlobals());
$scope =$token['scope'];


$Result = [];
try {
    $db = DB::getInstance();
    $Vehicles = $db->get('api_vehicles', ['vehicleActive' => 1, 'cust_id' => $scope])->results(true); // Use the get() method
    
    if (!$Vehicles) {
        http_response_code(404);
        echo json_encode(['error' => 'Vehicle not found']);
        exit;
    }

    $headerMatch = "application/vnd.fmsstandard.com.vehicles.v3.0+json; UTF-8";
    header("Content-Type: application/json;");
    header("Access-Control-Allow-Origin: *");
    ini_set("default_charset", "UTF-8");
    date_default_timezone_set('CET');
  //  ini_set('display_errors', 0);

    foreach ($Vehicles as $val) {
        $val['productionDate']      = Array('year' => $val['productionDate']);
        $val['possibleFuelType']    = Array($val['possibleFuelType']);
        $val['authorizedPaths']     = ["/vehiclepositions", "/vehiclestatuses"];
        foreach ($val as $key => $value) {
            if ($value === null) {  unset($val[$key]);}
        }
    }
    $Result['vehicleResponse'] = array('vehicles' => $Vehicles);
//    if (sizeof($collect) >= 200) {
//        $Result['moreDataAvailable'] = TRUE;
//    } else {
        $Result['moreDataAvailable'] = FALSE;
//    }
    $Result['requestServerDateTime'] = date('Y-m-d\TH:i:s.u\Z');

    // Return vehicle data as JSON
    http_response_code(200);
    echo json_encode($Result);
} catch (Exception $e) {
    // Handle database errors
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}
