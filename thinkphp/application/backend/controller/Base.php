<?php
/**
 * Created by PhpStorm.
 * User: Andy
 * Date: 2020/1/28
 * Time: 16:34
 */
namespace app\backend\controller;

use think\Controller;


class Base extends Controller
{


    public function _initialize()
    {
        parent::_initialize();
        if(empty(session('admin_user_name'))){
            $this->redirect(url('/backend/login/index'));
        }
        $this->assign([
            'admin_name' => session('admin_user_name'),
            'admin_id' => session('admin_user_id'),
        ]);
    }

}