<?php

/*
 * 系统核心函数
 */

error_reporting(0);

date_default_timezone_set('PRC'); // 默认时区 中国

//$global_lifeTime = 60 * 60 * 2 * 1;
//session_set_cookie_params($global_lifeTime); // 全局默认SESSION 有效时间：（60）秒 （60）分 （24）时 （365）天

session_start();

function mTime()
{
    $t = explode(' ',microtime());
    $t = $t[0] * 1000;
    $t = round($t,0);
    if($t < 100) $t = '0'.strval($t);
    if($t < 10) $t = '0'.strval($t);
    if($t < 1) $t = '0'.strval($t);
    return $t;
}

function uDate($format)
{
    return date(preg_replace('`(?<!\\\\)u`',mTime(),$format));
}

/*
 * 生成唯一ID
 */
function guid() {
    if (function_exists('com_create_guid')){
        $uuid = com_create_guid();
    }else{
        mt_srand();//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = "-";
        $uuid = "{"
            .substr($charid,0,8 ).$hyphen
            .substr($charid, 8,4 ).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20)
            ."}";
    }
    return $uuid;
}

/*
 *  获取客户端IP
 */

function get_real_ip()

{

    $ip=FALSE;

    //客户端IP 或 NONE

    if(!empty($_SERVER["HTTP_CLIENT_IP"])){

        $ip = $_SERVER["HTTP_CLIENT_IP"];

    }

    //多重代理服务器下的客户端真实IP地址（可能伪造）,如果没有使用代理，此字段为空

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {

        $ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);

        if ($ip) { array_unshift($ips, $ip); $ip = FALSE; }

        for ($i = 0; $i < count($ips); $i++) {

            if (!eregi ("^(10│172.16│192.168).", $ips[$i])) {

                $ip = $ips[$i];

                break;

            }

        }

    }

    //客户端IP 或 (最后一个)代理服务器 IP

    return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);

}

/*
 * 兼容raw json格式数据
 */
if(count($_POST) == 0){
    $_POST = json_decode(file_get_contents("php://input"),true);
}

/*
 * 过滤非法参数（$_POST 通用）
 */
$blacklist = Array("order by",'or','and','rpad','concat',' ','union','%a0',',','if','xor','join','rand','floor','outfile','mid','#','\|\|','--+','0[xX][0-9a-fA-F]+');
foreach ($_POST as $key => $value)
{
    if(is_array($value) || is_array(json_decode($value,true))){
        if(is_array(json_decode($value,true)))
        {
           $value =  json_decode($value,true);
        }
        foreach ($value as $k => $v){
            foreach ($blacklist as $blackItem){
                if (boolval(preg_match('/\b' . $blackItem . '\b/im', $v))) {
                    if((strstr($k,'time') || strstr($k,'Time')) || strstr($k,'Img') || strstr($k,'img'))
                    {
                        continue;
                    }
                    die(json_encode(Array('error'=>'非法参数'.$v)));
                }
            }
        }
    }
    else{
        foreach ($blacklist as $blackItem){
            if (boolval(preg_match('/\b' . $blackItem . '\b/im', $value))) {
                if(($key == 'time' || strstr($key,'Time')) || strstr($key,'Img'))
                {
                    continue;
                }
                die(json_encode(Array('error'=>'非法参数'.$value)));
            }
        }
    }
}