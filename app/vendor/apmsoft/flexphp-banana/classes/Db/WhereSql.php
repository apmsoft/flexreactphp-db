<?php
namespace Flex\Banana\Classes\Db;

use Flex\Banana\Classes\Db\WhereInterface;
use Flex\Banana\Classes\Log;
# 데이터베이스 QUERY구문에 사용되는 WHERE문 만드는데 도움을 주는 클래스
class WhereSql implements WhereInterface
{
	public const __version = '2.0';
	private string $where = '';
	private array $where_group = [];
	private string $current_group = '';
	private string $current_coord = '';
	private array $where_groups_data = [];
	private string $coord = 'AND'; # 전체 그룹을 마지막으로 묶을 coord

	# void
	# @fields : name+category+area 복수필드
	# @coord : [AND | OR]
	public function __construct(string $coord = 'AND')
	{
		$this->where = '';
		$this->coord = $coord;
		$this->init();
	}

	# void
	# 구문어를 만든다.
	# @where_str : name='홍길동'
	# @condition : [=,!=,<,>,<=,>=,IN,LIKE-R=dd%,LIKE-L=%dd,LIKE=%dd%]
	# @value : NULL | VALUE | % | Array
	public function case(string $field_name, string $condition ,mixed $value, bool $is_qutawrap=true, bool $join_detection=true) : WhereSql
	{
		$is_append = false;
		if($value == "0") $is_append = true;
		else if($value && $value !=''){
			$is_append = true;
		}

		# where 문을 그룹별로 묶기
		if($is_append)
		{
			$in_value = [];
			if (is_array($value)){ // array
				$in_value = $value;
			} else if (strpos($value, ",") !==false){
				$in_value = explode(',', $value);
			} else{
				$in_value[] = $value;
			}

			$_uppper_condition = strtoupper($condition);
			if($_uppper_condition == 'LIKE' || $_uppper_condition == 'LIKE-R' || $_uppper_condition == 'LIKE-L'){
				foreach($in_value as $n => $word)
				{
					// $_word = preg_replace("/[#\&\+\-%@=\/\\\:;,\.'\"\^`~\_|\!\?\*$#<>()\[\]\{\}]/i",' ',$word);
					$_word = preg_replace("/[#\&\+%@=\/\\;,'\"\^`~|\!\?\*$#<>()\[\]\{\}]/i",' ',$word);

					// append
					$this->where_group[$this->current_group][] = match($_uppper_condition) {
						'LIKE' => sprintf("%s LIKE '%%%s%%'", $field_name, $_word),
						'LIKE-R' => sprintf("%s LIKE '%s%%'", $field_name, $_word),
						'LIKE-L' => sprintf("%s LIKE '%%%s'", $field_name, $_word),
					};
				}
			}
			else if($_uppper_condition == 'IN' || $_uppper_condition == 'NOT IN'){
				if(strpos($in_value[0],'.') !==false){
					$in_value_str = implode ( ",", $in_value );
				}else{
					$in_value_str = ($is_qutawrap) ? "'" . implode ( "', '", $in_value ) . "'" : implode ( ",", $in_value );
				}

				// append
				$this->where_group[$this->current_group][] = sprintf("%s %s (%s)", $field_name, $_uppper_condition, $in_value_str);
			}
			else if($_uppper_condition == 'JSON_CONTAINS'){
				$in_value_str = json_encode($in_value, JSON_UNESCAPED_UNICODE);

				// append
				$this->where_group[$this->current_group][] = sprintf("JSON_CONTAINS(%s, '%s')", $field_name, $in_value_str);
			}
			else if($value == 'NULL'){
				// append
				$this->where_group[$this->current_group][] = sprintf("%s %s %s", $field_name, $condition, $value);
			}
			else{
				// set "a.name 형태인지 체크"
				$__value__ = ($is_qutawrap) ? sprintf("'%s'",$in_value[0]) : $in_value[0];
				$d_value = sprintf("%s %s %s", $field_name, $condition, $__value__);
				if($join_detection)
				{
					$pattern = "/^([a-zA-Z0-9]|_)+(\.)([a-zA-Z0-9]|_)/i";
					if(preg_match($pattern, $in_value[0])){
						$d_value = sprintf("%s %s %s", $field_name, $condition, $in_value[0]);
					}
				}

				$this->where_group[$this->current_group][] = $d_value;
			}
		}
	return $this;
	}

	# 상속한 부모 프라퍼티 값 포함한 가져오기
	public function __get($propertyName){
		if($propertyName == 'where'){
			#아직 종료되지 않은 begin end가 있는지 체크
			if($this->current_group){
				$this->end();
			}
			$this->where = (count($this->where_groups_data)) ? "(" . implode ( ") {$this->coord} (", $this->where_groups_data ) . ")" : '';
			$this->init();
		}

		return $this->{$propertyName};
	}

	# 초기화
	private function init() : void {
		$this->current_group = '';
		$this->current_coord = '';
		$this->where_group   = [];
		$this->where_groups_data = [];
	}

	public function fetch() : array
	{
		$result = $this->where_group;
		$this->init();
	return $result;
	}

	# where 그룹묶기 시작
	public function begin(string $coord) : WhereSql
	{
		$groupname = strtr(microtime(),[' '=>'','0.'=>'w']);
		$this->where_group[$groupname] = [];

		# end 자동닫기
		if($this->current_group){
			$this->end();
		}

		# 현재그룹 시작
		$this->current_group = $groupname;
		$this->current_coord = $coord;
	return $this;
	}

	# where 그룹묶기 종료
	public function end() : WhereSql{
		if(count($this->where_group[$this->current_group])){
			$wher_str = implode(sprintf(" %s ", $this->current_coord), $this->where_group[$this->current_group]);
			$this->where_groups_data[] = $wher_str;
		}

		# 현재그룹 시작
		$this->current_group = '';
		$this->current_coord = '';
	return $this;
	}

	public function __destruct(){
		$this->where             = '';
		$this->current_group     = '';
		$this->current_coord     = '';
		$this->where_group       = [];
		$this->where_groups_data =  [];
	}
}