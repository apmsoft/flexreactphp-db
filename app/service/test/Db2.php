<?php
namespace My\Service\Test;

use Flex\Banana\Classes\R;
use Flex\Banana\Classes\Log;
use Flex\Banana\Classes\Json\JsonEncoder;
use Flex\Banana\Classes\Model;
use Flex\Banana\Utils\Requested;

use My\Topadm\Db\DbManager;
use My\Topadm\Db\DbSqlAdapter;

class Db2 extends DbSqlAdapter
{
    public function __construct(
        private Requested $requested,
        DbManager $db
    ) {
        parent::__construct(db: $db);
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
        $rlt = $this->db->table(R::tables('customers'))
            ->distinct('name' )
            ->orderBy('name DESC')
            ->query();
        while ( $row = $rlt->fetch_assoc() )
        {
            // array push
            $model->data[] = $row;
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

        #============================
        # INNER JOIN
        #============================
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


        #============================
        # LEFT JOIN (LEFT OUTER JOIN)
        #============================
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


        #============================
        # RIGHT JOIN (RIGHT OUTER JOIN)
        #============================
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

        #============================
        # CROSS JOIN
        #============================
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

        #============================
        # FULL OUTER JOIN
        #============================
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

        #============================
        # UNION == FULL OUTER JOIN
        #============================
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
            $left_join,$right_join
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

    # GroupBy
    public function doGroupBy(?array $params=[]) : ?string
    {
        # request
        $this->requested->post();

        # model
        $model = new Model($params);
        $model->groupby = [
            "query" => "",
            "data" => []
        ];

        $model->groupby['query'] = $this->db->table(R::tables('customers'))
            ->select('customers.customer_id','customers.name','customers.city')->limit(3)
                ->groupBy('customers.customer_id')
                    ->query;
        $model->groupby['data'] = $this->db->query($model->groupby['query'])->fetch_assoc();


        $model->groupby_where = [
            "query" => "",
            "data" => []
        ];

        $model->groupby_where['query'] = $this->db->table(R::tables('customers'))
            ->select('customers.customer_id','customers.name','customers.city')->limit(3)
                ->groupBy('customers.customer_id, customers.name, customers.city')
                    ->where("customers.city","LIKE-R","New")
                    ->query;
        $groupby_where_rlt = $this->db->query($model->groupby_where['query']);
        while($row = $groupby_where_rlt->fetch_assoc()){
            $model->groupby_where['data'][] = $row;
        }


        # output
        return JsonEncoder::toJson([
            "result" => "true",
            "msg"    => $model->fetch()
        ]);
    }

    # Sub Query
    public function doSubQuery(?array $params=[]) : ?string
    {
        # request
        $this->requested->post();

        # model
        $model = new Model($params);
        $model->subquery_select = [
            "query" => "",
            "data" => []
        ];

        $model->subquery_select['query'] = $this->db->table(R::tables('customers'))
            ->select(
                'customer_id','name','city',
                sprintf("(%s) as amount",$this->db->tableSub( R::tables('orders') )->select("MAX(amount)")->where('customers.customer_id','=','orders.customer_id')->query)
            )
            ->orderBy("customer_id DESC")
            ->limit(10)
            ->query;

        $subquery_select_rlt = $this->db->query($model->subquery_select['query']);
        while($subquery_select_row = $subquery_select_rlt->fetch_assoc()){
            $model->subquery_select['data'][] = $subquery_select_row;
        }


        $model->subquery_where = [
            "query" => "",
            "data" => []
        ];

        $model->subquery_where['query'] = $this->db->table(R::tables('customers'))
            ->select('customer_id','name','city')
            ->where(sprintf(
                "customer_id IN (%s)",
                $this->db->tableSub( R::tables('orders') )->select("customer_id")->query
            ))
            ->orderBy("customer_id DESC")
            ->limit(10)
            ->query;

        $subquery_where_rlt = $this->db->query($model->subquery_where['query']);
        while($subquery_where_row = $subquery_where_rlt->fetch_assoc()){
            $model->subquery_where['data'][] = $subquery_where_row;
        }


        $model->subquery_select_where = [
            "query" => "",
            "data" => []
        ];

        $model->subquery_select_where['query'] = $this->db->table(R::tables('customers'))
            ->select(
                'customer_id','name','city',
                sprintf("(%s) as amount",$this->db->tableSub( R::tables('orders') )->select("MAX(amount)")->where('customers.customer_id','=','orders.customer_id')->query)
            )
            ->where(sprintf(
                "customer_id IN (%s)",
                $this->db->tableSub( R::tables('orders') )->select("customer_id")->query
            ))
            ->orderBy("customer_id DESC")
            ->limit(10)
            ->query;

        $subquery_select_where_rlt = $this->db->query($model->subquery_select_where['query']);
        while($subquery_select_where_row = $subquery_select_where_rlt->fetch_assoc()){
            $model->subquery_select_where['data'][] = $subquery_select_where_row;
        }


        # output
        return JsonEncoder::toJson([
            "result" => "true",
            "msg"    => $model->fetch()
        ]);
    }
}
