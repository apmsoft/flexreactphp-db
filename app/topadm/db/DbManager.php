<?php
namespace My\Topadm\Db;

use Flex\Banana\Classes\Request\Validation;
use My\Topadm\Db\QueryBuilderAbstract;
use \PDO;
use \PDOException;
use \Exception;
use \ArrayAccess;

class DbManager extends QueryBuilderAbstract implements DbSqlInterface,ArrayAccess{
    protected $pdo;
    private $params = [];
    private array $pdo_options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];

    public function __construct(string $db_type){
        parent::__construct($db_type);
    }

    public function connect(string $host, string $dbname, string $user, string $password, int $port, string $charset, ?array $options=[]) : self
    {
        $dsn = $this->createDNS($host, $dbname, $port, $charset);
        try {
            $this->pdo = new PDO($dsn, $user, $password, $this->pdo_options+$options);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }

        return $this;
    }

    #@ interface : ArrayAccess
	# 사용법 : $obj["two"] = "A value";
	public function offsetSet($offset, $value) : void {
		$this->params[$offset] = $value;
	}

	#@ interface : ArrayAccess
	# 사용법 : isset($obj["two"]); -> bool(true)
	public function offsetExists($offset) : bool{
		return isset($this->params[$offset]);
	}

	#@ interface : ArrayAccess
	# 사용법 : unset($obj["two"]); -> bool(false)
	public function offsetUnset($offset) : void{
		unset($this->params[$offset]);
	}

	#@ interface : ArrayAccess
	# 사용법 : $obj["two"]; -> string(7) "A value"
	public function offsetGet($offset) : mixed{
		return isset($this->params[$offset]) ? $this->params[$offset] : null;
	}

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->pdo, $name], $arguments);
    }

    # @ abstract : QueryBuilderAbstract
	public function table(...$tables) : DbManager{
		parent::init('MAIN');
		$length = count($tables);
		$value = ($length ==2) ? implode(',',$tables) : implode(' ',$tables);
		parent::set('table', $value);
	return $this;
	}

	# @ abstract : QueryBuilderAbstract
	public function tableSub(...$tables) : DbManager{
		parent::init('SUB');
		$length = count($tables);
		$value = ($length ==2) ? implode(',',$tables) : implode(' ',$tables);
		parent::set('table', $value);
	return $this;
	}

	# @ abstract : QueryBuilderAbstract
	public function tableJoin(string $join, ...$tables) : DbManager{
		parent::init('JOIN');

		$upcase = strtoupper($join);
		$implode_join = sprintf(" %s JOIN ",$upcase);
		switch($upcase){
			case 'UNION': # 중복제거
			case 'UNION ALL': # 중복포함
				parent::setQueryTpl('UNINON');
				$implode_join = sprintf(" %s ",$upcase);
				break;
			default :
				parent::setQueryTpl('default');
		}

		$value = implode($implode_join, $tables);
		parent::set('table', $value);
	return $this;
	}

	# @ abstract : QueryBuilderAbstract
    public function select(...$columns) : DbManager{
		$value = implode(',', $columns);
		parent::set('columns', $value);
	return $this;
	}

	# @ abstract : QueryBuilderAbstract
    public function selectGroupBy(...$columns) : DbManager{
		$argv = [];
		#복수인지 체크
		if(count($columns)<2){
			$columns = explode(',', $columns[0]);
		}

		foreach($columns as $name){
			$argv[] = (strpos($name,'(') !==false) ? $name : sprintf("ANY_VALUE(%s) as %s",$name,$name);
		}
		$value = implode(',', $argv);
		parent::set('columns', $value);
	return $this;
	}

	# @ abstract : QueryBuilderAbstract
    public function where(...$where) : DbManager
	{
		$result = parent::buildWhere($where);
		if($result){
			$value = 'WHERE '.$result;
			parent::set('where', $value);
		}
	return $this;
	}

	# @ abstract : QueryBuilderAbstract
    public function orderBy(...$orderby) : DbManager
	{
		$value = 'ORDER BY '.implode(',',$orderby);
		parent::set('orderby', $value);
	return $this;
	}

	# @ abstract : QueryBuilderAbstract
    public function on(...$on) : DbManager
	{
		$result = parent::buildWhere($on);
		if($result){
			$value = 'ON '.$result;
			parent::set('on', $value);
		}
	return $this;
	}

	# @ abstract : QueryBuilderAbstract
    public function limit(...$limit) : DbManager{
		$value = 'LIMIT '.implode(',',$limit);
		parent::set('limit', $value);
	return $this;
	}

	# @ abstract : QueryBuilderAbstract
    public function distinct(string $column_name) : DbManager{
		$value = sprintf("DISTINCT %s", $column_name);
		parent::set('columns', $value);
	return $this;
	}

	# @ abstract : QueryBuilderAbstract
    public function groupBy(...$columns) : DbManager{
		$value = 'GROUP BY '.implode(',',$columns);
		parent::set('groupby', $value);
	return $this;
	}

	# @ abstract : QueryBuilderAbstract
    public function having(...$having) : DbManager{
		$result = parent::buildWhere($having);
		if($result){
			$value = 'HAVING '.$result;
			parent::set('having', $value);
		}
	return $this;
	}

	# @ abstract : QueryBuilderAbstract
    public function total(string $column_name = '*') : int {
		$total = 0;
		$value = sprintf("COUNT(%s) AS total_count", $column_name); // Alias the count for clarity
		parent::set('columns', $value); // Set the columns for the query
		$query = parent::get(); // Get the constructed query

		// Call the custom query method to execute the SQL
		$result = $this->query($query);

		if ($result) {
			$row = $result[0]; // Assuming the result is an array of rows
			$total = (int)$row['total_count']; // Cast to int for safety
		}

		return $total; // Return the total count
	}

	# @ interface : DBSwitch
	public function query(string $query = '') : mixed
	{
        if (!$query) {
            $query = $this->query = parent::get();
        }

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Query failed: " . $e->getMessage());
        }
    }

	# @ interface : DBSwitch
	# $db['name'] = 1, $db['age'] = 2;
	public function insert() : bool {
        if (empty($this->params)) {
            return false;
        }

        $fields = implode(',', array_map(fn($k) => "`$k`", array_keys($this->params)));
        $values = implode(',', array_map(fn($v) => "'".$this->real_escape_string($v)."'", $this->params));
        $this->params = []; // Reset params

        $query = sprintf("INSERT INTO `%s` (%s) VALUES (%s)", $this->query_params['table'], $fields, $values);
        return $this->query($query);
    }

	# @ interface
	public function update() : bool {
        if (empty($this->params) || empty($this->query_params['where'])) {
            return false;
        }

        $fieldValues = implode(',', array_map(function ($k, $v) {
            $escapedValue = preg_match("/($k)(\+|\-|\*|\/)(\d+)/", $v) ? $v : "'".$this->real_escape_string($v)."'";
            return "`$k`=$escapedValue";
        }, array_keys($this->params), $this->params));

        $query = sprintf("UPDATE `%s` SET %s %s", $this->query_params['table'], $fieldValues, $this->query_params['where']);
        $this->params = []; // Reset params
        return $this->query($query);
    }

	# @ interface : DBSwitch
	public function delete() : bool {
        if (empty($this->query_params['where'])) {
            return false;
        }

        $query = sprintf("DELETE FROM `%s` %s", $this->query_params['table'], $this->query_params['where']);
        return $this->query($query);
    }

}

// $db = (new DbManager("mysql"))
//     ->connect("localhost","test_db","test","test1234",3360, "utf-8");

//     $db->close("asdfdsafdsa")
?>