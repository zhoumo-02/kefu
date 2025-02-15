<?php


namespace app\service\controller;

use app\service\model\Sentence;
use app\service\model\WechatPlatform;
use think\Db;
use app\service\model\Service;

/**
 *
 * 后台页面控制器.
 */
class Setting extends Base
{
    public function index()
    {
        $login = $_SESSION['Msg'];
        $template = WechatPlatform::get(['business_id' => $login['business_id']]);
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $update = ['lang' => $post['lang'], 'bd_trans_appid' => $post['bd_trans_appid'], 'bd_trans_secret' => $post['bd_trans_secret'], 'auto_trans' => $post['auto_trans'], 'auto_ip' => $post['auto_ip'], 'template_state' => $post['template_state']];
            Db::table('wolive_business')->where(['id' => $login['business_id']])->update($update);
            $template_data = ['business_id' => $login['business_id'], 'wx_id' => $post['wx_id'], 'app_id' => $post['app_id'], 'app_secret' => $post['app_secret'], 'wx_token' => $post['wx_token'], 'wx_aeskey' => $post['wx_aeskey'], 'visitor_tpl' => $post['visitor_tpl'], 'customer_tpl' => $post['customer_tpl'], 'msg_tpl' => $post['msg_tpl'], 'desc' => '无','addtime'=>time()];
            if($template){
                model('wechat_platform')->save($template_data,['business_id' => $login['business_id']]);
            }else{
                model('wechat_platform')->save($template_data);
            }
            $this->success("保存成功");
        }
        $business = Db::table('wolive_business')->where(['id' => $login['business_id']])->find();
        $this->assign('business', $business);
        $this->assign('template', $template);
        $this->assign('login', $login);
        return $this->fetch();
    }

    public function sentence()
    {
        if ($this->request->isAjax()) return Sentence::getList();
        return $this->fetch();
    }

    /**
     * description:
     * date: 2021/9/29 12:20
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function sentence_add()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $check = Sentence::get(['service_id' => $_SESSION['Msg']['service_id'], 'lang' => $post['lang']]);
            if ($check) $this->error('该语言已存在问候语！');
            $post['service_id'] = $_SESSION['Msg']['service_id'];
            $post['content'] = $this->request->post('content', '', '\app\Common::clearXSS');
            $res = Sentence::insert($post);
            if ($res) $this->success('添加成功');
            $this->error('添加失败！');
        }
        return $this->fetch();
    }

    /**
     * description:
     * date: 2021/9/29 12:12
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function sentence_edit()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $post['content'] = $this->request->post('content', '', '\app\Common::clearXSS');
            $res = Sentence::where("sid", $post['id'])->where('service_id', $_SESSION['Msg']['service_id'])->field(true)->update($post);
            if ($res) $this->success('修改成功');
            $this->error('修改失败！');
        }
        $id = $this->request->get('id');
        $robot = Sentence::get(['sid' => $id]);
        $this->assign('sentence', $robot);
        return $this->fetch();
    }

    public function sentence_remove()
    {
        $id = $this->request->get('id');
        if (Sentence::destroy(['sid' => $id])) $this->success('操作成功！');
        $this->error('操作失败！');
    }

    public function access()
    {
        $http_type = ((isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $web = $http_type . $_SERVER['HTTP_HOST'];
        $action = $web . request()->root();
        $login = $_SESSION['Msg'];
        $class = Db::table('wolive_group')->where('business_id', $login['business_id'])->select();
        $business = Db::table('wolive_business')->where('id', $login['business_id'])->find();
        $this->assign('class', $class);
        $this->assign('business', $login['business_id']);
        $this->assign('web', $web);
        $this->assign('login', $login);
        $this->assign('business', $business);
        $this->assign('action', $action);
        $this->assign("title", "接入方法");
        $this->assign("part", "接入方法");
        return $this->fetch();
    }

    public function course()
    {
        $this->assign("service", Service::getService());
        $this->assign("domain", $this->request->domain());
        $this->assign("business_id", $_SESSION['Msg']['business_id']);
        return $this->fetch();
    }
}