<?php

namespace ConnectingOfThings\Classes\Database;

use PDO;
use PDOException;
use Exception;
use Dotenv\Dotenv; 

class DB
{
    private static $_instance = null;
    private ?PDO $_pdo = null;
    private $_query;
    private $_error = false;
    private $_results;
    private $_resultsArray;
    private $_count = 0;
    private $_lastId;
    private $_queryCount = 0;
    private $_errorInfo;

    private function __construct()
    {
        // Load environment variables using dotenv
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../'); // Adjust the path if needed.  The path should point to the location of the .env file
        $dotenv->load();

        $opts = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION sql_mode = ''");
        try {
            $dbCharset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
            $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
            $dbName = $_ENV['DB_NAME'] ?? '';
            $dbUser = $_ENV['DB_USER'] ?? 'root';
            $dbPass = $_ENV['DB_PASSWORD'] ?? '';
            $dbPort = $_ENV['DB_PORT'] ?? '3306'; //added port

            $this->_pdo = new PDO(
                "mysql:host=$dbHost;dbname=$dbName;port=$dbPort;charset=$dbCharset",
                $dbUser,
                $dbPass,
                $opts
            );
            $this->_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->_pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new DB();
        }
        return self::$_instance;
    }
    
    public function getConnection(): ?PDO
    {
        return $this->_pdo;
    }
    
    public function query($sql, $params = [])
    {
        $this->_queryCount++;
        $this->_error = false;
        $this->_errorInfo = null;
        $this->_query = $this->_pdo->prepare($sql);

        if (count($params)) {
            $x = 1;
            foreach ($params as $param) {
                $this->_query->bindValue($x, $param);
                $x++;
            }
        }

        if ($this->_query->execute()) {
            $this->_results = $this->_query->fetchAll(PDO::FETCH_OBJ);
            $this->_resultsArray = json_decode(json_encode($this->_results), true);
            $this->_count = $this->_query->rowCount();
            $this->_lastId = $this->_pdo->lastInsertId();
        } else {
            $this->_error = true;
            $this->_errorInfo = $this->_query->errorInfo();
            throw new Exception("Query failed: " . $this->_query->errorInfo()[2]);
        }
        return $this;
    }

    public function findAll($table)
    {
        return $this->action('SELECT *', $table);
    }

    public function findById($id, $table)
    {
        return $this->action('SELECT *', $table, array('id', '=', $id));
    }

    public function action($action, $table, $where = [])
    {
        $sql = "{$action} FROM {$table}";
        $values = [];
        $is_ok = true;

        if ($where_text = $this->_calcWhere($where, $values, "and", $is_ok)) {
            $sql .= " WHERE $where_text";
        }

        if ($is_ok) {
            if (!$this->query($sql, $values)->error()) {
                return $this;
            }
        }
        return false;
    }

    private function _calcWhere($w, &$vals, $comboparg = 'and', &$is_ok = null)
    {
        if (is_array($w)) {
            $comb_ops = ['and', 'or', 'and not', 'or not'];
            $valid_ops = ['=', '<', '>', '<=', '>=', '<>', '!=', 'LIKE', 'NOT LIKE', 'ALIKE', 'NOT ALIKE', 'REGEXP', 'NOT REGEXP'];
            $two_args = ['IS NULL', 'IS NOT NULL'];
            $four_args = ['BETWEEN', 'NOT BETWEEN'];
            $arr_arg = ['IN', 'NOT IN'];
            $nested_arg = ['ANY', 'ALL', 'SOME'];
            $nested = ['EXISTS', 'NOT EXISTS'];
            $nestedIN = ['IN SELECT', 'NOT IN SELECT'];
            $wcount = count($w);

            if ($wcount == 0) {
                return "";
            }

            if (array_values($w) === $w) {
                if (in_array(strtolower($w[0]), $comb_ops)) {
                    $sql = '';
                    $combop = '';
                    for ($i = 1; $i < $wcount; $i++) {
                        $sql .= ' ' . $combop . ' ' . $this->_calcWhere($w[$i], $vals, "and", $is_ok);
                        $combop = $w[0];
                    }
                    return '(' . $sql . ')';
                } elseif ($wcount == 3 && in_array($w[1], $valid_ops)) {
                    $vals[] = $w[2];
                    return "{$w[0]} {$w[1]} ?";
                } elseif ($wcount == 2 && in_array($w[1], $two_args)) {
                    return "{$w[0]} {$w[1]}";
                } elseif ($wcount == 4 && in_array($w[1], $four_args)) {
                    $vals[] = $w[2];
                    $vals[] = $w[3];
                    return "{$w[0]} {$w[1]} ? AND ?";
                } elseif ($wcount == 3 && in_array($w[1], $arr_arg) && is_array($w[2])) {
                    $vals = array_merge($vals, $w[2]);
                    return "{$w[0]} {$w[1]} (" . substr(str_repeat(",?", count($w[2])), 1) . ")";
                } elseif (($wcount == 5 || $wcount == 6 && is_array($w[5])) && in_array($w[1], $valid_ops) && in_array($w[2], $nested_arg)) {
                    return "{$w[0]} {$w[1]} {$w[2]}" . $this->get_subquery_sql($w[4], $w[3], $w[5], $vals, $is_ok);
                } elseif (($wcount == 3 || $wcount == 4 && is_array($w[3])) && in_array($w[0], $nested)) {
                    return $w[0] . $this->get_subquery_sql($w[2], $w[1], $w[3], $vals, $is_ok);
                } elseif (($wcount == 4 || $wcount == 5 && is_array($w[4])) && in_array($w[1], $nestedIN)) {
                    return "{$w[0]} " . substr($w[1], 0, -7) . $this->get_subquery_sql($w[3], $w[2], $w[4], $vals, $is_ok);
                } else {
                    $is_ok = false;
                    throw new Exception("Invalid where clause: " . print_r($w, true));
                }
            } else {
                $sql = '';
                $combop = '';
                foreach ($w as $k => $v) {
                    if (in_array(strtolower($k), $comb_ops)) {
                        $sql .= $combop . ' (' . $this->_calcWhere($v, $vals, $k, $is_ok) . ') ';
                        $combop = $comboparg;
                    } else {
                        $vals[] = $v;
                        if (str_ends_with($k, '=') || str_ends_with($k, '<') || str_ends_with($k, '>')) {
                            $sql .= $combop . ' ' . $k . ' ? ';
                        } else {
                            $sql .= $combop . ' ' . $k . ' = ? ';
                        }
                        $combop = $comboparg;
                    }
                }
                return ' (' . $sql . ') ';
            }
        } else {
            $is_ok = false;
            throw new Exception("Where clause is not an array: " . $w);
        }
    }

