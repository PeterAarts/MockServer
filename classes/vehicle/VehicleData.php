<?php

namespace ConnectingOfThings\Classes\Vehicle;

class VehicleData
{
    public string $createdDateTime;
    public string $receivedDateTime;
    public string $vin;
    public string $triggerType;
    public int $hrTotalVehicleDistance;
    public int $engineTotalFuelUsed;
    public int $serviceDistance;
    public int $totalEngineHours;
    public string $driver1Id;
    public string $driver2Id;
    public string $GNSS_latitude;
    public string $GNSS_longitude;
    public string $speed;
    public string $heading;
    public string $altitude;
    public string $horizontalAccuracy;
    public string $verticalAccuracy;
    public string $bearing;
    public string $satellites;
    public string $signalStrength;
    public string $externalPower;
    public string $batteryLevel;
    public string $fuelLevel;
    public string $coolantTemperature;
    public string $engineOilPressure;
    public string $ambientAirTemperature;
    public string $acceleratorPedalPosition;
    public string $brakePedalStatus;
    public string $transmissionGearPosition;
    public string $transmissionOilTemperature;
    public string $wheelBasedSpeed;
    public string $ptoStatus;
    public string $roadSpeedLimit;
    public string $tachographCardInserted;
    public string $tachographVehicleSpeed;
    public string $axleWeightFront;
    public string $axleWeightRear;
    public string $cargoWeight;
    public string $doorStatusFrontLeft;
    public string $doorStatusFrontRight;
    public string $doorStatusRearLeft;
    public string $doorStatusRearRight;
    public string $wiperStatus;
    public string $headlightStatus;
    public string $odometerSource;
    public string $fuelSource;
    public string $maxSpeedSource;
    public string $idleFuelSource;
    public string $idleTimeSource;
    public string $engineHoursSource;
    public string $distanceCalculationSource;
    public string $speedSource;
    public string $torqueSource;
    public string $steeringAngleSource;
    public string $pitchSource;
    public string $rollSource;
    public string $yawSource;
    public string $accelerationSource;
    public string $wheelSpeedSource;
    public string $brakeStatusSource;
    public string $gearboxSource;
    public string $retarderSource;
    public string $tirePressureSource;
    public string $weightSource;
    public string $doorStatusSource;
    public string $wiperSource;
    public string $lightsSource;
    public string $context;
    public string $message;


    /**
     * Constructor for the VehicleData class.
     *
     * @param array $data An associative array of vehicle data.
     */
    public function __construct(array $data)
    {
        $this->createdDateTime = $data['createdDateTime'] ?? '';
        $this->receivedDateTime = $data['receivedDateTime'] ?? '';
        $this->vin = $data['vin'] ?? '';
        $this->triggerType = $data['triggerType'] ?? '';
        $this->hrTotalVehicleDistance = $data['hrTotalVehicleDistance'] ?? 0;
        $this->engineTotalFuelUsed = $data['engineTotalFuelUsed'] ?? 0;
        $this->serviceDistance = $data['serviceDistance'] ?? 0;
        $this->totalEngineHours = $data['totalEngineHours'] ?? 0;
        $this->driver1Id = $data['driver1Id'] ?? '';
        $this->driver2Id = $data['driver2Id'] ?? '';
        $this->GNSS_latitude = $data['GNSS_latitude'] ?? '';
        $this->GNSS_longitude = $data['GNSS_longitude'] ?? '';
        $this->speed = $data['speed'] ?? '';
        $this->heading = $data['heading'] ?? '';
        $this->altitude = $data['altitude'] ?? '';
        $this->horizontalAccuracy = $data['horizontalAccuracy'] ?? '';
        $this->verticalAccuracy = $data['verticalAccuracy'] ?? '';
        $this->bearing = $data['bearing'] ?? '';
        $this->satellites = $data['satellites'] ?? '';
        $this->signalStrength = $data['signalStrength'] ?? '';
        $this->externalPower = $data['externalPower'] ?? '';
        $this->batteryLevel = $data['batteryLevel'] ?? '';
        $this->fuelLevel = $data['fuelLevel'] ?? '';
        $this->coolantTemperature = $data['coolantTemperature'] ?? '';
        $this->engineOilPressure = $data['engineOilPressure'] ?? '';
        $this->ambientAirTemperature = $data['ambientAirTemperature'] ?? '';
        $this->acceleratorPedalPosition = $data['acceleratorPedalPosition'] ?? '';
        $this->brakePedalStatus = $data['brakePedalStatus'] ?? '';
        $this->transmissionGearPosition = $data['transmissionGearPosition'] ?? '';
        $this->transmissionOilTemperature = $data['transmissionOilTemperature'] ?? '';
        $this->wheelBasedSpeed = $data['wheelBasedSpeed'] ?? '';
        $this->ptoStatus = $data['ptoStatus'] ?? '';
        $this->roadSpeedLimit = $data['roadSpeedLimit'] ?? '';
        $this->tachographCardInserted = $data['tachographCardInserted'] ?? '';
        $this->tachographVehicleSpeed = $data['tachographVehicleSpeed'] ?? '';
        $this->axleWeightFront = $data['axleWeightFront'] ?? '';
        $this->axleWeightRear = $data['axleWeightRear'] ?? '';
        $this->cargoWeight = $data['cargoWeight'] ?? '';
        $this->doorStatusFrontLeft = $data['doorStatusFrontLeft'] ?? '';
        $this->doorStatusFrontRight = $data['doorStatusFrontRight'] ?? '';
        $this->doorStatusRearLeft = $data['doorStatusRearLeft'] ?? '';
        $this->doorStatusRearRight = $data['doorStatusRearRight'] ?? '';
        $this->wiperStatus = $data['wiperStatus'] ?? '';
        $this->headlightStatus = $data['headlightStatus'] ?? '';
        $this->odometerSource = $data['odometerSource'] ?? '';
        $this->fuelSource = $data['fuelSource'] ?? '';
        $this->maxSpeedSource = $data['maxSpeedSource'] ?? '';
        $this->idleFuelSource = $data['idleFuelSource'] ?? '';
        $this->idleTimeSource = $data['idleTimeSource'] ?? '';
        $this->engineHoursSource = $data['engineHoursSource'] ?? '';
        $this->distanceCalculationSource = $data['distanceCalculationSource'] ?? '';
        $this->speedSource = $data['speedSource'] ?? '';
        $this->torqueSource = $data['torqueSource'] ?? '';
        $this->steeringAngleSource = $data['steeringAngleSource'] ?? '';
        $this->pitchSource = $data['pitchSource'] ?? '';
        $this->rollSource = $data['rollSource'] ?? '';
        $this->yawSource = $data['yawSource'] ?? '';
        $this->accelerationSource = $data['accelerationSource'] ?? '';
        $this->wheelSpeedSource = $data['wheelSpeedSource'] ?? '';
        $this->brakeStatusSource = $data['brakeStatusSource'] ?? '';
        $this->gearboxSource = $data['gearboxSource'] ?? '';
        $this->retarderSource = $data['retarderSource'] ?? '';
        $this->tirePressureSource = $data['tirePressureSource'] ?? '';
        $this->weightSource = $data['weightSource'] ?? '';
        $this->doorStatusSource = $data['doorStatusSource'] ?? '';
        $this->wiperSource = $data['wiperSource'] ?? '';
        $this->lightsSource = $data['lightsSource'] ?? '';
        $this->context = $data['context'] ?? '';
        $this->message = $data['message'] ?? '';
    }
}
