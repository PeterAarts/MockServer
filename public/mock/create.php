<?php

// Use strict mode for better error handling and code quality
declare(strict_types=1);

// Include the Composer autoloader to load required classes, including dotenv
require_once '../../vendor/autoload.php';  // Adjusted path

// Load environment variables from .env file
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable('../../'); // Adjusted path
$dotenv->load();

// Start a session to manage user data across requests
session_start();

// Set the default timezone for the application.  This should be an environment variable.
date_default_timezone_set($_ENV['TIMEZONE'] ?? 'Europe/Amsterdam');

// Enable error reporting for development.  Consider using different settings for production.
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define a constant for the root path for easy access throughout the application.
define("ROOT", $_SERVER['DOCUMENT_ROOT'] . "/"); // Simplified root definition.  Check this in your environment.

// Include necessary classes  --  These are now autoloaded, but we need to use them.
use ConnectingOfThings\Classes\Database\DB;
use ConnectingOfThings\Classes\Mocking\MockServerDemoDataGenerator;
use ConnectingOfThings\Classes\Mocking\Rfms3\Calculations\Calculation;

// Instantiate the MockServerDemoDataGenerator and any other mock server dependencies
$calculation = new Calculation();
$demoDataGenerator = new MockServerDemoDataGenerator($pdo, $calculation); // $pdo must be defined before this line

use ConnectingOfThings\Classes\Vehicle\Vehicle;
use ConnectingOfThings\Classes\Vehicle\VehicleStatus;
use ConnectingOfThings\Classes\Vehicle\VehicleData;

use PDO;
use DateTime;
use DateInterval;
use Exception;


// Function to get UserSpice root.  --  No real change, but using the ROOT constant.
function get_us_root(): string
{
    return ROOT;
}

/**
 * Retrieves the database connection.  Uses a static variable for efficiency.
 * @return PDO The database connection.
 */
function getDatabaseConnection(): PDO
{
    static $db = null;

    if ($db === null) {
        // Database connection details from .env
        $dsn = "mysql:host=" . ($_ENV['DB_HOST'] ?? '192.168.1.74') . ";dbname=" . ($_ENV['DB_NAME'] ?? 'rfms_demodata');
        $dbUsername = $_ENV['DB_USER'] ?? 'api_user';
        $dbPassword = $_ENV['DB_PASSWORD'] ?? '{k16}~O)f3zEP/wz1km]ti>1<$9zXw';

        try {
            $db = new PDO($dsn, $dbUsername, $dbPassword);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Error connecting to database: " . $e->getMessage()); // Stop if DB connection fails.
        }
    }
    return $db;
}


/**
 * Function to get demo vehicles
 * @param PDO $db
 * @return array
 */
function GetDemoVehicles(PDO $db): array
{
    $query = "SELECT vs.*, v.vin AS demo_vin, v.originalVin AS real_vin, v.arrayAvailableDates
              FROM m_vs_calc_status vs LEFT JOIN m_vs_vehicles v ON vs.vin = v.id
              WHERE vs.active = 1 AND DATE(vs.last_calc_date) < CURRENT_DATE()";
    $Q = $db->query($query);
    $results = $Q->fetchAll(PDO::FETCH_OBJ);

    $vehicles = [];
    foreach ($results as $row) {
        $vehicle = new Vehicle($row->demo_vin, $row->real_vin, $row->arrayAvailableDates);
        $vehicleStatus = new VehicleStatus(
            $row->vin,
            $row->last_calc_date,
            $row->dayArray,
            $row->hrTotalVehicleDistance,
            $row->engineTotalFuelUsed,
            $row->serviceDistance,
            $row->totalEngineHours
        );
        $vehicles[] = ['vehicle' => $vehicle, 'vehicleStatus' => $vehicleStatus];
    }
    return $vehicles;
}

/**
 * Function to load rFMS vehicle demo data
 * @param PDO $db
 * @param Vehicle $vehicle
 * @param string $day
 * @return array
 */
