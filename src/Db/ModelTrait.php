<?php

namespace MabangSdk\Db;

use Illuminate\Support\Facades\Schema;

trait ModelTrait {
    
    private static $instanceMap = [];
    protected $suffix = null;
    protected $conn = null;
    protected $subTableCount = null;

    /**
     * 设置数据链接
     * @param string $conn
     * @return string
     */
    public function  setConn($conn = null) {
        $this->conn = $conn;
        return $this->getNewConn();
    }

    /**
     * 设置表后缀
     * 例如: 
     * $suffix = 100001;
     * $suffix = [100001, 'order'];
     * @param $suffix 分表后缀
     * @return string
     */
    public function setSuffix($suffix)
    {
        list($suffix, $alias) = $this->parseSuffix($suffix);

        if ($suffix !== null) {
            $this->setTable($this->originalTable .
                (isset($this->subTableCount) ?
                    $suffix % $this->subTableCount :
                    $suffix)
            );
        }

        return $this->getNewTable($alias);
    }

    /**
     * 根据传参解析分表后缀
     * 例如: 
     * $suffix = 100001;
     * $suffix = [100001, 'order'];
     * @param $suffix 分表后缀
     * @return array
     */
    protected function parseSuffix($suffix) {
        $alias = null;
        if(is_array($suffix)) {
            $suf = isset($suffix[0]) ? $suffix[0] : null;
            $alias = isset($suffix[1]) ? $suffix[1] : null;
        } else $suf = $suffix;
        return [$suf, $alias];
    }
 
    /**
     * 设置分表，则返回分表名
     * @param string $alias 
     * @return string
     */
    public function getNewTable($alias = null) {
        return $alias ? $this->getTable()." AS {$alias}" : $this->getTable();
    }
 
    /**
     * 设置分表，则返回原表名
     * @return string
     */
    public function getOriginalTable() {
        return $this->originalTable;
    }
 
    /**
     * 若设置表链接，则返回设置的链接，否则返回原链接
     * @return string
     */
    public function getNewConn() {
        return $this->conn ? $this->conn : $this->connection;
    }

    /**
     * 提供一个静态方法查询数据，可设置链接及表名
     * @param string $suffix 分表参数
     * @param string $connection 链接
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function suffix($suffix, $connection=null) {
        return self::query($suffix, $connection);
    }

    /**
     * 提供一个静态方法查询数据
     * @param string $suffix 分表参数
     * @param string $connection 链接
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function query($suffix=null, $connection=null) {
        $instance = self::me();
        $table = $instance->setSuffix($suffix);
        $instance->setTable($table);
        $conn = $instance->setConn($connection);
        if($conn !== null) {
            $instance->setConnection($conn);
        }
        return $instance->newQuery();
    }

    /**
     * 提供一个静态方法获取当前实例
     * @return static
     */
    public static function me()
    {
        $class = get_called_class();
        if(! isset(self::$instanceMap[$class])) {
            self::$instanceMap[$class] = new $class();
        }
        return self::$instanceMap[$class];
    }

    /**
     * 获取表属性注释
     * @return string
     */
    public function getTablePropertyAnnotation()
    {
        $columns = Schema::getColumnListing($this->getOriginalTable());
        $annotations = [];
        foreach($columns as $column) {
            $annotations[] = " * @property \${$column};";
        }
        return implode("\n", $annotations);
    }

    public function getSubTableCount(){
        return $this->subTableCount;
    }
}
