<?php


namespace app\service\controller;

use app\service\model\Admins;
use app\service\model\AdminLog;
use app\service\model\Business;
use think\Controller;
use think\captcha\Captcha;
use think\config;
use app\Common;
use app\extra\push\Pusher;
use think\Cookie;


/**
 * 登录控制器.
 */
class Login extends Controller
{
    private $business_id = null;

    public function _initialize()
    {
        $this->business_id = $this->request->param('business_id', Cookie::get('AIKF_APP_FLAG'));
        if (!empty($this->business_id)) Cookie::set('AIKF_APP_FLAG', $this->business_id);
        $this->assign('business_id', $this->business_id);
    }

    /**
     * 登陆首页.
     *
     * @return string
     */
    public function index()
    {
        $token = Cookie::get('service_token');
        if ($token) $this->redirect(url('service/index/index'));
        // 未登陆，呈现登陆页面.
        $params = [];
        $goto = $this->request->get('goto', '');
        if ($goto) $params['goto'] = urlencode($goto);
        $business = [];
        if ($this->business_id) $business = Business::get($this->business_id);
        $this->assign('business', $business);
        $this->assign('submit', url('check', $params));
        return $this->fetch();
    }


    /**
     * 验证码.
     *
     * @return \think\Response
     */
    public function captcha()
    {
        $captcha = new Captcha(Config::get('captcha'));
        ob_clean();
        return $captcha->entry('admin_login');
    }

    /**
     * 注册验证码.
     *
     * @return \think\Response
     */
    public function captchaForAdmin()
    {
        $captcha = new Captcha(Config::get('captcha'));
        return $captcha->entry('admin_regist');
    }

    /**
     * 登录检查.
     *
     * @return void
     */
    public function check()
    {
        $post = $this->request->post();
        if (!isset($post['username']) || !isset($post['password'])) $this->error('参数不完整!', url("/admin/login/index"));
        $post['user_name'] = htmlspecialchars($post['username']);
        $post["password"] = htmlspecialchars($post['password']);
        unset($post['username']);
        $result = $this->validate($post, 'Login');
        if ($result !== true) $this->error($result);
        $pass = md5($post['user_name'] . "hjkj" . $post['password']);
        $admin = Admins::table("wolive_service")
            ->where('user_name', $post['user_name'])
            ->where('password', $pass)
            ->find();
        if (!$admin) {
            $this->record_log('登录失败');
            $this->error('登录用户名或密码错误');
        }
        // 获取登陆数据
        $login = $admin->getData();
        // 删掉登录用户的敏感信息
        unset($login['password']);
        $res = Admins::table('wolive_service')->where('service_id', $login['service_id'])->update(['state' => 'online']);
        $_SESSION['Msg'] = $login;
        $business = Business::get($_SESSION['Msg']['business_id']);
        $_SESSION['Msg']['business'] = $business->getData();
        $common = new Common();
        $expire = 7 * 24 * 60 * 60;
        $service_token = $common->cpEncode($login['user_name'], AIKF_SALT, $expire);
        Cookie::set('service_token', $service_token, $expire);
        $ismoblie = $common->isMobile();
        $this->record_log('登录成功');
        if ($ismoblie) {
            $this->success('登录成功', url("mobile/admin/index"));
        } else {
            $this->success('登录成功', url("service/Index/index"));
        }
    }

    private function record_log($info)
    {
        $data = [
            'uid' => isset($_SESSION['Msg']['service_id']) ?$_SESSION['Msg']['service_id']: 0,
            'info' => $info,
            'ip' => $this->request->ip(),
            'user_agent' => $this->request->server('HTTP_USER_AGENT'),
            'create_time' => time(),
        ];
        AdminLog::table('wolive_admin_log')->insert($data);
    }

    /**
     * 退出登陆 并清除session.
     *
     * @return void
     */
    public function logout()
    {
        Cookie::delete('service_token');
        if (isset($_SESSION['Msg'])) {
            $login = $_SESSION['Msg'];
            // 更改状态
            Cookie::delete('service_token');
            setCookie("cu_com", "", time() - 60);
            $_SESSION['Msg'] = null;
        }
        $this->success('退出成功', url("service/Login/index"));

    }

    /**
     * socket_auth 验证
     * [auth description]
     * @return [type] [description]
     */
    public function auth()
    {
        $sarr = parse_url(ahost);
        if ($sarr['scheme'] == 'https') {
            $state = true;
        } else {
            $state = false;
        }
        $app_key = app_key;
        $app_secret = app_secret;
        $app_id = app_id;
        $options = array(
            'encrypted' => $state
        );
        $host = ahost;
        $port = aport;
        $pusher = new Pusher(
            $app_key,
            $app_secret,
            $app_id,
            $options,
            $host,
            $port
        );
        $data = $pusher->socket_auth($_POST['channel_name'], $_POST['socket_id']);
        return $data;
    }
}
