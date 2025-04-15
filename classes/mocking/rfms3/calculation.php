<?php

namespace ConnectingOfThings\Classes\Mocking\Rfms3\Calculations;

use ConnectingOfThings\Classes\Database\DB; 
use ConnectingOfThings\Classes\Vehicle\Vehicle;
use ConnectingOfThings\Classes\Vehicle\VehicleStatus;
use ConnectingOfThings\Classes\Vehicle\VehicleData;
use DateTime;
use DateInterval;
use Exception;

class Calculation
{
    /**
     * Retrieves demo vehicles from the database.
     *
     * @return array An array of vehicles, where each vehicle is an associative array
     * containing a 'vehicle' (Vehicle object) and a 'vehicleStatus' (VehicleStatus object).
     * Returns an empty array on failure.
     */
    public static function GetDemoVehicles(): array
    {
        try {
            $db = DB::getInstance();
            $query = "SELECT vs.*, v.vin AS demo_vin, v.originalVin AS real_vin, v.arrayAvailableDates
                      FROM m_vs_calc_status vs
                      LEFT JOIN m_vs_vehicles v ON vs.vin = v.id
                      WHERE vs.active = 1 AND DATE(vs.last_calc_date) < CURRENT_DATE()";
            $Q = $db->query($query);

            if ($Q->error()) {
                // Log the error (use a proper logging mechanism)
                error_log("Error in GetDemoVehicles: " . $Q->errorString());
                return []; // Return an empty array on error
            }

            $results = $Q->results();
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
        } catch (Exception $e) {
            // Handle the exception (e.g., log it, display a user-friendly message)
            error_log("Exception in GetDemoVehicles: " . $e->getMessage());
            return []; // Return empty array on Exception
        }
    }


