<?php


namespace app\service\controller;

use app\service\model\AdminLog;
use think\Db;
use app\Common;
/**
 *
 * 后台页面控制器.
 */
class Log extends Base
{

    public function index()
    {
        if($this->request->isAjax()){
            return AdminLog::getLog();
        }
        return $this->fetch();
    }

    public function removeLog(){
        Db::name('wolive_admin_log')->where(['uid'=>$_SESSION['Msg']['service_id']])->delete(true);
        $this->success('操作成功');
    }

    public function data(){
        if($this->request->isAjax()){
            $time_range = input('get.time');
            $business_id = $_SESSION['Msg']['business_id'];
            if($time_range){
                $time_range = explode('~',$time_range);
                $days = Common::getDaysByDay(trim($time_range[0]),trim($time_range[1]));
            }else{
                $days = Common::getDays(30);
            }
            $data = [];
            foreach ($days as $k=>$v){
                $first = strtotime($v);
                $last = $first+86400-1;
                $data[$k]['id'] = $k+1;
                $data[$k]['date'] = $v;
                $data[$k]['new_queue'] = Db::name('wolive_queue')->where("UNIX_TIMESTAMP(timestamp) BETWEEN $first AND $last AND business_id = $business_id")->count();
                $data[$k]['chats'] = Db::name('wolive_chats')->where("timestamp BETWEEN $first AND $last AND business_id = $business_id AND direction = 'to_service'")->group('visiter_id')->count();
                $data[$k]['service_chats'] = Db::name('wolive_chats')->where("timestamp BETWEEN $first AND $last AND business_id = $business_id AND direction = 'to_visiter'")->group('visiter_id')->count();
                $data[$k]['reply_date'] = $data[$k]['chats']?round($data[$k]['service_chats']/$data[$k]['chats']*100,2):0;
                if($data[$k]['reply_date']>=100){
                    $data[$k]['reply_date'] = '100%';
                }else{
                    $data[$k]['reply_date'] = $data[$k]['reply_date'].'%';
                }
            }
            return ['code'=>0,'data'=>$data,'count' => 0, 'limit' => 0];
        }
        return $this->fetch();
    }
}