    public function get($table, $where)
    {
        return $this->action('SELECT *', $table, $where);
    }

    public function delete($table, $where)
    {
        return $this->action('DELETE', $table, $where);
    }

    public function deleteById($table, $id)
    {
        return $this->action('DELETE', $table, array('id', '=', $id));
    }

    public function insert($table, $fields = [], $update = false)
    {
        $keys = array_keys($fields);
        $values = [];
        $records = 0;

        foreach ($fields as $field) {
            $count = is_array($field) ? count($field) : 1;
            if (!isset($first_time) || $count < $records) {
                $first_time = true;
                $records = $count;
            }
        }

        for ($i = 0; $i < $records; $i++) {
            foreach ($fields as $field) {
                $values[] = is_array($field) ? $field[$i] : $field;
            }
        }

        $col = ",(" . substr(str_repeat(",?", count($fields)), 1) . ")";
        $sql = "INSERT INTO {$table} (`" . implode('`,`', $keys) . "`) VALUES " . substr(str_repeat($col, $records), 1);

        if ($update) {
            $sql .= " ON DUPLICATE KEY UPDATE";
            foreach ($keys as $key) {
                if ($key != "id") {
                    $sql .= " `$key` = VALUES(`$key`),";
                }
            }
            if (!empty($keys)) {
                $sql = substr($sql, 0, -1);
            }
        }
        return !$this->query($sql, $values)->error();
    }

    public function update($table, $id, $fields)
    {
        $sql = "UPDATE {$table} SET " . (empty($fields) ? "" : "`") . implode("` = ?, `", array_keys($fields)) . (empty($fields) ? "" : "` = ? ");
        $is_ok = true;
        $values = array_values($fields);

        if (!is_array($id)) {
            $sql .= " WHERE id = ?";
            $values[] = $id;
        } else {
            if (empty($id)) {
                return false;
            }
            if ($where_text = $this->_calcWhere($id, $values, "and", $is_ok)) {
                $sql .= " WHERE $where_text";
            }
        }

        if ($is_ok) {
            if (!$this->query($sql, $values)->error()) {
                return true;
            }
        }
        return false;
    }

    public function results($assoc = false)
    {
        if ($assoc) {
            return ($this->_resultsArray) ? $this->_resultsArray : [];
        }
        return ($this->_results) ? $this->_results : [];
    }

    public function first($assoc = false)
    {
        return ($this->count() > 0) ? $this->results($assoc)[0] : [];
    }

    public function count()
    {
        return $this->_count;
    }

    public function error()
    {
        return $this->_error;
    }

    public function errorInfo()
    {
        return $this->_errorInfo;
    }

    public function errorString()
    {
        return 'ERROR #' . $this->_errorInfo[0] . ': ' . $this->_errorInfo[2];
    }

    public function lastId()
    {
        return $this->_lastId;
    }

    public function getQueryCount()
    {
        return $this->_queryCount;
    }

    private function get_subquery_sql($action, $table, $where, &$values, &$is_ok)
    {
        if (is_array($where)) {
            if ($where_text = $this->_calcWhere($where, $values, "and", $is_ok)) {
                $where_text = " WHERE $where_text";
            }
        }
        return " (SELECT $action FROM $table$where_text)";
    }

    public function cell($tablecolumn, $id = [])
    {
        $input = explode(".", $tablecolumn, 2);
        if (count($input) != 2) {
            return null;
        }
        $result = $this->action("SELECT {$input[1]}", $input[0], (is_numeric($id) ? ["id", "=", $id] : $id));
        return ($result && $this->_count > 0) ? $this->_resultsArray[0][$input[1]] : null;
    }

    public function getColCount()
    {
        return $this->_query->columnCount();
    }

    public function getColMeta($counter)
    {
        return $this->_query->getColumnMeta($counter);
    }
}