function LoadrFMSVehicleDemoData(PDO $db, Vehicle $vehicle, string $day): array
{
    $query = "SELECT v.* FROM m_vs_template v WHERE v.vin = ? AND DATE(v.createdDateTime) = ?";
    $Q = $db->prepare($query); // Use a prepared statement
    $Q->execute([$vehicle->originalVin, $day]);
    $results = $Q->fetchAll(PDO::FETCH_CLASS, VehicleData::class);
    return $results;
}

/**
 * Function to register rFMS vehicle counters
 * @param PDO $db
 * @param VehicleStatus $v
 * @param int $e
 * @return void
 */
function RegisterrFMSVehicleCounters(PDO $db, VehicleStatus $v, int $e): void
{
    if ($e == 0) {
        $query = "UPDATE m_vs_calc_status SET last_calc_date = ?, dayArray = ? WHERE vin = ?";
        $Q = $db->prepare($query);
        $Q->execute([date_format($v->dailyRegistration['cdt'], 'Y-m-d'), $v->dayArray + 1, $v->vin]);
    } else {
        $query = "UPDATE m_vs_calc_status SET last_calc_date = ?, dayArray = ?, hrTotalVehicleDistance = ?, engineTotalFuelUsed = ?, serviceDistance = ?, totalEngineHours = ? WHERE vin = ?";
        $Q = $db->prepare($query);
        $Q->execute([
            $v->dailyRegistration['cdt'],
            $v->dayArray + 1,
            $v->dailyRegistration['odo'],
            $v->dailyRegistration['tfu'],
            $v->dailyRegistration['sd'],
            $v->dailyRegistration['teh'],
            $v->vin
        ]);
    }
}

/**
 * Function to load rFMS vehicle counters
 * @param VehicleStatus $vs
 * @param Vehicle $v
 * @param DateTime $date
 * @return VehicleStatus
 */
function LoadrFMSVehicleCounters(VehicleStatus $vs, Vehicle $v, DateTime $date): VehicleStatus
{
    $vDayArray = $v->getAvailableDates();
    $vs->ArrayDays = count($vDayArray);
    if ($vs->dayArray >= $vs->ArrayDays) {
        $vs->dayArray = 0;
    }
    $vs->selectedDay = new DateTime($vDayArray[$vs->dayArray]);
    $vs->selectedDayT = $vDayArray[$vs->dayArray];
    $vs->demoDay = $date;
    $vs->demoDay->setTime(0, 0, 0);
    $vs->templateDayDiff = date_diff($vs->selectedDay, $vs->demoDay)->format('%a');
    return $vs;
}

/**
 * @param string $title
 * @param VehicleData $data
 * @return void
 */
function ShowrFMSRowData(string $title, VehicleData $data): void
{
    $rowclass = ($title == 'demo') ? 'text-primary fw-bold' : '';
    echo '<tr class="small">';
    echo '<td class="' . $rowclass . '">' . $title . '</td>';
    echo '<td class="' . $rowclass . '">' . $data->createdDateTime . '</td>';
    echo '<td class="' . $rowclass . '">' . $data->receivedDateTime . '</td>';
    echo '<td class="' . $rowclass . '">' . $data->vin . '</td>';
    echo '<td class="' . $rowclass . '">' . $data->triggerType . '</td>';
    echo '<td class="' . $rowclass . '">' . $data->hrTotalVehicleDistance . '</td>';
    echo '<td class="' . $rowclass . '">' . $data->engineTotalFuelUsed . '</td>';
    echo '<td class="' . $rowclass . '">' . $data->serviceDistance . '</td>';
    echo '<td class="' . $rowclass . '">' . $data->totalEngineHours . '</td>';
    echo '</tr>';
}

/**
 * @return void
 */
function ShowrFMSRowHeader(): void
{
    echo '<div class="d-flex">';
    echo '  <div class="w-100 p-1 alert border mb-1">';
    echo '    <table class="table display table-striped  mb-0 small">';
    echo '      <thead class="small text-secondary"> ';
    echo '      <tr>
                <td>count</td>
                <td>demoDay</td>
                <td>Demo Vin</td>
                <td>Real_vin </td>
                <td>Day of array</td>
                <td>templateDayDiff</td>
                <td>Start_odo</td>
                <td>DailyIncrease</td>
                <td>DailyRegistration</td>
                <td>DataRowsProcessed</td>
              </tr>';
    echo '      </thead> ';
    echo '      <tbody> ';
}

