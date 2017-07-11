<?php
session_start();

$username=$_POST['username'];
$password=$_POST['password'];
$openid=isset($_SESSION['user']['openid'])?$_SESSION['user']['openid']:'';

//验证用户名密码是否正确

//如果是绑定登录，且登录成功，update用户表的openid字段
if($openid!=''){
	$sql="update table set openid={$openid} where userid=?";
}

//登录成功，添加openid成功，提示登录成功，跳转页面
include('./view/success.html');

?>


