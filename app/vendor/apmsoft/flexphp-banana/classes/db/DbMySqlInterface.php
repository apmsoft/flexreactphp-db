<?php
namespace Flex\Banana\Classes\Db;
use \MySQLi;

# purpose : 각종 SQL 관련 디비를 통일성있게  작성할 수 있도록 틀을 제공
interface DbMySqlInterface
{
    public function query(string $query, mixed $result_mode) : \mysqli_result|bool;			# 쿼리
    public function insert() : bool;			# 저장
    public function update() : bool;	# 수정
    public function delete() : bool;	# 삭제
}
?>
