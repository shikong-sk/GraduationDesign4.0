<?php
//require_once($_SERVER['DOCUMENT_ROOT'] . '/System/Core/Class/Abstract/UserClass.php');
//
//require_once($_SERVER['DOCUMENT_ROOT'] . '/System/Core/Class/ManagementClass.php');
//
//$user = new User();
//
//$user->login('1','123+AbC');
//
//$management = new ManagementClass();
//
//$management->getPlaceList(5,500,'','');

//echo realpath('./');

$img = "data:image/png;base64,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";

$img = "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";

if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $img, $result)) {


    $type = $result[2];
    $new_file = "./test.{$type}";

    $img = str_replace($result[1], '', $img);

}
//var_dump($img);
//var_dump($result);

//include_once './Class/FileClass.php';
//
//class FileManager extends FileClass{
//
//}
//$f = new FileManager();
//echo $f->_root_ . $f->allowDirs[0];
//
//echo $f->uploadImage($img,'car');

//include_once './Class/Abstract/DataClass.php';
//
////header('Content-Type:application/json; charset=utf-8');
//
//$d = new DataClass('1','测试1','0','00000');
//
//die($d->pushPersonnel('1583814308','测试','440500199010011111','0','37.0'));

//ignore_user_abort(true);//关闭浏览器后，继续执行php代码
//set_time_limit(600);//程序执行时间无限制

//$_POST['id'];
//$device = $this->db->database->query("SELECT deviceID FROM $this->deviceTable WHERE deviceID = '$this->device'")->fetch_assoc()['deviceID'];

//$_POST['time'] = '2020-01-01 00:00:00';
////$blacklist = Array("order by",'or','and','rpad','concat',' ','union','%a0',',','if','xor','join','rand','floor','outfile','mid','#','\|\|','--+','0[xX][0-9a-fA-F]+');
////foreach ($_POST as $key => $value)
////{
////    foreach ($blacklist as $blackItem){
////        if (preg_match('/\b' . $blackItem . '\b/im', $value)) {
////            if($key = 'time' && $blackItem = ' ')
////            {
////                continue;
////            }
////            die('非法参数'.$value);
////        }
////    }
////}
////
////
////

session_start();

//require_once(dirname(__FILE__). '/Core.php');

//echo get_real_ip();
//
//echo '<br>';
//
//echo date('Y-m-d H:i:s');
//
//$d = strtotime('2020-01-01 23:00:00');
//var_dump(date('m',$d));
//var_dump(date('d',$d));
//var_dump(date('H',$d));

//include_once './Class/Abstract/FileClass.php';



//$_SESSION['user'] = '1';
//$_SESSION['device'] = '1';

//$f = new FileManager();
//echo $f->_root_ . $f->allowDirs[0];


//var_dump(json_decode($f->uploadImage($img,'car','2020-01-01 23:00:00'),true)[0]);

//echo $f->deleteImage('\/Storage\/Car\/2020\/01\/01\/23\/00\/64E437A5-20C3-6270-5E18-1C6C80C75D1D.png','car');


//include_once './Class/RoleClass.php';
//
//$r = new roleClass($_SESSION['uid']);
//
//echo $r->getRoleList();

//$data = "[
//    {
//        \"id\": \"91A41A60-3F2F-7C35-ADA9-7FC3B4D17403\",
//        \"areaCode\": \"440511000\",
//        \"dareaCode\": \"4405110000000990000\",
//        \"addPersonnel\": \"1\",
//        \"delPersonnel\": \"1\",
//        \"updatePersonnel\": \"1\",
//        \"selectPersonnel\": \"1\",
//        \"addCar\": \"1\",
//        \"delCar\": \"1\",
//        \"updateCar\": \"1\",
//        \"selectCar\": \"1\",
//        \"addEquipment\": \"0\",
//        \"delEquipment\": \"0\",
//        \"updateEquipment\": \"0\",
//        \"selectEquipment\": \"0\"
//    },
//    {
//        \"id\": \"91A41A60-3F2F-7C35-ADA9-7FC3B4D17403\",
//        \"areaCode\": \"440511005\",
//        \"dareaCode\": \"4405110000000990000\",
//        \"addPersonnel\": \"1\",
//        \"delPersonnel\": \"1\",
//        \"updatePersonnel\": \"1\",
//        \"selectPersonnel\": \"1\",
//        \"addCar\": \"1\",
//        \"delCar\": \"1\",
//        \"updateCar\": \"1\",
//        \"selectCar\": \"0\",
//        \"addEquipment\": \"0\",
//        \"delEquipment\": \"0\",
//        \"updateEquipment\": \"0\",
//        \"selectEquipment\": \"0\"
//    }
//]";
//
//$a = ["440511000","4405110000000990000"];
//$data = json_decode($data,true);
//
//foreach ($a as $t){
//    if(!in_array($t,$d = array_shift($data))){
//        echo $t;
//        var_dump($d);
//    }
//}
//require_once './Class/SqlHelper.php';
//error_reporting(E_ALL);

//$f = new FileManager();

