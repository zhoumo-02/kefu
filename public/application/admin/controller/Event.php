<?php

namespace app\admin\controller;

use app\admin\model\Comment;
use app\admin\model\CommentDetail;
use app\admin\model\Distribute;
use app\admin\model\RestSetting;
use app\admin\model\TplService;
use app\admin\model\Visiter;
use app\admin\model\WechatPlatform;
use app\common\lib\Storage;
use app\common\lib\storage\StorageException;
use app\platform\model\Admin;
use app\platform\model\Business;
use app\platform\model\Service;
use EasyWeChat\Core\Exception;
use think\Controller;
use app\admin\model\Admins;
use app\extra\push\Pusher;
use app\admin\iplocation\Ip;
use EasyWeChat\Foundation\Application;
use think\Db;
use think\Log;
use think\Request;
use think\Session;
use think\Lang;

header('Access-Control-Allow-Origin:*');
/**
 * 跨域公用控制器.
 */
class Event extends Controller
{
    protected $base_root = null;

    protected $lang_array = [];

    public function _initialize()
    {
        parent::_initialize();
        $basename = request()->root();
        if (pathinfo($basename, PATHINFO_EXTENSION) == 'php') {
            $basename = dirname($basename);
        }
        $this->base_root = $basename;
        if($this->request->param('business_id')&&$this->request->param('visiter_id')){
            $business_id = $this->request->param('business_id');
            $visiter_id = $this->request->param('visiter_id');
            $business = Db::table('wolive_business')->where('id', $business_id)->find();
            $visiter_lang = Db::name('wolive_visiter')->where('visiter_id', $visiter_id)->value('lang');
            if($visiter_lang){
                $business['lang'] = $visiter_lang;
            }else{
                if(session('user_lang')) $business['lang'] = session('user_lang');
            }
            $this->lang_array = Lang::load(APP_PATH.'lang/'.$business['lang'].'.php');
        }else{
            if(session('user_lang')) $this->serverLang = session('user_lang');
            $this->lang_array = Lang::load(APP_PATH.'lang/'.$this->serverLang.'.php');
        }
    }

    /**
     * 离线，在线监控类.
     *
     * @returngetanswer void
     */
    public function index()
    {
        // pusher 访问的 地址
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

        $webhook_signature = $_SERVER ['HTTP_X_PUSHER_SIGNATURE'];

        $body = file_get_contents('php://input');
        $expected_signature = hash_hmac('sha256', $body, $app_secret, false);

        if ($webhook_signature == $expected_signature) {

            $payload = json_decode($body, true);
            foreach ($payload['events'] as $event) {
                Log::error('ces1:'.json_encode($event));
                // 通知离线
                if ($event['name'] == 'channel_removed') {
                    // 客服 离线
                    if (strpos($event['channel'], 'kefu') === 0) {
                        $channel = str_replace('kefu', 'se', $event['channel']);
                        $id = str_replace('kefu', '', $event['channel']);
                        $pusher->trigger($channel, 'logout', array('message' => $this->lang_array['service_offline']));
                        $res = Admins::table('wolive_service')->where('service_id', $id)->update(['state' => 'offline']);

                    } elseif (strpos($event['channel'], 'cu') === 0) {
                        // 访客 离线
                        $channel = str_replace('cu', '', $event['channel']);

                        $newstr = pack("H*", $channel);

                        $data = explode("/", $newstr);
                        $res = Admins::table("wolive_queue")->where(['visiter_id' => $data[0], 'business_id' => $data[1]])->find();
                        Admins::table("wolive_queue")->where(['visiter_id' => $data[0], 'business_id' => $data[1]])->update(['remind_tpl'=>0,'remind_comment'=>0]);
                        $id = $res['service_id'];

                        $arr = array(
                            'chas' => $channel
                        );
                        $pusher->trigger("kefu" . $id, 'logout', array('message' => $arr));


                        $res2 = Admins::table("wolive_visiter")->where("channel", $channel)->update(["state" => 'offline']);
                    }
                }

                // 通知在线
                if ($event["name"] == "channel_added") {

                    if (strpos($event['channel'], 'kefu') === 0) {
                        // 通知 访客，客服在线
                        $channel = str_replace('kefu', 'se', $event['channel']);
                        $id = str_replace('kefu', '', $event['channel']);
                        $res = Admins::table('wolive_service')->where('service_id', $id)->update(['state' => 'online']);
                        $pusher->trigger($channel, 'geton', array('message' => $this->lang_array['service_online']));

                    } elseif (strpos($event['channel'], 'cu') === 0) {
                        // 通知 客服 ，访客在线
                        $channel = str_replace('cu', '', $event['channel']);

                        $newstr = pack("H*", $channel);

                        $data = explode("/", $newstr);

                        $res = Admins::table("wolive_queue")->where(['visiter_id' => $data[0], 'business_id' => $data[1]])->find();

                        $id = $res['service_id'];
                        $arr = array(
                            'chas' => $channel
                        );
                        $pusher->trigger("kefu" . $id, 'geton', array('message' => $arr));

                        $res2 = Admins::table("wolive_visiter")->where("channel", $channel)->update(["state" => 'online']);
                    }
                }
            }
            header("Status: 200 OK");
        } else {
            header("Status: 401 Not authenticated");
        }
    }


