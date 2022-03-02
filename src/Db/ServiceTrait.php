<?php

namespace MabangSdk\Db;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

trait ServiceTrait
{
    /*
     * 订单查询
     * @param $params = array(
        // 设置连接的数据库
        '_conn'=>1,
        '_spcode'=>100001, // 主表，分表键值， 例如：企业编号
        '_select'=>['*'], 

        // 下面字段为主表Order的相关字段传参
        'buyerName'=>'', 
        'buyerName'=>['like', '%abc%'],
        'shopId'=>'', 
        'shopId'=>['in', [123, 456]], 
    
        // 下面为联表相关传参封装
        '_joinTables'=>[
            \Mabang\Services\V1\Order\OrderItemService::class => [
                '_joinField'=>'orderId', // 【必填】
                '_spcode'=>'orderId', // 如果设置分表，传分表参数

                'stockSku'=>'',
                'stockSku'=>['like', '%abc%'],
            ]
        ],

        // order by , 支持 字符串，数组传参
        '_orderBy'=>[
            ['a.ctimed', 'desc'], ['utimed']
        ],
        
        // group by 
        '_groupBy'=>'',
    
        // 分页
        '_paginate'=>[
            'perPage'=>15,
            'page'=>1
        ]
     )

     // 订单表测试样例
     $sql = OrderService::me()->simpleQuery([
            // 下面为主表字段
            'shopId'=>3,
            'paidFlag'=>['in', [1, 2]],
            // 以下为联表参数
            '_conn'=>1,
            '_spcode'=>100001,
            '_select'=>['a.id'],
            '_joinTables'=>[
                \Mabang\Services\V1\Order\OrderItemService::class => [
                    '_joinField'=>'orderId',
                    '_spcode'=>100001,
                    'stockSku'=>['in', ['M02422b', 'M02422h', 'M02422n']],
                    'title'=>['like', '%Gloss%', 'or']
                ],
                \Mabang\Services\V1\Order\OrderPlusService::class => [
                    '_joinField'=>'orderId',
                    '_spcode'=>100001,
                    'city'=>'Israel',
                ]
            ],
            '_paginate'=>[
                'page'=>5
            ],
            '_groupBy'=>[
                \Mabang\Services\V1\Order\OrderPlusService::class, 
                'createdTime'
            ]
            '_orderBy'=>[
                [\Mabang\Services\V1\Order\OrderPlusService::class, 'createdTime', 'desc'], 
                'paidTime'
            ],
        ]);
     */
    public function simpleQuery($params)
    {
        // 设置连接哪一个数据库
        $conn = isset($params['_conn']) ? $params['_conn'] : null;
        // 设置分表参数
        $spcode = isset($params['_spcode']) ? $params['_spcode'] : null;
        // 主表联表主键值
        $id = isset($params['_id']) ? $params['_id'] : 'id';

        // 是否要连其他表
        $hasJoinTb = isset($params['_joinTables']) && count($params['_joinTables']) ? true : false;

        // 通过联表，获取表前缀
        $prefixKeys = [self::me()->getOriginalTable() => 'a'];
        $model = self::suffix([$spcode, 'a'], $conn);
        foreach($params as $field=>$value) {
            if(preg_match('/^_/', $field)) continue;
            $this->joinWhereValue($model, ($hasJoinTb ? 'a.' : '').$field, $value);
        }
        // 联表查询
        $sl = 98; // 字母b
        if(isset($params['_joinTables']) && is_array($params['_joinTables']))
            foreach($params['_joinTables'] as $tbService => $tbInfos) {
            // 和主表的联表字段
            $joinField = isset($tbInfos['_joinField']) ? $tbInfos['_joinField'] : null;
            if(! $joinField) continue;
            // 分表参数
            $spcode = isset($tbInfos['_spcode']) ? $tbInfos['_spcode'] : null;            

            $letter = chr($sl); $prefixKeys[$tbService::me()->getOriginalTable()] = $letter;
            $model->leftJoin($tbService::me()->setSuffix([$spcode, $letter]), "a.{$id}", "=", "{$letter}.{$joinField}");
            foreach($tbInfos as $sField=>$sValue) {
                if(preg_match('/^_/', $sField)) continue;
                $this->joinWhereValue($model, "{$letter}.".$sField, $sValue);
            }
            $sl ++;
        }

        // 查询出的字段
        // ['id', 'name']
        if(isset($params['_select'])) {
            $select = isset($params['_select']) && is_array($params['_select']) ? $params['_select'] : ['*'];
            $model->selectRaw(implode(',', $select));
        } else {
            $model->select(['*']);    
        }

        // order by 查询
        // ctimed => order by asc
        // ['ctimed', 'desc'] => order by ctimed desc
        // [['ctimed', 'desc'], ['utimed']] => order by ctimed desc, utimed asc
        $orderBy = isset($params['_orderBy']) ? $params['_orderBy'] : [];
        if($orderBy) $this->parseOrderBy($model, $prefixKeys, $orderBy);

        // group by 查询
        $groupBy = isset($params['_groupBy']) ? $params['_groupBy'] : "";
        if($groupBy) $this->parseGroupBy($model, $prefixKeys, $groupBy);

        // 是否分页查询
        // ['page'=>1]
        // ['perPage'=>15, 'page'=>1]
        $paginate = isset($params['_paginate']) ? $params['_paginate'] : [];
        if(isset($paginate['page'])) {
            $page = (int)$paginate['page']; $page = $page > 0 ? $page : 1;
            $perPage = isset($paginate['perPage']) ? (int)$paginate['perPage'] : 0; $perPage = $perPage > 0 ? $perPage : 15;
            $model->forPage($page, $perPage);
        }

        // return $model->toSql(); // for debug
        return $model;

    }