//$db = new SqlHelper();
//$file_maxSize = $f->MaxFileSize;
//$disk_maxSize = intval(disk_free_space('./')/1024) - 51200;
//$personnelTable = $db->db_table_prefix . "_" . SqlHelper::Personnel;
//$personnelRecord = $db->database->query("SELECT count(*) num FROM $personnelTable")->fetch_assoc()['num'];
//$max_record = $disk_maxSize / $file_maxSize / 2 / 2;
//if($personnelRecord > $max_record)
//{
//
//}

//$maxNum = 123000 - 120000;
//$maxPage =  ceil(($maxNum)/5000);
//
//for($page=0;$page<$maxPage;$page++)
//{
//    if($maxNum>5000)
//    {
//        $num = 5000;
//        $maxNum -= 5000;
//    }
//    else{
//        $num = $maxNum;
//    }
//    echo "$page".','."$num\n";
//}
//
//$url = '1';
//$v = 'result';
//$k = 'url';
//$$k = $v;
//echo $url;
//
//$area = '1234567890123';
//
//echo substr($area,0,9);

//var_dump(dirname(__FILE__));
//
//echo $f->deleteImage('/Storage/Personnel/2020/03/21/12/12/现场/123123_现场_0FB15CFC-66D7-6FB4-22E1-1B85FB548284.png','personnel');

$json = json_decode("{
    \"passTime\": \"2020-03-28 17:11:38\",
    \"plateNum\": \"\",
    \"plateColor\": \"\",
    \"vehicleType\": \"轿车\",
    \"vehicleImg\": \"AAAAAElFTkSuQmCC\",
    \"areaCode\": \"440511000\",
    \"x\": \"\",
    \"y\": \"\",
    \"equipmentid\": \"440511000000001000\",
    \"equipmentName\": \"\",
    \"equipmentType\": \"01\",
    \"stationId\": \"\",
    \"stationName\": \"\",
    \"location\": \"\",
    \"vehicleColor\": \"\",
    \"dareaName\": \"默认\",
    \"dareaCode\": \"4405110000000990000\",
    \"cartype\": 3,
    \"placeType\": \"9\",
    \"status\": 0,
    \"visitReason\": \"\",
    \"visitor\": \"\",
    \"driverData\": \"\",
    \"passengerData\": \"\"
}",true);

if(!$json)
{
    echo 'json 格式错误';
}

//var_dump($json);
//
//if(array_key_exists('passTime',$json)){
//    var_dump($json['passTime']);
//}
//if(!array_key_exists('passTime2123',$json)){
//    var_dump('passTime2123 不存在');
//}


error_reporting(E_ALL);

require_once(dirname(__FILE__) .'/Class/StudentClass.php');
require_once(dirname(__FILE__) .'/Class/TeacherClass.php');

require_once(dirname(__FILE__) .'/Class/DepartmentClass.php');
$student = new StudentClass();
$teacher = new TeacherClass();

$department = new DepartmentClass();

ob_flush();
ob_clean();
//$student->register(
//    Array('studentId'=>"1730502127",'studentName'=>'test','gender'=>'男','both'=>'1999-07-10','password'=>'123+AbC',
//        'contact'=>'13600000000','grade'=>'17','years'=>'3','departmentId'=>'05','majorId'=>'02','classId'=>'17305021','class'=>'1','seat'=>'27','idCard'=>'440000199907102912'
//    )
//);

header('Content-Type:application/json; charset=utf-8');

//$student->login(Array('studentId'=>'1730502127','password'=>'123+AbC'));
$teacher->login(Array('teacherId'=>'1','password'=>'123+AbC'));

//echo $teacher->updateInfo(Array('teacherId'=>'1','password'=>'123+AbC'),Array('teacherImg'=>$img,'password'=>'123+AbC','salt'=>'123123'));

error_reporting(E_ALL);
//echo $student->getUserInfo();
//echo $teacher->getUserInfo();

//echo $department->addDepartment(Array('departmentId'=>'11','departmentName'=>'测试','active'=>'0'));

echo $department->updateDepartment(Array('departmentId'=>'05','departmentName'=>'计算机系','active'=>'1'));

//echo $department->getDepartmentList(Array('departmentName'=>'计算机','active'=>1),Array('page'=>'1','num'=>'10'));

//echo $student->updateInfo(Array('studentId'=>'1730502127','password'=>'123+AbC'),Array('studentImg'=>$img,'password'=>'123+AbC','salt'=>'123123'));

$student->logout();
//$file = new FileClass();

//echo $file->uploadUserImage($img);

//echo $file->deleteFile($file->uploadUserImage($img));

//$a = Array('A'=>'1','B'=>'2','C'=>'3');
//$t = "INSERT INTO t (";
//foreach ($a as $k=>$v)
//{
//    $t .= "`$k`,";
//}
//$t = rtrim($t,',');
//$t.=") VALUES (";
//foreach ($a as $k=>$v)
//{
//    $t .= "'$v',";
//}
//$t = rtrim($t,',');
//$t.= ")";
//echo $t;

//$q = "SELECT a,b,c FROM AAA";
//var_dump(preg_replace('/SELECT (.*) FROM/','SELECT count(*) num FROM',$q,1));
//$json = Array('1'=>'1','2'=>'2');
//array_unshift($json,'0');
//var_dump($json);