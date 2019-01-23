<?php
/**
 * Created by PhpStorm.
 * User: xiehuanjin
 * Date: 2019/1/13
 * Time: 14:37
 */

namespace Twinkle\Database;

class DB extends \PDO
{
    use InitTrait;

    const INSERT = "INSERT INTO";
    const UPDATE = "UPDATE";
    const REPLACE = "REPLACE";
    const DELETE = "DELETE";

    protected $retryLimit = 3;
    protected $retryWait = 1;

    /**
     * @var \PDO
     */
    public $pdo;

    public $dsn;
    public $username;
    public $password = '';
    public $options = [];

    public $exception_callback;

    public function __construct($config = [])
    {
        $this->setOptions([
            self::ATTR_STATEMENT_CLASS => [Statement::class, [$this]],
            self::ATTR_ERRMODE => self::ERRMODE_EXCEPTION,
        ]);
        $this->init($config);
    }

    public function connect()
    {
        // don't connect twice
        if ($this->pdo != null) {
            return;
        }

        $retries = 0;
        CONNECTION_RETRY: {
            try {
                $this->pdo = new \PDO(
                    $this->dsn,
                    $this->username,
                    $this->password,
                    $this->options
                );
            } catch (\Exception $e) {

                if ($retries == $this->retryLimit) {
                    if (null !== $this->exception_callback && is_callable($this->exception_callback)) {
                        call_user_func_array($this->exception_callback, [$e]);
                    } else {
                        throw $e;
                    }
                }

                usleep($this->retryWait * 1000);

                ++$retries;
                goto CONNECTION_RETRY;
            }
        }
    }

    public function insert($table, $data)
    {
        return $this->executeBySyntax(self::INSERT, $table, $data);
    }

    /**
     * Insert more row
     *
     * @throw PDOException
     *
     * @param string $table
     * @param array $data
     * @return Statement
     */
    public function batchInsert($table, $data)
    {
        foreach ($data as $key => $val) {
            $this->executeBySyntax(self::INSERT, $table, $val);
        }
    }

    /**
     * Update row in table, optionally use previous prepared stmt by stmt_key
     *
     * @throw PDOException
     *
     * @param string $table
     * @param array $data
     * @param mixed $where
     * @param mixed|array $whereParams
     * @return Statement
     */
    public function update($table, $data, $where, $whereParams = array())
    {
        return $this->executeBySyntax(self::UPDATE, $table, $data, $where, $whereParams);
    }

    /**
     * Save data to table
     *
     * @throw PDOException
     *
     * @param string $table
     * @param array $data
     * @param string $primaryKey Name of primary key column
     * @return Statement
     */
    public function save($table, $data, $primaryKey)
    {
        // Update if primary key exists in data set or insert new row
        if (!empty($data[$primaryKey])) {
            return $this->update($table, $data, $primaryKey . " = ?", $data[$primaryKey]);
        } else {
            return $this->insert($table, $data);
        }
    }

    /**
     * Insert or replace row in a table
     *
     * @throw PDOException
     *
     * @param string $table
     * @param array $data
     * @return Statement
     */
    public function replace($table, $data)
    {
        return $this->executeBySyntax(self::REPLACE, $table, $data);
    }

    /**
     * Delete rows from table
     *
     * @throw PDOException
     *
     * @param string $table
     * @param mixed $where
     * @param mixed $where_params
     * @return Statement
     */
    public function delete($table, $where, $where_params)
    {
        $sql = "DELETE FROM " . $table . $this->buildWhere($where);
        $stmt = $this->execQueryString($sql, $where_params);

        return $stmt;
    }

    /**
     * Count rows in one table - very simple implementation
     *
     * @param string $table
     * @param mixed $where
     * @param array $whereParams
     * @return int
     */
    public function count($table, $where = null, $whereParams = null)
    {
        $sql = "SELECT COUNT(1) FROM " . $table . $this->buildWhere($where);
        $stmt = $this->execQueryString($sql, $whereParams);

        return $stmt->fetchColumn();
    }

    /**
     * Get all columns from table
     *
     * @throw PDOException
     *
     * @param $table
     * @return array
     */
    public function getColumnsFromTable($table)
    {
        $sql = "DESCRIBE $table";

        return $this->execQueryString($sql)
            ->fetchAll(self::FETCH_COLUMN);
    }

    /**
     * @param Query $query
     * @return Statement
     */
    public function execQuery(Query $query)
    {
        return $this->execQueryString($query->getQuery(), $query->getParams());
    }

