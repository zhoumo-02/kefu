<?php
/**
 * Created by PhpStorm.
 * User: Andy
 * Date: 2020/4/10
 * Time: 11:21
 */
namespace app\service\model;

use think\Model;

class Vgroup extends Model
{
    protected $table = 'wolive_vgroup';
    protected $autoWriteTimestamp = false;

    public static function getList()
    {
        $where = [];
        $limit = input('get.limit');
        if ($groupname = input('get.group_name')) $where['group_name'] = $groupname;
        $where['business_id'] = $_SESSION['Msg']['business_id'];
        $list = self::order('id', 'asc')->where($where)->paginate($limit)->each(function ($item) {
            $item['group_num'] = self::table('wolive_queue')->where(['groupid' => $item['id']])->group('service_id')->count();
            return $item;
        });
        return ['code' => 0, 'data' => $list->items(), 'count' => $list->total(), 'limit' => $limit];
    }

    public function setCreateTimeAttr()
    {
        return date('Y-m-d H:i:s');
    }

    public function getCreateTimeAttr()
    {
        return date('Y-m-d H:i:s');
    }
}