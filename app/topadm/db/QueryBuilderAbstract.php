<?php
namespace My\Topadm\Db;

use My\Topadm\Db\DnsBuilder;
use Flex\Banana\Classes\Db\WhereHelper;
use \Exception;
# purpose : 각종 SQL 관련 디비를 통일성있게  작성할 수 있도록 틀을 제공
abstract class QueryBuilderAbstract extends DnsBuilder
{
    public const __version = '1.5.3';
    private string $query_mode;
    protected array $query_params;
    private array $sub_query_params;
    private string $query_tpl = '';
    private array $tpl = [
        'union'   => '{table}{where}{groupby}{having}{orderby}{limit}',
        'default' => 'SELECT {columns}FROM {table}{on}{where}{groupby}{having}{orderby}{limit}'
    ];
    protected string $query = '';
    const _QUERY_INIT_PARAMS_ = ['columns'=>'*','table'=>'','where'=>'','orderby'=>'','on'=>'','limit'=>'','groupby'=>'','having'=>''];

    abstract public function table(...$tables) : mixed;
    abstract public function tableJoin(string $type,...$tables) : mixed;
    abstract public function tableSub(...$tables) : mixed;
    abstract public function select(...$columns) : mixed;
    abstract public function selectGroupBy(...$columns) : mixed;
    // abstract public function selectCrypt(...$columns) : mixed;
    abstract public function where(...$where) : mixed;
    abstract public function orderBy(...$orderby) : mixed;
    abstract public function on(...$on) : mixed;
    abstract public function limit(...$limit) : mixed;
    abstract public function distinct(string $column_name) : mixed;
    abstract public function groupBy(...$columns) : mixed;
    abstract public function having(...$columns) : mixed;
    abstract public function total(string $column_name) : int;

    public function __construct(string $db_type){
        parent::__construct( $db_type);
    }

    public function init(string $type = 'main') : void
    {
        $this->query_mode = strtoupper($type);

        if($this->query_mode == 'JOIN'){
            $this->sub_query_params = [];
            $this->query_params = [];
            $this->query_params = self::_QUERY_INIT_PARAMS_;
        }
        else if($this->query_mode == 'SUB'){
            $this->query_tpl =  $this->tpl['default'];
            $this->sub_query_params = [];
            $this->sub_query_params = self::_QUERY_INIT_PARAMS_;
        }else {
            $this->sub_query_params = [];
            $this->query_params = [];
            $this->query_params = self::_QUERY_INIT_PARAMS_;
            $this->query_tpl =  $this->tpl['default'];
        }
    }

    public function setQueryTpl (string $tpl_mode){
        $upcase = strtoupper($tpl_mode);
        if($upcase == 'UNINON') $this->query_tpl = $this->tpl['union'];
        else $this->query_tpl = $this->tpl['default'];
    }

    public function set(string $style, string $value) : void {
        if($this->query_mode == 'SUB') $this->sub_query_params[$style] = $value;
        else $this->query_params[$style] = $value;
    }

    protected function quoteIdentifier($identifier): string
    {
        return match($this->db_type) {
            'pgsql' => '"' . str_replace('"', '""', $identifier) . '"',
            'mysql' => '`' . str_replace('`', '``', $identifier) . '`',
            default => throw new Exception("Unsupported database type for quoting: {$this->db_type}"),
        };
    }

    public function get() : string
    {
        preg_match_all("/({+)(.*?)(})/", $this->query_tpl, $matches);
        $patterns = $matches[0];
        $columns  = $matches[2];

        $render_args  = [];
        $query_params = [];
        $query_params = ($this->query_mode == 'SUB') ? $this->sub_query_params : $this->query_params;

        # binding
        foreach($patterns as $idx=>$text){
            $column_name = $columns[$idx];
            $render_args[$text] = (trim($query_params[$column_name])) ? $query_params[$column_name].' ':'';
        }
        $this->query = trim(strtr($this->query_tpl, $render_args));

        # reset
        if($this->query_mode == 'SUB' || $this->query_mode == 'JOIN') {
            $this->query_mode = 'MAIN';
        }
    return $this->query;
    }

    public function buildWhere(...$w) : string
    {
        $result = '';
        $length = (isset($w[0])) ? count($w[0]) : 0;
		if($length > 0)
		{
            $wa = $w[0];
			if(isset($wa[0]) && $wa[0])
			{
				$result = $wa[0];
				if($length > 1)
				{
                    $whereHelper = new WhereHelper();

                    # 배열
                    if(is_array($wa[0]))
                    {
                        $whereHelper->begin('AND');
                        foreach($wa as $idx => $argv)
                        {
                            $argv_length = count($argv);
                            if($argv_length ==2){
                                $whereHelper->case($argv[0], '=', $argv[1]);
                            }else if($argv_length ==3){
                                $whereHelper->case($argv[0], $argv[1], $argv[2]);
                            }
                        }
                        $whereHelper->end();
                    }else{ # string
                        if($length ==2){
                            $whereHelper->begin('AND')->case($wa[0], '=', $wa[1])->end();
                        }else if($length ==3){
                            $whereHelper->begin('AND')->case($wa[0], $wa[1], $wa[2])->end();
                        }
                    }
					$result = $whereHelper->__get('where');
				}
			}
		}
    return $result;
    }
}
?>