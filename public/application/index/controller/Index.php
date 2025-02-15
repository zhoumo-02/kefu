<?php


namespace app\index\controller;

use app\admin\iplocation\Ip;
use app\admin\model\RestSetting;
use app\admin\model\WechatPlatform;
use think\Controller;
use app\extra\push\Pusher;
use app\index\model\User;
use app\Common;
use think\Cookie;
use think\Db;
use think\Exception;
use think\Lang;

/**
 *
 * 前台Pc端对话窗口.
 */
class Index extends Controller
{

    public function _initialize()
    {
        $basename = request()->root();
        if (pathinfo($basename, PATHINFO_EXTENSION) == 'php') {
            $basename = dirname($basename);
        }
        $this->assign('basename',$basename);
    }

    public function testt(){
        $rebot_int = Lang::get("robot_error",[],$this->serverLang);
        $reply = $rebot_int[array_rand($rebot_int,1)];
        var_dump($reply);
    }

    public function set_lang(){
        if ($this->request->isAjax()) {
            $post = $this->request->post();
            Db::name("wolive_visiter")->where(['visiter_id'=>$post['visiter']])->update(['lang'=>$post['lang']]);
            session('user_lang',$post['lang']);
            $this->success('操作成功！');
        }
        $this->error('操作失败！');
    }

