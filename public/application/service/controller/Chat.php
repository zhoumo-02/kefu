<?php


namespace app\service\controller;

use think\Db;

/**
 *
 * 后台页面控制器.
 */
class Chat extends Base
{
    public function index()
    {
        $login = $_SESSION['Msg'];
        $res = Db::table('wolive_business')->where('id', $login['business_id'])->find();
        $service = Db::table('wolive_service')->where('service_id', $login['service_id'])->find();
        $this->assign("type", $res['video_state']);
        $this->assign("service", $service);
        $this->assign('atype', $res['audio_state']);
        return $this->fetch();
    }
}