<?php
namespace My\Service\Test;

use Flex\Banana\Classes\R;
use Flex\Banana\Classes\Log;
use Flex\Banana\Classes\Json\JsonEncoder;
use Flex\Banana\Classes\Model;
use Flex\Banana\Utils\Requested;

use Flex\Banana\Classes\Db\DbManager;
use Flex\Banana\Classes\Db\DbCipherGeneric;
use Flex\Banana\Adapters\DbSqlAdapter;
use Flex\Banana\Classes\Paging\Relation;
use Flex\Banana\Classes\Request\FormValidation as Validation;
use Flex\Banana\Classes\Cipher\PasswordHash;
use Flex\Banana\Classes\Cipher\CipherGeneric;

use Flex\Banana\Interfaces\ListInterface;
use Flex\Banana\Interfaces\InsertInterface;
use Flex\Banana\Interfaces\EditUpdateInterface;

class Db3 extends DbSqlAdapter implements ListInterface,InsertInterface,EditUpdateInterface
{
    private DbCipherGeneric $dbCipher;
    public function __construct(
        private Requested $requested,
        DbManager $db,
        DbCipherGeneric $dbCipher
    ) {
        parent::__construct(db: $db);

        # cipher
        $this->dbCipher = $dbCipher;
    }

    public function doList(?array $params=[]) : ?string
    {
        # request
        $this->requested->post();

        # Validation
        try{
            (new Validation('page', R::strings('page'),$this->requested->page ?? 1))->number();
            (new Validation('q',R::strings('q'),$this->requested->q ?? ''))->disliking(['']);
        }catch(\Exception $e){
            Log::e( $e->getMessage());
            return $e->getMessage();
        }

        # model
        $model = new Model($params);
        $model->total_record = 0; // 총레코드수
        $model->page         = $this->requested->page ?? 1;   // 현재페이지
        $model->page_count   = 10;  // 출력갯수
        $model->block_limit  = 2;   // 페이지블록수
        $model->q            = $this->requested->q ?? '';   // 검색어
        $model->data         = [];

        # total record
        $model->total_record = $this->db->table(R::tables('users'))->total();

        # pageing
        $paging = new Relation( totalRecord: $model->total_record, page: $model->page );
        $relation = $paging->query( pagecount: $model->page_count , blockLimit: $model->block_limit )->build()->paging();

        # query
        $rlt = $this->db->table(R::tables('users'))
            ->select(
                "id", 
                $this->dbCipher->decrypt("username")." as username",
                $this->dbCipher->decrypt("email")." as email",
                "passwd"
            )
            ->orderBy('id DESC')
            ->limit($paging->qLimitStart, $paging->qLimitEnd)
            ->query();
        while ( $row = $rlt->fetch_assoc() )
        {
            // array push
            $model->data[] = $row;
        }

        # output
        return JsonEncoder::toJson([
            "result"       => "true",
            'total_page'   => $paging->totalPage,
            'total_record' => $paging->totalRecord,
            'page'         => $paging->page,
            'paging'       => $relation,
            'q'            => $model->q,
            "msg"          => $model->data
        ]);
    }


    public function doInsert(?array $params = []): ?string
    {
        # request
        $this->requested->post();

        # Validation
        try{
            (new Validation('username', "이름",$this->requested->username))->null()->length(4,16)->space()->disliking([]);
            (new Validation('email', "이메일",$this->requested->email))->null()->email();
            (new Validation('passwd', "비밀번호",$this->requested->passwd))->null()->length(8,16)->space()->liking();
        }catch(\Exception $e){
            Log::e( $e->getMessage());
            return $e->getMessage();
        }

        # model
        $model = new Model($params);

        # db
        try{
            $this->db->beginTransaction();
            $this->db["username"] = $this->dbCipher->encrypt($this->requested->username);
            $this->db["email"]    = $this->dbCipher->encrypt($this->requested->email);
            $this->db["passwd"]   = (new CipherGeneric(new PasswordHash()))->hash( $this->requested->passwd );
            $this->db->table( R::tables('users') )->insert();
            $this->db->commit();
        }catch(\Exception $e){
            $this->db->rollBack();
            Log::e("Transaction failed: " . $e->getMessage());
        }

        # output
        return JsonEncoder::toJson([
            "result" => "true",
            "msg"    => R::sysmsg('v_insert')
        ]);
    }

    public function doEdit(?array $params = []): ?string
    {
        # request
        $this->requested->post();

        # Validation
        try{
            (new Validation('id', R::strings('id'),$this->requested->id))->null()->number();
        }catch(\Exception $e){
            Log::e( $e->getMessage());
            return $e->getMessage();
        }

        # model
        $model = new Model($params);
        $model->data = [];

        # check data db
        $model->data = $this->db->table( R::tables('users'))
            ->select(
                "id", 
                $this->dbCipher->decrypt("username")." as username",
                $this->dbCipher->decrypt("email")." as email"
            )
            ->where("id", $this->requested->id)
            ->query()->fetch_assoc();
        if(!isset($model->data["id"])){
            return  JsonEncoder::toJson(["result"=>"false","msg_code"=>"e_db_unenabled", "msg"=>R::sysmsg('e_db_unenabled')]);
        }

        # output
        return JsonEncoder::toJson([
            "result" => "true",
            "msg"    => $model->data
        ]);
    }

    public function doUpdate(?array $params = []): ?string
    {
        # request
        $this->requested->post();

        # Validation
        try{
            (new Validation('id', R::strings('id'),$this->requested->id))->null()->number();
            (new Validation('username', "이름",$this->requested->username))->null()->length(4,16)->space()->disliking([]);
            (new Validation('email', "이메일",$this->requested->email))->null()->email();
            (new Validation('passwd', "비밀번호",$this->requested->passwd))->null()->length(8,16)->space()->liking();
        }catch(\Exception $e){
            Log::e( $e->getMessage());
            return $e->getMessage();
        }

        # model
        $model = new Model($params);
        $model->data = [];

        # check data db
        $model->data = $this->db->table( R::tables('users'))
            ->where("id", $this->requested->id)
            ->query()->fetch_assoc();
        if(!isset($model->data["id"])){
            return  JsonEncoder::toJson(["result"=>"false","msg_code"=>"e_db_unenabled", "msg"=>R::sysmsg('e_db_unenabled')]);
        }

        # db
        try{
            $this->db->beginTransaction();
            $this->db["username"] = $this->dbCipher->encrypt($this->requested->username);
            $this->db["email"]    = $this->dbCipher->encrypt($this->requested->email);
            $this->db["passwd"]   = (new CipherGeneric(new PasswordHash()))->hash( $this->requested->passwd );
            $this->db->table( R::tables('users') )->where("id", $this->requested->id)->update();
            $this->db->commit();
        }catch(\Exception $e){
            $this->db->rollBack();
            Log::e($e->getMessage());
        }

        # output
        return JsonEncoder::toJson([
            "result" => "true",
            "msg"    => R::sysmsg('v_update')
        ]);
    }
}
