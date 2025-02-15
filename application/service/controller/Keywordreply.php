<?php


namespace app\service\controller;

use app\service\model\Keywords;
use think\Db;

/**
 *
 * 后台页面控制器.
 */
class Keywordreply extends Base
{

    public function index()
    {
        if ($this->request->isAjax()) return Keywords::getList();
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
            $post['content']=$this->request->post('content','','\app\Common::clearXSS');
            $res = Keywords::where("id", $post['id'])->field(true)->update($post);
            if ($res) $this->success('修改成功');
            $this->error('修改失败！');
        }
        $id = $this->request->get('id');
        $robot = Keywords::get(['id'=>$id]);
        $this->assign('robot', $robot);
        $groups = Keywords::table('wolive_group')->column("groupname","id");
        $this->assign('groups', $groups);
        return $this->fetch();
    }

    public function add()
    {
        if ($this->request->isAjax()) {
            $post=$this->request->post();
            $post['content']=$this->request->post('content','','\app\Common::clearXSS');
            $res =Keywords::insert($post);
            if ($res) $this->success('添加成功');
            $this->error('添加失败！');
        }
        $groups = Keywords::table('wolive_group')->column("groupname","id");
        // var_dump($groups);exit;
        $this->assign('groups', $groups);
        return $this->fetch();
    }

    public function remove()
    {
        $id = $this->request->get('id');
        if (Keywords::destroy(['id' => $id])) $this->success('操作成功！');
        $this->error('操作失败！');
    }
}