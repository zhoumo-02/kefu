<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 2097984975@qq.com
 * Date: 2019/3/17
 * Time: 4:24 PM
 */
namespace app\backend\controller;

use app\backend\model\Cache;
use think\Db;
use app\Common;
use app\backend\model\Admins;

class Index extends Base
{
    // 登录页面
    public function index()
    {
        return $this->fetch();
    }

    public function home(){
        $data = array();
        $time = date('Y-m-d', time());
        $t = strtotime(date('Y-m-d'));
        $times = date('Y-m-d H:i', time());
        $data['total_business'] = Db::table("wolive_business")->count();
        $data['total_service'] = Db::table("wolive_service")->count();
        $data['total_int'] = Db::table("wolive_chats")->distinct(true)->field('visiter_id')->count();
        $data['total_waiter'] = Db::table("wolive_queue")->where(['state' => 'normal'])->where("service_id", 0)->count();
        $data['total_chats'] = Db::table("wolive_chats")->count();
        $data['now_chats'] = Db::table("wolive_chats")->where('timestamp', '>', "{$t}")->where('timestamp', '<=', time())->count();
        $data['now_comment'] = Db::table("wolive_comment")->where('add_time', '>', "{$time}")->where('add_time', '<=', $times)->count();
        $data['total_comment'] = Db::table("wolive_comment")->count();
        $this->assign('data', $data);
        return $this->fetch();
    }

    public function cash(){
        if ($this->request->isPost()) {
            $tool = new Cache();
            $tool->setCache('on');
            $tool->setTemp('on');
            $tool->setLog('on');
            return $tool->clear();
        }
        $this->success('操作成功');
    }

    public function pass(){
        if($this->request->isAjax()){
            $post =$this->request->post();
            $result = $this->validate($post, 'Check');
            if($result !== true) return ['code'=>0,'msg'=>$result];
            $user =Admins::table('wolive_admin')->where("id",session('admin_user_id'))->find();
            $pass = md5(md5($post["oldpass"]) . $user['username']);
            if($user['password'] == $pass){
                $newpass = md5(md5($post["newpass"]) . $user['username']);
                $res =Admins::table("wolive_admin")->where("id",session('admin_user_id'))->update(["password"=>$newpass]);
                if($res) $this->success('修改成功');
                $this->error('修改失败！');
            }
            $this->error('旧密码不正确');
        }
        return $this->fetch();
    }

    public function menu(){
        $common = new Common();
        $menu = Db::table('wolive_admin_menu')->order('sort','asc')->select();
        return json($common->get_tree($menu));
    }

    private function get_tree($data, $pid = 0, $field1 = 'id', $field2 = 'pid', $field3 = 'children')
    {
        $arr = [];
        foreach ($data as $k => $v) {
            if ($v[$field2] == $pid) {
                $v[$field3] = self::get_tree($data, $v[$field1]);
                $arr[] = $v;
            }
        }
        return $arr;
    }

}