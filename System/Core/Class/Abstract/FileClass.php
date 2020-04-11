<?php

require_once(dirname(__FILE__). '/../Abstract/UserClass.php');
require_once(dirname(__FILE__). '/../RoleClass.php');

header('Content-Type:application/json; charset=utf-8');

abstract class FileClass
{
    var $fileName;
    var $fileSize;
    var $allowDirs = Array();
    var $MaxFileSize = 512;
    var $_root_;

    var $user;
    var $rolePermission;


    public function __construct()
    {
        if(!isset($_SESSION['user']))
        {
            die(json_encode(Array('error'=>'请先登录再进行此操作'), JSON_UNESCAPED_UNICODE));
        }
        else{
            $this->_root_ = dirname(__FILE__) .'/../../../../Storage';
            $this->user = new User();
            if($this->user->getPermission()->fetch_assoc()['permission'] == 0)
            {
//                die(json_encode(Array('error'=>'您没有该权限'), JSON_UNESCAPED_UNICODE));
                $this->allowDirs['user']='/User';
            }
            else{
                $this->allowDirs['user']='/User';
                $this->allowDirs['car']='/Car';
                $this->allowDirs['personnel']='/Personnel';
            }
            if(isset($_SESSION['device']))
            {
                $this->allowDirs['car']='/Car';
                $this->allowDirs['personnel']='/Personnel';
            }
//            $this->rolePermission = new rolePermissionClass($this->user->getRole());
//            if($this->rolePermission->checkAddCar() || $this->rolePermission->checkUpdateCar() || $this->rolePermission->checkDeleteCar()){
//                $this->allowDirs['car']='/Car';
//            }
//            if($this->rolePermission->checkAddPersonnel() || $this->rolePermission->checkUpdatePersonnel() || $this->rolePermission->checkDeletePersonnel()){
//                $this->allowDirs['personnel']='/Personnel';
//            }

//            if(count($this->allowDirs) <= 0){
//                die(json_encode(Array('error'=>'您没有该权限'), JSON_UNESCAPED_UNICODE));
//            }
        }
    }

