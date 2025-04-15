<?php
// include our OAuth2 Server object
require_once 'api/rfms_api_server.php';

use ConnectingOfThings\Classes\Database\DB; // Add this use statement

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
$scope = $token['scope'];

$Result = [];
try {
    $db = DB::getInstance();
    $order = '';
    $fault = '';
    $vin = '';

    if (isset($_GET['latestOnly'])) {
        if ($_GET['latestOnly'] == 'true') {
            $query = '  SELECT vs.*
                        FROM api_vehiclestatus vs
                        INNER JOIN (
                            SELECT vin, MAX(createdDateTime) AS latest_dt
                            FROM api_vehiclestatus
                            GROUP BY vin
                        ) AS latest_status ON vs.vin = latest_status.vin AND vs.createdDateTime = latest_status.latest_dt
                        INNER JOIN api_vehicles v ON vs.vin = v.vin
                        WHERE v.vehicleActive = 1;';
            $vin = '';
        } else {
            $fault = 'latestOnly=true';
        }
    } else {
        if (isset($_GET['vin'])) {
            $vin = "AND vs.vin='" . test_input($_GET['vin']) . "' ";
        }
        if (isset($_GET['starttime'])) {
            $SD = test_input($_GET['starttime']);
        } else {
            $fault .= 'starttime ';
        }
        if (isset($_GET['stoptime'])) {
            $ED = test_input($_GET['stoptime']);
        } else {
            $fault .= 'stoptime ';
        }
        if ($fault == '') {
                // Add date range validation
            if (strtotime($SD) > strtotime($ED)) {
                http_response_code(400);
                echo json_encode(['responseCode' => "400", "message" => "Invalid parameter", "value" => "starttime is after stoptime"]);
                die;
            }
            $order = "AND vs.receivedDateTime between '$SD' AND '$ED' ORDER BY vs.createdDateTime ASC LIMIT 200";
            $query = "SELECT * FROM api_vehiclestatus vs LEFT JOIN api_vehicles v ON v.vin=vs.vin WHERE v.vehicleActive = 1 and v.cust_id = '$scope' $vin $order"; // Corrected $scope
        }
    }
    if ($fault != '') {
        http_response_code(400);
        echo json_encode(['responseCode' => "400", "message" => "Missing parameter", "value" => "$fault"]);
        die;
    }

    $vehicleStatuses = $db->query($query)->results(true); // Use the query() and results() methods

    if (!$vehicleStatuses) {
        http_response_code(404);
        echo json_encode(['error' => 'Vehicle status not found']);
        exit;
    }

    $headerMatch = "application/vnd.fmsstandard.com.vehicles.v3.0+json; UTF-8";
    header("Content-Type: application/json;");
    header("Access-Control-Allow-Origin: *");
    ini_set("default_charset", "UTF-8");
    date_default_timezone_set('CET');
    ini_set('display_errors', 0);

    header("Content-Type: application/json; charset=utf-8");
    $collect = [];
    $counter = 0;
    foreach ($vehicleStatuses as $val) {
        $vs = [];
        $sn = [];
        $d1 = [];
        $ud = [];
        $ad = [];
        $gnss = [];
        $tt = [];
        $vs['vin'] = $val['vin'];
        // set TriggerType values
        $trigger[] = $val['triggerType'];
        $tt['triggerType'] = $val['triggerType'];
        $tt['context'] = $val['context'];
        if (strlen($val['triggerInfo']) > 0) {
            $tl = [];
            $pieces = explode("->", $val['triggerInfo']);
            $tl['tellTale'] = $pieces[0];
            $tl['state'] = $pieces[1];
            $tt['tellTaleInfo'] = $tl;
        }
        $vs['triggerType'] = $tt;
        //
        $vs['createdDateTime'] = date('Y-m-d\TH:i:s.u\Z', strtotime($val['createdDateTime']));
        $vs['receivedDateTime'] = date('Y-m-d\TH:i:s.u\Z', strtotime($val['receivedDateTime']));
        $vs['hrTotalVehicleDistance'] = intval($val['hrTotalVehicleDistance']);
        if (isset($val['totalEngineHours'])) {
            if ($trigger['triggerType'] != 'DRIVER_1_WORKING_STATE_CHANGED' || $trigger['triggerType'] != 'DISTANCE_TRAVELLED') {
                if ($val['totalEngineHours'] > 0) {
                    $vs['totalEngineHours'] = intval($val['totalEngineHours']);
                }
            }
        }
        $vs['driver1Id']['tachoDriverIdentification'] = array("driverIdentification" => $val['driver1Id']);
        // set Accumulated values
        if ($val['triggerType'] = 'ENGINE_ON' || $val['triggerType'] = 'ENGINE_OFF' || $val['triggerType'] = 'TIMER') {
            if ($val['engineTotalFuelUsed'] > 0) {
                $vs['engineTotalFuelUsed'] = intval($val['engineTotalFuelUsed']);
            }
        }
        //$vs['accumulatedData'] = $ad;}


        // set snapshotData values
        // set gnssPosition in SnapshotData
        $gnss['latitude'] = floatval($val['GNSS_latitude']);
        $gnss['longitude'] = floatval($val['GNSS_longitude']);
        if ($val['GNSS_heading'] > 0) {
            $gnss['heading'] = intval($val['GNSS_heading']);
        }
        if ($val['GNSS_altitude'] != 0) {
            $gnss['altitude'] = intval($val['GNSS_altitude']);
        }
        if (isset($val['GNSS_posDateTime'])) {
            $gnss['positionDateTime'] = date('Y-m-d\TH:i:s\Z', strtotime($val['GNSS_posDateTime']));
        }
        $sn['gnssPosition'] = $gnss;
        //if ($val['triggerType'] != 'DRIVER_1_WORKING_STATE_CHANGED'){
        $sn['wheelBasedSpeed'] = floatval($val['wheelBasedSpeed']);
        $sn['tachographSpeed'] = floatval($val['tachographSpeed']);
        $sn['grossCombinationVehicleWeight'] = intval($val['grossCombinationVehicleWeight']);
        $sn['fuelLevel1'] = intval($val['fuelLevel1']);
        $sn['catalystFuelLevel'] = intval($val['catalystFuelLevel']);
        $sn['ambientAirTemperature'] = intval($val['ambientAirTemperature']);
        //}
        $sn['driver1WorkingState'] = $val['driver1WorkingState'];
        $vs['snapshotData'] = $sn;
        if (isset($val['serviceDistance'])) {
            if ($val['triggerType'] == 'ENGINE_ON' || $val['triggerType'] == 'ENGINE_OFF') {
                $ud['serviceDistance'] = intval($val['serviceDistance']);
            }
        }
        $ud['engineCoolantTemperature'] = intval($val['engineCoolantTemperature']);
        $ud['serviceBrakeAirPressureCircuit1'] = intval($val['serviceBrakeAirPressureCircuit1']);
        $ud['serviceBrakeAirPressureCircuit2'] = intval($val['serviceBrakeAirPressureCircuit2']);
        if ($val['triggerType'] != 'DRIVER_1_WORKING_STATE_CHANGED' || $val['triggerType'] != 'DRIVER_2_WORKING_STATE_CHANGED') {
            $vs['uptimeData'] = $ud;
        }
        $collect[] = $vs;
    }
    $Result['vehicleStatusResponse']['vehicleStatuses'] = $collect;
    if (count($collect) > 199 && !isset($_GET['latestOnly'])) {
        $Result['moreDataAvailable'] = true;
    } else {
        $Result['moreDataAvailable'] = false;
    }
    $Result['objectsCollected'] = count($collect);
    $Result['requestServerDateTime'] = date('Y-m-d\TH:i:s.u\Z');
    $Result['scope'] = $scope;


    echo json_encode($Result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}
