<?php


namespace app\mobile\controller;

use think\Controller;
use think\Loader;
use think\Response;
use think\Session;
use app\admin\model\Admins;

/**
 * 基础验证是否登录.
 */
class Mbase extends Controller
{
    protected $base_root = null;

    /**
     * 验证session.
     *
     * @return void
     */
    public function _initialize()
    {

        if (empty($_SESSION['Msg']) && $this->request->action()!='login') {
            $this->redirect('Mobile/Mapp/login');
        }

        $login = $_SESSION['Msg'];
        $res =Admins::table('wolive_business')->where('id',$login['business_id'])->find();

        $group =Admins::table('wolive_group')->where('business_id',$login['business_id'])->select();

        $groupjson =json_encode($group);

        $data = json_encode($login);
        $app_key = app_key;

        $arr = parse_url(whost);

        if ($arr['scheme'] == 'ws') {
            $value = 'false';
            $port = 'wsPort';
        } else {
            $value = 'true';
            $port = 'wssPort';
        }
        $basename = request()->root();
        if (pathinfo($basename, PATHINFO_EXTENSION) == 'php') {
            $basename = dirname($basename);
        }
        $this->base_root = $basename;
        $this->assign('baseroot',$this->base_root);
        $this->assign('seo',$_SESSION['Msg']['business']);
        $this->assign('app_key', $app_key);
        $this->assign('whost',$arr['host']);
        $this->assign('value', $value);
        $this->assign('wport', wport);
        $this->assign('user', $login);
        $this->assign('port', $port);
        $this->assign('group',$groupjson);
        $this->assign('voice',$res['voice_state']);
        $this->assign('voice_address',$res['voice_address']);

    }

}
