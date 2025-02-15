<?php
/**
 * Created by PhpStorm.
 * User: Andy
 * Date: 2020/4/10
 * Time: 11:21
 */

namespace app\service\model;

use think\Model;

class Group extends Model
{
    protected $table = 'wolive_group';

    public static function getList()
    {
        $where = [];
        $limit = input('get.limit');
        if ($groupname = input('get.groupname')) $where['groupname'] = $groupname;
        $list = self::order('sort', 'asc')->where($where)->paginate($limit)->each(function ($item) {
            $item['group_num'] = self::table('wolive_service')->where(['groupid' => $item['id']])->count();
            return $item;
        });
        return ['code' => 0, 'data' => $list->items(), 'count' => $list->total(), 'limit' => $limit];
    }
}