/**
 * @param Vehicle $val
 * @param DateTime $SD
 * @param DateTime $ED
 * @param int $D
 * @return void
 */
function ShowrFMSVehicle(Vehicle $val, DateTime $SD, DateTime $ED, int $D): void
{
    echo '<div class="alert alert-primary p-2 border mb-2 d-flex">';
    echo '  <div class="col-3 h6">' . $val->vin . '</div>';
    echo '  <div class="ms-auto small">StartDate <b>' . date_format($SD, "Y-m-d H:i:s") . '</b> | EndDate <b>' . date_format($ED, "Y-m-d H:i:s") . '</b> | Days <b>' . $D . '</b></div>';
    echo '</div>';
}

/**
 * @param VehicleStatus $val
 * @return void
 */
function ShowrFMSVehicleCalc(VehicleStatus $val): void
{
    echo '
    <tr class="small">
      <td width="2%" class="h6">' . $val->counter . '</td>
      <td width="5%" class="border-right">' . date_format($val->demoDay, "Y-m-d") . '</td>
      <td width="5%" class="border-right">' . $val->vin . '</td>
      <td width="5%" class="border-right">' . $val->real_vin . '</td>
      <td width="5%" class="border-right">' . $val->dayArray . '/' . $val->ArrayDays . '</td>
      <td width="5%" class="border-right">' . $val->templateDayDiff . '</td>
      <td width="5%" class="border-right">' . round(intval($val->hrTotalVehicleDistance) / 1000, 0) . '</td>
      <td class="border-right  text-secondary">' . json_encode($val->dailyIncrease) . '</td>
      <td class="border-right  text-secondary">' . json_encode($val->dailyRegistration) . '</td>
      <td width="2%" class="text-end">' . $val->dataRowCount . '</td>
    </tr>';
}

/**
 * @return void
 */
function ShowrFMSDataCounter(): void
{
    //echo " | "; // Removed
}

/**
 * @return void
 */
function ShowrFMSCloseVehicle(): void
{
    echo '</table></div></div>';
}


/**
 * @param VehicleStatus $c
 * @param array $d
 * @return VehicleStatus
 */
function CalculaterFMSStartValues(VehicleStatus $c, array $d): VehicleStatus
{
    $c->dailyIncrease['odo'] = 0;
    $c->dailyIncrease['tfu'] = 0;
    $c->dailyIncrease['sd'] = 0;
    $c->dailyIncrease['teh'] = 0;
    foreach ($d as $val) {
        if ($val->hrTotalVehicleDistance != 0 && $c->dailyIncrease['odo'] == 0) {
            $c->dailyIncrease['odo'] = intval($c->hrTotalVehicleDistance) - intval($val->hrTotalVehicleDistance);
        }
        if ($val->engineTotalFuelUsed != 0 && $c->dailyIncrease['tfu'] == 0) {
            $c->dailyIncrease['tfu'] = intval($c->engineTotalFuelUsed) - intval($val->engineTotalFuelUsed);
        }
        if ($val->serviceDistance != 0 && $c->dailyIncrease['sd'] == 0) {
            $c->dailyIncrease['sd'] = intval($c->serviceDistance) - intval($val->serviceDistance);
        }
        if ($val->totalEngineHours != 0 && $c->dailyIncrease['teh'] == 0) {
            $c->dailyIncrease['teh'] = intval($c->totalEngineHours) - intval($val->totalEngineHours);
        }
    }
    return $c;
}

/**
 * @param VehicleData $ar
 * @return void
 */
function InsertVehicleStatusesrFMS3(VehicleData $ar): void
{
    $db = getDatabaseConnection(); // Get the database connection
    $sql = 'INSERT IGNORE INTO api_vehiclestatus SET ';
    $fields = [];
    $values = [];
    $a = '';
    foreach ($ar as $key => $value) {
        $fields[] = $key;
        $values[] = $value;
        $sql .= $a . $key . '=?';
        $a = ', ';
    }
    $Q = $db->prepare($sql); // Prepare the statement.
    $Q->execute($values);
    $myfile = fopen("API_VehicleStatus_to_SQL.txt", "a");  // Added for debugging
    fwrite($myfile, $sql . ";\n");
    fclose($myfile);
}