    /**
     *  注册接口
     *
     * @return string
     */
    public function registApi()
    {

        $post = $_POST;

        $time = time();

        if ($time - $post['timestamp'] > 60) {

            $data = ['code' => 1, 'msg' => '注册验证超时！'];
            return json_encode($data);
        }

        $token = md5($post['user_name'] . registToken . $post['timestamp']);

        if ($token == $post['token']) {

            if (!$post['nick_name']) {
                $post['nick_name'] = "管理员" . $post['user_name'];
            }

            if (!$post['business_id']) {
                $post['business_id'] = $post['user_name'];
            }

            $arr = [
                'user_name' => $post['user_name'],
                'nick_name' => $post['nick_name'],
                'avatar' => $post['avatar'],
                'business_id' => $post['business_id'],
                'password' => $post['password'],
            ];

            $debug = Admins::table('wolive_service')->insert($arr);

            $data = ['code' => 0, 'msg' => 'success'];

            return json_encode($data);

        } else {

            $data = ['code' => 1, 'msg' => '注册token验证失败！'];

            return json_encode($data);
        }
    }


    /**
     *  微信前台对话pusher类.
     *
     * @return void
     */
    public function chat()
    {
        $arr = $_POST;

        if (!ahost) {
            $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';

            $domain = $http_type . $_SERVER['HTTP_HOST'];
        } else {
            $domain = ahost;
        }

        $sarr = parse_url($domain);


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
        $host = $domain;
        $port = aport;

        $pusher = new Pusher(
            $app_key,
            $app_secret,
            $app_id,
            $options,
            $host,
            $port
        );

        $service = Admins::table('wolive_queue')->where('business_id', $arr['business_id'])->where('visiter_id', $arr['visiter_id'])->where('state', 'normal')->find();

        $visiter_lang = Db::name('wolive_visiter')->where('visiter_id', $arr['visiter_id'])->value('lang');
        if($visiter_lang){
            $lang = $visiter_lang;
        }else{
            $lang = Db::table('wolive_business')->where('id', $arr['business_id'])->value('lang');
            if(session('user_lang')) $lang = session('user_lang');
        }

        $banword = Db::table('wolive_banword')->where(['id'=>$arr['business_id'],'lang'=>$lang,'status'=>1])->whereLike('keyword',"%{$arr['content']}%")->find();
        if($banword) return array('code' => 1, 'msg' => $this->lang_array['banword_tip']);

        $service_id = $service ? $service['service_id'] : null;
        if ($service_id != $arr['service_id']) {
            if (!empty($service_id)) {
                $rebot_int = $this->lang_array['robot_error'];
                //机器人自动回复
                $robot = Db::table('wolive_robot')
                    ->where('business_id', $arr['business_id'])
                    ->where('lang', $lang)
                    ->whereLike('keyword',"%{$arr['content']}%")
                    ->order('sort asc')
                    ->find();

                if($robot){
                    if(($robot['status'] == 1&&$arr['content']==$robot['keyword'])||$robot['status'] == 0){
                        $reply = $robot['reply'];
                    }else{
                        $reply = $rebot_int[array_rand($rebot_int,1)];
                    }
                }else{
                    $reply = $rebot_int[array_rand($rebot_int,1)];
                }
                
                $channel = bin2hex($arr['visiter_id'] . '/' . $arr['business_id']);
                $robot_arr = array(
                    'visiter_id' => $arr['visiter_id'],
                    'content' => $reply,
                    'unstr' => md5(uniqid()),
                    'business_id' => $arr['business_id'],
                    'service_id' => $arr['service_id'],
                    'direction' => 'to_visiter',
                    'timestamp' => time()+5,
                    'type' => 2,
                );
                $cid = Db::table('wolive_chats')->insertGetId($robot_arr);
                $robot_arr['cid'] = $cid;
                $robot_arr['avatar'] = '/assets/images/index/ai_service.png';
                $pusher->trigger("cu" . $channel, 'my-event', array('message' => $robot_arr));
                
                $returndata = ['code' => 0, 'msg' => 'success'];
                return $returndata;
            } else {
                // 客服关闭了对话框，重新设置为打开
                $data = ['state' => 'normal'];
                $qid = Admins::table('wolive_queue')->where('business_id', $arr['business_id'])->where('visiter_id', $arr['visiter_id'])->where('state', 'complete')->order('qid', 'desc')->value('qid');
                if ($qid) {
                    Admins::table('wolive_queue')->where('qid', $qid)->update($data);
                    $service_id = $arr['service_id'];
                } else {
                    $returndata = ['code' => 1, 'msg' => $this->lang_array['say_is_off'], 'id' => $service_id];
                    return $returndata;
                }
            }
        }

        $arr["timestamp"] = time();
        $arr['service_id'] = $service_id;


        function extract_attrib($tag)
        {
            preg_match_all('/(id|alt|title|src)=("[^"]*")/i', $tag, $matches);

            $ret = array();
            foreach ($matches[1] as $i => $v) {
                $ret[$v] = $matches[2][$i];
            }
            return $ret;
        }

        if (!isset($arr['debug'])) {

            $content = $arr["content"];
            $values = preg_match_all('/<img.*\>/isU', $content, $out);


            if ($values) {

                $img = $out[0];

                if ($img) {

                    $chats = "";

                    foreach ($img as $v) {

                        $attr = extract_attrib($v);

                        $src = $attr["src"];

                        if ($src) {
                            if (strpos($src, "emo_")) {
                                $newimg = "<img src={$src}>";
                            } else {
                                $newimg = "<img  src={$src}>";
                            }
                        }
                        $chats .= $newimg;
                    }

                }
            } else {
                $chats = "";
            }
            $newstr = preg_replace('/<img.*\>/isU', "", $content);

            $values2 = preg_match_all('/<audio.*\>/isU', $content, $out);

            $vas = preg_match_all('/<a.*\>/isU', $content, $array2);

            if (!$values2 && !$vas) {

                $newstr = htmlspecialchars($newstr);
            }

            $arr["content"] = $chats . $newstr;
        }

        try {
            // 推送消息
            $visiter = Db::table('wolive_visiter')->where('visiter_id',$arr['visiter_id'])->find();
            $arr['channel']=$visiter['channel'];
            $pusher->trigger('kefu' . $service_id, 'cu-event', array('message' => $arr));

            $arr['direction'] = 'to_service';
            unset($arr['record']);
            unset($arr['avatar']);
            unset($arr['channel']);

            if (isset($arr['debug'])) {
                unset($arr['debug']);
            }
            Admins::table('wolive_chats')->insert($arr);

            //机器人自动回复
            $robot = Db::table('wolive_robot')
                ->where('business_id', $arr['business_id'])
                ->where('lang', $lang)
                ->whereLike('keyword',"%{$arr['content']}%")
                ->order('sort asc')
                ->find();
            if($robot){
                if(($robot['status'] == 1&&$arr['content']==$robot['keyword'])||$robot['status'] == 0){
                    $channel = bin2hex($arr['visiter_id'] . '/' . $arr['business_id']);
                    $robot_arr = array(
                        'visiter_id' => $arr['visiter_id'],
                        'content' => $robot['reply'],
                        'unstr' => md5(uniqid()),
                        'business_id' => $arr['business_id'],
                        'service_id' => $arr['service_id'],
                        'direction' => 'to_visiter',
                        'timestamp' => time()+5,
                        'type' => 2,
                    );
                    $cid = Db::table('wolive_chats')->insertGetId($robot_arr);
                    $robot_arr['cid'] = $cid;
                    $robot_arr['avatar'] = '/assets/images/index/ai_service.png';
                    $pusher->trigger("cu" . $channel, 'my-event', array('message' => $robot_arr));
                }
            }

            $wechat = WechatPlatform::get(['business_id'=>$arr['business_id']]);
            $service_data = Service::get($service_id);
            $business = Business::get($arr['business_id']);
            $sended = $service['remind_tpl'];
            //改成离线状态接收通知
            if (empty($sended) && $business['template_state']=='open') {
                TplService::send($arr["business_id"],$service_data['open_id'],url('weixin/login/callback',['business_id'=>$arr['business_id'],'service_id'=>$service_id],true,true),$wechat['msg_tpl'],[
                    "first"  => "你有一条新的客户信息!",
                    "keyword1"   => $visiter["visiter_name"] ?$visiter["visiter_name"]:'游客'.$arr['visiter_id'],
                    "keyword2"  => date('Y-m-d H:i:s',time()),
                    "keyword3"  => $arr["content"],
                    "remark" => $business['business_name']."提示:客户等不及啦,快去回复吧~",
                ]);
                Admins::table('wolive_queue')->where('business_id', $arr['business_id'])->where('visiter_id', $arr['visiter_id'])->update(['remind_tpl'=>1]);
            }

            $data = ['code' => 0, 'msg' => 'success', 'data' => ['getui' => isset($getui_ret) ? $getui_ret : (isset($getui_e) ? $getui_e : '')]];

            if (isset($arr['debug'])) {
                return json_encode($data);
            } else {
                return $data;
            }

        } catch (\Exception $e) {

            $error = $e->getMessage();
            $data = ['code' => 0, 'msg' => $error, 'data' => ['getui' => isset($getui_ret) ? $getui_ret : (isset($getui_e) ? $getui_e : '')]];
            return $data;

        }

    }


