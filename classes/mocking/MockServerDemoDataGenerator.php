<?php
namespace ConnectingOfThings\Classes\Mocking; 

use ConnectingOfThings\Classes\EventEngine\EventEngine;
use ConnectingOfThings\Classes\Vehicle\Vehicle;
use ConnectingOfThings\Classes\Vehicle\VehicleStatus;
use ConnectingOfThings\Classes\Mocking\Rfms3\Calculations\Calculation; 
use ConnectingOfThings\Classes\Mocking\Tpms\TpmsData;           
use ConnectingOfThings\Classes\Mocking\Trailer\TrailerEventData; 
use PDO;
use Exception;
use DateTime;
use DateInterval;

class MockServerDemoDataGenerator
{
    private PDO $pdo;
    private Calculation $calculation;

    public function __construct(PDO $pdo, Calculation $calculation)
    {
        $this->pdo = $pdo;
        $this->calculation = $calculation;
    }

    public function generateMockDemoData(): void
    {
        try {
            // 1. Get the demo vehicles using the Calculation class.
            $vehiclesWithStatus = Calculation::GetDemoVehicles();

            foreach ($vehiclesWithStatus as $vehicleData) {
                /** @var Vehicle $vehicle */
                $vehicle = $vehicleData['vehicle'];
                /** @var VehicleStatus $vehicleStatus */
                $vehicleStatus = $vehicleData['vehicleStatus'];

                $currentDate = new DateTime();
                $startDate = clone $vehicleStatus->lastCalcDate;
                $startDate->modify('+1 day');
                $endDate = clone $currentDate;
                $endDate->setTime(0, 0, 0); // Process up to the beginning of the current day

                $interval = $startDate->diff($endDate);
                $daysToProcess = (int)$interval->format('%a');

                for ($i = 0; $i < $daysToProcess; $i++) {
                    $processDate = clone $startDate;
                    $processDate->modify("+$i day");
                    $formattedProcessDate = $processDate->format('Y-m-d');

                    // 2. Load the template data for the current vehicle and day.
                    $templateEvents = Calculation::LoadrFMSVehicleDemoData($vehicle, $formattedProcessDate);

                    if (!empty($templateEvents)) {
                        // 3. Load and update vehicle counters.
                        $vehicleStatus = Calculation::LoadrFMSVehicleCounters($vehicleStatus, $vehicle, $processDate);

                        // 4. Calculate initial counter offsets.
                        $vehicleStatus = Calculation::CalculaterFMSStartValues($vehicleStatus, $templateEvents);

                        // 5. Process and insert each template event.
                        foreach ($templateEvents as $event) {
                            // Modify driver ID (example).
                            $event->driver1Id = Calculation::ReplaceDriver($event->driver1Id, $vehicle->vin);
                            $event->driver2Id = Calculation::ReplaceDriver($event->driver2Id, $vehicle->vin);

                            // Apply the calculated offsets to the lifetime counters.
                            $event->hrTotalVehicleDistance += $vehicleStatus->dailyIncrease['odo'];
                            $event->engineTotalFuelUsed += $vehicleStatus->dailyIncrease['tfu'];
                            $event->serviceDistance += $vehicleStatus->dailyIncrease['sd'];
                            $event->totalEngineHours += $vehicleStatus->dailyIncrease['teh'];

                            // Insert the processed event into api_vehiclestatus.
                            Calculation::InsertVehicleStatusesrFMS3($event);

                            $vehicleStatus->dailyRegistration['cdt'] = $event->createdDateTime;
                            $vehicleStatus->dailyRegistration['odo'] = $event->hrTotalVehicleDistance;
                            $vehicleStatus->dailyRegistration['tfu'] = $event->engineTotalFuelUsed;
                            $vehicleStatus->dailyRegistration['sd'] = $event->serviceDistance;
                            $vehicleStatus->dailyRegistration['teh'] = $event->totalEngineHours;
                        }

                        // 6. Register the updated counters and last calculation date.
                        Calculation::RegisterrFMSVehicleCounters($vehicleStatus, 1); // Update all counters
                    } else {
                        // If no template data for the day, just update the last calc date.
                        $vehicleStatus->dailyRegistration['cdt'] = $formattedProcessDate . ' 00:00:00'; // Use start of day
                        Calculation::RegisterrFMSVehicleCounters($vehicleStatus, 0); // Only update date and dayArray
                    }
                }
            }

            echo "rFMS demo data created and inserted successfully.\n";

        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}