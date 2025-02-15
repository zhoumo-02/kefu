<?php
/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 2097984975@qq.com
 * Date: 2019/3/17
 * Time: 4:24 PM
 */
namespace app\backend\controller;

use think\Controller;
use think\config;
use think\captcha\Captcha;
use app\backend\model\Admins;

class Login extends Controller
{
    // 登录页面
    public function index()
    {
        return $this->fetch();
    }

    public function logout()
    {
        session('admin_user_name', null);
        session('admin_user_id', null);
        $this->success('退出成功', url("/backend/Login/index"));
    }

    public function check(){
        if(request()->isPost()) {
            $post = $this->request->post();
            $post['username'] = htmlspecialchars($post['username']);
            $post["password"] = htmlspecialchars($post['password']);
            $result = $this->validate($post, 'Login');
            if ($result !== true) $this->error($result);
            $pass = md5(md5($post["password"]) . $post['username']);
            $admin = Admins::table("wolive_admin")
                ->where('username', $post['username'])
                ->where('password', $pass)
                ->find();
            if (!$admin) $this->error('登录用户名或密码错误');
            // 获取登陆数据
            $login = $admin->getData();
            // 设置session标识状态
            session('admin_user_name', $login['username']);
            session('admin_user_id', $login['id']);
            $this->success('登录成功', url("/backend/index/index"));
        }
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
        return $captcha->entry('backend_login');
    }
}