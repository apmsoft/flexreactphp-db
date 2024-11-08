<?php
namespace My\Service\Test;

use Flex\Banana\Classes\R;
use Flex\Banana\Classes\Log;
use Flex\Banana\Classes\Json\JsonEncoder;
use Flex\Banana\Classes\Model;
use Flex\Banana\Utils\Requested;

use Flex\Banana\Classes\Db\DbManager;
use Flex\Banana\Adapters\DbAdapter;
use Flex\Banana\Classes\Paging\Relation;
use Flex\Banana\Classes\Request\FormValidation as Validation;
use Flex\Banana\Classes\Date\DateTimez;

use Flex\Banana\Interfaces\ListInterface;
use Flex\Banana\Interfaces\InsertInterface;
use Flex\Banana\Interfaces\EditUpdateInterface;
use Flex\Banana\Interfaces\DeleteInterface;

use My\Columns\Test\TestEnum;

class Db extends DbAdapter implements ListInterface,EditUpdateInterface,InsertInterface,DeleteInterface
{
    # Enum&Types 인스턴스
    private TestEnum $testEnum;

    public function __construct(
        private Requested $requested,
        DbManager $db
    ) {
        parent::__construct(db: $db);

        # Enum&Types 인스턴스 생성
        $this->testEnum = TestEnum::create();
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
        $model->where = null;

        # where
        $model->where = $this->db->whereHelper()->
            begin('OR')->
                case(TestEnum::TITLE(),"LIKE", $model->q)
            ->where;

        # total record
        $model->total_record = $this->db->table(R::tables('test'))->where($model->where)->total();

        # pageing
        $paging = new Relation( totalRecord: $model->total_record, page: $model->page );
        $relation = $paging->query( pagecount: $model->page_count , blockLimit: $model->block_limit )->build()->paging();

        # query
        $rlt = $this->db->table(R::tables('test'))
            ->select(
                TestEnum::ID(),
                TestEnum::TITLE(),
                TestEnum::SIGNDATE(),
                TestEnum::VIEW_COUNT()
            )
            ->where($model->where)
            ->orderBy(TestEnum::ID().' DESC')
            ->limit($paging->qLimitStart, $paging->qLimitEnd)
            ->query();
        while ( $row = $rlt->fetch_assoc() )
        {
            // array push
            $model->data[] = [
                TestEnum::ID()        => $this->testEnum->setId( $row[TestEnum::ID()] )->getId(),
                TestEnum::TITLE()     => $this->testEnum->setTitle( $row[TestEnum::TITLE()] )->getTitle(),
                TestEnum::SIGNDATE()  => $this->testEnum->setSigndate( $row[TestEnum::SIGNDATE()] )->getSigndate(),
                TestEnum::VIEW_COUNT()=> $this->testEnum->setViewCount( (int)$row[TestEnum::VIEW_COUNT()] )->getViewCount(),
            ];
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
            (new Validation('title', R::strings('title'),$this->requested->title))->null();
        }catch(\Exception $e){
            Log::e( $e->getMessage());
            return $e->getMessage();
        }

        # model
        $model = new Model($params);
        $model->data = [];

        # db
        try{
            $this->db->beginTransaction();
            $this->db[TestEnum::TITLE()]      = $this->requested->title;
            $this->db[TestEnum::SIGNDATE()]   = (new DateTimez("now"))->format('Y-m-d H:i:s');
            $this->db[TestEnum::VIEW_COUNT()] = 0;
            $this->db->table( R::tables('test') )->insert();
            $this->db->commit();
        }catch(\Exception $e){
            $this->db->rollBack();
            Log::e($e->getMessage());
            return JsonEncoder::toJson(["result"=>"false","msg"=>$e->getMessage()]);
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
            (new Validation('id', R::strings('id'),$this->requested->id))->null()->disliking([]);
        }catch(\Exception $e){
            Log::e( $e->getMessage());
            return $e->getMessage();
        }

        # model
        $model = new Model($params);
        $model->data = [];

        # check data db
        $model->data = $this->db->table( R::tables('test'))
            ->select('*')
            ->where(TestEnum::ID(), $this->requested->id)
            ->query()->fetch_assoc();
        if(!isset($model->data[TestEnum::ID()])){
            return  JsonEncoder::toJson(["result"=>"false","msg_code"=>"e_db_unenabled", "msg"=>R::sysmsg('e_db_unenabled')]);
        }

        # db
        try{
            $this->db->beginTransaction();
            if(isset($model->data['_rev'])){
                $this->db['_rev']  = $model->data['_rev'];
            }
            $this->db[TestEnum::TITLE()]     = $model->data[TestEnum::TITLE()];
            $this->db[TestEnum::SIGNDATE()]  = $model->data[TestEnum::SIGNDATE()];
            $this->db[TestEnum::VIEW_COUNT()]= $model->data[TestEnum::VIEW_COUNT()]+1;
            $this->db->table( R::tables('test') )->where(TestEnum::ID(), $this->requested->id)->update();
            $this->db->commit();
        }catch(\Exception $e){
            $this->db->rollBack();
            Log::e($e->getMessage());
            return JsonEncoder::toJson(["result"=>"false","msg"=>$e->getMessage()]);
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
            (new Validation('id', R::strings('id'),$this->requested->id))->null()->disliking([]);
            (new Validation('title', R::strings('title'),$this->requested->title))->null();
        }catch(\Exception $e){
            Log::e( $e->getMessage());
            return $e->getMessage();
        }

        # model
        $model = new Model($params);
        $model->data = [];

        # check data db
        $model->data = $this->db->table( R::tables('test'))
            ->select('*')
            ->where(TestEnum::ID(), $this->requested->id)
            ->query()->fetch_assoc();
        if(!isset($model->data[TestEnum::ID()])){
            return  JsonEncoder::toJson(["result"=>"false","msg_code"=>"e_db_unenabled", "msg"=>R::sysmsg('e_db_unenabled')]);
        }

        # db
        try{
            $this->db->beginTransaction();
            if(isset($model->data['_rev'])){
                $this->db['_rev']  = $model->data['_rev'];
            }
            $this->db[TestEnum::TITLE()]     = $this->requested->title;
            $this->db[TestEnum::SIGNDATE()]  = $model->data[TestEnum::SIGNDATE()];
            $this->db[TestEnum::VIEW_COUNT()]= $model->data[TestEnum::VIEW_COUNT()];
            $this->db->table( R::tables('test') )->where(TestEnum::ID(), $this->requested->id)->update();
            $this->db->commit();
        }catch(\Exception $e){
            $this->db->rollBack();
            Log::e($e->getMessage());
            return JsonEncoder::toJson(["result"=>"false","msg"=>$e->getMessage()]);
        }

        # output
        return JsonEncoder::toJson([
            "result" => "true",
            "msg"    => R::sysmsg('v_update')
        ]);
    }


