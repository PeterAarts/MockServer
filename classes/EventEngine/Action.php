<?php

namespace ConnectingOfThings\Classes\EventEngine;

use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

// Base Exception for Action Class
class ActionException extends Exception
{
}

/**
 * The Action class represents an action that can be executed when a rule's
 * conditions are met.  Actions can perform various operations, such as
 * updating data, sending notifications, or triggering other events.
 */
class Action
{
    /**
     * @var string The action string (PHP code to execute).
     */
    private $actionString;

    /**
     * @var LoggerInterface The logger instance.
     */
    private $logger;

    /**
     * Constructor for the Action class.
     *
     * @param string $actionString The action string (PHP code).
     * @param LoggerInterface|null $logger The logger instance.
     */
    public function __construct(string $actionString, LoggerInterface $logger = null)
    {
        $this->actionString = $actionString;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Executes the action.
     *
     * This method executes the action string (which is PHP code) in the context
     * of the provided data.
     *
     * @param array $data An array of data (event data, facts, etc.) that can be
     * used in the action. The keys of the array
     * become variables available in the action
     * execution scope.
     * @return mixed|null The result of the action execution, or null if there is an error.
     * @throws ActionException If there is an error executing the action.
     */
    public function execute(array $data)
    {
        $this->logger->debug("Executing action: {$this->actionString} with data: " . json_encode($data));

        // Extract variables from the data array.
        extract($data); // This is safe(r) because the action is defined by us, not user input, and any errors will be caught.

        try {
            // Execute the action string as PHP code.
            $result = eval($this->actionString . ";"); // Semicolon is added to the action string
            if ($result === false && error_get_last() !== null) {
                $error = error_get_last();
                $this->logger->error("Error executing action: {$this->actionString}. Error: " . $error['message']);
                throw new ActionException("Error executing action: {$this->actionString}. PHP Error: " . $error['message']);
            }

            $this->logger->debug("Action: {$this->actionString} executed successfully.");
            return $result;
        } catch (\Throwable $e) {
            $this->logger->error("Exception executing action: {$this->actionString}. Exception: " . $e->getMessage());
            throw new ActionException("Error executing action: {$this->actionString}. Exception: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Returns the action string.
     *
     * @return string The action string.
     */
    public function getActionString(): string
    {
        return $this->actionString;
    }

    /**
     * Sets the action string
     *
     * @param string $actionString
     * @return void
     */
    public function setActionString(string $actionString): void
    {
        $this->actionString = $actionString;
    }
}
