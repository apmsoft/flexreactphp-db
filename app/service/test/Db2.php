<?php
namespace My\Service\Test;

use Flex\Banana\Classes\R;
use Flex\Banana\Classes\Log;
use Flex\Banana\Classes\Json\JsonEncoder;
use Flex\Banana\Classes\Model;
use Flex\Banana\Utils\Requested;

use My\Topadm\Db\DbManager;
use My\Topadm\Db\DbSqlAdapter;


use My\Columns\Test\TestEnum;

class Db2 extends DbSqlAdapter
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

    # Distinct
    public function doDistinct(?array $params=[]) : ?string
    {
        # request
        $this->requested->post();

        # model
        $model = new Model($params);
        $model->data         = [];

        # query
        $rlt = $this->db->table(R::tables('test'))
            ->distinct(TestEnum::TITLE() )
            ->orderBy(TestEnum::TITLE().' DESC')
            ->query();
        while ( $row = $rlt->fetch_assoc() )
        {
            // array push
            $model->data[] = [
                // TestEnum::ID()      => $this->testEnum->setId( (int)$row[TestEnum::ID()] )->getId(),
                TestEnum::TITLE()   => $this->testEnum->setTitle( $row[TestEnum::TITLE()] )->getTitle(),
                // TestEnum::SIGNDATE()=> $this->testEnum->setSigndate( $row[TestEnum::SIGNDATE()] )->getSigndate()
            ];
        }

        # output
        return JsonEncoder::toJson([
            "result" => "true",
            "msg"    => $model->data
        ]);
    }


    # Join
    public function doJoin(?array $params=[]) : ?string
    {
        # request
        $this->requested->post();

        # model
        $model = new Model($params);

        # INNER JOIN
        $model->inner_join = [
            "query" => "",
            "data" => []
        ];

        $model->inner_join['query'] = $this->db
            ->tableJoin("INNER", R::tables('customers')." a", R::tables('orders')." b")
                ->select('a.name', 'b.product', 'b.amount')
                    ->on('a.customer_id = b.customer_id')->limit(100)->query;

        $inner_join_rlt = $this->db->query( $model->inner_join['query'] );
        while ( $inner_join_row = $inner_join_rlt->fetch_assoc()){
            $model->inner_join['data'][] = $inner_join_row;
        }


        # LEFT JOIN (LEFT OUTER JOIN)
        $model->left_join = [
            "query" => "",
            "data" => []
        ];

        $model->left_join['query'] = $this->db
        ->tableJoin("LEFT", R::tables('customers')." a", R::tables('orders')." b")
            ->select('a.name', 'b.product', 'b.amount')
                ->on('a.customer_id = b.customer_id')->limit(100)->query;

        $left_join_rlt = $this->db->query( $model->left_join['query'] );
        while ( $left_join_row = $left_join_rlt->fetch_assoc()){
            $model->left_join['data'][] = $left_join_row;
        }


        # RIGHT JOIN (RIGHT OUTER JOIN)
        $model->right_join = [
            "query" => "",
            "data" => []
        ];

        $model->right_join['query'] = $this->db
        ->tableJoin("RIGHT", R::tables('customers')." a", R::tables('orders')." b")
            ->select('a.name', 'b.product', 'b.amount')
                ->on('a.customer_id = b.customer_id')->limit(100)->query;

        $right_join_rlt = $this->db->query( $model->right_join['query'] );
        while ( $right_join_row = $right_join_rlt->fetch_assoc()){
            $model->right_join['data'][] = $right_join_row;
        }

        # CROSS JOIN
        $model->cross_join = [
            "query" => "",
            "data" => []
        ];

        $model->cross_join['query'] = $this->db
        ->tableJoin("CROSS", R::tables('customers')." a", R::tables('orders')." b")
            ->select('a.name', 'b.product')
                ->limit(100)->query;

        $cross_join_rlt = $this->db->query( $model->cross_join['query'] );
        while ( $cross_join_row = $cross_join_rlt->fetch_assoc()){
            $model->cross_join['data'][] = $cross_join_row;
        }

        # FULL OUTER JOIN
        $model->full_outer_join = [
            "query" => "PostGreSQL 에서만 지원하는 기능입니다.",
            "data" => []
        ];

        if($this->db->db_type == 'pgsql')
        {
            $model->full_outer_join['query'] = $this->db
            ->tableJoin("FULL OUTER", R::tables('customers')." a", R::tables('orders')." b")
                ->select('a.name', 'b.product', 'b.amount')
                    ->on('a.customer_id = b.customer_id')->limit(100)->query;

            $full_outer_join_rlt = $this->db->query( $model->full_outer_join['query'] );
            while ( $full_outer_join_row = $full_outer_join_rlt->fetch_assoc()){
                $model->full_outer_join['data'][] = $full_outer_join_row;
            }
        }

        # UNION == FULL OUTER JOIN
        $model->union_join = [
            "query" => "MySQL 에서만 지원하는 기능입니다.",
            "data" => []
        ];
        if($this->db->db_type == 'mysql')
        {
            # left
            $left_join = $this->db
                ->tableJoin("LEFT", R::tables('customers')." a", R::tables('orders')." b")
                    ->select('a.name', 'b.product', 'b.amount')
                        ->on('a.customer_id = b.customer_id')
                        ->query;

            # right
            $right_join = $this->db
            ->tableJoin("RIGHT", R::tables('customers')." a", R::tables('orders')." b")
                ->select('a.name', 'b.product', 'b.amount')
                    ->on('a.customer_id = b.customer_id')
                    ->query;

            $model->union_join['query'] = $this->db->tableJoin("UNION",
            $left_join,
            $right_join
            )
                ->limit(100)->query;

            $union_join_rlt = $this->db->query( $model->union_join['query'] );
            while ( $union_join_row = $union_join_rlt->fetch_assoc()){
                $model->union_join['data'][] = $union_join_row;
            }
        }

        # output
        return JsonEncoder::toJson([
            "result" => "true",
            "msg"    => $model->fetch()
        ],["amount"]);
    }
}