    /**
     * Prepare & execute query with params
     *
     * @param string $sql
     * @param null | array $params
     * @return \PDOStatement
     */
    public function execQueryString($sql, $params = null)
    {
        if (!is_array($params) && !is_null($params)) {
            $params = array($params);
        }

        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Build where statement for SQL query
     *
     * @param mixed $where
     * @param string $operand AND | OR
     * @return string
     */
    public function buildWhere($where, $operand = "AND")
    {
        if (empty($where)) {
            return "";
        }

        if (is_array($where)) {
            $wheres = array();
            foreach ($where as $k => $w) {
                $wheres[] = "(" . $w . ")";
            }
            $where = implode(" $operand ", $wheres);
        }

        return " WHERE " . $where;
    }

    /**
     *
     * Begins a transaction and turns off autocommit mode.
     *
     * @return bool True on success, false on failure.
     *
     * @see http://php.net/manual/en/pdo.begintransaction.php
     *
     */
    public function beginTransaction()
    {
        $this->connect();
        return $this->pdo->beginTransaction();
    }

    /**
     *
     * Commits the existing transaction and restores autocommit mode.
     *
     * @return bool True on success, false on failure.
     *
     * @see http://php.net/manual/en/pdo.commit.php
     *
     */
    public function commit()
    {
        $this->connect();
        return $this->pdo->commit();
    }

    /**
     *
     * Rolls back the current transaction, and restores autocommit mode.
     *
     * @return bool True on success, false on failure.
     *
     * @see http://php.net/manual/en/pdo.rollback.php
     *
     */
    public function rollBack()
    {
        $this->connect();
        return $this->pdo->rollBack();
    }

    /**
     * @param string $sql
     * @param array $driverOptions
     * @return \PDOStatement
     */
    public function prepare($sql, $driverOptions = [])
    {
        $this->connect();

        if (stripos($sql, static::UPDATE) !== false || stripos($sql, static::INSERT) !== false || stripos($sql, static::DELETE) !== false) {
            $FileStr = '';
            $sqlSource = '';
            $BackTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
            if ($BackTrace) {
                foreach ($BackTrace AS $val) {
                    if (isset($val['file'], $val['line'])) {
                        $FileStr .= $val['file'] . ':' . $val['line'];
                    }
                }
            }

            !empty($_SERVER['REDIRECT_URL']) && $sqlSource .= 'REDIRECT_URL: ' . $_SERVER['REDIRECT_URL'];
            !empty($_SERVER['HTTP_REFERER']) && $sqlSource .= ';HTTP_REFERER: ' . $_SERVER['HTTP_REFERER'];
            !empty($_SERVER['QUERY_STRING']) && $sqlSource .= ';QUERY_STRING:' . $_SERVER['QUERY_STRING'];
            $sqlSource = str_replace(['*/', '?'], [' ', '#'], $sqlSource . ';FILE: ' . $FileStr);
            $sql .= ' /* ' . $sqlSource . ' */';
        }

        return $this->pdo->prepare($sql, $driverOptions);
    }

    /**
     * Build and Get SET statement
     *
     * $this->getSetStmt(DB::INSERT, "mytable", array("name" => "John"));
     * will return:
     * INSERT INTO
     * @param string $syntax INSERT, UPDATE, REPLACE
     * @param string $table
     * @param array $data
     * @param null | string | array $where
     * @return \PDOStatement
     */
    protected function getSetStmt($syntax, $table, $data, $where = null)
    {
        $columns = array();

        foreach (array_keys($data) as $column) {
            $columns[] = "`" . $column . "` = ?";
        }
        $columns = implode(", ", $columns);

        $sql = "$syntax `$table` SET " . $columns . $this->buildWhere($where);

        return $this->prepare($sql);
    }

    /**
     * Perform INSERT, UPDATE, REPLACE
     *
     * @param string $syntax
     * @param string $table
     * @param array $data
     * @param null $where
     * @param array $where_params
     * @return Statement|\PDOStatement
     */
    protected function executeBySyntax($syntax, $table, $data, $where = null, $where_params = [])
    {
        if (!is_null($where) && !is_array($where)) {
            $where = array($where);
        }

        if (is_object($data)) {
            $data = (array)$data;
        }

        // support for scalar param
        if (!is_array($where_params)) {
            $where_params = array($where_params);
        }

        $stmt = $this->getSetStmt($syntax, $table, $data, $where);

        $stmt->execute(array_merge(array_values($data), $where_params));

        return $stmt;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options + $this->options;
    }

    protected function getExcludeInitProperty()
    {
        return ['pdo'];
    }

}