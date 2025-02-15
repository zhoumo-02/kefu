<?php
use think\Db;
if (!function_exists('db')) {
    /**
     * 实例化数据库类
     * @param string        $name 操作的数据表名称（不含前缀）
     * @param array|string  $config 数据库配置参数
     * @param bool          $force 是否强制重新连接
     * @return \think\db\Query
     */
    function db($name = '', $config = [], $force = false)
    {
        return Db::connect($config, $force)->name($name);
    }
}

function getRedis(){
        $options = [
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => '',
            'select' => 0,
            'expire' => 0,
            'persistent' => false,
            'prefix' => '',
        ];
        
        $redis = new \Redis;
        $redis->connect($options['host'], $options['port'], 0);
 
        if ('' != $options['password']) {
            $redis->auth($options['password']);
        }
 
        $redis->select(0);
        return $redis;
    }