    /**
     * 解析传参条件
     * @param object &$model 
     * @param string $field 字段
     * @param int|string|array $value
     *      以下当值传为数组的格式
     *      [$operator, $value, $boolean='and']
     *      [$operator, $value]
     * @return bool
     */
    private function joinWhereValue(&$model, $field, $whereValue) {
        $value = $whereValue; $operator = '='; $boolean = 'and';

        // 字段带后缀方式拼接查询
        if(preg_match('/_(gt|lt|geq|leq|neq|eq|like|nlike|in|nin|raw)$/', $field))
        {       
            $pos = strrpos($field, '_');
            $suffix = substr($field, $pos + 1); $field = substr($field, 0, $pos); 
            switch ($suffix) {
                case 'eq': $operator = '='; break;
                case 'neq': $operator = '!='; break;
                case 'gt': $operator = '>'; break;
                case 'lt': $operator = '<'; break;
                case 'geq': $operator = '>='; break;
                case 'leq': $operator = '<='; break;
                case 'like': $operator = 'like'; break;
                case 'nlike': $operator = 'nlike'; break;
                case 'raw': $operator = 'raw'; break;
                case 'in':
                case 'nin': // in, nin(not in) 处理方式相同
                    $operator = $suffix; 
                    if(is_scalar($whereValue)) $whereValue = [$operator, explode(',', $whereValue)];
                    break;
                default:
                    return false;
                    break;
            }
        }

        // 判断传入的参数格式
        if(is_scalar($whereValue)) {}
        else if(is_array($whereValue) 
            && ($len = count($whereValue)) > 0 
            && in_array($len, [2, 3])) { // 判断传入数组长度是否2位或3位
            if(2 == $len) list($operator, $value) = $whereValue;
            else if(3 == $len) list($operator, $value, $boolean) = $whereValue;
        } else {
            return false;
        }

        // 将传参拼结到where查询条件中
        if(in_array($operator, ['=', '<', '>', '<=', '>=', '<>', '!=', 'like', 'nlike'])) {
            $model->where($field, $operator, $value, $boolean);    
        } else if(in_array($operator, ['in', 'nin'])) {
            $model->whereIn($field, $value, $boolean, 'nin' == $operator);
        } else if(in_array($operator, ['null', 'nnull'])) {
            $model->whereNull($field, $value, $boolean, 'nnull' == $operator);
        } else if(in_array($operator, ['between', 'nbetween'])) {
            $model->whereBetween($field, $value, $boolean, 'nbetween' == $operator);
        } else if(in_array($operator, ['raw'])) {
            $model->whereRaw($value);
        }

        return true;
    }

    /**
     * 解析order by 查询参数
     * ctimed => order by a.ctimed asc
     * ['ctimed', 'desc'] => order by a.ctimed desc
     * [\Mabang\Services\V1\Order\OrderItemService::class, 'ctimed', 'desc'] => order by b.ctimed desc
     * [['ctimed', 'desc'], ['utimed']] => order by a.ctimed desc, a.utimed asc
     */
    private function parseOrderBy(&$model, $prefixKeys, $orderBy) {
        if(is_string($orderBy)) $model->orderBy($orderBy); // 默认asc
        else if(is_array($orderBy) && ($len = count($orderBy)) > 0) {
            if(is_subclass_of($orderBy[0], '\\Mabang\\Models\\V1\\Model') 
                || is_subclass_of($orderBy[0], '\\Mabang\\Models\\V1\\MongoModel')) {
                if(isset($prefixKeys[$orderBy[0]::me()->getOriginalTable()]) 
                    && isset($orderBy[1]) && is_string($orderBy[1])) {
                    // [\Mabang\Services\V1\Order\OrderItemService::class, 'ctimed', 'desc']
                    $prefix = $prefixKeys[$orderBy[0]::me()->getOriginalTable()];
                    $ascOrDesc = isset($orderBy[2]) && is_string($orderBy[2]) ? $orderBy[2] : 'asc';
                    $model->orderBy($prefix.'.'.$orderBy[1], $ascOrDesc);
                }
            }
            else if(1 == $len && is_string($orderBy[0])) {
                // ['ctimed']
                $model->orderBy('a.'.$orderBy[0]);
            }
            else if(2 == $len && is_string($orderBy[0]) && is_string($orderBy[1])) {
                // ['ctimed', 'desc']
                $model->orderBy('a.'.$orderBy[0], $orderBy[1]);
            }
            else {
                foreach($orderBy as $ob) {
                    $this->parseOrderBy($model, $prefixKeys, $ob);
                }
            }
        }
        return true;
    }