    /**
     *
     * [home description]
     * @return [type] [description]
     */
    public function home()
    {
        $data = $this->request->param('','');
        $business = Db::table('wolive_business')->where('id', $data['business_id'])->find();
        $data['theme'] = $business['theme'];
        if (isset($data['code']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            try{
                $wechat = WechatPlatform::get(['business_id' => $data['business_id']]);
                $appid = $wechat['app_id'];
                $appsecret = $wechat['app_secret'];
                $weixin = file_get_contents("https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appid&secret=$appsecret&code={$data['code']}&grant_type=authorization_code");
                $array = json_decode($weixin,true);
                if(!isset($array['access_token'])){
                    //说明没有获取到
                    $this->error($array['errmsg'],$url = null, $data = '', $wait = 999999999);
                }
                cache('oauth_access_token',$array['access_token'],7000);

                $info = file_get_contents("https://api.weixin.qq.com/sns/userinfo?access_token={$array['access_token']}&openid={$array['openid']}&lang=zh_CN");
                $infoarray = json_decode($info,true);
                $data['visiter_id'] = $infoarray['openid'];
                $common = new Common();
                $data['visiter_name'] = $common->remove_emoji($infoarray['nickname']);
                $data['avatar'] = $infoarray['headimgurl'];
                if (!isset($data['groupid'])) {
                    $data['groupid'] = 0;
                }
            }catch (Exception $exception) {
                $this->error($exception->getMessage(),$url = null, $data = '', $wait = 999999999);
            }
        }else{
            session('from_url',isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'');
        }

        if (!isset($data['product'])) {
            $data['product'] = "";
        }

        if (!isset($data['special'])) {
            $data['special'] = "";
        }

        $str = 'theme='.$data['theme']."&visiter_id=" . $data['visiter_id'] . "&visiter_name=" . $data['visiter_name'] . "&avatar=" . $data['avatar'] . "&business_id=" . $data['business_id'] . "&groupid=" . $data['groupid'] . "&product=" . $data['product']."&special=" . $data['special'];

        $common = new Common();

        $newstr = $common->encrypt($str, 'E', 'QQ290430368');

        $a = urlencode($newstr);

        $this->redirect(request()->root().'/index/index?code=' . $a);

    }

    /**
     * 对话窗口页面.
     *
     * @return mixed
     */
    public function index()
    {
        $param = $this->request->param();
        if(empty($param)) $this->redirect(request()->root().'/index/index/welcome');
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

        $common = new Common();

        $is_mobile = $common->isMobile();

        $url = domain;
        $from_url=session('from_url');
        if(!$from_url){
            if (isset($_SERVER['HTTP_REFERER'])) {
                $from_url = $_SERVER['HTTP_REFERER'];
            } else {
                $from_url = '';
            }
        }


        $arr = $this->request->get();

        $data = $common->encrypt($arr['code'], 'D', 'QQ290430368');

        if (!$data) {
            $this->redirect(request()->root().'/index/index/errors');
        }

        parse_str($data, $arr2);
        $special = isset($arr2['special']) ? $arr2['special']:null;

        if (!isset($arr2['visiter_id']) || !isset($arr2['visiter_name']) || !isset($arr2['product']) || !isset($arr2['groupid']) || !isset($arr2['business_id']) || !isset($arr2['avatar'])) {
            $this->redirect(request()->root().'/index/index/errors');
        }

        $theme=isset($arr2['theme'])?$arr2['theme']:'13c9cb';

        if ($is_mobile) {
            $this->redirect(request()->root().'/mobile/index/home?theme=' . $theme . '&visiter_id=' . $arr2['visiter_id'] . '&visiter_name=' . $arr2['visiter_name'] . '&avatar=' . $arr2['avatar'] . '&business_id=' . $arr2['business_id'] . '&product=' . $arr2['product'] . '&groupid=' . $arr2['groupid']."&special=".$special);
        }


        $content = json_decode($arr2['product'], true);
        if (!$content) {
            $arr2['product'] = NULL;

        }
        $business_id = htmlspecialchars($arr2['business_id']);
        $visiter_id = htmlspecialchars($arr2['visiter_id']);
        if ($visiter_id === '') {
            $visiter_id=cookie('visiter_id');
            if (!$visiter_id) {
                $visiter_id = bin2hex(pack('N', time())).strtolower($common->rand(8));
                cookie('visiter_id', $visiter_id, 63072000);
            }
        }

        // 判断是否访问过
        if ($visiter_id) {

            if (!isset($_COOKIE['product_id'])) {

                if ($arr2['product'] != NULL) {
                    $content = json_decode($arr2['product'], true);
                    if (isset($content['pid']) && isset($content['url']) && isset($content['img']) && isset($content['title']) && isset($content['info']) && isset($content['price'])) {
                        setcookie("product_id", $content['pid'], time() + 3600 * 12);
                        $arr2['timestamp'] = time();
                        $service = User::table('wolive_queue')->where(['visiter_id' => $visiter_id, 'business_id' => $business_id])->find();
                        if ($service) {
                            $service_id = $service['service_id'];
                        } else {
                            $service_id = 0;
                        }
                        $str = '<a href="' . $content['url'] . '" target="_blank" class="wolive_product">';
                        $str .= '<div class="wolive_img"><img src="' . $content['img'] . '" width="100px"></div>';
                        $str .= '<div class="wolive_head"><p class="wolive_info">' . $content['title'] . '</p><p class="wolive_price">' . $content['price'] . '</p>';
                        $str .= '<p class="wolive_info">' . $content['info'] . '</p>';
                        $str .= '</div></a>';
                        $mydata = ['service_id' => $service_id, 'visiter_id' => $visiter_id, 'content' => $str, 'timestamp' => time(), 'business_id' => $business_id, 'direction' => 'to_service'];

                        $pusher->trigger('kefu' . $service_id, 'cu-event', array('message' => $mydata));
                        $chats = User::table('wolive_chats')->insert($mydata);
                    }

                }
            } else {

                $pid = isset($_COOKIE['product_id']) ? $_COOKIE['product_id'] : '';
                if ($arr2['product'] != NULL) {
                    $content = json_decode($arr2['product'], true);
                    if (isset($content['pid']) && isset($content['url']) && isset($content['img']) && isset($content['title']) && isset($content['info']) && isset($content['price']) && $content['pid'] != $pid) {
                        $service = User::table('wolive_queue')->where(['visiter_id' => $visiter_id, 'business_id' => $business_id])->find();

                        if ($service) {
                            $service_id = $service['service_id'];
                        } else {
                            $service_id = 0;
                        }
                        $str = '<a href="' . $content['url'] . '" target="_blank" class="wolive_product">';
                        $str .= '<div class="wolive_img"><img src="' . $content['img'] . '" width="100px"></div>';
                        $str .= '<div class="wolive_head"><p class="wolive_info">' . $content['title'] . '</p><p class="wolive_price">' . $content['price'] . '</p>';
                        $str .= '<p class="wolive_info">' . $content['info'] . '</p>';
                        $str .= '</div></a>';
                        $mydata = ['service_id' => $service_id, 'visiter_id' => $visiter_id, 'content' => $str, 'timestamp' => time(), 'business_id' => $business_id, 'direction' => 'to_service'];
                        $pusher->trigger('kefu' . $service_id, 'cu-event', array('message' => $mydata));
                        $chats = User::table('wolive_chats')->insert($mydata);

                    }
                }
            }

        } else {

            if (!isset($_COOKIE['product_id'])) {

                if ($arr2['product'] != NULL) {
                    $content = json_decode($arr2['product'], true);
                    if (isset($content['pid']) && isset($content['url']) && isset($content['img']) && isset($content['title']) && isset($content['info']) && isset($content['price'])) {
                        setcookie("product_id", $content['pid'], time() + 3600 * 12);
                        $arr2['timestamp'] = time();

                        $service = User::table('wolive_queue')->where(['visiter_id' => $visiter_id, 'business_id' => $business_id])->find();
                        if ($service) {
                            $service_id = $service['service_id'];
                        } else {
                            $service_id = 0;
                        }
                        $str = '<a href="' . $content['url'] . '" target="_blank" class="wolive_product">';
                        $str .= '<div class="wolive_img"><img src="' . $content['img'] . '" width="100px"></div>';
                        $str .= '<div class="wolive_head"><p class="wolive_info">' . $content['title'] . '</p><p class="wolive_price">' . $content['price'] . '</p>';
                        $str .= '<p class="wolive_info">' . $content['info'] . '</p>';
                        $str .= '</div></a>';
                        $mydata = ['service_id' => $service_id, 'visiter_id' => $visiter_id, 'content' => $str, 'timestamp' => time(), 'business_id' => $business_id, 'direction' => 'to_service'];
                        $pusher->trigger('kefu' . $service_id, 'cu-event', array('message' => $mydata));
                        $chats = User::table('wolive_chats')->insert($mydata);
                    }

                }
            } else {
                if ($arr2['product'] != NULL) {
                    if ($arr2['visiter_id'] != cookie('visiter_id')) {
                        $content = json_decode($arr2['product'], true);
                        if (isset($content['pid']) && isset($content['url']) && isset($content['img']) && isset($content['title']) && isset($content['info']) && isset($content['price'])) {
                            $service = User::table('wolive_queue')->where(['visiter_id' => $visiter_id, 'business_id' => $business_id])->find();
                            if ($service) {
                                $service_id = $service['service_id'];
                            } else {
                                $service_id = 0;
                            }
                            $str = '<a href="' . $content['url'] . '" target="_blank" class="wolive_product">';
                            $str .= '<div class="wolive_img"><img src="' . $content['img'] . '" width="100px"></div>';
                            $str .= '<div class="wolive_head"><p class="wolive_info">' . $content['title'] . '</p><p class="wolive_price">' . $content['price'] . '</p><p>';
                            $str .= '<p class="wolive_info">' . $content['info'] . '</p>';
                            $str .= '</div></a>';
                            $mydata = ['service_id' => $service_id, 'visiter_id' => $visiter_id, 'content' => $str, 'timestamp' => time(), 'business_id' => $business_id, 'direction' => 'to_service'];
                            $pusher->trigger('kefu' . $service_id, 'cu-event', array('message' => $mydata));
                            $chats = User::table('wolive_chats')->insert($mydata);
                        }
                    } else {
                        $pid = $_COOKIE['product_id'];
                        $product = $arr2['product'];
                        $content = json_decode($arr2['product'], true);
                        // 判断是否是同个商品
                        if (isset($content['pid']) && isset($content['url']) && isset($content['img']) && isset($content['title']) && isset($content['info']) && isset($content['price']) && $content['pid'] != $pid) {
                            $service = User::table('wolive_queue')->where(['visiter_id' => $visiter_id, 'business_id' => $business_id])->find();
                            if ($service) {
                                $service_id = $service['service_id'];
                            } else {
                                $service_id = 0;
                            }
                            $str = '<a href="' . $content['url'] . '" target="_blank" class="wolive_product">';
                            $str .= '<div class="wolive_img"><img src="' . $content['img'] . '" width="100px"></div>';
                            $str .= '<div class="wolive_head"><p class="wolive_info">' . $content['title'] . '</p><p class="wolive_price">' . $content['price'] . '</p>';
                            $str .= '<p class="wolive_info">' . $content['info'] . '</p>';
                            $str .= '</div></a>';
                            $mydata = ['service_id' => $service_id, 'visiter_id' => $visiter_id, 'content' => $str, 'timestamp' => time(), 'business_id' => $business_id, 'direction' => 'to_service'];
                            $pusher->trigger('kefu' . $service_id, 'cu-event', array('message' => $mydata));
                            $chats = User::table('wolive_chats')->insert($mydata);
                        }
                    }

                }
            }
        }

        $channel = bin2hex($visiter_id . '/' . $business_id);
        $visiter_name = htmlspecialchars($arr2['visiter_name']);
        $avatar = htmlspecialchars($arr2['avatar']);
        if ($visiter_name == '') {
            $visiter_name = '游客' . $visiter_id;
        }
        $groupid = htmlspecialchars($arr2['groupid']);
        $app_key = app_key;
        $whost = whost;
        $arr = parse_url($whost);
        if ($arr['scheme'] == 'ws') {

            $port = 'wsPort';
            $value = 'false';
        } else {

            $value = 'true';
            $port = 'wssPort';
        }
        session('from_url',null);


        $business = User::table('wolive_business')->where('id', $business_id)->find();
        $visiter_lang = User::name('wolive_visiter')->where('visiter_id', $visiter_id)->value('lang');
        if($visiter_lang){
            $business['lang'] = $visiter_lang;
        }else{
            if($business['auto_ip']) $business['lang'] = Ip::check_country($this->request->ip())?:$business['lang'];
            if(session('user_lang')) $business['lang'] = session('user_lang');
        }

        $rest = RestSetting::get(['business_id'=>$business_id]);
        $state = empty($rest) ? false : $rest->isOpen($business_id,$visiter_id);
        $this->assign('lang', Lang::load(APP_PATH.'lang/'.$business['lang'].'.php'));
        $this->assign('reststate', $state);
        $this->assign('restsetting',$rest);
        $this->assign('business_name',$business['business_name']);
        $this->assign("type", $business['video_state']);
        $this->assign("atype", $business['audio_state']);
        $this->assign('app_key', $app_key);
        $this->assign('whost', $arr['host']);
        $this->assign('value', $value);
        $this->assign('wport', wport);;
        $this->assign('port', $port);
        $this->assign('url', $url);
        $this->assign('groupid', $groupid);
        $this->assign('visiter', $visiter_name);
        $this->assign('business_id', $business_id);
        $this->assign('from_url', $from_url);
        $this->assign('channel', $channel);
        $this->assign('visiter_id', $visiter_id);
        $this->assign('avatar', $avatar);
        $this->assign('theme', $theme);
        $this->assign('special',$special);

        require_once __DIR__ . '/../../../service/config.php';
        $this->assign('proxy_port', $proxy_port);

        return $this->fetch();
    }

    /**
     * 404页面
     */

    public function errors()
    {
        return $this->fetch();
    }

    /**
     * 获取排队数量.
     *
     * @return mixed
     */
    public function getwaitnum()
    {
        $post = $this->request->post();
        $num = User::table('wolive_queue')->where('visiter_id', $post['visiter_id'])->where("service_id", 0)->count();
        return $num;
    }

    public function wechat()
    {
        $business_id = $this->request->param('business_id', '');
        $group_id = $this->request->param('groupid',0);
        $special = $this->request->param('special','');
        $theme = $this->request->param('theme','7571f9');
        if(empty($business_id)){
            abort(500);
        }
        session('from_url',isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'');
        $wechat = WechatPlatform::get(['business_id' => $business_id]);
        $APPID = $wechat['app_id'];
        $REDIRECT_URI = url('index/index/home',['business_id'=>$business_id,'groupid'=>$group_id,'special'=>$special,'theme'=>$theme],true,true);
        $scope = 'snsapi_userinfo';
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $APPID . '&redirect_uri=' . urlencode($REDIRECT_URI) . '&response_type=code&scope=' . $scope . '&state=123#wechat_redirect';
        $this->redirect($url);
    }

    public function welcome(){
        $common = new Common();
        $ismoblie = $common->isMobile();
        if ($ismoblie) {
            $this->redirect(url('/index/index/home?visiter_id=&visiter_name=&avatar=&business_id=1&groupid=0'));
        } else {
            return $this->fetch();
        }
    }
}