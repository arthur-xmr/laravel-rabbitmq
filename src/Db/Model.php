<?php

namespace MabangSdk\Db;

use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * 数据库操作底层，建议封装到底层通用部分
 */
class Model extends EloquentModel
{
    use ModelTrait, ServiceTrait;

    public $timestamps = false;
    protected $originalTable;
 
    public function __construct(array $attributes = []) {
        parent::__construct($attributes);
        $this->originalTable = $this->getTable();
    }
    /**
     * 追加 data 数据
     *
     * @param array $data
     * @param string $field
     * @param array $params
     * @param string $encode
     *
     */
    public  function appendData(&$data, $field, array $params, $encode = '')
    {
        $value = Arr::get($params, $field);
        if ($value) {
            switch ($encode) {
                case 'json':
                    $value = json_encode($value, JSON_UNESCAPED_SLASHES);
                    break;
                default:
            }
            $data[$field] = $value;
        }
    }
}