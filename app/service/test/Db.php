<?php
namespace My\Service\Test;

use Flex\Banana\Classes\R;
use Flex\Banana\Classes\Log;
use Flex\Banana\Classes\Json\JsonEncoder;
use Flex\Banana\Classes\Model;
use Flex\Banana\Utils\Requested;

use Flex\Banana\Classes\Db\DbMySqli;
use Flex\Banana\Adapters\DbMySqlAdapter;
use Flex\Banana\Classes\Paging\Relation;
use Flex\Banana\Classes\Request\FormValidation as Validation;

use Flex\Banana\Interfaces\ListInterface;
use Flex\Banana\Interfaces\InsertInterface;
use Flex\Banana\Interfaces\EditUpdateInterface;
use Flex\Banana\Interfaces\DeleteInterface;

use My\Columns\Test\TestEnum;

class Db extends DbMySqlAdapter implements ListInterface 
{
    # Enum&Types 인스턴스
    private TestEnum $testEnum;

    public function __construct(
        private Requested $requested,
        DbMySqli $db
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
        $model->data = [];

        # total record
        $model->total_record = $this->db->table(R::tables('test'))->total();

        # pageing
        $paging = new Relation( totalRecord: $model->total_record, page: $model->page );
        $relation = $paging->query( pagecount: $model->page_count , blockLimit: $model->block_limit )->build()->paging();

        # query
        $rlt = $this->db->table(R::tables('test'))
            ->select(
                TestEnum::ID(),
                TestEnum::TITLE(),
                TestEnum::SIGNDATE()
            )
            ->orderBy(TestEnum::ID().' DESC')
            ->limit($paging->qLimitStart, $paging->qLimitEnd)
            ->query();
        while ( $row = $rlt->fetch_assoc() )
        {
            // array push
            $model->data[] = [
                TestEnum::ID()      => $this->testEnum->setId( (int)$row[TestEnum::ID()] )->getId(),
                TestEnum::TITLE()   => $this->testEnum->setTitle( $row[TestEnum::TITLE()] )->getTitle(),
                TestEnum::SIGNDATE()=> $this->testEnum->setSigndate( $row[TestEnum::SIGNDATE()] )->getSigndate()
            ];
        }

        # output
        return JsonEncoder::toJson([
            "result" => "true",
            "msg" => $model->data
        ]);
    }
}