    /**
     * 前台寻求客服对话类.
     *
     * @return mixed
     */
    public function notice()
    {

//        $ip = $_SERVER["REMOTE_ADDR"];
        $ip = $this->request->ip();

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

        $arr = $this->request->post();

        if (!isset($arr['visiter_id'])  || !isset($arr['business_id'])) {

            $returndata = ['code' => 1, 'msg' => $this->lang_array['data_error']];
            return $returndata;
        }

        $arr["visiter_name"] = htmlspecialchars($arr['visiter_name']);
        $arr["business_id"] = htmlspecialchars($arr['business_id']);
        $arr["from_url"] = htmlspecialchars($arr['from_url']);
        $arr["avatar"] = htmlspecialchars($arr['avatar']);
        $arr['visiter_id'] = htmlspecialchars($arr['visiter_id']);
        $arr["channel"] = bin2hex($arr['visiter_id'] . '/' . $arr['business_id']);
        $arr['ip'] = $ip;
        include VENDOR.'phpuseragent/lib/phpUserAgent.php';
        include VENDOR.'phpuseragent/lib/phpUserAgentStringParser.php';
        $ua = new \phpUserAgent();
        $arr['extends'] = json_encode(['browserName'    => $ua->getBrowserNameCn(),
            'browserVersion' => $ua->getBrowserVersion(),
            'os'             => $ua->getOperatingSystemCn(),
            'engine'         => $ua->getEngine()]);

        $business = Admins::table('wolive_business')->where('id', $arr['business_id'])->find();
        Db::table("wolive_visiter")->where("channel", $arr["channel"])->update(["state" => 'online']);
        $visiter_id = $arr['visiter_id'];
        $business_id = $arr['business_id'];
        $special = isset($arr['special']) ? $arr['special']:null;

        $visiter_lang = Db::name('wolive_visiter')->where('visiter_id', $visiter_id)->value('lang');
        if($visiter_lang){
            $lang = $visiter_lang;
        }else{
            $lang = $business['lang'];
            if($business['auto_ip']) $lang = Ip::check_country($ip)?:$business['lang'];
            if(session('user_lang')) $lang = session('user_lang');
        }
        $arr['lang'] = $lang;

        if ($business['state'] == 'close') {
            $returndata = ['code' => 1, 'msg' => $this->lang_array['service_ban']];
            return $returndata;
        }

        if ($business['distribution_rule'] == 'claim') {
            //认领模式

            $visiter = Admins::table('wolive_visiter')->where(['visiter_id' => $visiter_id, 'business_id' => $business_id])->find();

            if ($visiter) {
                //老用户
                $service = Admins::table('wolive_queue')->where(['visiter_id' => $visiter_id, 'business_id' => $business_id])->where('state', 'normal')->find();

                if (!$service) {

                    $data = ['visiter_id' => $visiter_id, 'service_id' => 0, 'business_id' => $business_id, 'groupid' => $arr['groupid']];

                    $qu = Admins::table("wolive_queue")->where(['visiter_id' => $visiter_id, 'business_id' => $business_id, 'service_id' => 0, 'state' => 'normal'])->find();

                    if ($qu) {

                        $queue = Admins::table("wolive_queue")->where('qid', $qu['qid'])->update(['groupid' => $arr['groupid'], 'service_id' => 0]);

                    } else {

                        $queue = Admins::table("wolive_queue")->insert($data);
                    }

                    $num = Admins::table('wolive_queue')->where(['service_id' => 0, 'business_id' => $business_id, 'groupid' => $arr['groupid']])->where('state', 'normal')->count();

                    $class = Admins::table('wolive_group')->where('id', $arr['groupid'])->find();

                    $allnotice = ['msg' => "公告:" . $arr['visiter_name'] . "需要" . $class['groupname'] . "的咨询", 'groupid' => $arr['groupid']];

                    $pusher->trigger("all" . $arr['business_id'], 'on_notice', array('message' => $allnotice));


                    $returndata = ['code' => 2, 'msg' => '等待认领！', 'data' => $num];
                    return $returndata;
                }


                // 替换成最新头像
                $newvisiter = Admins::table('wolive_visiter')->where(['visiter_id' => $visiter_id, 'business_id' => $business_id])->update(array_filter(['avatar' => $arr['avatar'],'visiter_name' => $arr['visiter_name']]));
                // 最后一次服务的客服id
                $service_id = $service['service_id'];


                if ($arr['groupid']) {
                    $service_data = Admins::table("wolive_service")->where('service_id', $service_id)->where('groupid', ['=', 0], ['=', $arr['groupid']], 'or')->find();
                } else {

                    $service_data = Admins::table("wolive_service")->where('service_id', $service_id)->find();

                }

                $queue = Admins::table("wolive_queue")->where('visiter_id', $visiter_id)->where('business_id', $business_id)->update(['groupid' => $arr['groupid']]);

                if ($service_data) {

                    $state = $service_data['state'];

                    if ($state == 'online') {

                        $words = Admins::table('wolive_sentence')->where("lang",$lang)->where("service_id", $service_id)->where('state', 'using')->find();

                        if ($words&&$words['content']) {
                            $service_data['content'] = htmlspecialchars_decode($words['content']);
                        } else {
                            $service_data['content'] = $this->lang_array['hello'];
                        }

                        $returndata = ['code' => 0, 'msg' => 'success', 'data' => $service_data];

                        return $returndata;

                    } else {

                        $returndata = ['code' => 4, 'msg' => $this->lang_array['service_leave'], 'data' => $service_data];
                        return $returndata;
                    }

                } else {
                    //换了分组
                    $qid = $service['qid'];

                    $res = Admins::table('wolive_queue')->where('qid', $qid)->update(['groupid' => $arr['groupid'], 'service_id' => 0, 'state' => 'normal']);


                    $num = Admins::table('wolive_queue')->where(['service_id' => 0, 'business_id' => $business_id, 'groupid' => $arr['groupid']])->where('state', 'normal')->count();

                    $class = Admins::table('wolive_group')->where('id', $arr['groupid'])->find();

                    $allnotice = ['msg' => "公告:" . $arr['visiter_name'] . "需要" . $class['groupname'] . "的咨询", 'groupid' => $arr['groupid']];

                    $pusher->trigger("all" . $arr['business_id'], 'on_notice', array('message' => $allnotice));

                    $returndata = ['code' => 2, 'msg' => '等待认领！', 'data' => $num];
                    return $returndata;
                }

            } else {
                // 新用户
                $arr['state'] = 'offline';
                $data = ['visiter_id' => $visiter_id, 'service_id' => 0, 'business_id' => $business_id, 'groupid' => $arr['groupid']];
                $class = Admins::table('wolive_group')->where('id', $arr['groupid'])->find();


                $qu = Admins::table("wolive_queue")->where(['visiter_id' => $visiter_id, 'business_id' => $business_id, 'service_id' => 0, 'state' => 'normal'])->find();
                if ($qu) {
                    $queue = Admins::table("wolive_queue")->where('qid', $qu['qid'])->update(['groupid' => $arr['groupid'], 'service_id' => 0]);
                } else {
                    $queue = Admins::table("wolive_queue")->insert($data);
                }

                $num = Admins::table('wolive_queue')->where(['service_id' => 0, 'business_id' => $business_id, 'groupid' => $arr['groupid']])->where('state', 'normal')->count();

                $groupid = $arr['groupid'];
                unset($arr['groupid']);
                unset($arr['special']);
                $newvisiter = Admins::table('wolive_visiter')->insert($arr);
                $allnotice = ['msg' => "公告:" . $arr['visiter_name'] . "需要" . $class['groupname'] . "的咨询", 'groupid' => $groupid];

                $pusher->trigger("all" . $arr['business_id'], 'on_notice', array('message' => $allnotice));

                $returndata = ['code' => 2, 'msg' => '等待认领！', 'data' => $num];
                return $returndata;
            }
        } else {
            // 智能分配
            $visiter = Admins::table('wolive_visiter')->where(['visiter_id' => $visiter_id, 'business_id' => $business_id])->find();

            if ($visiter) {
                // 替换成最新头像 'login_times'=> ['exp','login_times+1']
                Admins::table('wolive_visiter')->where(['visiter_id' => $visiter_id, 'business_id' => $business_id])->update(array_filter(['avatar' => $arr['avatar'],'login_times'=>Db::raw('login_times+1'),'visiter_name' => $arr['visiter_name']]));
                // 老用户
                $service = Admins::table('wolive_queue')->where(['visiter_id' => $visiter_id, 'business_id' => $business_id])->find();
                //最后服务id
                $service_id = $service['service_id'];
                $service_data = Admins::table("wolive_service")->field('avatar,business_id,email,open_id,groupid,nick_name,service_id,state')->where('service_id', $service_id)->where('groupid', $arr['groupid'])->find();


                //如果被客服拉黑了
                if ($service['state'] == 'in_black_list') {
                    $service_data = Admins::table("wolive_service")->field('avatar,business_id,email,open_id,groupid,nick_name,service_id,state')->where('service_id', $service_id)->find();
                    $service_data['content'] = $this->lang_array['hello'];
                    unset($service_data['open_id']);
                    $returndata = ['code' => 0, 'msg' => 'success', 'data' => $service_data];
                    return $returndata;
                }

                if ($service_data) {
                    if ($service_data['state'] == 'offline' ) {
                        $serv = Distribute::run($business_id,'online',$arr['groupid']);
                        !empty($serv) && $service_data = $serv;
                    }

                    if (!empty($special)) {
                        $service_data = Service::get(['service_id' => $special,'business_id'=>$arr["business_id"]]);
                        if (empty($service_data)) {
                            $res = ['code' => 1, 'msg' => $this->lang_array['service_empty']];
                            return $res;
                        }
                        unset($service_data['password']);
                    }
                    
                    $data = ['state' => 'normal', 'service_id' => $service_data['service_id'], 'groupid' => $arr['groupid']];
                    $queue = Admins::table('wolive_queue')->where('qid', $service['qid'])->update($data);
                    $words = Admins::table('wolive_sentence')->where("lang",$lang)->where("service_id", $service_data['service_id'])->where('state', 'using')->find();

                    if ($words&&$words['content']) {
                        $service_data['content'] = htmlspecialchars_decode($words['content']);;
                    } else {
                        $service_data['content'] = $this->lang_array['hello'];
                    }
                    unset($service_data['open_id']);
                    $returndata = ['code' => 0, 'msg' => 'success', 'data' => $service_data];
                    return $returndata;

                } else {
                    // 不存在
                    if (!empty($special)) {
                        $service_data = Service::get(['service_id' => $special,'business_id'=>$arr["business_id"]]);
                        if (empty($service_data)) {
                            $res = ['code' => 1, 'msg' => $this->lang_array['service_empty']];
                            return $res;
                        }
                        unset($service_data['password']);
                    } else {
                        $service_data = Distribute::run($business_id,'online',$arr['groupid']);

                        if(!$service_data) {
                            $service_data = Distribute::run($business_id,null,$arr['groupid']);
                        }
                    }

                    if (empty($service_data)) {
                        $res = ['code' => 1, 'msg' => $this->lang_array['group_service_offline'], 'data' => $service_data];
                        return $res;
                    }
                    $gid = $arr['groupid'];

                    $res = Admins::table('wolive_queue')->where(['visiter_id' => $visiter_id, 'business_id' => $business_id, 'state' => 'normal'])->find();


                    if ($res) {

                        $queue = Admins::table("wolive_queue")->where('qid', $res['qid'])->update(['service_id' => $service_data['service_id'], 'groupid' => $arr['groupid']]);


                    } else {
                        $data = ['visiter_id' => $visiter_id, 'service_id' => $service_data['service_id'], 'business_id' => $business_id, 'groupid' => $gid];

                        $queue = Admins::table("wolive_queue")->insert($data);
                    }

                    // 推送游客信息
                    $pusher->trigger("ud" . $service_data['service_id'], 'on_notice', array('message' => $arr));

                    $words = Admins::table('wolive_sentence')->where("lang",$lang)->where("service_id", $service_data['service_id'])->where('state', 'using')->find();

                    if ($words&&$words['content']) {
                        $service_data['content'] = htmlspecialchars_decode($words['content']);;
                    } else {
                        $service_data['content'] = $this->lang_array['hello'];
                    }

                    unset($service_data['open_id']);
                    $returndata = ['code' => 0, 'msg' => 'success', 'data' => $service_data];
                    return $returndata;

                }

            } else {
                // 新用户
                $arr['state'] = 'offline';
                $gid = $arr['groupid'];
                unset($arr['groupid']);
                unset($arr['special']);
                $newvisiter = Admins::table('wolive_visiter')->insert($arr);

                if (!empty($special)) {
                    $service_data = Service::get(['service_id' => $special,'business_id'=>$arr["business_id"]]);
                    if (empty($service_data)) {
                        $res = ['code' => 1, 'msg' => $this->lang_array['service_empty']];
                        return $res;
                    }
                    unset($service_data['password']);
                } else {
                    $service_data = Distribute::run($business_id,'online',$gid);
                    if(!$service_data) {
                        $service_data = Distribute::run($business_id,null,$gid);
                    }
                }

                if (empty($service_data)) {
                    $res = ['code' => 1, 'msg' => $this->lang_array['group_service_offline'], 'data' => $service_data];
                    return $res;
                }

                $data = ['visiter_id' => $visiter_id, 'service_id' => $service_data['service_id'], 'business_id' => $business_id, 'groupid' => $gid];

                $qu = Admins::table("wolive_queue")->where(['visiter_id' => $visiter_id, 'business_id' => $business_id, 'state' => 'normal'])->find();
                if ($qu) {
                    $qu = Admins::table("wolive_queue")->where('qid', $qu['qid'])->update(['groupid' => $gid, 'service_id' => $service_data['service_id']]);
                } else {
                    $queue = Admins::table("wolive_queue")->insert($data);
                }

                // 推送游客信息
                $pusher->trigger("ud" . $service_data['service_id'], 'on_notice', array('message' => $arr));

                $words = Admins::table('wolive_sentence')->where("lang",$lang)->where("service_id", $service_data['service_id'])->where('state', 'using')->find();

                if ($words&&$words['content']) {
                    $service_data['content'] = htmlspecialchars_decode($words['content']);;
                } else {
                    $service_data['content'] = $this->lang_array['hello'];
                }

                Admins::table('wolive_chats')->where(['visiter_id' => $visiter_id, 'service_id' => 0])->where('business_id', $business_id)->update(['service_id' => $service_data['service_id']]);

                $returndata = ['code' => 0, 'msg' => 'success', 'data' => $service_data];

                $wechat = WechatPlatform::get(['business_id'=>$business_id]);
                if($business['template_state'] == 'open'){
                    try {
                        TplService::send($arr["business_id"],$service_data['open_id'],url('weixin/login/callback',['business_id'=>$arr['business_id'],'service_id'=>$service_data['service_id']],true,true),$wechat['visitor_tpl'],[
                            "first"  => "您有新访客！",
                            "keyword1"   => $arr["visiter_name"] ?$arr["visiter_name"]:'游客'.$arr['visiter_id'],
                            "keyword2"  => date('Y-m-d H:i:s',time()),
                            "remark" => $business['business_name']."提示:有新客户啦,快去撩一把~",
                        ]);
                    }catch (\Exception $exception) {
                        Log::error($exception->getMessage());
                    }
                }
                unset($service_data['open_id']);
                return $returndata;
            }
        }
    }

