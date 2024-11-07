<?php
namespace Flex\Banana\Classes\Db;
use Flex\Banana\Classes\Json\JsonEncoder;

abstract class QueryBuilderAbstractCouch
{
    public const __version = '1.0.0';
    protected array $query_params;
    protected WhereCouch $whereCouch;

    protected const _QUERY_INIT_PARAMS_ = [
        'selector' => [],
        'fields' => [],
        'sort' => [],
        'limit' => null,
        'skip' => null,
        'use_index' => null
    ];

    public function __construct()
    {
        $this->init();
        $this->whereCouch = new WhereCouch();
    }

    abstract public function table(...$tables) : mixed;
    abstract public function select(...$columns) : mixed;
    abstract public function where(...$where) : mixed;
    abstract public function orderBy(...$orderby) : mixed;
    // abstract public function on(...$on) : mixed;
    abstract public function limit(...$limit) : mixed;
    abstract public function total(string $column_name) : int;
    abstract public function useIndex(...$index): self;

    public function init(): void
    {
        $this->query_params = self::_QUERY_INIT_PARAMS_;
        $this->whereBuilder = new WhereCouch();
    }

    public function set(string $key, $value): void
    {
        $this->query_params[$key] = $value;
    }

    public function get(): array
    {
        $query = [];
        foreach ($this->query_params as $key => $value) {
            if ($value !== null && count($value)) {
                $query[$key] = $value;
            }
        }
        return $query;
    }

    public function bindingDNS (string $tpl, array $dsn_options) : string 
    {
        preg_match_all("/({+)(.*?)(})/", $tpl, $matches);
        $patterns = $matches[0];
        $columns  = $matches[2];

        # binding
        foreach($patterns as $idx => $text){
            $column_name = $columns[$idx];
            $render_args[$text] = (trim($dsn_options[$column_name])) ? $dsn_options[$column_name] :'';
        }
        return trim(strtr($tpl, $render_args));
    }

    protected function buildWhere(...$conditions): array
    {
        $this->whereCouch->begin('and');
        foreach ($conditions as $condition) {
            if (is_array($condition) && count($condition) >= 2) {
                if (count($condition) == 2) {
                    $this->whereCouch->case($condition[0], '=', $condition[1]);
                } elseif (count($condition) == 3) {
                    $this->whereCouch->case($condition[0], $condition[1], $condition[2]);
                }
            }
        }
        return $this->whereCouch->where;
    }
}