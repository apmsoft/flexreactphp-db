<?php
namespace My\Topadm\Db;

use Flex\Banana\Classes\Request\Validation;
use My\Topadm\Db\QueryBuilderAbstract;
use My\Topadm\Db\DbSqlResult;
use \PDO;
use \PDOException;
use \Exception;
use \ArrayAccess;

class DbManager extends QueryBuilderAbstract implements DbSqlInterface,ArrayAccess
{
	public const __version = '0.1.1';
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
		$dsn = $this->createDSN($host, $dbname, $port, $charset);
		echo $dsn.PHP_EOL;
		try {
			$this->pdo = new PDO($dsn, $user, $password, $this->pdo_options+$options);
		} catch (PDOException $e) {
			throw new Exception($e->getMessage());
		}

		// Verify database selection
		$query = $this->selectDB();
		$result = $this->pdo->query($query)->fetchColumn();
		if ($result !== $dbname) {
			throw new Exception("Connected to database '$result' instead of '$dbname'");
		}

		return $this;
	}

	protected function selectDB(): string
	{
		return match($this->db_type) {
			'mysql' => "SELECT DATABASE()",
			'pgsql' => "SELECT current_database()",
			default => throw new Exception("Unsupported database type: " . $this->db_type),
		};
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

    # @ abstract : QueryBuilderAbstract
	public function table(...$tables) : DbManager {
		parent::init('MAIN');
		$length = count($tables);
		$value = ($length == 2) ? $tables[0] . ',' . $tables[1] : $tables[0];
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
    # @ abstract : QueryBuilderAbstract
	public function limit(...$limit): DbManager {
		$value = match ($this->db_type) {
			'mysql' => 'LIMIT ' . implode(',', $limit),
			'pgsql' => match (count($limit)) {
				1 => 'LIMIT ' . $limit[0],
				2 => 'LIMIT ' . $limit[1] . ' OFFSET ' . $limit[0],
				default => throw new Exception("Invalid number of arguments for LIMIT clause"),
			},
			default => throw new Exception("Unsupported database type for LIMIT clause: {$this->db_type}"),
		};

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

	# @ interface : DBSwitch
	public function query(string $query = '', array $params = []): DbSqlResult
	{
		if (!$query) {
			$query = $this->query = parent::get();
		}

		try {
			$stmt = $this->pdo->prepare($query);
			$stmt->execute($params);
			return new DbSqlResult($stmt);
		} catch (PDOException $e) {
			throw new Exception("Query failed: " . $e->getMessage());
		}
	}

	# @ abstract : QueryBuilderAbstract
	public function total(string $column_name = '*') : int {
		$value = sprintf("COUNT(%s) AS total_count", $column_name);
		parent::set('columns', $value);
		$query = parent::get();

		$result = $this->query($query);
		$row = $result->fetch_assoc();
		return (int)($row['total_count'] ?? 0);
	}

	# @ interface : DBSwitch
	# $db['name'] = 1, $db['age'] = 2;
	public function insert() : bool {
		if (empty($this->params)) {
			return false;
		}

		$fields = implode(',', array_map([$this, 'quoteIdentifier'], array_keys($this->params)));
		$placeholders = implode(',', array_map(fn($k) => ":$k", array_keys($this->params)));

		$query = sprintf("INSERT INTO %s (%s) VALUES (%s)", 
			$this->query_params['table'],
			$fields, 
			$placeholders
		);

		echo $query.PHP_EOL;

		try {
			$result = $this->query($query, $this->params);
			$this->params = []; // Reset params
			return true; // PostgreSQL doesn't always return affected rows for INSERT
		} catch (Exception $e) {
			error_log("Insert failed: " . $e->getMessage());
			return false;
		}
	}

	# @ interface
	public function update() : bool {
		if (empty($this->params) || empty($this->query_params['where'])) {
			return false;
		}

		$setClause = implode(',', array_map(fn($k) => $this->quoteIdentifier($k) . " = :$k", array_keys($this->params)));
		$query = sprintf("UPDATE %s SET %s %s", 
			$this->query_params['table'], 
			$setClause, 
			$this->query_params['where']
		);

		$result = $this->query($query, $this->params);
		$this->params = []; // Reset params
		return $result->num_rows() > 0;
	}

	# @ interface : DBSwitch
	public function delete() : bool {
		if (empty($this->query_params['where'])) {
			return false;
		}

		$query = sprintf("DELETE FROM %s %s", 
			$this->query_params['table'], 
			$this->query_params['where']
		);
		$result = $this->query($query);
		return $result->num_rows() > 0;
	}


    public function __call($method, $args)
    {
		if(method_exists($this, $method)){
			if($method == 'aes_decrypt' || $method == 'aes_encrypt'){
				return call_user_func_array([$this, $method],$args);
			}
		}else{
			return call_user_func_array([$this->pdo, $method], $args);
		}
    }

	public function __get(string $propertyName) {
		if(property_exists(__CLASS__,$propertyName)){
			if($propertyName == 'query'){
				return parent::get();
			}else{
				return $this->{$propertyName};
			}
		}
	}

	# db close
	public function __destruct(){
		// parent::close();
	}
}