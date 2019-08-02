<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class WeixinController extends Controller
{
    //
    public function valid(){
        echo $_GET['echostr'];
    }

    //获取access token
    public function get_token()
    {
        $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_SECRET');
        $response = json_decode(file_get_contents($url),true);
        $token = Redis::set('wx_access_token',$response['access_token']);
        return $token;
    }

    //
    public function wxEvent()
    {
        $content = file_get_contents("php://input");
        $time=date("Y-m-d h:i:s");
        $str = $content . $time ."/n";
        file_put_contents('logs/wx_event.log',$str,FILE_APPEND);
        $obj = simplexml_load_string($content); //将XML字符串转换成simplexml对象
        $open_id = $obj->FromUserName;      //用户openid
        $kf_id = $obj->ToUserName;            // 公众号ID
        $msg_type = $obj->MsgType;          //消息类型
        $event = $obj->Event;
        //用户关注微信公众号
        if($event=='subscribe'){
            $userInfo=WxUserModel::where('openid','=',"$open_id")->first();
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

        if($msg_type=='text'){          //处理文本消息
            //纯文字信息  不回复
            $content=$obj->Content;
            $arr=[
                'type'=>$msg_type,
                'font'=>$content,
                'create_time'=>time(),
                'openid'=>$open_id
            ];
            $res=WxUserContentModel::insert($arr);
            if($res){
                echo 'SUCCESS';
            }else{
                echo 'fail';
            }

            //推送最新商品
            if(strpos($obj->Content,'商品')){
                $v=GoodsModel::orderBy('create_time','desc')->first();
                // dd($data);
                echo '<xml>
						  <ToUserName><![CDATA['.$open_id.']]></ToUserName>
						  <FromUserName><![CDATA['.$kf_id.']]></FromUserName>
						  <CreateTime>'.time().'</CreateTime>
						  <MsgType><![CDATA[news]]></MsgType>
						  <ArticleCount>1</ArticleCount>
						  <Articles>
						    <item>
						      <Title><![CDATA['.$v->goods_name.']]></Title>
						      <Description><![CDATA['.$v->desc.']]></Description>
						      <PicUrl><![CDATA['.'http://1809zhangyuzhen.zyzyz.top/img/share.jpeg'.']]></PicUrl>
						      <Url><![CDATA['.'http://1809zhangyuzhen.zyzyz.top/goods/detail/?goods_id='.$v->goods_id.']]></Url>
						    </item>
						  </Articles>
						</xml>';
            }else{
                //自动回复天气信息
                if(strpos($obj->Content,"-天气")){
                    $city=explode('-',$obj->Content)[0];
                    $url="https://free-api.heweather.net/s6/weather/now?key=97fd134d532b42bab66e7d97ab9f1c9f&location=".$city;
                    $arr=json_decode(file_get_contents($url),true);
//                dd($arr);
                    if($arr['HeWeather6'][0]['status']=='ok'){
                        $shd = $arr['HeWeather6'][0]['now']['tmp'];//摄氏度
                        $fx = $arr['HeWeather6'][0]['now']['wind_dir'];//风向
                        $fl = $arr['HeWeather6'][0]['now']['wind_sc'];//风力
                        $sd = $arr['HeWeather6'][0]['now']['hum'];//湿度
                        $tq = $arr['HeWeather6'][0]['now']['cond_txt'];//天气
                        $str = "温度：".$shd."\n" . "风向：".$fx."\n" . "风力：".$fl."\n" . "湿度：".$sd."\n" . "天气：".$tq."\n";
                        echo'<xml>
                                  <ToUserName><![CDATA['.$open_id.']]></ToUserName>
                                  <FromUserName><![CDATA['.$kf_id.']]></FromUserName>
                                  <CreateTime>'.time().'</CreateTime>
                                  <MsgType><![CDATA[text]]></MsgType>
                                  <Content><![CDATA['.$str.']]></Content>
                            </xml>';
                    }else{
                        $data="请输入正确的城市进行查询";
                        echo'<xml>
                                  <ToUserName><![CDATA['.$open_id.']]></ToUserName>
                                  <FromUserName><![CDATA['.$kf_id.']]></FromUserName>
                                  <CreateTime>'.time().'</CreateTime>
                                  <MsgType><![CDATA[text]]></MsgType>
                                  <Content><![CDATA['.$data.']]></Content>
                            </xml>';
                    }
                }
            }

            //用户发送商品名称 推送数据  模糊搜寻
            $data = GoodsModel::where('goods_name','like',"%$obj->Content%")->first();
            if($data){
                echo '<xml>
						  <ToUserName><![CDATA['.$open_id.']]></ToUserName>
						  <FromUserName><![CDATA['.$kf_id.']]></FromUserName>
						  <CreateTime>'.time().'</CreateTime>
						  <MsgType><![CDATA[news]]></MsgType>
						  <ArticleCount>1</ArticleCount>
						  <Articles>
						    <item>
						      <Title><![CDATA['.$data->goods_name.']]></Title>
						      <Description><![CDATA[没啥]]></Description>
						      <PicUrl><![CDATA[http://1809zhangyuzhen.zyzyz.top/goodsimg/'.$data->goods_img.']]></PicUrl>
						      <Url><![CDATA['.'http://1809zhangyuzhen.zyzyz.top/goods/detail/?goods_id='.$data->goods_id.']]></Url>
						    </item>
						  </Articles>
						</xml>';
            }else{
                $all_data = GoodsModel::all()->toArray();
                $key_name = array_rand($all_data,1);
                echo '<xml>
						  <ToUserName><![CDATA['.$open_id.']]></ToUserName>
						  <FromUserName><![CDATA['.$kf_id.']]></FromUserName>
						  <CreateTime>'.time().'</CreateTime>
						  <MsgType><![CDATA[news]]></MsgType>
						  <ArticleCount>1</ArticleCount>
						  <Articles>
						    <item>
						      <Title><![CDATA['.$all_data[$key_name]['goods_name'].']]></Title>
						      <Description><![CDATA[没啥]]></Description>
						      <PicUrl><![CDATA[http://1809zhangyuzhen.zyzyz.top/goodsimg/'.$all_data[$key_name]['goods_img'].']]></PicUrl>
						      <Url><![CDATA['.'http://1809zhangyuzhen.zyzyz.top/goods/detail/?goods_id='.$all_data[$key_name]['goods_id'].']]></Url>
						    </item>
						  </Articles>
						</xml>';
            }

        }else if($msg_type='event'){
            $event=$obj->Event;
            $scene_id=$obj->EventKey;
            $goods_id=substr($scene_id,0,1);
            switch($event){     //已关注
                case 'SCAN':                //扫码
                    if(isset($obj->EventKey)){
                        $this->dealCode($obj);
                    }
                    break;
                case 'subscribe':
                    $this->subscribe($obj);//扫码关注
                default:
                    $response_xml = 'success';
                    echo $response_xml;
            }
        }

//
    }

}
