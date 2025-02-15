<?php 
namespace app\layer\controller;

use app\admin\iplocation\Ip;
use app\admin\model\RestSetting;
use app\Common;
use think\Controller;
use app\extra\push\Pusher;
use app\index\model\User;
use think\Lang;


/**
 * 
 */
class Index extends Controller
{
    public $basename;
    public function _initialize()
    {
        $this->basename = request()->root();
        if (pathinfo($this->basename, PATHINFO_EXTENSION) == 'php') {
            $basename = dirname($this->basename);
        }
        $this->assign('basename',$this->basename);
    }
     /**
    * 唯一随机数方法
    * [rand description]
    * @param  [type] $len [description]
    * @return [type]      [description]
    */
    public function rand($len)
    {
        $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $string=substr(time(),-3);
        for(;$len>=1;$len--)
        {
            $position=rand()%strlen($chars);
            $position2=rand()%strlen($string);
            $string=substr_replace($string,substr($chars,$position,1),$position2,0);
        }
        return $string;
    }

  
 
	/**
	 * [index description]
	 * @return [type] [description]
	 */
	public function indexbak(){
		$request = $this->request->get();
        $sarr = parse_url(ahost);
        if($sarr['scheme'] == 'https'){
            $state = true;
        }else{
            $state =false;
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

        $app_key = app_key;
        $whost = whost;
        $arr = parse_url($whost);
        if($arr['scheme'] == 'ws'){

            $port ='wsPort';
            $value ='false';
        }else{

            $value ='true';
            $port ='wssPort';
        }
         
        $business_id =htmlspecialchars($request['business_id']);
        $url=domain;
        $groupid=htmlspecialchars($request['groupid']);
        $visiter_id=htmlspecialchars($request['visiter_id']);
    
        $avatar=htmlspecialchars($request['avatar']);

        if(!$visiter_id){
            if(isset($_COOKIE['visiter_id'])){
            	$visiter_id =$_COOKIE['visiter_id'];
            }else{
            	$visiter_id =$this->rand(2);
            	setcookie("visiter_id",$visiter_id,time()+3600*12);
            }        	
        }

        $service =User::table('wolive_queue')->where(['visiter_id'=>$visiter_id,'business_id'=>$business_id])->find();

        if(isset($_SERVER['HTTP_REFERER'])){
           $from_url=$_SERVER['HTTP_REFERER'];
        }else{
           $from_url='';
        }

        $visiter_name =htmlspecialchars($request['visiter_name']);
        
        if($visiter_name == ''){
            $visiter_name='游客'.$visiter_id;
        }


        $business =User::table('wolive_business')->where('id',$business_id)->find();

        $channel=bin2hex($visiter_id.'/'.$business_id);
           
        $this->assign("video",$business['video_state']);
        $this->assign("audio",$business['audio_state']);

        $this->assign('app_key', $app_key);
        $this->assign('whost', $arr['host']);
        $this->assign('value', $value);
        $this->assign('wport', wport);;
        $this->assign('port',$port);
        $this->assign('url',$url);
        $this->assign('groupid',$groupid);
        $this->assign('visiter',$visiter_name);
        $this->assign('business_id',$business_id);
        $this->assign('from_url',$from_url);
        $this->assign('channel',$channel);
        $this->assign('visiter_id',$visiter_id);
        $this->assign('avatar',$avatar);
		
		return  $this->fetch();
	}

    /**
     *
     * 手机端首页.
     *
     * @return mixed
     */
    public function index()
    {
        $this->comchat();

        require_once __DIR__ . '/../../../service/config.php';
        $this->assign('proxy_port', $proxy_port);

        return $this->fetch();
    }
    protected function comchat(){
        $url = domain;
        $arr2 = $this->request->get();
        $special = isset($arr2['special']) ? $arr2['special']:null;
        $theme = isset($arr2['theme']) ? $arr2['theme']:null;

        if (!isset($arr2['visiter_id']) || !isset($arr2['visiter_name']) || !isset($arr2['product']) || !isset($arr2['groupid']) || !isset($arr2['business_id']) || !isset($arr2['avatar'])) {
            $this->redirect($this->basename.'/index/index/errors');
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

        $business_id = $arr2['business_id'];
        $visiter_id = $arr2['visiter_id'];
        $arr2['product'] = htmlspecialchars_decode($arr2['product']);
        if (trim($visiter_id) == '') {
            $visiter_id=cookie('visiter_id');
            if (!$visiter_id) {
                $common = new Common();
                $visiter_id = bin2hex(pack('N', time())).strtolower($common->rand(8));
                cookie('visiter_id', $visiter_id, 63072000);
            }
        }else{
            cookie('visiter_id', $visiter_id, 63072000);
        }

        if ($visiter_id) {
            if (!isset($_COOKIE['product_id'])) {
                // 没有product_id
                if ($arr2['product']) {
                    $content = json_decode(htmlspecialchars_decode($arr2['product']), true);
                    if (isset($content['pid']) && isset($content['url']) && isset($content['img']) && isset($content['title']) && isset($content['info']) && isset($content['price'])) {
                        setcookie("product_id", $content['pid'], time() + 3600 * 12);
                        $arr2['timestamp'] = time();

                        $service = \app\mobile\model\User::table('wolive_queue')->where(['visiter_id' => $visiter_id, 'business_id' => $business_id])->find();
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

                $pid = $_COOKIE['product_id'];
                if ($arr2['product']) {
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
                // 没有product_id
                if ($arr2['product']) {
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
                $pid = $_COOKIE['product_id'];
                if ($arr2['product']) {
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
        }
        $channel = bin2hex($visiter_id . '/' . $business_id);
        $visiter_name = htmlspecialchars($arr2['visiter_name']);
        if (isset($_SERVER['HTTP_REFERER'])) {
            $from_url = $_SERVER['HTTP_REFERER'];
        } else {
            $from_url = '';
        }
        $avatar = $arr2['avatar'];
        $groupid = $arr2['groupid'];
        if ($visiter_name == '') {
             $visiter_name = '游客' . $visiter_id;
         }
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
        $this->assign("atype", $business['audio_state']);
        $this->assign('groupid', $groupid);
        $this->assign('app_key', $app_key);
        $this->assign('whost', $arr['host']);
        $this->assign('value', $value);
        $this->assign('wport', wport);
        $this->assign('port', $port);
        $this->assign('url', $url);
        $this->assign('visiter', $visiter_name);
        $this->assign('business_id', $business_id);
        $this->assign('from_url', $from_url);
        $this->assign('channel', $channel);
        $this->assign('visiter_id', $visiter_id);
        $this->assign('avatar', $avatar);
        $this->assign('special',$special);
        $this->assign('theme',$theme);
    }
}