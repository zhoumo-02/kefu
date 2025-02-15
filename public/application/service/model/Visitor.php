<?php


namespace app\service\model;

use think\Model;
use app\service\iplocation\Ip;

/**
 * 数据模型类.
 */
class Visitor extends Model
{
    protected $table = 'wolive_visiter';

    public static function getList()
    {
        $where = [];
        $limit = input('get.limit');
        $lang = config('lang');
        if ($group_id = input('get.groupid')) $where['q.groupid'] =  $group_id;
        if ($state = input('get.state')) $where['q.state'] =  $state;
        $where['v.business_id'] = $_SESSION['Msg']['business_id'];
        $list = self::alias('v')->join('wolive_queue q','q.visiter_id = v.visiter_id','left')->order('vid','desc')->group('q.visiter_id')->where($where)->paginate($limit)->each(function($item)use($lang){
            $ip_area = Ip::find($item['ip']);
            $item['ip'] = $item['ip']."【{$ip_area[0]}{$ip_area[1]}{$ip_area[2]}】";
            $group = self::table('wolive_vgroup')->where(['id'=>$item['groupid']])->find();
            $item['group_name'] = $group['group_name']?:'暂未分组';
            $item['lang'] = $lang[$item['lang']];
            $item['extends'] = json_decode($item['extends']);
            return $item;
        });
        return ['code'=>0,'data'=>$list->items(),'count' => $list->total(), 'limit' => $limit];
    }
}
