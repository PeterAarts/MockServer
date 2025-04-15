<?php

namespace ConnectingOfThings\Classes\EventEngine;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Exception;

// Base Exception for Rule Class
class RuleException extends Exception
{
}

/**
 * The Rule class represents a rule in the event engine.  A rule consists of
 * a condition and an action.  When the condition is met, the action is executed.
 */
class Rule
{
    /**
     * @var string The name of the rule.
     */
    private $name;

    /**
     * @var Condition The condition for the rule.
     */
    private $condition;

    /**
     * @var Action The action to execute when the condition is met.
     */
    private $action;

    /**
     * @var int The priority of the rule.  Higher priority rules are evaluated first.
     */
    private $priority = 0;

    /**
     * @var bool Indicates whether the rule is enabled.
     */
    private $enabled = true;

    /**
     * @var LoggerInterface The logger instance.
     */
    private $logger;

    /**
     * Constructor for the Rule class.
     *
     * @param string $name The name of the rule.
     * @param Condition $condition The condition for the rule.
     * @param Action $action The action to execute.
     * @param int $priority The priority of the rule (optional, default is 0).
     * @param bool $enabled Whether the rule is enabled (optional, default is true).
     * @param LoggerInterface|null $logger The logger instance.
     */
    public function __construct(
        string $name,
        Condition $condition,
        Action $action,
        int $priority = 0,
        bool $enabled = true,
        LoggerInterface $logger = null
    ) {
        $this->name = $name;
        $this->condition = $condition;
        $this->action = $action;
        $this->priority = $priority;
        $this->enabled = $enabled;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Evaluates the rule's condition and executes the action if the condition is met.
     *
     * @param array $data The data to use when evaluating the condition and executing
     * the action.
     * @return mixed|null The result of the action execution, or null if the condition
     * is not met or an error occurs.  Returns false if the rule
     * is disabled.
     * @throws RuleException If there is an error during condition evaluation or
     * action execution.
     */
    public function evaluateAndExecute(array $data)
    {
        if (!$this->enabled) {
            $this->logger->debug("Rule '{$this->name}' is disabled. Skipping evaluation.");
            return false; // Return false to indicate the rule was skipped.
        }

        $this->logger->debug("Evaluating rule: {$this->name}");

        try {
            if ($this->condition->evaluate($data)) {
                $this->logger->info("Condition for rule '{$this->name}' is met. Executing action.");
                return $this->action->execute($data); // Return the result of the action.
            } else {
                $this->logger->debug("Condition for rule '{$this->name}' is not met.");
                return null; // Return null to indicate condition not met.
            }
        } catch (ConditionException $e) {
            throw new RuleException("Error evaluating condition for rule '{$this->name}': " . $e->getMessage(), 0, $e);
        } catch (ActionException $e) {
            throw new RuleException("Error executing action for rule '{$this->name}': " . $e->getMessage(), 0, $e);
        } catch (\Throwable $e) {
            throw new RuleException("Unexpected error in rule '{$this->name}': " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Returns the name of the rule.
     *
     * @return string The name of the rule.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the name of the rule.
     *
     * @param string $name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Returns the condition for the rule.
     *
     * @return Condition The condition for the rule.
     */
    public function getCondition(): Condition
    {
        return $this->condition;
    }

    /**
     * Sets the condition for the rule.
     *
     * @param Condition $condition
     * @return void
     */
    public function setCondition(Condition $condition): void
    {
        $this->condition = $condition;
    }

    /**
     * Returns the action for the rule.
     *
     * @return Action The action for the rule.
     */
    public function getAction(): Action
    {
        return $this->action;
    }

    /**
     * Sets the action for the rule.
     *
     * @param Action $action
     * @return void
     */
    public function setAction(Action $action): void
    {
        $this->action = $action;
    }

    /**
     * Returns the priority of the rule.
     *
     * @return int The priority of the rule.
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Sets the priority of the rule.
     *
     * @param int $priority
     * @return void
     */
    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    /**
     * Returns whether the rule is enabled.
     *
     * @return bool Whether the rule is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Sets whether the rule is enabled.
     *
     * @param bool $enabled
     * @return void
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }
}
