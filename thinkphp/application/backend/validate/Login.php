<?php
/**
 * Created by PhpStorm.
 * User: Andy
 * Date: 2020/1/29
 * Time: 9:41
 */


namespace app\backend\validate;

use think\Validate;

/**
 *
 * 登陆验证器.
 */
class Login extends Validate
{

    /**
     * 验证规则.
     * [$rule description]
     * @var array
     */
    protected $rule = [
        'username' => 'require',
        'password' => 'require',
        'captcha'  => 'require|captcha:backend_login'
    ];

    protected $field = [
        'username'  => '用户名',
        'password'   => '密码',
        'captcha' => '验证码',
    ];

    /**
     * 验证消息.
     * [$messege description]
     * @var [type]
     */
    protected $message = [
        'username.require' => '请填写登录帐号',
        'password.require' => '请填写登录密码',
        'captcha.require'  => '请填写验证码',
        'captcha.captcha'  => '验证码不正确'
    ];
}
