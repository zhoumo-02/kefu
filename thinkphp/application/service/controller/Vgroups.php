<?php


namespace app\service\controller;

use app\service\model\Vgroup;
use think\Db;

/**
 *
 * 后台页面控制器.
 */
class Vgroups extends Base
{

    public function index()
    {
        if ($this->request->isAjax()) return Vgroup::getList();
        return $this->fetch();
    }

    /**
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function edit()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            if(mb_strlen($post['group_name'],'UTF8') > 20) $this->error('分组名不能多于12个字符');
            $group = Vgroup::get(['group_name'=>$post['group_name']]);
            if ($group) $this->error('该组名称已存在');
            $res = Vgroup::where("id", $post['id'])->field(true)->update($post);
            if ($res) $this->success('修改成功');
            $this->error('修改失败！');
        }
        $id = $this->request->get('id');
        $group = Vgroup::get(['id' => $id]);
        $this->assign('group', $group);
        return $this->fetch();
    }

    /**
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function add()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $post['business_id'] = $_SESSION['Msg']['business_id'];
            $post['service_id'] = $_SESSION['Msg']['service_id'];
            $post['create_time'] = date('Y-m-d H:i:s');
            if(mb_strlen($post['group_name'],'UTF8') > 20) $this->error('分组名不能多于12个字符');
            $group = Vgroup::get(['group_name'=>$post['group_name']]);
            if ($group) $this->error('该组名称已存在');
            $res = Vgroup::insert($post);
            if ($res) $this->success('添加成功');
            $this->error('添加失败！');
        }
        return $this->fetch();
    }

    public function remove()
    {
        $id = $this->request->get('id');
        $check = Db::name('wolive_queue')->where(['groupid'=>$id])->find();
        if($check) $this->error('该分组下有用户，不能删除');
        if (Vgroup::destroy(['id' => $id])) $this->success('操作成功！');
        $this->error('操作失败！');
    }
}