<?php

namespace app\weixin\controller;
use app\admin\model\WechatPlatform;
use app\extra\push\Pusher;
use app\weixin\model\Weixin;
use think\Controller;
use EasyWeChat\Foundation\Application;
use app\weixin\model\Admins;
use think\Log;

class  Index extends Controller
{
   public function index()
   {
//       file_put_contents(PUBLIC_PATH.'/wxindex.txt',var_export($_REQUEST,true),FILE_APPEND);
       $business_id = $this->request->param('business_id',null);
       !$business_id && abort(500,'参数错误');
       $wechat = WechatPlatform::get(['business_id' => $business_id]);
       // config配置
       $options=[
           'debug'  => true,
            'app_id' => $wechat['app_id'],
            'secret' => $wechat['app_secret'],
            'aes_key' => $wechat['wx_aeskey'],
            'token'  => $wechat['wx_token'],
            'log' => [
                'level' => 'debug',
                'file'  => PUBLIC_PATH.'/easywechat.log', // XXX: 绝对路径！！！！
            ],
       ];
       $url = domain;
       $app = new Application($options);
       $server = $app->server;
       // 消息回复
       $server->setMessageHandler(function ($message) use($business_id) {
           Log::info($message);
           // $message->FromUserName // 用户的 openid
           // $message->MsgType // 消息类型：event, text....
//           file_put_contents(PUBLIC_PATH.'/wxmessage.txt',var_export($message,true),FILE_APPEND);
//           FromUserName
           switch ($message->MsgType) {
               case 'event':
                   switch ($message->Event) {
                       case 'subscribe':
                           $this->upscribe($business_id,$message->FromUserName,1);
                           return '欢迎关注';
                           break;
                       case 'unsubscribe':
                           $this->upscribe($business_id,$message->FromUserName,0);
                           return '取消关注';
                           break;
                       case 'SCAN':
//                           return '用户通过扫描带参二维码'.$message->EventKey;
                           /*Weixin::create([
                               'open_id' => $message->FromUserName,
                               'service_id' => $message->EventKey,
                           ]);

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

                           $pusher->trigger("kefu" . $message->EventKey, "bind-wechat", array("message" => "绑定账号成功！"));*/
                           return 'success';
                           break;
                   }
                   break;
               case 'text':
                   switch ($message->Content) {
                       case 'qq':
                           return 'qq:1500203929';
                           break;
                       case '价格':
                           return '价格：￥4999';
                           break;
                       case '官网':
                           return '请访问:<a href="http://www.80zx.com/">八零在线</a>提供技术驱动';
                           break;
                   }
                   break;
               default:
                   return '收到其它消息';
                   break;
           }

       });

       // 自定义菜单
       $menu = $app->menu;
       $buttons =[
         
            [
                "name"=>"客服系统", 
               "sub_button"=>[ 
                  [
                  "type"=>"view", 
                  "name"=>"工作台",
                  "url"=>$url."/weixin/login/callback/business_id/".$business_id
                  ],
                   [
                       "type" => "view",
                       "name" => "联系客服",
                       "url"  => $url."/index/index/wechat/groupid/0/business_id/".$business_id
                   ]
                 ],
            ], 
       ];


       $menu->add($buttons);
       $response = $server->serve();
       $response->send();
   }
//   关注操作
   private function upscribe($business_id,$openid,$subscribe){
       $wxInfo=db('wolive_weixin')->field('subscribe')->where(['business_id'=>$business_id,'open_id'=>$openid])->find();
       if(isset($wxInfo['subscribe'])){
           if($wxInfo['subscribe']!=$subscribe){
//                        不相等则更新
               db('wolive_weixin')->where(['business_id'=>$business_id,'open_id'=>$openid])->update(['subscribe' => $subscribe,'subscribe_time'=>time()]);
           }
       }else{
           db('wolive_weixin')->insert(['subscribe' => $subscribe,'business_id'=>$business_id,'open_id'=>$openid,'subscribe_time'=>time()]);
       }
   }

}
