<?php


namespace app\service\controller;

use app\service\model\Robot;
use think\Db;

/**
 *
 * 后台页面控制器.
 */
class Robots extends Base
{

    public function index()
    {
        if ($this->request->isAjax()) return Robot::getList();
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
            $post['reply']=$this->request->post('reply','','\app\Common::clearXSS');
            if (mb_strlen($post['keyword'],'UTF8') > 8) $this->error('关键词不能大于8个字！');
            $sort = $this->request->post('sort/d',0);
            if (!is_int($sort)) $this->error('排序字段必须是整数！');
            $status = $this->request->post('status/d',0);
            if (!is_int($status)) $this->error('匹配方式字段非法！');
            $res = Robot::where("id", $post['id'])->field(true)->update($post);
            if ($res) $this->success('修改成功');
            $this->error('修改失败！');
        }
        $id = $this->request->get('id');
        $robot = Robot::get(['id'=>$id]);
        $this->assign('robot', $robot);
        return $this->fetch();
    }

    public function add()
    {
        if ($this->request->isAjax()) {
            $post=$this->request->post();
            $post['business_id']=$_SESSION['Msg']['business_id'];
            $post['reply']=$this->request->post('reply','','\app\Common::clearXSS');
            if (mb_strlen($post['keyword'],'UTF8') > 8) $this->error('关键词不能大于8个字！');
            $sort = $this->request->post('sort/d',0);
            if (!is_int($sort)) $this->error('排序字段必须是整数！');
            $status = $this->request->post('status/d',0);
            if (!is_int($status)) $this->error('匹配方式字段非法！');
            $res =Robot::insert($post);
            if ($res) $this->success('添加成功');
            $this->error('添加失败！');
        }
        return $this->fetch();
    }

    public function remove()
    {
        $id = $this->request->get('id');
        if (Robot::destroy(['id' => $id])) $this->success('操作成功！');
        $this->error('操作失败！');
    }
}