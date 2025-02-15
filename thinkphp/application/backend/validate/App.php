<?php
/**
 * Created by PhpStorm.
 * User: Andy
 * Date: 2020/1/30
 * Time: 17:12
 */

namespace app\backend\validate;

use think\Validate;

class App extends Validate
{

    /**
     * 验证规则.
     * [$rule description]
     * @var array
     */
    protected $rule = [
        'business_name' => 'require|length:3,16|alphaDash',
        'password'  => 'require|length:6,16',
        'max_count' => 'require|number',
    ];

    /**
     * 验证消息.
     * [$messege description]
     * @var [type]
     */
    protected $message = [
        'business_name.require' => '请填写商户名称',
        'business_name.length' => '商户名称为3~16个字符',
        'business_name.alphaDash' => '商户名称只能是字母、数字、下划线 _ ',
        'password.require' => '请填写登录密码',
        'password.length' => '密码长度为1~16个字符',
        'max_count.require' =>'请填写数量',
        'max_count.number' =>'客服数量只能是数字',
    ];

    protected $scene = [
        'edit' => ['business_name','max_count'],
        'insert' => ['business_name','password','max_count']
    ];
}