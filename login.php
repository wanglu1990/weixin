<?php
require_once "./function.php";

//获取参数
$queryString=isset($_GET['params'])?$_GET['params']:'';

//如果queryString参数为空普通登陆，不进行绑定操作
if($queryString==''){
    
	//普通登录处理,不需要绑定微信号
    
}else{
    
    //绑定微信号登录
	
    //解析参数
	$queryString=base64_decode(rawurldecode($queryString));
   	$params=parseParams($queryString);
        
    
    
    //校验签名
	if( !checkSign($params) ){
    	include("./view/error.html");
        die();
    }
    
    session_start();
    $_SESSION['user']['openid']=$params['openid'];
    
    
}

include('./view/login.html');
