    /**
     * Loads vehicle demo data for a specific vehicle and day.
     *
     * @param Vehicle $vehicle The Vehicle object.
     * @param string $day The day for which to load data (in 'Y-m-d' format).
     * @return array An array of VehicleData objects, or an empty array on error.
     */
    public static function LoadrFMSVehicleDemoData(Vehicle $vehicle, string $day): array
    {
        try {
            $db = DB::getInstance();
            $query = "SELECT v.* FROM m_vs_template v WHERE v.vin = ? AND DATE(v.createdDateTime) = ?";
            $Q = $db->query($query, [$vehicle->originalVin, $day]);

            if ($Q->error()) {
                error_log("Error in LoadrFMSVehicleDemoData: " . $Q->errorString());
                return [];
            }

            $results = $Q->results();
            $vehicleData = [];
            foreach ($results as $data) {
                $vehicleData[] = new VehicleData((array)$data);
            }
            return $vehicleData;
        } catch (Exception $e) {
            error_log("Exception in LoadrFMSVehicleDemoData: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Registers or updates vehicle counters in the database.
     *
     * @param VehicleStatus $v The VehicleStatus object.
     * @param int $e An integer indicating whether to update all counters (1) or just the date and dayArray (0).
     * @return void
     */
    public static function RegisterrFMSVehicleCounters(VehicleStatus $v, int $e): void
    {
        try {
            $db = DB::getInstance();
            if ($e == 0) {
                $query = "UPDATE m_vs_calc_status SET last_calc_date = ?, dayArray = ? WHERE vin = ?";
                $db->query($query, [date_format($v->dailyRegistration['cdt'], 'Y-m-d'), $v->dayArray + 1, $v->vin]);
            } else {
                $query = "UPDATE m_vs_calc_status SET last_calc_date = ?, dayArray = ?, hrTotalVehicleDistance = ?, engineTotalFuelUsed = ?, serviceDistance = ?, totalEngineHours = ? WHERE vin = ?";
                $db->query($query, [
                    $v->dailyRegistration['cdt'],
                    $v->dayArray + 1,
                    $v->dailyRegistration['odo'],
                    $v->dailyRegistration['tfu'],
                    $v->dailyRegistration['sd'],
                    $v->dailyRegistration['teh'],
                    $v->vin
                ]);
            }
            if ($db->error()) {
                error_log("Error in RegisterrFMSVehicleCounters: " . $db->errorString());
            }
        } catch (Exception $e) {
            error_log("Exception in RegisterrFMSVehicleCounters: " . $e->getMessage());
        }
    }

    /**
     * Loads vehicle counters and related data.
     *
     * @param VehicleStatus $vs The VehicleStatus object.
     * @param Vehicle $v The Vehicle object.
     * @param DateTime $date The current date.
     * @return VehicleStatus The updated VehicleStatus object.
     */
    public static function LoadrFMSVehicleCounters(VehicleStatus $vs, Vehicle $v, DateTime $date): VehicleStatus
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
     * Displays a single row of vehicle data.
     *
     * @param string $title The title for the row ('demo' or other).
     * @param VehicleData $data The VehicleData object.
     * @return void
     */
    public static function ShowrFMSRowData(string $title, VehicleData $data): void
    {
        $rowclass = ($title == 'demo') ? 'text-primary fw-bold' : '';
        echo '<tr class="small">';
        echo '<td class="' . $rowclass . '">' . htmlspecialchars($title) . '</td>'; //prevent XSS
        echo '<td class="' . $rowclass . '">' . htmlspecialchars($data->createdDateTime) . '</td>';
        echo '<td class="' . $rowclass . '">' . htmlspecialchars($data->receivedDateTime) . '</td>';
        echo '<td class="' . $rowclass . '">' . htmlspecialchars($data->vin) . '</td>';
        echo '<td class="' . $rowclass . '">' . htmlspecialchars($data->triggerType) . '</td>';
        echo '<td class="' . $rowclass . '">' . htmlspecialchars($data->hrTotalVehicleDistance) . '</td>';
        echo '<td class="' . $rowclass . '">' . htmlspecialchars($data->engineTotalFuelUsed) . '</td>';
        echo '<td class="' . $rowclass . '">' . htmlspecialchars($data->serviceDistance) . '</td>';
        echo '<td class="' . $rowclass . '">' . htmlspecialchars($data->totalEngineHours) . '</td>';
        echo '</tr>';
    }

    /**
     * Displays the header row for the vehicle data table.
     *
     * @return void
     */
    public static function ShowrFMSRowHeader(): void
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
     * Displays vehicle information.
     *
     * @param Vehicle $val The Vehicle object.
     * @param DateTime $SD The start date.
     * @param DateTime $ED The end date.
     * @param int $D The number of days.
     * @return void
     */
    public static function ShowrFMSVehicle(Vehicle $val, DateTime $SD, DateTime $ED, int $D): void
    {
        echo '<div class="alert alert-primary p-2 border mb-2 d-flex">';
        echo '  <div class="col-3 h6">' . htmlspecialchars($val->vin) . '</div>';
        echo '  <div class="ms-auto small">StartDate <b>' . date_format($SD, "Y-m-d H:i:s") . '</b> | EndDate <b>' . date_format($ED, "Y-m-d H:i:s") . '</b> | Days <b>' . htmlspecialchars((string)$D) . '</b></div>';
        echo '</div>';
    }

    /**
     * Displays calculated vehicle status.
     *
     * @param VehicleStatus $val The VehicleStatus object.
     * @return void
     */
    public static function ShowrFMSVehicleCalc(VehicleStatus $val): void
    {
        echo '
        <tr class="small">
          <td width="2%" class="h6">' . htmlspecialchars((string)$val->counter) . '</td>
          <td width="5%" class="border-right">' . htmlspecialchars(date_format($val->demoDay, "Y-m-d")) . '</td>
          <td width="5%" class="border-right">' . htmlspecialchars($val->vin) . '</td>
          <td width="5%" class="border-right">' . htmlspecialchars($val->real_vin) . '</td>
          <td width="5%" class="border-right">' . htmlspecialchars((string)$val->dayArray) . '/' . htmlspecialchars((string)$val->ArrayDays) . '</td>
          <td width="5%" class="border-right">' . htmlspecialchars((string)$val->templateDayDiff) . '</td>
          <td width="5%" class="border-right">' . htmlspecialchars(round(intval($val->hrTotalVehicleDistance) / 1000, 0)) . '</td>
          <td class="border-right  text-secondary">' . htmlspecialchars(json_encode($val->dailyIncrease)) . '</td>
          <td class="border-right  text-secondary">' . htmlspecialchars(json_encode($val->dailyRegistration)) . '</td>
          <td width="2%" class="text-end">' . htmlspecialchars((string)$val->dataRowCount) . '</td>
        </tr>';
    }

    /**
     * Displays a data counter (currently empty).
     *
     * @return void
     */
    public static function ShowrFMSDataCounter(): void
    {
        //  echo " | ";  // Removed empty echo
    }

    /**
     * Closes the vehicle data table.
     *
     * @return void
     */
    public static function ShowrFMSCloseVehicle(): void
    {
        echo '</table></div></div>';
    }

    /**
     * Calculates and sets the starting values for daily increases in VehicleStatus.
     *
     * @param VehicleStatus $c The VehicleStatus object to update.
     * @param array $d An array of VehicleData objects.
     * @return VehicleStatus The updated VehicleStatus object.
     */
    public static function CalculaterFMSStartValues(VehicleStatus $c, array $d): VehicleStatus
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
     * Replaces a portion of the driver ID with the last 6 digits of the VIN.
     *
     * @param string $driver The driver ID.
     * @param string $vin The vehicle identification number.
     * @return string The modified driver ID.
     */
    public static function ReplaceDriver(string $driver, string $vin): string
    {
        $part = substr($vin, -6, 6);
        return (strlen($driver) == 0) ? "" : substr_replace($driver, $part, 4, 6);
    }

    /**
     * Inserts vehicle status data into the database.
     *
     * @param VehicleData $ar The VehicleData object.
     * @return void
     */
    public static function InsertVehicleStatusesrFMS3(VehicleData $ar): void
    {
        try {
            $db = DB::getInstance();
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
            $db->query($sql, $values);
            if ($db->error()) {
                error_log("Error in InsertVehicleStatusesrFMS3: " . $db->errorString());
            }
            $myfile = fopen("API_VehicleStatus_to_SQL.txt", "a");  // Added for debugging -  Consider a better logging solution.
            fwrite($myfile, $sql . ";\n");
            fclose($myfile);
        } catch (Exception $e) {
            error_log("Exception in InsertVehicleStatusesrFMS3: " . $e->getMessage());
        }
    }
    
}
?>
