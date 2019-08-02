<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WeixinController extends Controller
{
    //
    public function valid(){
        echo $_GET['echostr'];
    }

    //获取access token
    public function get_token()
    {

    }

    public function wxEvent()
    {
        $content = file_get_contents("php://input");
        $time=date("Y-m-d h:i:s");
        $str = $content . $time ."/n";
        $path = 'logs/wx_event.log';
        if(!is_dir($path))
        {
            file_put_contents($path,$str,FILE_APPEND);
        }
        $obj = simplexml_load_string($content); //将XML字符串转换成simplexml对象
        $open_id = $obj->FromUserName;      //用户openid
        $kf_id = $obj->ToUserName;            // 公众号ID
        $msg_type = $obj->MsgType;          //消息类型
        $event = $obj->Event;
        //用户关注微信公众号
        if($event=='subscribe'){
            $userInfo=DB::table('wx_user')::where('openid','=',"$open_id")->first();
            if($userInfo){
                echo '<xml><ToUserName><![CDATA['.$open_id.']]></ToUserName>
                        <FromUserName><![CDATA['.$kf_id.']]></FromUserName>
                        <CreateTime>.time().</CreateTime>
                        <MsgType><![CDATA[text]]></MsgType>
                        <Content><![CDATA['.'欢迎回来'.$userInfo['nickname'].']]></Content>
                </xml>';
            }else{
                //获取用户信息
                $info = $this->WxUserInfo($open_id);//对象格式
                //用户信息入库
                $data=[
                    'openid'=>$info->openid,
                    'nickname'=>$info->nickname,
                    'sex'=>$info->sex,
                    'province'=>$info->province,
                    'headimgurl'=>$info->headimgurl,
                    'subscribe_time'=>$info->subscribe_time
                ];
                WxUserModel::insert($data);
                echo '<xml>
                        <ToUserName><![CDATA['.$open_id.']]></ToUserName>
                        <FromUserName><![CDATA['.$kf_id.']]></FromUserName>
                        <CreateTime>'.time().'</CreateTime>
                        <MsgType><![CDATA[text]]></MsgType>
                        <Content><![CDATA['. '谢谢'. $info->nickname .'小可爱  这么好看还关注我'.']]></Content>
                      </xml>';

            }

        }
    }
}
