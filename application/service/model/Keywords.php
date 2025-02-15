<?php
/**
 * Created by PhpStorm.
 * User: Andy
 * Date: 2020/4/10
 * Time: 11:21
 */

namespace app\service\model;

use think\Model;

class Keywords extends Model
{
    protected $table = 'wolive_keywords';

    public static function getList()
    {
        $langs = config('lang');
        $group = self::table('wolive_group')->column("groupname","id");
        $where = [];
        $limit = input('get.limit');
        if ($keyword = input('get.keyword')) $where['keyword'] = $keyword;
        if ($lang = input('get.lang')) $where['lang'] = $lang;
        $list = self::order('id', 'asc')->where($where)->paginate($limit)->each(function($item)use($group,$langs){
            $item['lang'] = $langs[$item['lang']];
            $item['groupid'] = isset($group[$item['groupid']])?$group[$item['groupid']]:"未选择分组";
            return $item;
        });
        return ['code' => 0, 'data' => $list->items(), 'count' => $list->total(), 'limit' => $limit];
    }
}