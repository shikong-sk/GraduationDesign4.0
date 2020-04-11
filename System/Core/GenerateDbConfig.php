<?php

/*
 * 数据库配置文件生成测试
 */

$file = '../Config/db.config.php';

$db_ip = "127.0.0.1";
$db_port = "3306";
$db_user = "root";
$db_password = "";

$db_name = "management_system";
$db_table_prefix="ms";
$f = fopen($file,'w+');

//fwrite($f,"<?php\n\theader(\"HTTP/1.1 403 Forbidden\");\n");

fwrite($f,"<?php\n");

fwrite($f,"\t".'$db_ip = "' . "$db_ip" . '";' ."\n");
fwrite($f,"\t".'$db_port = "' . "$db_port" . '";' ."\n");
fwrite($f,"\t".'$db_user = "' . "$db_user" . '";' ."\n");
fwrite($f,"\t".'$db_password = "' . "$db_password" . '";' ."\n");
fwrite($f,"\t".'$db_name = "' . "$db_name" . '";' ."\n");
fwrite($f,"\t".'$db_table_prefix = "' . "$db_table_prefix" . '";' ."\n");