    public function uploadUserImage($imgData){
        $dir = $this->_root_.$this->allowDirs['user'];
        $file_path = '/Storage'.$this->allowDirs['user'];

        if(!file_exists($dir))
        {
            mkdir($dir);
        }

        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $imgData, $result)) {
            if(!isset($result[0]) && !isset($result[1]) && !isset($result[2]))
            {
                return json_encode(Array('error' => "操作失败，图片数据错误"), JSON_UNESCAPED_UNICODE);
            }

            $type = $result[2];

            $file_name = trim(guid(), '{}').".$type";
            $new_file = $dir."/".$file_name;

            $img = str_replace($result[1], '', $imgData);
            $imgLen = strlen($img);
            $imgSize = $imgLen - ($imgLen / 8) * 2;

            $imgSize = $imgSize / 1024;
            if($imgSize > $this->MaxFileSize)
            {
                return json_encode(Array('error' => "操作失败，文件大小不能大于 $this->MaxFileSize kB"), JSON_UNESCAPED_UNICODE);
            }

            if (file_put_contents($new_file, base64_decode($img))) {
                return json_encode(Array(0=>$file_path.'/'.$file_name,'success' => '文件上传成功'), JSON_UNESCAPED_UNICODE);
            }

        }
    }

    public function uploadImage($imgData,$source,$time,$fileName=''){
        if(!isset($this->allowDirs['personnel']) && !isset($this->allowDirs['car'])){
            die(json_encode(Array('error'=>'您没有该权限'), JSON_UNESCAPED_UNICODE));
        }

        if(strlen($imgData) <= 0 && strlen($source) <= 0 && strlen($time) <= 0)
        {
            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
        }

        $time = strtotime($time);
        $Y = date('Y',$time);
        $m = date('m',$time);
        $d = date('d',$time);
        $H = date('H',$time);
        $i = date('i',$time);

        switch ($source){
            case 'personnel' : $dir = $this->_root_.$this->allowDirs['personnel'];$file_path = '/Storage'.$this->allowDirs['personnel'];break;
            case 'car' : $dir = $this->_root_.$this->allowDirs['car'];$file_path = '/Storage'.$this->allowDirs['car'];break;
            default :
                return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
        }

        if(!file_exists($dir))
        {
            mkdir($dir);
        }
        if(!file_exists($dir.'/'.$Y))
        {
            mkdir($dir.'/'.$Y);
        }
        if(!file_exists($dir.'/'.$Y.'/'.$m))
        {
            mkdir($dir.'/'.$Y.'/'.$m);
        }
        if(!file_exists($dir.'/'.$Y.'/'.$m.'/'.$d))
        {
            mkdir($dir.'/'.$Y.'/'.$m.'/'.$d);
        }
        if(!file_exists($dir.'/'.$Y.'/'.$m.'/'.$d.'/'.$H))
        {
            mkdir($dir.'/'.$Y.'/'.$m.'/'.$d.'/'.$H);
        }
        if(!file_exists($dir.'/'.$Y.'/'.$m.'/'.$d.'/'.$H.'/'.$i))
        {
            mkdir($dir.'/'.$Y.'/'.$m.'/'.$d.'/'.$H.'/'.$i);
        }

        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $imgData, $result)) {
            $type = $result[2];

            $file_name = $fileName . '_' . trim(guid(), '{}') . ".$type";

            $img = str_replace($result[1], '', $imgData);

        }
        else{
            $type = '.png';
            $file_name = $fileName . '_' . trim(guid(), '{}') . ".$type";
            $img = $imgData;
        }

            if($source == 'personnel')
            {
                if(!file_exists($dir.'/'.$Y.'/'.$m.'/'.$d.'/'.$H.'/'.$i.'/证件'))
                {
                    mkdir($dir.'/'.$Y.'/'.$m.'/'.$d.'/'.$H.'/'.$i.'/证件');
                }
                if(!file_exists($dir.'/'.$Y.'/'.$m.'/'.$d.'/'.$H.'/'.$i.'/现场'))
                {
                    mkdir($dir.'/'.$Y.'/'.$m.'/'.$d.'/'.$H.'/'.$i.'/现场');
                }
                if(strstr($fileName,'_证件'))
                {
                    $file_name = '证件/'.$file_name;
                }
                else if(strstr($fileName,'_现场')){
                    $file_name = '现场/'.$file_name;
                }
            }

            if($source == 'car')
            {
                if(!file_exists($dir.'/'.$Y.'/'.$m.'/'.$d.'/'.$H.'/'.$i.'/车辆'))
                {
                    mkdir($dir.'/'.$Y.'/'.$m.'/'.$d.'/'.$H.'/'.$i.'/车辆');
                }
                if(!file_exists($dir.'/'.$Y.'/'.$m.'/'.$d.'/'.$H.'/'.$i.'/车牌'))
                {
                    mkdir($dir.'/'.$Y.'/'.$m.'/'.$d.'/'.$H.'/'.$i.'/车牌');
                }
                if(strstr($fileName,'_车辆'))
                {
                    $file_name = '车辆/'.$file_name;
                }
                else if(strstr($fileName,'_车牌')){
                    $file_name = '车牌/'.$file_name;
                }
            }

            $new_file = $dir.'/'.$Y.'/'.$m.'/'.$d.'/'.$H.'/'.$i."/".$file_name;


            $imgLen = strlen($img);
            $imgSize = $imgLen - ($imgLen / 8) * 2;

            $imgSize = $imgSize / 1024;
            if($imgSize > $this->MaxFileSize)
            {
                die(json_encode(Array('error' => "操作失败，文件大小不能大于 $this->MaxFileSize kB"), JSON_UNESCAPED_UNICODE));
            }

            if (file_put_contents($new_file, base64_decode($img))) {
                return json_encode(Array(0=>$file_path.'/'.$Y.'/'.$m.'/'.$d.'/'.$H.'/'.$i.'/'.$file_name,'success' => '文件上传成功'), JSON_UNESCAPED_UNICODE);
            }



    }

    public function deleteImage($imgPath,$source){
        if(strlen($imgPath) <= 0)
        {
            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
        }

        switch ($source){
            case 'personnel' : break;
            case 'car' : break;
            case 'user':break;
            default :
                return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
        }

//        die($this->_root_.'/..'.$imgPath);

//        var_dump($this->_root_.'/..'.stripcslashes($imgPath));

        if(unlink($this->_root_.'/..'.stripcslashes($imgPath))){
            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
        }
        else{
            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
        }
    }

    public function setRoot($root){
        $this->_root_ = $root;
    }
}

class FileManager extends FileClass{

}