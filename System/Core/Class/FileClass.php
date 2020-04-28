<?php

class FileClass
{
    var $fileName;
    var $fileSize;
    var $allowDirs = Array();
    var $MaxFileSize = 512;
    var $_root_;

    var $user;
    var $identity;


    public function __construct()
    {
        if(!isset($_SESSION['ms_user']))
        {
            die(json_encode(Array('error'=>'请先登录再进行此操作'), JSON_UNESCAPED_UNICODE));
        }
        else{
            $this->_root_ = dirname(__FILE__) .'/../../../Storage';
            $this->user = $_SESSION['ms_id'].'_'.$_SESSION['ms_user'];
            $this->identity = $_SESSION['ms_identity'];
        }
    }

    public function uploadUserImage($imgData,$fileName='',$identity=null){
        $fileName = strlen($fileName) == 0 ? $this->user : $fileName;
        $allow_path = $identity == null ? '/User/'.$this->identity : '/User/'.$identity;
        $dir = $this->_root_.$allow_path;
        $file_path = './Storage'.$allow_path;

        if(!file_exists($dir))
        {
            mkdir($dir,0755,true);
        }

        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $imgData, $result)) {
            $type = ".".$result[2];
            $img = str_replace($result[1], '', $imgData);
        }
        else{
            $type = '.png';
            $img = $imgData;
        }

            $fileName = $fileName . '_' . trim(guid(), '{}') . "$type";
            $new_file = $dir."/".$fileName;

            $imgLen = strlen($img);
            $imgSize = $imgLen - ($imgLen / 8) * 2;

            $imgSize = $imgSize / 1024;
            if($imgSize > $this->MaxFileSize)
            {
                die(json_encode(Array('error' => "操作失败，文件大小不能大于 $this->MaxFileSize kB"), JSON_UNESCAPED_UNICODE));
            }

            if (file_put_contents(iconv('utf-8','gb2312',$new_file), base64_decode($img))) {
                return $file_path.'/'.$fileName;
            }

            return null;
    }

