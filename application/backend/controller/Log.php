<?php


namespace app\backend\controller;

use app\backend\model\AdminLog;
use think\Db;
/**
 *
 * 后台页面控制器.
 */
class Log extends Base
{

    public function index()
    {
        if($this->request->isAjax()){
            return AdminLog::getLog();
        }
        return $this->fetch();
    }

    public function removeLog(){
        Db::name('wolive_admin_log')->delete(true);
        $this->success('操作成功');
    }
}