/**
 * Main function to process and display rFMS demo data.
 *
 * @return void
 */
function Create_rFMS_DemoData(): void
{
    $db = getDatabaseConnection(); // Get the database connection
    $vehicles = GetDemoVehicles($db);
    $debug = false; //  Make this configurable if needed

    if (count($vehicles) > 0) {
        foreach ($vehicles as $vehicleData) {
            $vehicle = $vehicleData['vehicle'];
            $vehicleStatus = $vehicleData['vehicleStatus'];
            try {
                $StartDate = clone $vehicleStatus->lastCalcDate;
                $StartDate->modify('+1 day');
                $EndDate = new DateTime();
                $Days = date_diff($StartDate, $EndDate);
                $Days = intval($Days->format("%a")) + 1;
                ShowrFMSVehicle($vehicle, $StartDate, $EndDate, $Days);
                ShowrFMSRowHeader();


                for ($i = 0; $i < $Days; $i++) {
                    $vehicleStatus = LoadrFMSVehicleCounters($vehicleStatus, $vehicle, $StartDate);
                    $vehicleStatus->dataRowCount = 0;
                    $vehicleStatus->dailyRegistration['cdt'] = $StartDate;
                    $vehicleStatus->dailyRegistration['rdt'] = $StartDate; //initialize rdt
                    $vehicleDemoData = LoadrFMSVehicleDemoData($db, $vehicle, $vehicleStatus->selectedDayT);

                    if (count($vehicleDemoData) > 0) {
                        $vehicleStatus = CalculaterFMSStartValues($vehicleStatus, $vehicleDemoData);
                        foreach ($vehicleDemoData as $data) {
                            if ($debug) {
                                ShowrFMSRowData('original', $data);
                            } else {
                                ShowrFMSDataCounter();
                            }
                            $data->driver1Id = Calculation::ReplaceDriver($data->driver1Id, $vehicle->vin);
                            $data->driver2Id = Calculation::ReplaceDriver($data->driver2Id, $vehicle->vin);
                            if ($data->hrTotalVehicleDistance > 0) {
                                $data->hrTotalVehicleDistance += $vehicleStatus->dailyIncrease['odo'];
                            }
                            if ($data->engineTotalFuelUsed > 0) {
                                $data->engineTotalFuelUsed += $vehicleStatus->dailyIncrease['tfu'];
                            }
                            if ($data->serviceDistance > 0) {
                                $data->serviceDistance += $vehicleStatus->dailyIncrease['sd'];
                            }
                            if ($data->totalEngineHours > 0) {
                                $data->totalEngineHours += $vehicleStatus->dailyIncrease['teh'];
                            }
                            $vehicleStatus->dailyRegistration['odo'] = $data->hrTotalVehicleDistance;
                            $vehicleStatus->dailyRegistration['tfu'] = $data->engineTotalFuelUsed;
                            $vehicleStatus->dailyRegistration['sd'] = $data->serviceDistance;
                            $vehicleStatus->dailyRegistration['teh'] = $data->totalEngineHours;
                            $vehicleStatus->dailyRegistration['cdt'] = $data->createdDateTime;
                            $vehicleStatus->dailyRegistration['rdt'] = $data->receivedDateTime;
                            InsertVehicleStatusesrFMS3($data);
                       //     UpdateVehicleTriprFMS3($data);
                            $vehicleStatus->dataRowCount++;
                        }
                    }
                    RegisterrFMSVehicleCounters($db, $vehicleStatus, 0);
                    ShowrFMSVehicleCalc($vehicleStatus);
                    $StartDate->modify('+1 day');
                }
                RegisterrFMSVehicleCounters($db, $vehicleStatus, 1);
                ShowrFMSCloseVehicle();
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage(); // basic error.
            }
        }
    } else {
        echo "No vehicles to process.";
    }
}

Create_rFMS_DemoData();