    /**
     * 图片上传.
     *
     * @return [type] [description]
     */
    public function upload()
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

        $post = $_POST;


        $service = Admins::table('wolive_queue')->where('business_id', $post['business_id'])->where('visiter_id', $post['visiter_id'])->where('state', 'normal')->find();

        if ($service['service_id'] != $post['service_id']) {

            $returndata = ['code' => 1, 'msg' => $this->lang_array['session_close']];
            return $returndata;
        }

        $service_id = $service['service_id'];
        $post["timestamp"] = time();
        $post['service_id'] = $service_id;

        try {
            Storage::$variable = 'upload';
            $url = Storage::put();
            $html = '<img  src="' . $url['url'] . '" />';
            $post['content'] = $html;
            $pusher->trigger('kefu' . $service_id, 'cu-event', array('message' => $post));
            $post['direction'] = 'to_service';
            unset($post['avatar']);
            unset($post['record']);
            $res = Admins::table('wolive_chats')->insert($post);
            $data = [
                "code" => 0,
                "msg" => "",
                "data" => $html
            ];
            return $data;
        } catch (StorageException $exception) {
            $data = ['code'=> -1,'msg'=>$exception->getMessage(),'data'=>''];
        } catch (\Exception $e) {
            $data = ['code'=> -1,'msg'=>$this->lang_array['save_file_error']];
        }
        return $data;
    }

    /**
     * 文件上传.
     *
     * @return [type] [description]
     */
    public function uploadfile()
    {

        return ['code'=> -1,'msg'=> '错误的上传方式','data'=>''];

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

        $post = $_POST;
        $service = Admins::table('wolive_queue')->where('business_id', $post['business_id'])->where('visiter_id', $post['visiter_id'])->where('state', 'normal')->find();

        if ($service['service_id'] != $post['service_id']) {

            $returndata = ['code' => 1, 'msg' => $this->lang_array['session_close']];
            return $returndata;
        }

        $service_id = $service['service_id'];

        $post["timestamp"] = time();
        $post['service_id'] = $service_id;

        $name = $_FILES["folder"]["name"];

        try {
            Storage::$variable = 'folder';
            $url = Storage::put();
            $html = "<div><a href='" . $url['url'] . "' style='display: inline-block;text-align: center;min-width: 70px;text-decoration: none;' download='" . $name . "'><i class='layui-icon' style='font-size: 60px;'>&#xe61e;</i><br>" . $name . "</a></div>";
            if(strpos($url['url'], '.mp4') !== false) $html = "<video src='{$url['url']}' controls='controls' style='width: 100%'>ERROR</video>";
            $post['content'] = $html;
            $pusher->trigger('kefu' . $service_id, 'cu-event', array('message' => $post));
            unset($post['avatar']);
            unset($post['record']);
            $post['direction'] = 'to_service';
            $res = Admins::table('wolive_chats')->insert($post);

            $data = [
                "code" => 0,
                "msg" => "",
                "data" => $url['url']
            ];

            return $data;
        } catch (StorageException $exception) {
            $data = ['code'=> -1,'msg'=>$exception->getMessage(),'data'=>''];
        } catch (\Exception $e) {
            $data = ['code'=> -1,'msg'=>$this->lang_array['save_file_error']];
        }
        return $data;

    }


    /**
     * 获取最近对话信息.
     *
     * @return string
     */
    public function chatdata()
    {

        $post = $this->request->post();
        $vid = $post['vid'];
        $service_id = $post['service_id'];

        if ($post["hid"] == '') {

            $data = Admins::table('wolive_chats')->where(['service_id' => $service_id, 'visiter_id' => $vid, 'business_id' => $post['business_id']])->order('timestamp desc,cid asc')->limit(10)->select();

            $vdata = Admins::table('wolive_visiter')->where('visiter_id', $vid)->find();
            $sdata = Admins::table('wolive_service')->where('service_id', $service_id)->find();
            foreach ($data as $v) {

                if ($v['direction'] == 'to_service') {

                    $v['avatar'] = $vdata['avatar'];
                } else {

                    $v['avatar'] = $sdata?$sdata['avatar']:'/assets/images/index/ai_service.png';
                }

            }
            reset($data);
        } else {
            $data = Admins::table('wolive_chats')->where(['service_id' => $service_id, 'visiter_id' => $vid, 'business_id' => $post['business_id']])->where('cid', '<', $post['hid'])->order('timestamp desc,cid asc')->limit(10)->select();
            $vdata = Admins::table('wolive_visiter')->where('visiter_id', $vid)->find();
            $sdata = Admins::table('wolive_service')->where('service_id', $service_id)->find();

            foreach ($data as $v) {
                if ($v['direction'] == 'to_service') {
                    $v['avatar'] = $vdata['avatar'];
                } else {
                    $v['avatar'] = $sdata?$sdata['avatar']:'/assets/images/index/ai_service.png';
                }
            }
            reset($data);
        }

        $result = array_reverse($data);

        $data = ['code' => 0, 'data' => $result];
        return $data;
    }


    /**
     * 删除访客信息.
     *
     * @return boolAdmins
     */
    public function qdelete()
    {

        $post = $this->request->post();
        $result = Admins::table('wolive_queue')->where(['visiter_id' => $post['visiter_id'], 'business_id' => $post['business_id']])->delete();
        if ($result) {
            return true;
        } else {
            return false;
        }
    }


    /**
     *
     * @return string
     */
    public function apply()
    {
        $post = $this->request->post();

        $visiter = Admins::table('wolive_service')->where('service_id', $post['id'])->find();

        $queue = Admins::table('wolive_queue')->where(['visiter_id' => $post['visiter_id'], 'service_id' => $post['id']])->where('business_id', $post['business_id'])->update(['state' => 'normal']);


        $type = $visiter['state'];

        if ($type == 'offline') {

            $data = ['code' => 1, 'msg' => $this->lang_array['offline']];

            return $data;
        }

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
        $pusher->trigger("kefu" . $post['id'], "video", array("message" => "申请视频连接", "channel" => $post['channel'], "avatar" => $post['avatar'], 'username' => $post['name'], "cid" => $post['cha']));

        $data = ['code' => 0, 'msg' => 'success'];

        return $data;

    }

    /**
     *
     * [refuse description]
     * @return [type] [description]
     */
    public function refuse()
    {
        $post = $this->request->post();

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

        $pusher->trigger("kefu" . $post['id'], "video-refuse", array("message" => "对方拒绝视频连接！"));
    }

    /**
     * [getquestion description]
     * @return [type] [description]
     */
    public function getquestion()
    {
        $post = $this->request->post();
        $business_id = $post['business_id'];
        $visiter_lang = Db::name('wolive_visiter')->where('visiter_id', $post['visiter_id'])->value('lang');
        if($visiter_lang){
            $lang = $visiter_lang;
        }else{
            $lang = Db::name('wolive_business')->where('id', $business_id)->value('lang');
            if(session('user_lang')) $lang = session('user_lang');
        }
        $result = Admins::table('wolive_question')
            ->where('business_id', $business_id)
            ->where('lang', $lang)
            ->where('status','eq', 1)
            ->order('sort desc')
            ->select();
        $business = Business::get($business_id);
        $keyword = Admins::table('wolive_question')
            ->where('business_id', $business_id)
            ->where('lang', $lang)
            ->where('status','eq', 1)
            ->where('keyword','neq','')
            ->count();
        if ($result) {
            $arr = ['code' => 0, 'msg' => 'success', 'data' => $result,'keyword'=>$keyword,'logo'=>$business['logo']];
            return $arr;
        }
    }

    /**
     *
     * [getanswer description]
     * @return [type] [description]
     */
    public function getanswer()
    {
        $post = $this->request->post();
        $qid = $post['qid'];
        $service_id = isset($post['service_id']) ? $post['service_id']: 0 ;
        $service = Service::get($service_id);
        $visiter_id = isset($post['visiter_id']) ? $post['visiter_id']: 0 ;
        $result = Admins::table('wolive_question')->where('qid', $qid)->find();
        if ($result) {
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
            $arr['visiter_id'] = $visiter_id;
            $arr['business_id'] = $result['business_id'];
            $arr['service_id'] = $service_id;
            $arr["timestamp"] = time();
            $arr["content"] = $result['question'];
            $arr['direction'] = 'to_service';
            $data1 = $arr;
            $pusher->trigger('kefu' .  $service_id, 'cu-event', array('message' => $arr));
            $arr["content"] = $result['answer'];
            $arr['direction'] = 'to_visiter';
            $arr["timestamp"] = time();
            $channel = bin2hex($arr['visiter_id'] . '/' . $arr['business_id']);
            Admins::table('wolive_chats')->insert($arr);
            Admins::table('wolive_chats')->insert($data1);


            $arr['avatar'] = $service['avatar'];
            $pusher->trigger("cu" . $channel, 'my-event', array('message' => $arr));
            $arr = ['code' => 0, 'msg' => 'success', 'data' => $result];
            return $arr;
        } else {
            $arr = ['code' => 1, 'msg' => $this->lang_array['question_delete']];
            return $arr;
        }
    }

    /**
     *
     * [groupNum description]
     * @return [type] [description]
     */
    public function groupNum()
    {
        $num = Admins::table('wolive_group')->count();
        return $num;
    }

    /**
     * [getchangekefu description]
     * @return [type] [description]
     */
    public function getchangekefu()
    {
        $post = $this->request->post();

        $res = Admins::table('wolive_queue')->where('visiter_id', $post['visiter_id'])->where('business_id', $post['business_id'])->update(['service_id' => 0]);

        return $arr = ['code' => 0, 'msg' => 'success'];

    }

    /**
     *
     * [gettablist description]
     * @return [type] [description]
     */
    public function gettablist()
    {
        $post = $this->request->post();
        $business_id = $post['business_id'];
        $result = Admins::table('wolive_tablist')->where('business_id', $business_id)->select();

        $arr = ['code' => 0, 'msg' => 'success', 'data' => $result];

        return $arr;
    }


    /**
     *
     * [uploadimg description]
     * @return [type] [description]
     */
    public function uploadimg()
    {

        $file = $this->request->file("editormd-image-file");
        $name = $_FILES["editormd-image-file"]["name"];
        $ext  = substr( $name, strrpos( $name, '.' ) + 1);
        $formats  = array('gif', 'jpg', 'jpeg', 'png', 'bmp', 'webp'
        );
        if(!in_array($ext, $formats)){
            $error = $this->lang_array['ext_error'];

            $data = [
                "code" => -1,
                "msg" => $error,
                "data" => ""
            ];
            $json = json_encode($data);
            return $json;
        }
        $uploaded_tmp  = $_FILES["editormd-image-file"]['tmp_name'];
        if(!getimagesize($uploaded_tmp)){
            $error = $this->lang_array['illegal_img_error'];
            $data = [
                "code" => -1,
                "msg" => $error,
                "data" => ""
            ];
            $json = json_encode($data);
            return $json;
        }
        if ($file) {
            $newpaths = ROOT_PATH . "/public/upload/files/";
            $info = $file->move($newpaths, time());
            if ($info) {
                $imgname = $info->getFilename();

                $imgpath = $this->base_root."/upload/files/" . $imgname;

                $data = [
                    "success" => 1,
                    "msg" => "success",
                    "url" => $imgpath
                ];

                return json_encode($data);
            }
        }
    }

    /**
     *
     * [uploadVoice description]
     * @return [type] [description]
     */
    public function uploadVoice()
    {

        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $file = $this->request->file('file');

        if ($file) {
            $newpath = ROOT_PATH . "/public/assets/upload/voices/";
            $info = $file->move($newpath, time() . ".wav");

            if ($info) {

                $imgname = $info->getFilename();
                $imgpath = $this->base_root. "/assets/upload/voices/" . $imgname;
                $arr = [
                    'data' => [
                        'src' => $imgpath
                    ]
                ];

                return json_encode($arr);
            } else {

                return false;
            }
        }
    }


    /**
     *
     * [getwaitnum description]
     * @return [type] [description]
     */

    public function getwaitnum()
    {

        $post = $_POST;

        if ($post['groupid'] == 0) {
            $count = Admins::table('wolive_queue')->where(['business_id' => $post['business_id'], 'state' => 'normal', 'service_id' => 0])->count();
        } else {
            $count = Admins::table('wolive_queue')->where(['business_id' => $post['business_id'], 'state' => 'normal', 'service_id' => 0, 'groupid' => $post['groupid']])->count();
        }
        return $count;
    }

    public function comment()
    {
        $post = $this->request->post();
        $visiter = Visiter::get(['visiter_id'=>$post['visiter_id'],'business_id'=>$post['business_id']]);
        if (empty($visiter)) {
            return json([
                'code' => 1,
                'msg' => $this->lang_array['evaluate_error'],
            ]);
        }

        $service = Service::get(['service_id'=>$post['service_id']]);
        if (empty($service)) {
            return json([
                'code' => 1,
                'msg' => $this->lang_array['evaluate_error'],
            ]);
        }
        $post['scores'] = $this->request->post('scores',[],null);
        $data = json_decode($post['scores'],true);
        if (!is_array($data)) {
            return json([
                'code' => 1,
                'msg' => $this->lang_array['evaluate_error'],
            ]);
        }

        foreach ($data as $v) {
            if ($v['score'] == 0) {
                return json([
                    'code' => 1,
                    'msg' => $this->lang_array['evaluate_score']."{$v['title']}",
                ]);
            }
        }

        $res =  Comment::create([
            'business_id'=>$post['business_id'],
            'service_id'=> $service['service_id'],
            'group_id' => $service['groupid'],
            'visiter_id'=>$visiter['visiter_id'],
            'visiter_name'=>$visiter['visiter_name'],
            'word_comment'=>$post['comment'],
        ]);

        $model = new CommentDetail();
        foreach ($data as &$v) {
            $v['comment_id'] = $res['id'];
        }
        unset($v);
        $model->insertAll($data);

        if ($res !== false) {
            return json([
                'code' => 0,
                'msg' => $this->lang_array['evaluate_thk'],
            ]);
        } else {
            return json([
                'code' => 1,
                'msg' => $this->lang_array['evaluate_error'],
            ]);
        }
    }

    public function info()
    {
        $post = $this->request->post();
        $visiter = Visiter::get(['visiter_id'=>$post['visiter_id'],'business_id'=>$post['business_id']]);
        $rest = RestSetting::get(['business_id'=>$post['business_id']]);
        if (empty($visiter)) {
            return  [
                'code'=>1,
                'msg'=> 'error',
            ];
        }

        $post['name'] = $this->request->post('name','');
        $post['tel'] = $this->request->post('tel','');

        $rule = '^1(3|4|5|7|8)[0-9]\d{8}$^';
        $result = preg_match($rule, $post['tel']);

        if ($rest['tel_state'] == 'open') {
            if (!$result) {
                return  [
                    'code'=>1,
                    'msg'=> $this->lang_array['mobile_error'],
                ];
            }
        }

        if ($rest['name_state'] == 'open') {
            if (empty($post['name'])) {
                return  [
                    'code'=>1,
                    'msg'=> $this->lang_array['name_error'],
                ];
            }
        }

        $res = $visiter->save(['name'=>$post['name'],'tel'=>$post['tel'],'msg_time'=>date('Y-m-d H:i:s',time())]);
        if ($res !== false) {
            return json([
                'code' => 0,
                'msg' => $this->lang_array['save_ok'],
            ]);
        } else {
            return json([
                'code' => 1,
                'msg' => $this->lang_array['save_error'],
            ]);
        }
    }
}