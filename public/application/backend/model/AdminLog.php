<?php


namespace app\backend\model;

use think\Model;
use app\service\iplocation\Ip;

/**
 * 数据模型类.
 */
class AdminLog extends Model
{
    // 获取日志列表
    public static function getLog()
    {
        $where = [];
        $limit = input('get.limit');
        $list = self::table('wolive_admin_log')->order('id','desc')->paginate($limit)->each(function($item,$key){
            $ip_area = Ip::find($item['ip']);
            $item['ip'] = $item['ip']."【{$ip_area[0]}{$ip_area[1]}{$ip_area[2]}】";
            $service = self::table('wolive_service')->where(['service_id'=>$item['uid']])->find();
            $item['user_name'] = $service['user_name']?:'未知用户';
            return $item;
        });
        return ['code'=>0,'data'=>$list->items(),'count' => $list->total(), 'limit' => $limit];
    }
}
