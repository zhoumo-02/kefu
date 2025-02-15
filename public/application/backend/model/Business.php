<?php


namespace app\backend\model;

use think\Model;
use think\Db;
use think\Exception;

/**
 * 数据模型类.
 */
class Business extends Model
{
    protected $table = 'wolive_business';

    public static function getList()
    {
        $where = [];
        $limit = input('get.limit');
        $lang = config('lang');
        if ($user_name = input('get.user_name')) $where['business_name'] =  $user_name;
        $list = self::order('id','desc')->where($where)->paginate($limit)->each(function($item)use($lang){
            $item['service_count'] = self::table('wolive_service')->where(['business_id'=>$item['id']])->count();
            $item['lang'] = $lang[$item['lang']];
            $item['max_count'] = $item['max_count']==0?'无限':$item['max_count'];
            $item['expire_time'] = $item['expire_time']>0?date('Y-m-d H:i:s',$item['expire_time']):'永久';
            return $item;
        });
        return ['code'=>0,'data'=>$list->items(),'count' => $list->total(), 'limit' => $limit];
    }

    public static function addBusiness($post)
    {
        //账号注册时需要开启事务,避免出现垃圾数据
        Db::startTrans();
        try
        {
            $business = Business::create([
                'admin_id' => 0,
                'business_name' => $post['business_name'],
                'max_count' => $post['max_count'],
                'is_delete' => 0,
                'expire_time' => $post['expire_time']
            ]);

            Service::create([
                'business_id' => $business->id,
                'level' => 'super_manager',
                'user_name' => $post['business_name'],
                'nick_name' => $post['business_name'],
                'password' => md5($post['business_name'] . "hjkj" . $post['password'])
            ]);
            Db::commit();
            return true;
        }
        catch (Exception $e)
        {
            Db::rollback();
            return false;
        }
    }

    public static function editBusiness($post)
    {
        Db::startTrans();
        try
        {
            Business::where('id',$post['id'])->update([
                'max_count' => $post['max_count'],
                'expire_time' => strtotime($post['expire_time']),
                'lang' => $post['lang'],
            ]);

            if($post['password']){
                $business = Business::get(['id'=>$post['id']]);
                Service::where('user_name',$business['business_name'])->update([
                    'password' => md5($business['business_name'] . "hjkj" . $post['password'])
                ]);
            }

            Db::commit();
            return true;
        }
        catch (Exception $e)
        {
            Db::rollback();
            return false;
        }
    }
}
