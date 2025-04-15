<?php

namespace ConnectingOfThings\Classes\Vehicle;

use DateTime;

class VehicleStatus
{
    public string $vin;
    public DateTime $lastCalcDate;
    public int $dayArray;
    public int $hrTotalVehicleDistance;
    public int $engineTotalFuelUsed;
    public int $serviceDistance;
    public int $totalEngineHours;
    public array $dailyIncrease;
    public array $dailyRegistration;
    public int $ArrayDays;
    public DateTime $selectedDay;
    public string $selectedDayT;
    public DateTime $demoDay;
    public int $templateDayDiff;
    public int $counter;
    public int $dataRowCount;

    /**
     * Constructor for the VehicleStatus class.
     *
     * @param string $vin The vehicle identification number.
     * @param string $lastCalcDate The last calculation date.
     * @param int $dayArray The day array.
     * @param int $hrTotalVehicleDistance The total vehicle distance.
     * @param int $engineTotalFuelUsed The total fuel used.
     * @param int $serviceDistance The service distance.
     * @param int $totalEngineHours The total engine hours.
     */
    public function __construct(
        string $vin,
        string $lastCalcDate,
        int $dayArray,
        int $hrTotalVehicleDistance,
        int $engineTotalFuelUsed,
        int $serviceDistance,
        int $totalEngineHours
    ) {
        $this->vin = $vin;
        try {
            $this->lastCalcDate = new DateTime($lastCalcDate);
        } catch (\Exception $e) {
            // Handle the exception (e.g., log it, set a default date)
            error_log("Invalid date format for lastCalcDate in VehicleStatus constructor.  Setting to today.");
            $this->lastCalcDate = new DateTime(); // Set to today's date
        }
        $this->dayArray = $dayArray;
        $this->hrTotalVehicleDistance = $hrTotalVehicleDistance;
        $this->engineTotalFuelUsed = $engineTotalFuelUsed;
        $this->serviceDistance = $serviceDistance;
        $this->totalEngineHours = $totalEngineHours;
        $this->dailyIncrease = [];
        $this->dailyRegistration = [];
        $this->ArrayDays = 0;
        $this->selectedDay = new DateTime();  // Initialize with a default value.
        $this->selectedDayT = '';            // Initialize with a default value.
        $this->demoDay = new DateTime();      // Initialize with a default value.
        $this->templateDayDiff = 0;
        $this->counter = 0;
        $this->dataRowCount = 0;
    }
}
