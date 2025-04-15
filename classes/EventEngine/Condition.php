<?php

namespace ConnectingOfThings\Classes\EventEngine;

use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

// Base Exception for Condition Class
class ConditionException extends Exception
{
}

/**
 * The Condition class represents a condition that can be evaluated to determine
 * if a rule should be executed.
 */
class Condition
{
    /**
     * @var string The condition string.
     */
    private $conditionString;

    /**
     * @var LoggerInterface The logger instance.
     */
    private $logger;

    /**
     * Constructor for the Condition class.
     *
     * @param string $conditionString The condition string to evaluate.
     * @param LoggerInterface|null $logger The logger instance.
     */
    public function __construct(string $conditionString, LoggerInterface $logger = null)
    {
        $this->conditionString = $conditionString;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Evaluates the condition.
     *
     * This method evaluates the condition string (which can contain PHP code)
     * in the context of the provided data.
     *
     * @param array $data An array of data (event data, facts, etc.) that can be
     * used in the condition.  The keys of the array
     * become variables available in the condition
     * evaluation scope.
     * @return bool True if the condition is met, false otherwise.
     * @throws ConditionException If there is an error evaluating the condition.
     */
    public function evaluate(array $data): bool
    {
        $this->logger->debug("Evaluating condition: {$this->conditionString} with data: " . json_encode($data));
        // Extract variables from the data array.
        extract($data); // This is safe(r) because the condition is defined by us, not user input, and any errors will be caught.

        try {
            // Evaluate the condition string as PHP code.
            $result = eval("return {$this->conditionString};");
            if ($result === false && error_get_last() !== null) {
                $error = error_get_last();
                $this->logger->error("Error evaluating condition: {$this->conditionString}. Error: " . $error['message']);
                throw new ConditionException("Error evaluating condition: {$this->conditionString}.  PHP Error: " . $error['message']);
            }
            $this->logger->debug("Condition: {$this->conditionString} evaluated to: " . ($result ? 'true' : 'false'));
            return (bool) $result; // Ensure the result is a boolean.
        } catch (\Throwable $e) {
            $this->logger->error("Exception evaluating condition: {$this->conditionString}.  Exception: " . $e->getMessage());
            throw new ConditionException("Error evaluating condition: {$this->conditionString}. Exception: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Returns the condition string.
     *
     * @return string The condition string.
     */
    public function getConditionString(): string
    {
        return $this->conditionString;
    }

     /**
     * Sets the condition string.
     *
     * @param string $conditionString
     * @return void
     */
    public function setConditionString(string $conditionString): void
    {
        $this->conditionString = $conditionString;
    }
}
