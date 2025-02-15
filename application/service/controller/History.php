<?php


namespace app\service\controller;

use think\Db;

/**
 *
 * 后台页面控制器.
 */
class History extends Base
{
    public function index()
    {
        $login = $_SESSION['Msg'];
        $services = Db::table("wolive_service")->where('parent_id', $login['service_id'])->select();
        $this->assign("services", $services);
        return $this->fetch();
    }

    public function getviews()
    {
        $login = $_SESSION['Msg'];
        $post = $this->request->post();
        $vid = $post["visiter_id"];
        if(isset($post['service_id'])) $service_id = $post['service_id'];
        $business_id = $login['business_id'];
        $time = $post['puttime'];
        if ($time == 1) {
            $puttime = strtotime("-1 month");
            $result = Db::table('wolive_chats')->where(['visiter_id' => $vid, 'business_id' => $business_id])->where('timestamp', '>', $puttime)->order('timestamp')->select();
            $vdata = Db::table('wolive_visiter')->where('visiter_id', $vid)->where('business_id', $login['business_id'])->find();
            $sdata = Db::table('wolive_service')->where('service_id', $service_id)->find();
            foreach ($result as &$v) {
                if ($v['direction'] == 'to_service') {
                    $v['avatar'] = $vdata['avatar'];
                } else {
                    $v['avatar'] = $sdata['avatar'];
                }
            }
            reset($result);
        } else if ($time == 7) {
            $puttime = strtotime("-1 week");
            $result = Db::table('wolive_chats')->where(['visiter_id' => $vid, 'business_id' => $business_id])->where('timestamp', '>', $puttime)->order('timestamp')->select();
            $vdata = Db::table('wolive_visiter')->where('visiter_id', $vid)->where('business_id', $login['business_id'])->find();
            $sdata = Db::table('wolive_service')->where('service_id', $service_id)->find();
            foreach ($result as &$v) {
                if ($v['direction'] == 'to_service') {
                    $v['avatar'] = $vdata['avatar'];
                } else {
                    $v['avatar'] = $sdata['avatar'];
                }
            }
            reset($result);
        } else if ($time == 0) {
            $result = Db::table('wolive_chats')->where(['visiter_id' => $vid, 'business_id' => $business_id])->order('timestamp')->select();
            $vdata = Db::table('wolive_visiter')->where('visiter_id', $vid)->where('business_id', $login['business_id'])->find();
            if($result){
                $sdata = Db::table('wolive_service')->where('service_id', $result[0]['service_id'])->find();
            }
            foreach ($result as &$v) {
                if ($v['direction'] == 'to_service') {
                    $v['avatar'] = $vdata['avatar'];
                } else {
                    $v['avatar'] = $sdata['avatar'];
                }
            }
            reset($result);
        }
        $data = ['code' => 0, 'data' => $result];
        return $data;
    }

    public function getvisiters()
    {
        $login = $_SESSION['Msg'];
        $post = $this->request->post();
        if(isset($post['keyword'])&&$post['keyword']){
            $visiters = Db::table('wolive_chats')->field('visiter_id,max(cid) as cid,content')->where(['business_id' => $login['business_id']])->whereLike('content',"%{$post['keyword']}%")->group('visiter_id')->order('cid', 'desc')->select();
            if(!$visiters){
                $visiters = Db::table('wolive_visiter')->field('visiter_id')->where(['business_id' => $login['business_id'],'ip'=>$post['keyword']])->group('visiter_id')->select();
            }
        }else{
            $id = $post["service"];
            $visiters = Db::table('wolive_chats')->field('visiter_id,max(cid) as cid')->where(['service_id' => $id, 'business_id' => $login['business_id']])->group('visiter_id')->order('cid', 'desc')->select();
        }
        $visiterdata = [];
        foreach ($visiters as $v) {
            $visiterdata[] = $v['visiter_id'];
        }
        if ($visiterdata) {
            $data = Db::table('wolive_visiter')->where('business_id', $login['business_id'])->where('visiter_id', 'in', $visiterdata)->select();
            $data = array_column(collection($data)->toArray(), null, 'visiter_id');
            $datas = [];
            foreach ($visiterdata as $v) {
                if(isset($data[$v])) $datas[] = $data[$v];
            }
        } else {
            $datas = '';
        }
        $data = ['code' => 0, 'data' => $datas];
        return $data;
    }

    public function getdesignForViews()
    {
        $login = $_SESSION['Msg'];
        $post = $this->request->post();
        $cha = $post["channel"];
        $s_time = strtotime($post['start']);
        $e_time = strtotime($post['end']) + 24 * 60 * 60;
        $result = Db::table('wolive_chats')->where('visiter_id', $cha)->where('timestamp', '>=', $s_time)->where('timestamp', '<=', $e_time)->select();
        foreach ($result as $v) {
            if ($v['direction'] == 'to_service') {
                $data = Db::table('wolive_visiter')->where('visiter_id', $v['visiter_id'])->where('business_id', $login['business_id'])->find();
                $v['avatar'] = $data['avatar'];
            } else {
                $data = Db::table('wolive_service')->where('service_id', $v['service_id'])->find();
                $v['avatar'] = $data['avatar'];
            }
        }
        reset($result);
        $data = ['code' => 0, 'data' => $result];
        return $data;
    }
}