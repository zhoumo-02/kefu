<?php


namespace app\service\controller;

use app\service\model\Admins;
use app\service\model\AdminPermission;
use app\service\model\WechatPlatform;
use app\service\model\WechatService;
use think\Db;
use think\Paginator;
use app\Common;
/**
 *
 * 后台页面控制器.
 */
class Index extends Base
{

    /**
     * 后台首页.
     *
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }

    public function home(){
        $common = new Common();
        if ($common->isMobile()) $this->redirect('mobile/admin/index');
        $login = $_SESSION['Msg'];
        $time = date('Y-m-d', time());
        $t = strtotime(date('Y-m-d'));
        $times = date('Y-m-d H:i', time());
        $ftime = date('Y-m-d', time());
        $frtime = strtotime($ftime);
        $where = array('service_id' => $login['service_id']);
        if($login['level'] == 'super_manager') $where = array('business_id' => $login['business_id']);
        
        // 接入总量
        $getinall = Admins::table("wolive_chats")->distinct(true)->field('visiter_id')->where('business_id', $login['business_id'])->count();
        // 获取总会话量
        $chatsall = Admins::table("wolive_chats")->where($where)->count();
        // 正在排队人数
        $waiter = Admins::table("wolive_queue")->where(['business_id' => $login['business_id'], 'state' => 'normal'])->where("service_id", 0)->count();
        // 正在咨询的人
        $talking = Admins::table('wolive_queue')->where(['business_id' => $login['business_id']])->where('state', 'normal')->where("service_id", '<>', 0)->count();
        // 在线客服人数
        $services = Admins::table("wolive_service")->where($where)->where(['state' => 'online'])->count();
        // 今日会话量
        $nowchats = Admins::table("wolive_chats")->where($where)->where('timestamp', '>', "{$t}")->where('timestamp', '<=', time())->count();
        //今日评价人数
        $nowcomments = Admins::table("wolive_comment")->where($where)->where('add_time', '>', "{$time}")->where('add_time', '<=', $times)->count();
        //评价总数
        $allcomments = Admins::table("wolive_comment")->where($where)->count();
        
        $days = Common::getDays(15);
        foreach(array_reverse($days) as $k=>$v){
            $first = strtotime($v);
            $last = $first+86400-1;
            $chatsdata[$k]['date'] = date('m-d',strtotime($v));
            $chatsdata[$k]['chat'] = Admins::table('wolive_chats')->where($where)->where("timestamp BETWEEN $first AND $last")->count();
            $chatsdata[$k]['line'] = Admins::table('wolive_chats')->distinct(true)->field('visiter_id')->where($where)->where("timestamp BETWEEN $first AND $last")->count();
            $chatsdata[$k]['comment'] = Admins::table('wolive_comment')->where($where)->where("UNIX_TIMESTAMP(add_time) BETWEEN $first AND $last")->count();
        }
        $this->assign('nowcomments',$nowcomments);
        $this->assign('allcomments',$allcomments);
        $this->assign('chatsdata', $chatsdata);
        $this->assign('getinall', $getinall);
        $this->assign('waiter', $waiter);
        $this->assign('chatsall', $chatsall);
        $this->assign('talking', $talking);
        $this->assign('services', $services);
        $this->assign('nowchats', $nowchats);
        $this->assign("part", "首页");
        $this->assign('title', '首页');
        return $this->fetch();
    }

    public function menu(){
        $login = $_SESSION['Msg'];
        $where = array('is_admin' => 0);
        if($login['level'] == 'super_manager') $where = "";
        $common = new Common();
        $menu = AdminPermission::table('wolive_admin_permission')->where($where)->order('sort','asc')->select();
        return json($common->get_tree($menu));
    }

    private function get_tree($data, $pid = 0, $field1 = 'id', $field2 = 'pid', $field3 = 'children')
    {
        $arr = [];
        foreach ($data as $k => $v) {
            if ($v[$field2] == $pid) {
                $v[$field3] = self::get_tree($data, $v[$field1]);
                $arr[] = $v;
            }
        }
        return $arr;
    }

    public function pass(){
        if($this->request->isAjax()){
            $post =$this->request->post();
            $result = $this->validate($post, 'Check');
            if($result !== true) return ['code'=>0,'msg'=>$result];
            $user =Admins::table('wolive_service')->where("service_id",$_SESSION['Msg']['service_id'])->find();
            $pass = md5($user['user_name']."hjkj" . $post['oldpass']);
            if($user['password'] == $pass){
                $newpass =md5($user['user_name']."hjkj" . $post['newpass']);
                $res =Admins::table("wolive_service")->where("service_id",$user['service_id'])->update(["password"=>$newpass]);
                if($res) $this->success('修改成功');
                $this->error('修改失败！');
            }
            $this->error('旧密码不正确');
        }
        return $this->fetch();
    }

    public function cash(){
        $this->success('操作成功');
    }

    /**
     * 后台对话页面.
     *
     * @return mixed
     */
    public function chats()
    {
        $login = $_SESSION['Msg'];
        $res = Admins::table('wolive_business')->where('id', $login['business_id'])->find();
        $this->assign("type", $res['video_state']);
        $this->assign('atype', $res['audio_state']);
        $this->assign("title", "客户咨询");
        $this->assign('part', '客户咨询');
        return $this->fetch();
    }


