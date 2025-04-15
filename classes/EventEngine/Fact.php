<?php

namespace ConnectingOfThings\Classes\EventEngine;

use ConnectingOfThings\Classes\Database\DB;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Exception;

// Base Exception for Fact Class
class FactException extends Exception
{
}

/**
 * The Fact class represents a fact or piece of data that can be used in the
 * event processing logic.  Facts can be stored in the database and retrieved
 * for use in rule conditions and actions.
 */
class Fact
{
    /**
     * @var DB The database connection.
     */
    private $db;

    /**
     * @var LoggerInterface The logger instance.
     */
    private $logger;

    /**
     * @var string The name of the fact.
     */
    private $factName;

    /**
     * @var mixed The value of the fact.
     */
    private $factValue;

    /**
     * Constructor for the Fact class.
     *
     * @param DB $db The database connection.
     * @param string $factName The name of the fact.
     * @param LoggerInterface|null $logger The logger instance.
     */
    public function __construct(DB $db, string $factName, LoggerInterface $logger = null)
    {
        $this->db = $db;
        $this->logger = $logger ?? new NullLogger();
        $this->factName = $factName;
        $this->loadFact(); // Load the fact value from the database.
    }

    /**
     * Retrieves the value of the fact.
     *
     * @return mixed The value of the fact, or null if it doesn't exist.
     */
    public function getValue()
    {
        return $this->factValue;
    }

    /**
     * Sets the value of the fact.
     *
     * @param mixed $value The value to set for the fact.
     * @throws FactException If there is an error updating the fact in the database.
     */
    public function setValue($value): void
    {
        $this->factValue = $value;
        try {
            $query = "UPDATE facts SET fact_value = :fact_value WHERE fact_name = :fact_name";
            $this->db->update($query, [
                ':fact_value' => is_scalar($value) ? (string)$value : json_encode($value), //store scalars as strings, json_encode arrays and objects
                ':fact_name' => $this->factName,
            ]);
            $this->logger->info("Set value for fact: {$this->factName} to: " . (is_scalar($value) ? $value : json_encode($value)));
        } catch (\Throwable $e) {
            $this->logger->error("Error setting value for fact: {$this->factName}: " . $e->getMessage());
            throw new FactException("Error setting value for fact: {$this->factName}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Loads the fact value from the database.
     *
     * @throws FactException If there is an error loading the fact from the database.
     */
    private function loadFact(): void
    {
        try {
            $query = "SELECT fact_value FROM facts WHERE fact_name = :fact_name";
            $result = $this->db->fetchOne($query, [':fact_name' => $this->factName]);

            if ($result === false) {
                $this->factValue = null; // Fact not found.
                $this->logger->notice("Fact: {$this->factName} not found in the database.");
                return;
            }
            //determine how to set the value.
            $this->factValue = $this->decodeFactValue($result['fact_value']);

            $this->logger->info("Loaded value for fact: {$this->factName} from the database.");
        } catch (\Throwable $e) {
            $this->logger->error("Error loading fact: {$this->factName} from the database: " . $e->getMessage());
            throw new FactException("Error loading fact: {$this->factName} from the database: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Creates a new fact in the database.
     *
     * This static method allows you to create a new fact directly without needing an instance
     * of the Fact class.  It's useful for initializing facts.
     *
     * @param DB $db The database connection.
     * @param string $factName The name of the new fact.
     * @param mixed $factValue The initial value of the new fact.
     * @param LoggerInterface|null $logger The logger instance.
     * @throws FactException If there is an error creating the fact in the database.
     */
    public static function createFact(DB $db, string $factName, $factValue, LoggerInterface $logger = null): void
    {
        $logger = $logger ?? new NullLogger();
        try {
            $query = "INSERT INTO facts (fact_name, fact_value) VALUES (:fact_name, :fact_value)";
            $db->insert($query, [
                ':fact_name' => $factName,
                ':fact_value' => is_scalar($factValue) ? (string)$factValue : json_encode($factValue), //store scalars as strings, json_encode arrays and objects
            ]);
            $logger->info("Created new fact: {$factName} with initial value: " . (is_scalar($factValue) ? $factValue: json_encode($factValue)));
        } catch (\Throwable $e) {
            $logger->error("Error creating fact: {$factName} in the database: " . $e->getMessage());
            throw new FactException("Error creating fact: {$factName} in the database: " . $e->getMessage(), 0, $e);
        }
    }

    /**
    * Helper function to determine how to decode the fact value
    */
    private function decodeFactValue(string $value)
    {
         $decoded = json_decode($value, true);
         if (json_last_error() == JSON_ERROR_NONE) {
            return $decoded;
         }
         return $value; //it is a scalar
    }
}
