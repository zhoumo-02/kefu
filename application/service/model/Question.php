<?php
/**
 * Created by PhpStorm.
 * User: Andy
 * Date: 2020/4/10
 * Time: 11:21
 */

namespace app\service\model;

use think\Model;

class Question extends Model
{
    protected $table = 'wolive_question';

    public static function getList()
    {
        $langs = config('lang');
        $types = config('ques_types');
        $where = [];
        $limit = input('get.limit');
        if ($keyword = input('get.keyword')) $where['keyword'] = $keyword;
        if ($lang = input('get.lang')) $where['lang'] = $lang;
        $where['business_id'] = $_SESSION['Msg']['business_id'];
        $list = self::order('sort', 'asc')->where($where)->paginate($limit)->each(function($item)use($langs, $types){
            $item['lang'] = $langs[$item['lang']];
            $item['ques_type'] = $types[$item['ques_type']];
            return $item;
        });
        return ['code' => 0, 'data' => $list->items(), 'count' => $list->total(), 'limit' => $limit];
    }
    
    public static function getListByType($type)
    {
        $langs = config('lang');
        $types = config('ques_types');
        $where = ['ques_type'=>$type];
        $limit = input('get.limit');
        if ($keyword = input('get.keyword')) $where['keyword'] = $keyword;
        if ($lang = input('get.lang')) $where['lang'] = $lang;
        $where['business_id'] = $_SESSION['Msg']['business_id'];
        $list = self::order('sort', 'asc')->where($where)->paginate($limit)->each(function($item)use($langs, $types){
            $item['lang'] = $langs[$item['lang']];
            $item['ques_type'] = $types[$item['ques_type']];
            return $item;
        });
        return ['code' => 0, 'data' => $list->items(), 'count' => $list->total(), 'limit' => $limit];
    }
}