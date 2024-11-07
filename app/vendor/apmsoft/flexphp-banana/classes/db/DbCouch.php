<?php
namespace Flex\Banana\Classes\Db;

use Flex\Banana\Classes\Log;
use Flex\Banana\Classes\Json\JsonEncoder;
use Flex\Banana\Classes\Db\DbResultCouch;
use Flex\Banana\Classes\Db\DbInterface;
use Flex\Banana\Classes\Http\HttpRequest;
use Flex\Banana\Classes\Db\WhereHelper;
use \Exception;
use \ArrayAccess;

class DbCouch extends QueryBuilderAbstractCouch implements DbInterface, ArrayAccess
{
    public const __version = '0.1.2';
    private const BASE_URL = "http://{host}:{port}";

    public string $baseUrl;
    private string $authHeader;
    private string $database;
    private array $params = [];

    public function __construct(
        public WhereHelper $whereHelper
    )
    {
        $this->init();
        $this->baseUrl = '';
        $this->authHeader = '';
        $this->database = '';
    }

    public function connect(string $host, string $dbname, string $user, string $password, int $port, string $charset, ?array $options = []): self
    {
        $this->baseUrl = $this->bindingDNS(self::BASE_URL, [
            "host" => $host,
            "port" => $port
        ]);

        $this->authHeader = "Authorization: Basic " . base64_encode("$user:$password");

        $httpRequest = new HttpRequest();
        $httpRequest->set($this->baseUrl, "", [$this->authHeader,"Content-Type: application/json"]);
        $httpRequest->get(function($response) {
            if (empty($response) || isset($response[0]['error'])) {
                throw new Exception("Failed to connect to CouchDB server");
            }
        });
    return $this;
    }

    public function selectDB(string $dbname): self
    {
        $httpRequest = new HttpRequest();
        $dbUrl = $this->baseUrl . "/$dbname";
        $httpRequest->set($dbUrl, "", [$this->authHeader,"Content-Type: application/json"]);
        $httpRequest->get(function($response) use ($dbname) {
            if (empty($response) || isset($response[0]['error'])) {
                throw new Exception("Failed to connect to database '$dbname'");
            }
        });
        $this->database = $dbname;
        return $this;
    }

    public function createDatabase(string $dbname): void
    {
        $httpRequest = new HttpRequest();
        $dbUrl = $this->baseUrl . "/$dbname";
        $httpRequest->set($dbUrl, "", [$this->authHeader,"Content-Type: application/json"]);
        $httpRequest->put(function($response) use ($dbname) {
            if (empty($response) || isset($response[0]['error'])) {
                throw new Exception("Failed to create database '$dbname'");
            }
        });
    }

    public function total(string $column_name = '_id'): int
    {
        $query = $this->get();
        $query['fields'] = [$column_name];
        $query['limit'] = 1;

        Log::d('total', $query);

        $result = $this->query(JsonEncoder::toJson($query));
        Log::d('total result',$result);
        $totalCount = $result->total_rows ?? count($result->docs ?? []);

        return (int)$totalCount;
    }

    public function table(...$tables): self
    {
        $this->selectDB($tables[0]);
        return $this;
    }

    public function select(...$columns) : self{
		if(count($columns) == 1){
            if(strpos($columns,",") !==false) {
                $columns = explode(",", $columns);
            }
        }
		$this->set('fields', $columns);
	return $this;
	}

    public function where(...$where): self
    {
        // Implement where method
        $result = $this->buildWhere(...$where);
		if($result){
			$this->set('selector', $result);
		}
    return $this;
    }

    public function orderBy(...$orderby): self
    {
        // Implement orderBy method
        $sort = [];
        foreach ($orderby as $field) {
            $direction = 'asc';
            $field = strtolower($field);
            if (strpos($field, ' desc') !== false) {
                $field = str_replace(' desc', '', $field);
                $direction = 'desc';
            }
            $sort[] = [$field => $direction];
        }
        $this->set('sort', $sort);
        return $this;
    }

    public function limit(...$limit): self
    {
        // Implement limit method
        if (isset($limit[1])) {
            $this->set('skip', $limit[0]);
            $this->set('limit', $limit[1]);
        } else {
            $this->set('limit', $limit[0]);
        }
        return $this;
    }

    public function useIndex(...$index): self
    {
        // Implement useIndex method
        $this->set('use_index', $index);
        return $this;
    }

    public function query(string $query = '', array $params = []): DbResultCouch
    {
        if (!$query) {
            $query = JsonEncoder::toJson($this->get());
        }
        Log::d('query',$query);
        $httpRequest = new HttpRequest();
        $url = $this->baseUrl . "/{$this->database}/_find";
        $httpRequest->set($url, $query, [$this->authHeader,'Content-Type: application/json']);

        $result = null;
        $httpRequest->post(function($response) use (&$result) {
            if (empty($response) || isset($response[0]['error'])) {
                throw new Exception("Query failed: " . ($response[0]['error'] ?? 'Unknown error'));
            }
            $result = new DbResultCouch($response[0]);
        });

        return $result;
    }

