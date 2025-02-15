<?php


namespace app\service\controller;

use app\service\model\Visitor;
use think\Db;
use think\File;

/**
 *
 * 后台页面控制器.
 */
class Visitors extends Base
{

    public function index()
    {
        if ($this->request->isAjax()) {
            return Visitor::getList();
        }
        $group = Db::name("wolive_vgroup")->where(['business_id'=>$_SESSION['Msg']['business_id']])->select();
        $this->assign('group', $group);
        return $this->fetch();
    }

    public function blacklist(){
        $get = $this->request->get();
        $res = Db::table('wolive_queue')->where('visiter_id', $get['id'])->update(['state' => 'in_black_list']);
        if ($res) $this->success('操作成功');
        $this->error('操作失败');
    }

    public function white(){
        $get = $this->request->get();
        $res = Db::table('wolive_queue')->where('visiter_id', $get['id'])->update(['state' => 'normal']);
        if ($res) $this->success('操作成功');
        $this->error('操作失败');
    }

    public function lang(){
        $id = $this->request->get('id');
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $res = Db::name("wolive_visiter")->where(['visiter_id' => $id])->field(true)->update($post);
            if ($res) $this->success('修改成功');
            $this->error('修改失败！');
        }
        $visiter = Db::name("wolive_visiter")->where(['visiter_id' => $id])->find();
        $this->assign('visiter', $visiter);
        return $this->fetch();
    }

    public function edit()
    {
        $id = $this->request->get('id');
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $res = Db::name("wolive_queue")->where(['visiter_id' => $id])->field(true)->update($post);
            if ($res) $this->success('修改成功');
            $this->error('修改失败！');
        }
        $queue = Db::name("wolive_queue")->where(['visiter_id' => $id])->find();
        $group = Db::name("wolive_vgroup")->select();
        $this->assign('queue', $queue);
        $this->assign('group', $group);
        return $this->fetch();
    }
}