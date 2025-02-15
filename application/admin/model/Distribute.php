<?php
/**
 * Created by PhpStorm.
 * User: Andy
 * Date: 2020/2/18
 * Time: 18:35
 */
namespace app\admin\model;

use think\Log;

class Distribute
{
    public static function run($business_id,$state = null,$groupid)
    {
        $where = [
            'business_id' => $business_id,
            // 'state' => 'online',
            'groupid' => $groupid,
        ];

        $service_data = [];

        if ($state == null ) {
            unset($where['state']);
            $where['offline_first'] = 1;
            $services = self::getList($where);
            if ($services) {
                self::sort($services,$service_data);
            } else {
                // $where['groupid'] = 0;
                $services_all = self::getList($where);
                if ($services_all){
                    self::sort($services_all,$service_data);
                }
            }
            reset($service_data);
            if (!empty($service_data)) {
                return $service_data;
            } else {
                unset($where['offline_first']);
            }
        }

        $services = self::getList($where);
        Log::record('33:'.json_encode($services));
        
        if ($services) {
            self::sort($services,$service_data);
            Log::record('44444:'.json_encode($service_data));
        } else {
            // $where['groupid'] = 0;
            $services_all = self::getList($where);
            if ($services_all) {
                self::sort($services_all,$service_data);
            }
        }
        reset($service_data);
        Log::record('33333:'.json_encode($service_data));
        return $service_data;
    }

    public static function sort($services,&$service_data)
    {
        foreach ($services as $v) {
            $service_id = $v['service_id'];
            $num = Admins::table('wolive_queue')->alias("q")->join('wolive_visiter v',"q.visiter_id  = v.visiter_id ")
            ->where(['q.service_id' => $v['service_id']])->where(['v.state' => "online"])->count();

            if (isset($service_data['num'])) {

                if ($service_data['num'] > $num) {
                    $v['num'] = $num;
                    $service_data = $v;
                }
            } else {
                $v['num'] = $num;
                $service_data = $v;
            }
        }
    }

    public static function getList($where)
    {
        return Admins::table('wolive_service')
            ->field('avatar,business_id,email,groupid,open_id,nick_name,service_id,state')
            ->where($where)
            ->where('frozen' , 0)
            ->select();
    }
}