//    public function uploadImage($imgData,$source,$time,$fileName=''){
//        if(!isset($this->allowDirs['personnel']) && !isset($this->allowDirs['car'])){
//            die(json_encode(Array('error'=>'您没有该权限'), JSON_UNESCAPED_UNICODE));
//        }
//
//        if(strlen($imgData) <= 0 && strlen($source) <= 0 && strlen($time) <= 0)
//        {
//            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//
//        $time = strtotime($time);
//        $Y = date('Y',$time);
//        $m = date('m',$time);
//        $d = date('d',$time);
//        $H = date('H',$time);
//        $i = date('i',$time);
//
//        switch ($source){
//            case 'personnel' : $dir = $this->_root_.$this->allowDirs['personnel'];$file_path = '/Storage'.$this->allowDirs['personnel'];break;
//            case 'car' : $dir = $this->_root_.$this->allowDirs['car'];$file_path = '/Storage'.$this->allowDirs['car'];break;
//            default :
//                return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
//        }
//
//        if(!file_exists($dir))
//        {
//            mkdir($dir);
//        }
//        if(!file_exists($dir.'/'.$Y))
//        {
//            mkdir($dir.'/'.$Y);
//        }
//        if(!file_exists($dir.'/'.$Y.'/'.$m))
//        {
//            mkdir($dir.'/'.$Y.'/'.$m);
//        }
//        if(!file_exists($dir.'/'.$Y.'/'.$m.'/'.$d))
//        {
//            mkdir($dir.'/'.$Y.'/'.$m.'/'.$d);
//        }
//        if(!file_exists($dir.'/'.$Y.'/'.$m.'/'.$d.'/'.$H))
//        {
//            mkdir($dir.'/'.$Y.'/'.$m.'/'.$d.'/'.$H);
//        }
//        if(!file_exists($dir.'/'.$Y.'/'.$m.'/'.$d.'/'.$H.'/'.$i))
//        {
//            mkdir($dir.'/'.$Y.'/'.$m.'/'.$d.'/'.$H.'/'.$i);
//        }
//
//        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $imgData, $result)) {
//            $type = $result[2];
//
//            $file_name = $fileName . '_' . trim(guid(), '{}') . ".$type";
//
//            $img = str_replace($result[1], '', $imgData);
//
//        }
//        else{
//            $type = '.png';
//            $file_name = $fileName . '_' . trim(guid(), '{}') . ".$type";
//            $img = $imgData;
//        }
//
//            if($source == 'personnel')
//            {
//                if(!file_exists($dir.'/'.$Y.'/'.$m.'/'.$d.'/'.$H.'/'.$i.'/证件'))
//                {
//                    mkdir($dir.'/'.$Y.'/'.$m.'/'.$d.'/'.$H.'/'.$i.'/证件');
//                }
//                if(!file_exists($dir.'/'.$Y.'/'.$m.'/'.$d.'/'.$H.'/'.$i.'/现场'))
//                {
//                    mkdir($dir.'/'.$Y.'/'.$m.'/'.$d.'/'.$H.'/'.$i.'/现场');
//                }
//                if(strstr($fileName,'_证件'))
//                {
//                    $file_name = '证件/'.$file_name;
//                }
//                else if(strstr($fileName,'_现场')){
//                    $file_name = '现场/'.$file_name;
//                }
//            }
//
//            if($source == 'car')
//            {
//                if(!file_exists($dir.'/'.$Y.'/'.$m.'/'.$d.'/'.$H.'/'.$i.'/车辆'))
//                {
//                    mkdir($dir.'/'.$Y.'/'.$m.'/'.$d.'/'.$H.'/'.$i.'/车辆');
//                }
//                if(!file_exists($dir.'/'.$Y.'/'.$m.'/'.$d.'/'.$H.'/'.$i.'/车牌'))
//                {
//                    mkdir($dir.'/'.$Y.'/'.$m.'/'.$d.'/'.$H.'/'.$i.'/车牌');
//                }
//                if(strstr($fileName,'_车辆'))
//                {
//                    $file_name = '车辆/'.$file_name;
//                }
//                else if(strstr($fileName,'_车牌')){
//                    $file_name = '车牌/'.$file_name;
//                }
//            }
//
//            $new_file = $dir.'/'.$Y.'/'.$m.'/'.$d.'/'.$H.'/'.$i."/".$file_name;
//
//
//            $imgLen = strlen($img);
//            $imgSize = $imgLen - ($imgLen / 8) * 2;
//
//            $imgSize = $imgSize / 1024;
//            if($imgSize > $this->MaxFileSize)
//            {
//                die(json_encode(Array('error' => "操作失败，文件大小不能大于 $this->MaxFileSize kB"), JSON_UNESCAPED_UNICODE));
//            }
//
//            if (file_put_contents($new_file, base64_decode($img))) {
//                return json_encode(Array(0=>$file_path.'/'.$Y.'/'.$m.'/'.$d.'/'.$H.'/'.$i.'/'.$file_name,'success' => '文件上传成功'), JSON_UNESCAPED_UNICODE);
//            }
//
//
//
//    }

    public function deleteFile($imgPath){
        if(strlen($imgPath) <= 0)
        {
            return json_encode(Array('error' => '操作失败，请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
        }

        if(unlink(iconv('utf-8','gb2312',$this->_root_.'/../'.stripcslashes($imgPath)))){
            return json_encode(Array('success' => '操作成功'), JSON_UNESCAPED_UNICODE);
        }
        else{
            return json_encode(Array('warning' => '文件删除失败：'.stripcslashes($imgPath)), JSON_UNESCAPED_UNICODE);
        }
    }

    public function setRoot($root){
        $this->_root_ = $root;
    }
}