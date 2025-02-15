<?php
/**
 * @copyright ©2020 AI在线客服系统
 * Created by PhpStorm.
 * User: Andy - Wangjie
 * Date: 2020/5/9
 * Time: 10:04
 */

namespace app\service\model;

use think\Model;
use app\service\model\Visiter;

class Comment extends Model
{
    protected $table = 'wolive_comment';

    public function detail()
    {
        return $this->hasMany('CommentDetail', 'comment_id', 'id');
    }

    public function service()
    {
        return $this->hasOne('Service', 'service_id', 'service_id');
    }

    public function group()
    {
        return $this->hasOne('Group', 'id', 'group_id');
    }

    public static function getList()
    {
        $list = [];
        $limit = input('get.limit');
        $star = input('get.star');
        $group = input('get.group');
        $keyword = input('get.keyword');
        if ($star) $model = self::hasWhere('detail', ['score' => $star])->distinct('*');
        elseif (!empty($group)) $model = self::where('group_id', $group);
        else $model = self::with('detail,service,group');
        if (!empty($keyword)) {
            $services = Service::where(function ($query) use ($keyword){
                    $query->where('nick_name|user_name','like',"%".$keyword."%");
                })->select();
            $servicelist = array_column(collection($services)->toArray(),'service_id');
            if (!empty($servicelist)){
                $model->where('service_id','in',$servicelist);
                $list = $model->where(['business_id'=>$_SESSION['Msg']['business_id']])->order('add_time desc')->paginate()->each(function($item){
                    $item['visiterinfo'] = Visiter::get(['visiter_id'=>$item['visiter_id'],'business_id'=>$item['business_id']]);
                    return $item;
                });
            }
        }else{
            $list = $model->where(['business_id'=>$_SESSION['Msg']['business_id']])->order('add_time', 'desc')->paginate($limit)->each(function($item){
                $item['visiter_info'] = Visiter::get(['visiter_id'=>$item['visiter_id'],'business_id'=>$item['business_id']]);
                return $item;
            });
        }
        return ['code' => 0, 'data' => $list->items(), 'count' => $list->total(), 'limit' => $limit];
    }
}