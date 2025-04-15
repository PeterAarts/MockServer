<?php
// eventEngine.php
require_once 'rule.php';

class EventEngine {
    private $rules = [];

    public function addRule(Rule $rule) {
        $this->rules[] = $rule;
    }

    public function getRules(): array {
        return $this->rules;
    }

    public function processEvent(array $event) {
        foreach ($this->rules as $rule) {
            // Basic rule matching (you'll need to implement more complex logic)
             if ($this->isRuleMatch($rule, $event)) {
                $this->executeActions($rule->actions, $event);
             }
        }
    }

    private function isRuleMatch(Rule $rule, array $event): bool {
        //check for trigger
        if ($rule->trigger->eventSource != $event['source'] || $rule->trigger->triggerCondition != $event['condition'])
        {
            return false;
        }
       // Check conditions.  This is simplified for demonstration.
        foreach ($rule->trigger->conditions as $condition) {
            if (!$this->checkCondition($condition, $event)) {
                return false;
            }
        }
        return true;
    }

    private function checkCondition(Condition $condition, array $event): bool {
        //  Simplified condition checking.  You'll need to adapt this
        //  to your specific condition logic (e.g., handling different operators).
        $factValue = $event[$condition->fact] ?? null; // Get value from event
        if ($factValue === null) {
            return false; // Fact not present in event
        }

        switch ($condition->operator) {
            case '=':  return $factValue == $condition->value;
            case '!=': return $factValue != $condition->value;
            case '>':  return $factValue > $condition->value;
            case '<':  return $factValue < $condition->value;
            case '>=': return $factValue >= $condition->value;
            case '<=': return $factValue <= $condition->value;
            case 'contains': return strpos($factValue, $condition->value) !== false;
            default:   return false; // Unsupported operator
        }
    }

    private function executeActions(array $actions, array $event) {
        foreach ($actions as $action) {
            //  This is where you'd handle different action types.
            //  For example, you might have a switch statement here
            //  to call different functions based on the action type.
            //  For now, we'll just print the action.
            echo "Executing action: {$action->type} with parameters: " . json_encode($action->parameters) . " for event: ". json_encode($event) . "<br>";
            // In a real application, you would do something more meaningful here,
            // like updating a database, sending a message, etc.
        }
    }

    public function loadRulesFromDatabase(PDO $pdo): void {
        $stmt = $pdo->query("SELECT * FROM event_rules");
        $rulesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rulesData as $ruleData) {
            $conditions = json_decode($ruleData['conditions'], true);
            $duration = json_decode($ruleData['duration'], true);
            $actions = json_decode($ruleData['actions'], true);

            $trigger = new Trigger(
                $ruleData['event_source'],
                $ruleData['trigger_condition'],
                array_map(function($conditionData) {
                    return new Condition(
                        $conditionData['fact'],
                        $conditionData['operator'],
                        $conditionData['value']
                    );
                }, $conditions)
            );

            $rule = new Rule(
                $ruleData['scenario'],
                $trigger,
                $duration['min'] ?? 0,  // Use 0 as default if null
                $duration['max'] ?? 0,  // Use 0 as default if null
                array_map(function($actionData) {
                    //  This is where you'd handle different action types.
                    //  For now, I'll assume a simple Action class.  You'll need
                    //  to expand this to handle queries, etc.
                    return new Action($actionData['type'], $actionData['parameters'] ?? []); //Use empty array if parameters not set
                }, $actions),
                $ruleData['created_by'], // Pass created_by
                $ruleData['updated_by']  // Pass updated_by
            );

            $this->addRule($rule);
        }
    }
}

// Example usage (assuming you have a database connection in $pdo):
$pdo = new PDO("mysql:host=localhost;dbname=your_database_name", "your_username", "your_password");
$eventEngine = new EventEngine();
$eventEngine->loadRulesFromDatabase($pdo);

// Example event
$event = [
    'source' => 'temperature_sensor',
    'condition' => 'temperature_above_threshold',
    'temperature' => 28,
    'threshold' => 25,
];

$eventEngine->processEvent($event);
print_r($eventEngine->getRules());
?>
