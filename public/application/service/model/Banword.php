<?php
/**
 * Created by PhpStorm.
 * User: Andy
 * Date: 2020/4/10
 * Time: 11:21
 */

namespace app\service\model;

use think\Model;

class Banword extends Model
{
    protected $table = 'wolive_banword';

    public static function getList()
    {
        $langs = config('lang');
        $where = [];
        $limit = input('get.limit');
        if ($keyword = input('get.keyword')) $where['keyword'] = $keyword;
        if ($lang = input('get.lang')) $where['lang'] = $lang;
        $where['business_id'] = $_SESSION['Msg']['business_id'];
        $list = self::order('id', 'desc')->where($where)->paginate($limit)->each(function($item)use($langs){
            $item['lang'] = $langs[$item['lang']];
            return $item;
        });
        return ['code' => 0, 'data' => $list->items(), 'count' => $list->total(), 'limit' => $limit];
    }
}