    /**
     * 解析group by 查询参数
     * ctimed => group by a.ctimed
     * [\Mabang\Services\V1\Order\OrderItemService::class, 'ctimed'] => group by b.ctimed
     */
    private function parseGroupBy(&$model, $prefixKeys, $groupBy) {
        if(is_string($groupBy)) $model->groupBy($groupBy); // 默认asc
        else if(is_array($groupBy) && ($len = count($groupBy)) > 0) {
            if(1 == $len && is_string($groupBy[0])) {
                // ['ctimed']
                $model->groupBy('a.'.$groupBy[0]);
            }
            else if(2 == $len) {
                if((is_subclass_of($groupBy[0], '\\Mabang\\Models\\V1\\Model') 
                    || is_subclass_of($groupBy[0], '\\Mabang\\Models\\V1\\MongoModel'))
                && isset($prefixKeys[$groupBy[0]::me()->getOriginalTable()])
                && is_string($groupBy[1])) {
                    // [\Mabang\Services\V1\Order\OrderItemService::class, 'ctimed']
                    $prefix = $prefixKeys[$groupBy[0]::me()->getOriginalTable()];
                    $model->groupBy($prefix.'.'.$groupBy[1]);
                }
            }
        }
    }

    /**
     * 通过表主键获取一行数据
     * @param int $id 主键id
     * @param string|int $suffix 分表后缀
     * @param string|int $connection 数据库链接
     * @return array
     */
    public function getById(int $id, $suffix=null, $connection=null) {
        $model = self::query($suffix, $connection)->find($id);
        return empty($model) ? [] : $model->toArray();
    }

    /**
     * 通过条件获取一行数据
     * @param array $where 条件
     * @param string|int $suffix 分表后缀
     * @param string|int $connection 数据库链接
     * @return array
     */
    public function getFirstByWhere(array $where, $suffix=null, $connection=null) {
        $model = self::query($suffix, $connection)->where($where)->first();
        return empty($model) ? [] : $model->toArray();
    }

    /**
     * 插入数据并发返回id
     * @param string|int $suffix 分表后缀
     * @param string|int $connection 数据库链接
     * @return int
     */
    public function insertAndGetId(array $data, $suffix=null, $connection=null) {
        return self::query($suffix, $connection)->insertGetId($data);
    }

    /**
     * 更新数据
     * @param string|int $suffix 分表后缀
     * @param string|int $connection 数据库链接
     * @param int
     */
    public function updateAndGetId(int $id, array $data, $suffix=null, $connection=null) {
        if(! $id || ! $data) return 0;
        self::query($suffix, $connection)->where('id', $id)->update(['id' => $id] + $data);
        return $id;
    }

    /**
     * 通过主键编号删除数据
     * @param string|int $suffix 分表后缀
     * @param string|int $connection 数据库链接
     * @return int
     */
    public function deleteById(int $id, $suffix=null, $connection=null) {
        return $this->deleteByWhere(['id'=>$id], $suffix, $connection);
    }

    /**
     * 通过条件删除数据
     * @param string|int $suffix 分表后缀
     * @param string|int $connection 数据库链接
     * @return int
     */
    public function deleteByWhere(array $where, $suffix=null, $connection=null) {
        return self::query($suffix, $connection)->where($where)->delete();
    }


    /**
     * 事务开启, 例如: OrderService::beginTransaction();
     * @throws \Exception
     * @return void
     */
    public static function beginTransaction() : void {
        DB::connection(self::me()->connection)->beginTransaction();
    }

    /**
     * 事务提交, 例如: OrderService::commit();
     * @throws \Exception
     * @return void
     */
    public static function commit() : void {
        DB::connection(self::me()->connection)->commit();
    }

    /**
     * 事务回滚, 例如: OrderService::rollback();
     * @throws \Exception
     * @return void
     */
    public static function rollback() : void {
        DB::connection(self::me()->connection)->rollBack();
    }

}