    /**
     * 常用语页面.
     *
     * @return mixed
     */
    public function custom()
    {
        $login = $_SESSION['Msg'];
        $data = Admins::table("wolive_sentence")->where('service_id', $login['service_id'])->paginate(9);
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign('lister', $data);
        $this->assign('title', "问候语设置");
        $this->assign('part', "设置");

        return $this->fetch();
    }

    /**
     * 常见问题设置.
     *
     * @return mixed
     */
    public function question()
    {
        $login = $_SESSION['Msg'];
        if ($login['level'] == 'service') {
            $this->redirect('admin/index/index');
        }
        $data = Admins::table("wolive_question")
            ->where('business_id', $login['business_id'])
            ->order('sort desc')
            ->paginate();
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign('lister', $data);
        $this->assign('title', "常见问题设置");
        $this->assign('part', "设置");
        return $this->fetch();
    }


    /**
     * 生成前台文件页面.
     *
     * @return mixed
     */
    public function front()
    {
        $http_type = ((isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';

        $web = $http_type . $_SERVER['HTTP_HOST'];
        $action = $web.request()->root();

        $login = $_SESSION['Msg'];
        $class = Admins::table('wolive_group')->where('business_id', $login['business_id'])->select();

        $this->assign('class', $class);
        $this->assign('business', $login['business_id']);
        $this->assign('web', $web);
        $this->assign('login', $login);
        $this->assign('action', $action);
        $this->assign("title", "接入方法");
        $this->assign("part", "接入方法");

        return $this->fetch();
    }


    /**
     * 所有聊天记录页面。
     * [history description]
     * @return [type] [description]
     */
    public function history()
    {
        $visiter_id = $this->request->param('visiter_id');
        $this->assign('visiter_id',$visiter_id);
        return $this->fetch();
    }

    /**
     * 留言页面.
     *
     * @return mixed
     */
    private function message()
    {
        $login = $_SESSION['Msg'];
        $post = $this->request->get();
        $userAdmin = Admins::table('wolive_message');
        $pageParam = ['query' => []];
        unset($post['page']);
        if ($post) {
            $pushtime = $post['pushtime'];

            if ($pushtime) {
                if ($pushtime == 1) {
                    $timetoday = date("Y-m-d", time());
                    $userAdmin->where('timestamp', 'like', $timetoday . "%");
                    $this->assign('pushtime', $pushtime);
                    $pageParam['query']['timestamp'] = $pushtime;
                } elseif ($pushtime == 7) {
                    $timechou = strtotime("-1 week");
                    $times = date("Y-m-d", $timechou);
                    $userAdmin->where('timestamp', ">", $times);
                    $this->assign('pushtime', $pushtime);
                    $pageParam['query']['timestamp'] = $pushtime;
                }
            }
        }

        $data = $userAdmin->where('business_id', $login['business_id'])->paginate(8, false, $pageParam);
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign('msgdata', $data);
        $this->assign('title', "留言查看");
        $this->assign('part', "留言查看");

        return $this->fetch();
    }

    /**
     * 转接客服页面
     * @return [type] [description]
     */
    public function service()
    {

        $get = $_GET;

        $visiter_id = $_GET['visiter_id'];

        $login = $_SESSION['Msg'];

        $business_id = $login['business_id'];

        $res = Admins::table('wolive_service')->where('business_id', "{$business_id}")->where('service_id', '<>', $login['service_id'])->select();

        $this->assign('service', $res);
        $this->assign('visiter_id', $visiter_id);
        $this->assign('name', $get['name']);

        return $this->fetch();
    }
    
    /**
     * 转接客服类.
     * @return [type] [description]
     */
    public function getswitch()
    {
        $login = $_SESSION['Msg'];
        $post = $this->request->post();
        $admin = Admins::table('wolive_service')->where('service_id', $post['id'])->find();
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
        $channel = bin2hex($post['visiter_id'] . '/' . $login['business_id']);
        $pusher->trigger("cu" . $channel, 'getswitch', array('message' => $admin));
        $pusher->trigger('kefu' . $post['id'], 'getswitch', array('message' => $post['name'] . "  转接访客给你"));
        $result = Admins::table('wolive_queue')->where("visiter_id", $post['visiter_id'])->where('business_id', $login['business_id'])->where('state', 'normal')->update(['service_id' => $post['id']]);
        if ($result) {
            $arr = ['code' => 0, 'msg' => '转接成功！'];
            return $arr;
        } else {
            $arr = ['code' => 1, 'msg' => '转接失败！'];
            return $arr;
        }
    }

    public function servicejson()
    {
        $get = $_GET;

        $visiter_id = $_GET['visiter_id'];

        $login = $_SESSION['Msg'];

        $business_id = $login['business_id'];

        $res = Admins::table('wolive_service')->where('business_id', "{$business_id}")->where('service_id', '<>', $login['service_id'])->select();

        return json(['code'=>0,'data'=>['visiter_id'=>$visiter_id,'name'=>$get['name'],'service'=>$res]]);
    }

    /**
     * 常见问题编辑页面
     * [editer description]
     * @return [type] [description]
     */
    public function editer()
    {
        $login = $_SESSION['Msg'];
        if ($login['level'] == 'service') {
            $this->redirect('admin/index/index');
        }

        $get = $this->request->get();

        $res = Admins::table('wolive_question')
            ->where('qid', $get['qid'])
            ->order('sort desc')
            ->find();

        $this->assign('question', $res['question']);
        $this->assign('keyword',$res['keyword']);
        $this->assign('answer', $res['answer']);
        $this->assign('qid', $get['qid']);
        $this->assign('sort', $res['sort']);
        $this->assign('status', $res['status']);

        return $this->fetch();
    }
    /**
     * 常见问题编辑页面
     * [editer description]
     * @return [type] [description]
     */
    public function custom_editer()
    {
        $login = $_SESSION['Msg'];
        if ($login['level'] == 'service') {
            $this->redirect('admin/index/index');
        }

        $get = $this->request->get();
        $id=isset($get['id'])?$get['id']:0;

        $res = Admins::table('wolive_question')
            ->where('id', $get['id'])
            ->order('sort desc')
            ->find();

        $this->assign('question', $res['question']);
        $this->assign('keyword',$res['keyword']);
        $this->assign('answer', $res['answer_read']);
        $this->assign('qid', $get['qid']);
        $this->assign('sort', $res['sort']);
        $this->assign('status', $res['status']);

        return $this->fetch();
    }


    /**
     * 编辑tab页面
     * [editertab description]
     * @return [type] [description]
     */
    public function editertab()
    {

        $login = $_SESSION['Msg'];
        if ($login['level'] == 'service') {
            $this->redirect('admin/index/index');
        }

        $get = $this->request->get();

        $res = Admins::table('wolive_tablist')->where('tid', $get['tid'])->find();

        $this->assign('title', $res['title']);
        $this->assign('content', $res['content_read']);
        $this->assign('tid', $get['tid']);

        return $this->fetch();
    }

    public function editercustom()
    {
        $login = $_SESSION['Msg'];
        $get = $this->request->get();
$content='';
        $sid=0;
        if($get['sid']>0){
            $res = Admins::table('wolive_sentence')
                ->where('sid', $get['sid'])
                ->where('service_id',$login['service_id'])
                ->find();
            $content=$res['content'];
            $sid=$res['sid'];
        }
        $this->assign('content', $content);
        $this->assign('sid', $sid);

        return $this->fetch();
    }

    /**
     * 设置页面
     * [set description]
     */
    public function set()
    {

        $this->assign('user', $_SESSION['Msg']);
        $this->assign('title', '系统设置');
        $this->assign('part', '系统设置');
        return $this->fetch();
    }


    public function setup()
    {

        $login = $_SESSION['Msg'];
        if ($login['level'] == 'service') {
            $this->redirect('admin/index/index');
        }
        $res = Admins::table("wolive_business")->where('id', $login['business_id'])->find();

        $this->assign('video', $res['video_state']);
        $this->assign('audio', $res['audio_state']);
        $this->assign('voice', $res['voice_state']);
        $this->assign('voice_addr', $res['voice_address']);
        $this->assign('template', $res['template_state']);
        $this->assign('method', $res['distribution_rule']);
        $this->assign('push_url',$res['push_url']);
        $this->assign('title', '通用设置');
        $this->assign('part', '设置');

        return $this->fetch();
    }

    /**
     * tab面版页面。
     * [tablist description]
     * @return [type] [description]
     */
    public function tablist()
    {


        if ($_SESSION['Msg']['level'] == 'service') {
            $this->redirect('admin/index/index');
        }

        $business_id = $_SESSION['Msg']['business_id'];

        $res = Admins::table('wolive_tablist')->where('business_id', $business_id)->select();

        $this->assign('tablist', $res);

        $this->assign('title', '编辑前端tab面版');
        $this->assign('part', '设置');

        return $this->fetch();
    }


    /**
     *
     * [replylist description]
     * @return [type] [description]
     */
    public function replylist()
    {

        $id = $_SESSION['Msg']['service_id'];
        $res = Admins::table('wolive_reply')->where('service_id', $id)->paginate(8);
        $page = $res->render();
        $this->assign('page', $page);
        $this->assign('replyword', $res);

        return $this->fetch();
    }

    public function template()
    {
        $common = new Common();
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $post['business_id'] = $_SESSION['Msg']['business_id'];
            $post=$common->deep_array_map_trim($post);
            $res = WechatPlatform::edit($post);

            $arr = $res!== false ? ['code' => 0, 'msg' => '成功']: ['code' => 1, 'msg' => '失败'];
            return $arr;
        } else {
            $template = WechatPlatform::get(['business_id'=>$_SESSION['Msg']['business_id']]);

            $protocol=$common->isHTTPS()?'https://':'http://';
            $this->assign('template',$template);
            $this->assign('protocol',$protocol);
            $this->assign('title', '公众号与模板消息设置');
            $this->assign('part', "设置");
            return $this->fetch();
        }
    }

    public function qrcode()
    {
        $qrcode = WechatService::get()->qrcode;
//        fangke
        $result = $qrcode->temporary('kefu_'.$_SESSION['Msg']['service_id'], 6 * 24 * 3600);

        $ticket = $result->ticket;// 或者 $result['ticket']
        $url = $qrcode->url($ticket);
        return json(['code'=>0,'data'=>$url]);
    }

    public function test(){

    }

    public function trans(){
        if($this->request->isAjax()){
            $post = $this->request->post();
            $text = strip_tags($this->request->post('text','','\app\Common::clearXSS'));
            if(empty($text)) $this->error('翻译失败');
            $business = Db::table('wolive_business')->where(['id'=>$_SESSION['Msg']['business_id']])->field("bd_trans_appid,bd_trans_secret")->find();
            if(empty($business['bd_trans_appid'])||empty($business['bd_trans_secret'])) $this->error('请先配置百度翻译API接口');
            $to = 'zh';
            if(isset($post['to'])&&$post['to']) $to = config('lang_trans')[$post['to']];
            $salt = time();
            $sign = md5($business['bd_trans_appid'].$text.$salt.$business['bd_trans_secret']);
            $query = http_build_query([
                "q" => $text,
                "from" => 'auto',
                "to" => $to,
                "appid" => $business['bd_trans_appid'],
                "salt" => $salt,
                "sign" => $sign,
            ]);
            $res = file_get_contents("http://api.fanyi.baidu.com/api/trans/vip/translate?$query");
            $res = json_decode($res,true);
            if(!isset($res['error_code'])&&isset($res['trans_result'][0]['dst'])){
                if(isset($post['cid'])&&$post['cid']) Db::table('wolive_chats')->where("cid",$post['cid'])->update(["content_trans"=>$res['trans_result'][0]['dst']]);
                $this->success('翻译成功',"",$res['trans_result'][0]['dst']);
            }else{
                $this->error('翻译失败');
            }
        }
        $this->error('未知参数');
    }
}