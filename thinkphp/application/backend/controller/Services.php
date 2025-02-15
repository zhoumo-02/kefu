<?php


namespace app\backend\controller;

use app\backend\model\Service;
use think\Db;

/**
 *
 * 后台页面控制器.
 */
class Services extends Base
{

    public function index()
    {
        if ($this->request->isAjax()) {
            return Service::getList();
        }
        return $this->fetch();
    }

    public function remove()
    {
        $id = $this->request->get('service_id');
        if (Service::destroy(['service_id' => $id])) $this->success('操作成功！');
        $this->error('操作失败！');
    }

    public function clear()
    {
        $id = $this->request->get('id');
        if (Db::name('wolive_chats')->where('service_id', $id)->delete()) {
            $this->success('操作成功！');
        }
        $this->error('操作失败！');
    }
}