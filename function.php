<?php

//验证签名
function checkSign($params)
{
    $timestamp = isset($params['timestamp'])?$params['timestamp']:''; //时间戳timestamp：这是为了防止链接泄漏出去被恶意利用，具体来说就是一个你指定的过期时间，超过这个时间这个链接就失效了，用户只能再次获取。
    $openID = isset($params['openid'])?$params['openid']:'';//微信用户openID
    $signature=isset($params['signature'])?$params['signature']:'';
    
    if( $timestamp == '' || $openID == "" || $signature == ""){
     	return false;
    }

    $token = "#@FJLW@DKDK112";//密钥
    $token=md5(md5($token));

    $tmpArr = array($token, $timestamp, $openID);
    sort($tmpArr, SORT_STRING);
    $tmpStr = implode( $tmpArr );
    $tmpStr = sha1( $tmpStr );
	
    
    //超时或者签名错误
   	if(  ( $tmpStr === $signature ) && ( $timestamp>=time() )  ){
        return true;
    }else{
       return false;
    }
}

//将地址栏参数字符串解析成数组
function parseParams($str)
{
    $arrParams = array();
    parse_str(html_entity_decode(urldecode($str)), $arrParams);
    return $arrParams;
}



?>