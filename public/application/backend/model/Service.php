<?php


namespace app\backend\model;

use think\Model;

/**
 * 数据模型类.
 */
class Service extends Model
{
    protected $table = 'wolive_service';

    public static function getList()
    {
        $where = [];
        $limit = input('get.limit');
        if ($user_name = input('get.user_name')) $where['user_name'] =  $user_name;
        $http_type = ((isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $web = $http_type . $_SERVER['HTTP_HOST'];
        $action = $web.request()->root();
        $list = self::order('service_id','desc')->where($where)->paginate($limit)->each(function($item)use($action){
            $item['personal'] = $action.'/index/index/home?visiter_id=&visiter_name=&avatar=&business_id='.$item['business_id'].'&groupid='.$item['groupid'].'&special='.$item['service_id'];
            $item['personalwechat'] = $action.'/index/index/wechat/business_id/'.$item['business_id'].'/groupid/'.$item['groupid'].'/special/'.$item['service_id'];
            $business = self::table('wolive_business')->where(['id'=>$item['business_id']])->find();
            $item['business_name'] = $business['business_name']?:'未知';
            return $item;
        });
        return ['code'=>0,'data'=>$list->items(),'count' => $list->total(), 'limit' => $limit];
    }
}
