<?php


namespace app\service\model;

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
        if ($group_id = input('get.groupid')) $where['groupid'] =  $group_id;
        $where['business_id'] =  $_SESSION['Msg']['business_id'];
        $http_type = ((isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $web = $http_type . $_SERVER['HTTP_HOST'];
        $action = $web.request()->root();
        $list = self::order('service_id','desc')->where($where)->paginate($limit)->each(function($item)use($action){
            $item['personal'] = $action.'/index/index/home?visiter_id=&visiter_name=&avatar=&business_id='.$item['business_id'].'&groupid='.$item['groupid'].'&special='.$item['service_id'];
            $item['personalwechat'] = $action.'/index/index/wechat/business_id/'.$item['business_id'].'/groupid/'.$item['groupid'].'/special/'.$item['service_id'];
            $group = self::table('wolive_group')->where(['id'=>$item['groupid']])->find();
            $item['group_name'] = $group['groupname']?:'暂未分组';
            return $item;
        });
        return ['code'=>0,'data'=>$list->items(),'count' => $list->total(), 'limit' => $limit];
    }

    public static function getService(){
        $http_type = ((isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $web = $http_type . $_SERVER['HTTP_HOST'];
        $action = $web.request()->root();
        $where['business_id'] =  $_SESSION['Msg']['business_id'];
        $list = self::order('service_id','desc')->where($where)->select();
        foreach ($list as &$v){
            $v['personal'] = $action.'/index/index/home?visiter_id=&visiter_name=&avatar=&business_id='.$v['business_id'].'&groupid='.$v['groupid'].'&special='.$v['service_id'];
            $v['personalwechat'] = $action.'/index/index/wechat/business_id/'.$v['business_id'].'/groupid/'.$v['groupid'].'/special/'.$v['service_id'];
            $group = self::table('wolive_group')->where(['id'=>$v['groupid']])->find();
            $v['group_name'] = $group['groupname']?:'暂未分组';
        }
        return $list;
    }
}