    public function insert(): void
    {
        if (empty($this->params)) {
            throw new Exception("Empty params");
        }

        $httpRequest = new HttpRequest();
        $url = $this->baseUrl . "/$this->database";
        $httpRequest->set($url, JsonEncoder::toJson($this->params), [$this->authHeader, "Content-Type: application/json"]);
        $httpRequest->post(function($response) {
            if (empty($response) || isset($response[0]['error'])) {
                throw new Exception("Insert failed: " . ($response[0]['error'] ?? 'Unknown error'));
            }
        });
    }

    public function update(): void
    {
        if (empty($this->params) || empty($this->selector)) {
            throw new Exception("Empty parameters or selector is missing");
        }
        Log::d('update params', $this->params);

        // Use the query function to find documents
        $queryResult = $this->query(JsonEncoder::toJson(['selector' => $this->selector]));

        // Check if any documents were found
        if (empty($queryResult->docs)) {
            throw new Exception("No documents found for the given selector");
        }

        foreach ($queryResult->docs as $doc) 
        {
            // Merge the existing document with the new parameters
            $updateDoc = array_merge($doc, $this->params);
            
            // Ensure the _rev is included in the update document
            if (isset($doc['_rev'])) {
                $updateDoc['_rev'] = $doc['_rev'];
            } else {
                throw new Exception("Document revision (_rev) is missing");
            }

            // Prepare the update request
            $updateUrl = $this->baseUrl . "/$this->database/" . $doc['_id'];
            $httpRequestForUpdate = new HttpRequest();
            $httpRequestForUpdate->set($updateUrl, JsonEncoder::toJson($updateDoc), [$this->authHeader, "Content-Type: application/json"]);
            
            // Update document
            $httpRequestForUpdate->put(function($updateResponse) {
                if (empty($updateResponse) || isset($updateResponse[0]['error'])) {
                    throw new Exception("Update failed: " . ($updateResponse[0]['error'] ?? 'Unknown error'));
                }
            });
        }

        // Clear params after processing
        $this->params = [];
    }

    public function delete(): void
    {
        if (empty($this->selector)) {
            throw new Exception("Selector is missing");
        }

        $httpRequest = new HttpRequest();
        $url = $this->baseUrl . "/$this->database/_find";
        $findQuery = JsonEncoder::toJson(['selector' => $this->selector]);
        $httpRequest->set($url, $findQuery, [$this->authHeader,"Content-Type: application/json; charset=utf-8"]);

        $httpRequest->post(function($response) 
        {
            $docs = json_decode($response[0], true)['docs'] ?? [];
            foreach ($docs as $doc) 
            {
                $httpRequest = new HttpRequest();
                $deleteUrl = $this->baseUrl . "/$this->database/" . $doc['_id'] . "?rev=" . $doc['_rev'];
                $httpRequest->set($deleteUrl, "",[$this->authHeader]);
                $httpRequest->delete(function($deleteResponse) {
                    if (empty($deleteResponse) || isset($deleteResponse[0]['error'])) {
                        throw new Exception("Delete failed: " . ($deleteResponse[0]['error'] ?? 'Unknown error'));
                    }
                });
            }
        });

        $this->selector = new \stdClass();
    }

    public function beginTransaction(): bool
    {
        $this->params = [];
        return true;
    }

    public function commit(): bool
    {
        $this->params = [];
        return true;
    }

    public function rollBack(): bool
    {
        $this->params = [];
        return true;
    }

    public function offsetSet($offset, $value): void
    {
        // 증가 연산을 감지하여 처리
        if (is_string($value) && preg_match("/^" . preg_quote($offset, '/') . "(\+|\-)[0-9]+$/", $value)) {
            // '+1' 또는 '-1' 형식의 값 처리
            $parts = explode(substr($value, strlen($offset), 1), $value);
            $operator = substr($value, strlen($offset), 1);
            $operand = intval($parts[1]);

            // 현재 값을 가져와서 증가/감소 연산 수행
            if (isset($this->params[$offset])) {
                $currentValue = $this->params[$offset];
                if (is_numeric($currentValue)) {
                    $newValue = match ($operator) {
                        '+' => $currentValue + $operand,
                        '-' => $currentValue - $operand,
                        default => $currentValue,
                    };
                    $this->params[$offset] = $newValue;
                } else {
                    // 초기값 설정 (예: 0)
                    $this->params[$offset] = ($operator === '+') ? $operand : -$operand;
                }
            } else {
                // 초기값 설정 (예: 0)
                $this->params[$offset] = ($operator === '+') ? $operand : -$operand;
            }
        } else {
            // 일반적인 값 설정
            $this->params[$offset] = $value;
        }
    }

	# @ ArrayAccess
	# 사용법 : isset($obj["two"]); -> bool(true)
	public function offsetExists($offset) : bool{
		return isset($this->params[$offset]);
	}

	# @ ArrayAccess
	# 사용법 : unset($obj["two"]); -> bool(false)
	public function offsetUnset($offset) : void{
		unset($this->params[$offset]);
	}

	# @ ArrayAccess
	# 사용법 : $obj["two"]; -> string(7) "A value"
	public function offsetGet($offset) : mixed{
		return isset($this->params[$offset]) ? $this->params[$offset] : null;
	}

    public function __call($method, $args)
    {
        // Implement if needed
    }

    public function __get(string $propertyName)
    {
        if (property_exists(__CLASS__, $propertyName)) {
            if ($propertyName == 'query') {
                return JsonEncoder::toJson($this->get());
            } else {
                return $this->{$propertyName};
            }
        }
    }
}