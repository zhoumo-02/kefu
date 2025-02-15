<?php


namespace app\service\controller;

use app\service\model\Group;
use think\Db;

/**
 *
 * 后台页面控制器.
 */
class Groups extends Base
{

    public function index()
    {
        if ($this->request->isAjax()) return Group::getList();
        return $this->fetch();
    }

    public function edit()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $res = Group::where("id", $post['id'])->field(true)->update($post);
            if ($res) $this->success('修改成功');
            $this->error('修改失败！');
        }
        $id = $this->request->get('id');
        $group = Group::where(['id' => $id])->find();
        $this->assign('group', $group);
        return $this->fetch();
    }

    public function add()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $post['business_id'] = $_SESSION['Msg']['business_id'];
            $post['sort'] = $post['sort'] + 0;
            $post['create_time'] = time();
            $res = Group::insert($post);
            if ($res) $this->success('添加成功');
            $this->error('添加失败！');
        }
        return $this->fetch();
    }

    public function remove()
    {
        $id = $this->request->get('id');
        $check = Db::name('wolive_service')->where(['groupid'=>$id])->find();
        if($check) $this->error('该分组下有客服，不能删除');
        if (Group::destroy(['id' => $id])) $this->success('操作成功！');
        $this->error('操作失败！');
    }
}