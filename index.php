<?php
header('Content-type:text');
define("TOKEN", "weiphp");

$wechatObj = new wechatCallbackapiTest();
if (isset($_GET['echostr'])) {
    $wechatObj->valid();
}else{
    $wechatObj->responseMsg();
}

class wechatCallbackapiTest
{

    public $accessToken;
    public $accessTokenExpires;
    public $AppID     = "wx40215b7ca1c8b02d";
    public $AppSecret = "e8e3c24ee413b559a941030a0f379b6c";
    public $url       = "http://qianxiao.applinzi.com";


    public function valid()
    {
        $echoStr = $_GET["echostr"];
        if($this->checkSignature()){
            header('content-type:text');
            echo $echoStr;
            exit;
        }
    }

    /**
     * 获取访问令牌
     * @return mixed
     */
    public function getAccessToken()
    {
        if(!empty($this->accessToken) && time() <= $this->accessTokenExpires)
        {
            return $this->accessToken;
        }

        $appId = $this->AppID;
        $appSecret = $this->AppSecret;
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appId."&secret=".$appSecret;
        $ch  = curl_init($url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//跳过证书验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
        $res = curl_exec($ch);
        if(curl_errno($ch))
        {
            var_dump(curl_error($ch));
        }
        $resArr = json_decode($res,1);
        curl_close($ch);

        $this->accessToken        = $resArr["access_token"];
        $this->accessTokenExpires = time() + $resArr["expires_in"];

        return $this->accessToken;
    }

    public function responseMsg()
    {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

        if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);
            //消息类型分离
            switch ($RX_TYPE)
            {
                case "event"://事件消息
                    $result = $this->receiveEvent($postObj);
                    break;
                case "text"://文本消息
                    $result = $this->receiveText($postObj);
                    break;
                case "image"://图片消息
                    $result = $this->receiveImage($postObj);
                    break;
                case "location"://地理位置消息
                    $result = $this->receiveLocation($postObj);
                    break;
                case "voice"://语音消息
                    $result = $this->receiveVoice($postObj);
                    break;
                case "video"://视频、小视频消息
                case "shortvideo":
                    $result = $this->receiveVideo($postObj);
                    break;
                case "link"://链接消息
                    $result = $this->receiveLink($postObj);
                    break;
                default:
                    $result = "unknown msg type: ".$RX_TYPE;
                    break;
            }
            echo $result;
        }else{
            echo "";
            exit;
        }
    }

    /**
     * 生成二维码
     * @param $scene_info
     * @param int $type
     * @return string
     */
    public function getEwm($scene_info,$type = 1)
    {
        //$scene_info = '{"scene_id": "97"}';
        //$scene_info = '{"scene_str": "type-1-id-97"}';商品
        //$scene_info = '{"scene_str": "type-2-id-206"}';//订单
        $url  = $this->getQrcodeurl($scene_info,$type);
        return $url;
    }

    /**
     * 生成并获取二维码图片地址
     * @param $scene_info 场景内容
     * @param int $type 二维码类型 1：永久二维码 2：临时二维码
     * @return string
     */
    private function getQrcodeurl($scene_info,$type = 1)
    {
        $ACCESS_TOKEN = $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$ACCESS_TOKEN;

        //生成永久二维码
        if($type == 1)
        {
            $code = '{"action_name": "QR_LIMIT_STR_SCENE", "action_info": {"scene": '.$scene_info.'}}';
        }

        //生成临时二维码
        else
        {
            $code = '{"expire_seconds": 1800, "action_name": "QR_SCENE", "action_info": {"scene": '.$scene_info.'}}';
        }

        $result = $this->httpPost($url,$code);
        $oo     = json_decode($result);
        if(!$oo->ticket)
        {
            echo "";
            exit();
        }

        $url = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$oo->ticket.'';

        return $url;
    }

    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }

    protected function httpPost($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);//请求地址
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");//POST请求
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//跳过证书验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_HTTPHEADER, "Accept-Charset: utf-8");
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);
        if (curl_errno($ch))
        {
            curl_close( $ch );
            return $ch;
        }
        else
        {
            curl_close( $ch );
            return $tmpInfo;
        }
    }

    /**
     * 接收文本消息
     * @param $object 前边解析出来的 postObj对象
     * @return string
     */
    private function receiveText($object)
    {
        $content = trim($object->Content);

        if($content == "商品二维码")
        {
            $scene_info = '{"scene_str": "type-1-id-97"}';
            $content =  "二维码图片：".$this->getEwm($scene_info,$type = 1);
        }

        if($content == "订单二维码")
        {
            $scene_info = '{"scene_str": "type-2-id-206"}';//订单
            $content =  "二维码图片：".$this->getEwm($scene_info,$type = 1);
        }
		if($content=="绑定")
        {
            $openid=$object->FromUserName;//微信用户openid
            $timestamp = time()+2*60*60;//设置此次的签名的过期时间
            $signature=$this->getSign($openid,$timestamp);//签名
            
            //要传的参数明文加密转码
            $queryString="openid=".$openid."&timestamp=".$timestamp."&signature=".$signature;
            $tmpStr=rawurlencode(base64_encode($queryString));
            
            //绑定的路由
            $url="http://qianxiao.applinzi.com/login.php?params=".$tmpStr;
            $content =  "您还没有绑定云衫账户哦！绑定后即享\r\n<a href='".$url."'>点击这里，立即绑定</a>";
          
        }
        if($content=="消息列表")
        {
            
            $openid=$object->FromUserName;//微信用户openid
            $timestamp = time()+2*60*60;//设置此次的签名的过期时间
            $signature=$this->getSign($openid,$timestamp);//签名
            
            //要传的参数明文加密转码
            $queryString="openid=".$openid."&timestamp=".$timestamp."&signature=".$signature;
            $tmpStr=rawurlencode(base64_encode($queryString));
            
            //绑定的路由
            $url="http://qianxiao.applinzi.com/login.php?params=".$tmpStr;
            
            //根据openid调用接口查询数据
            //返回数据
            $content=array();
            $content[0]=array('Title'=>'绑定云杉思维','Description'=>'云衫思维让决策更有效！','PicUrl'=>"http://www.4sone.com/pub/images/bannere.png",'Url'=>"http://qianxiao.applinzi.com/login.php?params=".$queryString);
        	return $this->transmitNews($object,$content);
            die();
        }
        $result  = $this->transmitText($object, $content);

        return $result;
    }
    /**
     * 加密处理
     * @param $openid 微信用户openid
     * @param $timestamp 签名过期时间
     * @return string  
     */
    private function getSign($openid,$timestamp)
    {
        $token = "#@FJLW@DKDK112";//密钥
        $token=md5(md5($token));
        
        $timestamp = $timestamp; //时间戳timestamp：这是为了防止链接泄漏出去被恶意利用，具体来说就是一个你指定的过期时间，超过这个时间这个链接就失效了，用户只能再次获取。
        $openID = $openid;//微信用户openID

        $tmpArr = array($token, $timestamp, $openID);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $signature = sha1( $tmpStr );

        return $signature;

    }

    /**
     * 回复文本消息
     * @param $object
     * @param $content contentj是前台传过来的字符串变量，不是数组
     * @return string
     */
    private function transmitText($object, $content)
    {
        if (!isset($content) || empty($content))
        {
            return "";
        }


        $xmlTpl = "
        <xml> 
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[text]]></MsgType>
        <Content><![CDATA[%s]]></Content>
        </xml>";

        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), $content);
        return $result;
    }


    /**
     * 接收事件消息
     * @param $object 前边解析出来的 postObj对象
     * @return string
     */
    private function receiveEvent($object)
    {
        $content = "";
        switch ($object->Event)
        {
            case "subscribe":
                $content  = "欢迎关注";
                break;
            case "unsubscribe":
                $content = "取消关注";
                break;
            case "CLICK"://菜单点击事件
                switch ($object->EventKey)
                {
                    case "COMPANY":
                        $content = array();
                        $content[] = array("Title"=>"方倍工作室", "Description"=>"", "PicUrl"=>"http://discuz.comli.com/weixin/weather/icon/cartoon.jpg", "Url" =>"http://m.cnblogs.com/?u=txw1958");
                        break;
                    default:
                        $content = "点击菜单：".$object->EventKey;
                        break;
                }
                break;
            case "VIEW":
                $content = "跳转链接 ".$object->EventKey;
                break;
            case "SCAN":
                //事件KEY值，是一个32位无符号整数，即创建二维码时的二维码scene_id
                $key = $object->EventKey;
                $arr = explode("-",$key);
                if(count($arr) == 4 && $arr[0]=="type" && $arr[2]=="id")
                {
                    if($arr[1]==1)
                    {
                        $content = "商品地址："."http://cnbm-store-stage.chinacloudapp.cn/pro_details.php?id=$arr[3]";
                    }
                    else if($arr[1]==2)
                    {
                        $content = "订单地址："."http://cnbm-store-stage.chinacloudapp.cn/admin/agent_order.php?act=order_detail&order_id=$arr[3]";
                    }
                }
                break;
            case "LOCATION":
                $content = "上传位置：纬度 ".$object->Latitude.";经度 ".$object->Longitude;
                break;
            case "scancode_waitmsg":
                if ($object->ScanCodeInfo->ScanType == "qrcode")
                {
                    $content = "扫码带提示：类型 二维码 结果：".$object->ScanCodeInfo->ScanResult;
                }
                else if ($object->ScanCodeInfo->ScanType == "barcode")
                {
                    $codeinfo = explode(",",strval($object->ScanCodeInfo->ScanResult));
                    //strval函数将$object->ScanCodeInfo->ScanResult转化成字符串；explode将字符串按其中的逗号分隔成若干的元素，最后赋值给数组codeinfo
                    $codeValue = $codeinfo[1];  //数组中第一个元素的值赋给字符串变量codeValue
                    $content = "扫码带提示：类型 条形码 结果：".$codeValue;
                }
                else
                {
                    $content = "扫码带提示：类型 ".$object->ScanCodeInfo->ScanType." 结果：".$object->ScanCodeInfo->ScanResult;
                }
                break;
            case "scancode_push":
                $content = "扫码推事件";
                break;
            case "pic_sysphoto":
                $content = "系统拍照";
                break;
            case "pic_weixin":
                $content = "相册发图：数量 ".$object->SendPicsInfo->Count;
                break;
            case "pic_photo_or_album":
                $content = "拍照或者相册：数量 ".$object->SendPicsInfo->Count;
                break;
            case "location_select":
                $content = "发送位置：标签 ".$object->SendLocationInfo->Label;
                break;
            default:
                $content = "receive a new event: ".$object->Event;
                break;
        }

        if(is_array($content)){
            $result = $this->transmitNews($object, $content);
        }else{
            $result = $this->transmitText($object, $content);
        }
        return $result;
    }

    /**
     * 接收图片消息---------
     * @param $object 前边解析出来的 postObj对象
     * @return string
     */
    private function receiveImage($object)
    {
        $content = array("MediaId"=>$object->MediaId);
        $result  = $this->transmitImage($object, $content);
        return $result;
    }

    /**
     * 接收位置消息
     * @param $object 前边解析出来的 postObj对象
     * @return string
     */
    private function receiveLocation($object)
    {
        //如果object对象是一个位置信息类的对象，其本身就带有Location_Y、Location_X、Scale、Labe等相关属性
        $content = "你发送的是位置，经度为：".$object->Location_Y."；纬度为：".$object->Location_X."；缩放级别为：".$object->Scale."；位置为：".$object->Label;
        $result  = $this->transmitText($object, $content);
        return $result;
    }

    /**
     * 接收语音消息
     * @param $object 前边解析出来的 postObj对象
     * @return string
     */
    private function receiveVoice($object)
    {
        if (isset($object->Recognition) && !empty($object->Recognition))
        {
            $content = "你刚才说的是：".$object->Recognition;
            $result  = $this->transmitText($object, $content);
        }
        else
        {
            $content = array("MediaId"=>$object->MediaId);
            $result  = $this->transmitVoice($object, $content);
        }
        return $result;
    }

    /**
     * 接收视频消息
     * @param $object
     * @return string
     */
    private function receiveVideo($object)
    {
        $content = "上传视频类型：".$object->MsgType;
        $result  = $this->transmitText($object, $content);
        return $result;
    }

    /**
     * 接收链接消息
     * @param $object
     * @return string
     */
    private function receiveLink($object)
    {
        $content = "你发送的是链接，标题为：".$object->Title."；内容为：".$object->Description."；链接地址为：".$object->Url;
        $result = $this->transmitText($object, $content);
        return $result;
    }

    /**
     * 回复图文消息---------
     * @param $object
     * @param $newsArray newsArray用来接收前边传过来的content二维数组（单图文或多图文）
     * @return string
     */
    private function transmitNews($object, $newsArray)
    {
        if(!is_array($newsArray)){
            return "";
        }
        $itemTpl = "        <item>
            <Title><![CDATA[%s]]></Title>
            <Description><![CDATA[%s]]></Description>
            <PicUrl><![CDATA[%s]]></PicUrl>
            <Url><![CDATA[%s]]></Url>
        </item>
";
        $item_str = "";
        foreach ($newsArray as $item){
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
        }
        $xmlTpl = "<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[news]]></MsgType>
    <ArticleCount>%s</ArticleCount>
    <Articles>$item_str</Articles> //<Articles></Articles>等都是微信定义的格式，由微信平台来解析与PHP语法无任何关系
</xml>";


        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time(), count($newsArray));
        return $result;
    }

    /**
     * 回复音乐消息---------
     * @param $object
     * @param $musicArray musicArray用来接收前边传过来的content一维数组（音乐）
     * @return string
     */
    private function transmitMusic($object, $musicArray)
    {
        if(!is_array($musicArray)){
            return "";
        }
        $itemTpl = "<Music>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
        <MusicUrl><![CDATA[%s]]></MusicUrl>
        <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
    </Music>";


        $item_str = sprintf($itemTpl, $musicArray['Title'], $musicArray['Description'], $musicArray['MusicUrl'], $musicArray['HQMusicUrl']);


        $xmlTpl = "<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[music]]></MsgType>
    $item_str 
</xml>";


        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    /**
     * 回复图片消息
     * @param $object
     * @param $imageArray imageArray用来接收前边传过来的content一维数组（图片）
     * @return string
     */
    private function transmitImage($object, $imageArray)
    {
        $itemTpl = "
        <Image>
        <MediaId><![CDATA[%s]]></MediaId>
        </Image>";

        $item_str = sprintf($itemTpl, $imageArray['MediaId']);

        $xmlTpl = "
        <xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[image]]></MsgType>
        $item_str //就相当于回复文本消息中的 <Content><![CDATA[%s]]></Content>，实际要显示的内容
        </xml>";


        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    /**
     * 回复语音消息---------
     * @param $object
     * @param $voiceArray
     * @return string
     */
    private function transmitVoice($object, $voiceArray)
    {
        $itemTpl = "
        <Voice>
        <MediaId><![CDATA[%s]]></MediaId>
        </Voice>";


        $item_str = sprintf($itemTpl, $voiceArray['MediaId']);
        $xmlTpl = "
        <xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[voice]]></MsgType>
        $item_str
        </xml>";


        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    /**
     * 回复视频消息---------
     * @param $object
     * @param $videoArray
     * @return string
     */
    private function transmitVideo($object, $videoArray)
    {
        $itemTpl = "
        <Video>
        <MediaId><![CDATA[%s]]></MediaId>
        <ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
        </Video>";


        $item_str = sprintf($itemTpl, $videoArray['MediaId'], $videoArray['ThumbMediaId'], $videoArray['Title'], $videoArray['Description']);


        $xmlTpl = "
        <xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[video]]></MsgType>
        $item_str
        </xml>";


        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    /**
     * 回复多客服消息---------
     * @param $object
     * @return string
     */
    private function transmitService($object)
    {
        $xmlTpl = "
        <xml>
        <ToUserName><![CDATA[%s]]></ToUserName>
        <FromUserName><![CDATA[%s]]></FromUserName>
        <CreateTime>%s</CreateTime>
        <MsgType><![CDATA[transfer_customer_service]]></MsgType>
        </xml>";
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    /**
     * 回复第三方接口消息
     * @param $url
     * @param $rawData
     * @return mixed
     */
    private function relayPart3($url, $rawData)
    {
        $headers = array("Content-Type: text/xml; charset=utf-8");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $rawData);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    /**
     * 字节转Emoji表情
     * @param $cp
     * @return string
     */
    function bytes_to_emoji($cp)
    {
        if ($cp > 0x10000){       # 4 bytes
            return chr(0xF0 | (($cp & 0x1C0000) >> 18)).chr(0x80 | (($cp & 0x3F000) >> 12)).chr(0x80 | (($cp & 0xFC0) >> 6)).chr(0x80 | ($cp & 0x3F));
        }else if ($cp > 0x800){   # 3 bytes
            return chr(0xE0 | (($cp & 0xF000) >> 12)).chr(0x80 | (($cp & 0xFC0) >> 6)).chr(0x80 | ($cp & 0x3F));
        }else if ($cp > 0x80){    # 2 bytes
            return chr(0xC0 | (($cp & 0x7C0) >> 6)).chr(0x80 | ($cp & 0x3F));
        }else{                    # 1 byte
            return chr($cp);
        }
    }
}

