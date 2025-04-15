<?php
namespace ConnectingOfThings\Classes\Vehicle;

use DateTime;
use Exception;

class Vehicle
{
    public string $vin;
    public string $originalVin;
    public array $availableDates;

    /**
     * Constructor for the Vehicle class.
     *
     * @param string $vin The vehicle identification number.
     * @param string $originalVin The original vehicle identification number.
     * @param string|null $availableDates A comma-separated string of available dates, or null.
     */
    public function __construct(string $vin, string $originalVin, ?string $availableDates)
    {
        $this->vin = $vin;
        $this->originalVin = $originalVin;
        $this->availableDates = $this->processAvailableDates($availableDates);
    }

    /**
     * Processes the comma-separated string of available dates into an array of DateTime objects.
     *
     * @param string|null $availableDates The comma-separated string of available dates, or null.
     * @return array An array of DateTime objects, or an empty array if $availableDates is null or empty.
     */
    private function processAvailableDates(?string $availableDates): array
    {
        $dates = [];
        if ($availableDates) {
            $dateStrings = explode(',', $availableDates);
            foreach ($dateStrings as $dateString) {
                try {
                    // Create DateTime object from the date string
                    $date = new DateTime(trim($dateString));
                    $dates[] = $date;
                } catch (Exception $e) {
                    // Log the error (consider using a proper logging mechanism)
                    error_log("Invalid date format: " . $dateString . " in Vehicle constructor.  Skipping.");
                    // Skip invalid dates
                }
            }
        }
        return $dates;
    }

    /**
     * Gets the available dates.
     *
     * @return array An array of DateTime objects representing the available dates.
     */
    public function getAvailableDates(): array
    {
        return $this->availableDates;
    }
}