    public function doDelete(?array $params = []): ?string
    {
        # request
        $this->requested->post();

        # Validation
        try{
            (new Validation('id', R::strings('id'),$this->requested->id))->null()->disliking([]);
        }catch(\Exception $e){
            Log::e( $e->getMessage());
            return $e->getMessage();
        }

        # model
        $model = new Model($params);
        $model->data = [];

        # check data db
        $model->data = $this->db->table( R::tables('test'))
            ->select(TestEnum::ID())
            ->where(TestEnum::ID(), $this->requested->id)
            ->query()->fetch_assoc();
        if(!isset($model->data[TestEnum::ID()])){
            return  JsonEncoder::toJson(["result"=>"false","msg_code"=>"e_db_unenabled", "msg"=>R::sysmsg('e_db_unenabled')]);
        }

        # db
        try{
            $this->db->beginTransaction();
            if(isset($model->data['_rev'])){
                $this->db['_rev']  = $model->data['_rev'];
            }
            $this->db->table( R::tables('test') )->where(TestEnum::ID(), $this->requested->id)->delete();
            $this->db->commit();
        }catch(\Exception $e){
            $this->db->rollBack();
            Log::e($e->getMessage());
        }

        # output
        return JsonEncoder::toJson([
            "result" => "true",
            "msg"    => R::sysmsg('v_delete')
        ]);
    }
}
