<?php


namespace app\service\controller;

use app\service\model\Service;
use think\Db;
use app\service\model\Business;
use think\File;

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
        $group = Db::name("wolive_group")->where(['business_id'=>$_SESSION['Msg']['business_id']])->select();
        $this->assign('group', $group);
        return $this->fetch();
    }

    public function add()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $result = $this->validate($post, 'Services');
            if ($result !== true) $this->error('验证失败！');
            if ($post['nick_name'] == "") $post['nick_name'] = "客服" . $post['user_name'];
            $num = Service::where('business_id', $_SESSION['Msg']['business_id'])->count();
            $max = Business::where('id', $_SESSION['Msg']['business_id'])->value('max_count');
            if ($max != 0 && $num >= $max) $this->error('新增客服已经达到限制,不能再添加!');
            $service = Service::where('user_name', $post['user_name'])->find();
            if ($service) $this->error('该客服名已经存在!');
            unset($post['password2']);
            $post['parent_id'] = $_SESSION['Msg']['service_id'];
            $post["business_id"] = $_SESSION['Msg']['business_id'];
            $pass = md5($post['user_name'] . "hjkj" . $post["password"]);
            $post['password'] = $pass;
            $res = Service::field(true)->insert($post);
            if ($res) $this->success('添加成功');
            $this->error('添加失败！');
        }
        $group = Db::name("wolive_group")->select();
        $this->assign('group', $group);
        return $this->fetch();
    }

    public function edit()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            $res = Service::where("service_id", $post['id'])->field(true)->update($post);
            if ($res) $this->success('修改成功');
            $this->error('修改失败！');
        }
        $id = $this->request->get('id');
        $service = Service::where(['service_id' => $id])->find();
        $group = Db::name("wolive_group")->select();
        $this->assign('service', $service);
        $this->assign('group', $group);
        return $this->fetch();
    }

    public function upload_avatar()
    {
        $file = $this->request->file('file');
        if ($file) {
            $newpath = ROOT_PATH . "/public/upload/images/{$_SESSION['Msg']['business_id']}/";
            $info = $file->validate(['ext' => 'jpg,png,gif,jpeg'])->move($newpath, time());
            if ($info) {
                $imgname = $info->getFilename();
                $imgpath = $this->base_root . "/upload/images/{$_SESSION['Msg']['business_id']}/" . $imgname;
                $this->success('上传成功', '', $imgpath);
            } else {
                $this->error('上传失败！');
            }
        }
        $this->error('上传失败！');
    }

    public function pass()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            if ($post['newpass'] != $post['newpass2']) $this->error('新密码不一致');
            $result = $this->validate($post, 'Check.change_service_pwd');
            if ($result !== true) return ['code' => 0, 'msg' => $result];
            $user = Service::where("service_id", $post['id'])->find();
            $pass = md5($user['user_name'] . "hjkj" . $post['newpass']);
            $res = Service::table("wolive_service")->where("service_id", $post['id'])->update(["password" => $pass]);
            if ($res) $this->success('修改成功');
            $this->error('修改失败！');
        }
        return $this->fetch();
    }

    public function offline_first()
    {
        $post = $this->request->post();
        $result = Service::where('service_id', $post['service_id'])->update(['offline_first' => $post['offline_first']]);
        if ($result) $this->success('操作成功！');
        $this->error('操作失败！');
    }

    public function remove()
    {
        $id = $this->request->get('service_id');
        if (Service::destroy(['service_id' => $id,'business_id'=>$_SESSION['Msg']['business_id']])) $this->success('操作成功！');
        $this->error('操作失败！');
    }
}