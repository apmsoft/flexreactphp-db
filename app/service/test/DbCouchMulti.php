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
use Flex\Banana\Classes\Array\ArrayHelper;

use Flex\Banana\Interfaces\ListInterface;
use Flex\Banana\Interfaces\InsertInterface;
use Flex\Banana\Interfaces\UpdateInterface;

use My\Columns\Test\TestEnum;

class DbCouchMulti extends DbAdapter implements ListInterface,UpdateInterface,InsertInterface
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

        $model = new Model();
        $model->data1 = [];
        $model->data2 = [];
        $model->data = [];

        # 멀티쿼리
        $queries = [];
        $queries[] = $this->db->table(R::tables('test'))
            ->select(
                TestEnum::ID(),
                TestEnum::TITLE(),
                TestEnum::SIGNDATE(),
                TestEnum::VIEW_COUNT()
            )
            ->where(TestEnum::TITLE(),"LIKE","업")
            ->orderBy(TestEnum::ID().' DESC')
            ->limit(10)
            ->query;
        $queries[] = $this->db->table(R::tables('test'))
            ->select(
                TestEnum::ID(),
                TestEnum::TITLE(),
                TestEnum::SIGNDATE(),
                TestEnum::VIEW_COUNT()
            )
            ->where(TestEnum::TITLE(),"LIKE","테스트")
            ->orderBy(TestEnum::ID().' DESC')
            ->limit(10)
            ->query;
        $results = $this->db->query(JsonEncoder::toJson($queries));
        foreach($results as $idx => $result)
        {
            $data_name = "data".($idx+1);
            while ( $row = $result->fetch_assoc() )
            {
                // array push
                $model->{$data_name}[] = [
                    TestEnum::ID()        => $this->testEnum->setId( $row[TestEnum::ID()] )->getId(),
                    TestEnum::TITLE()     => $this->testEnum->setTitle( $row[TestEnum::TITLE()] )->getTitle(),
                    TestEnum::SIGNDATE()  => $this->testEnum->setSigndate( $row[TestEnum::SIGNDATE()] )->getSigndate(),
                    TestEnum::VIEW_COUNT()=> $this->testEnum->setViewCount( (int)$row[TestEnum::VIEW_COUNT()] )->getViewCount(),
                ];
            }
        }

        $union = (new ArrayHelper( [] ))
            ->unionAll( [$model->data1,$model->data2] )
                ->sorting("_id", "DESC")
                ->value;

        # output
        return JsonEncoder::toJson([
            "result" => "true",
            "data1"  => $model->data1,
            "data2"  => $model->data2,
            "msg"    => $union
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

            # insert 1
            $this->db[TestEnum::TITLE()]      = $this->requested->title."_1";
            $this->db[TestEnum::SIGNDATE()]   = (new DateTimez("now"))->format('Y-m-d H:i:s');
            $this->db[TestEnum::VIEW_COUNT()] = 0;
            $this->db->table( R::tables('test') )->insert();

            # insert 2
            $this->db[TestEnum::TITLE()]      = $this->requested->title."_2";
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

    public function doUpdate(?array $params = []): ?string
    {
        # request
        $this->requested->post();

        # Validation
        try{
            (new Validation('id1', R::strings('id'),$this->requested->id1))->null()->disliking(["_",":","-"]);
            (new Validation('id2', R::strings('id'),$this->requested->id2))->null()->disliking(["_",":","-"]);
            (new Validation('title', R::strings('title'),$this->requested->title))->null()->disliking([]);
        }catch(\Exception $e){
            Log::e( $e->getMessage());
            return $e->getMessage();
        }

        # model
        $model = new Model($params);
        $model->data1 = [];
        $model->data2 = [];

        # 멀티쿼리
        $queries = [];
        $queries[] = $this->db->table(R::tables('test'))->select('*')->where(TestEnum::ID(),$this->requested->id1)->query;
        $queries[] = $this->db->table(R::tables('test'))->select('*')->where(TestEnum::ID(),$this->requested->id2)->query;

        $results = $this->db->query(JsonEncoder::toJson($queries));
        foreach($results as $idx => $result)
        {
            $data_name = "data".($idx+1);
            while ( $row = $result->fetch_assoc() )
            {
                // array push
                $model->{$data_name} = $row;
            }
        }

        # db
        try{
            $this->db->beginTransaction();

            # update 1
            $this->db['_rev']                = $model->data1['_rev'];
            $this->db[TestEnum::TITLE()]     = $this->requested->title."_up1";
            $this->db[TestEnum::SIGNDATE()]  = $model->data1[TestEnum::SIGNDATE()];
            $this->db[TestEnum::VIEW_COUNT()]= $model->data1[TestEnum::VIEW_COUNT()]+1;
            $this->db->table( R::tables('test') )->where(TestEnum::ID(), $this->requested->id1)->update();

            # update 2
            $this->db['_rev']                = $model->data2['_rev'];
            $this->db[TestEnum::TITLE()]     = $this->requested->title."_up2";
            $this->db[TestEnum::SIGNDATE()]  = $model->data2[TestEnum::SIGNDATE()];
            $this->db[TestEnum::VIEW_COUNT()]= $model->data2[TestEnum::VIEW_COUNT()]+1;
            $this->db->table( R::tables('test') )->where(TestEnum::ID(), $this->requested->id2)->update();

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
}
