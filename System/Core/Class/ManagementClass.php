<?php

require_once(dirname(__FILE__) . '/SqlHelper.php');

class ManagementClass
{
    var $db;

    var $studentTable;
    var $teacherTable;

    var $departmentTable;
    var $classTable;
    var $gradeTable;
    var $majorTable;
    var $scoreTable;
    var $courseTable;

    var $studentModel = array(
        'studentId' => null,
        'studentName' => null,
        'gender' => null,
        'both' => null,
        'salt' => null,
        'password' => null,
        'contact' => null,
        'grade' => null,
        'years' => null,
        'departmentId' => null,
        'departmentName' => null,
        'majorId' => null,
        'majorName' => null,
        'classId' => null,
        'class' => null,
        'seat' => null,
        'active' => null,
        'idCard' => null,
        'address' => null,
        'studentImg' => null,
    );

    var $teacherModel = array(
        'teacherId' => null,
        'teacherName' => null,
        'gender' => null,
        'salt' => null,
        'password' => null,
        'contact' => null,
        'departmentId' => null,
        'departmentName' => null,
        'active' => null,
        'idCard' => null,
        'address' => null,
        'teacherImg' => null,
        "permission" => null,
        "email" => null
    );

    var $filter = array(
        'page' => null,
        'num' => null,
    );


    public function __construct()
    {
        $this->db = new SqlHelper();
        $this->teacherTable = $this->db->db_table_prefix . "_" . SqlHelper::TEACHER;

        $this->classTable = $this->db->db_table_prefix . "_" . SqlHelper::CLASSES;

        $this->departmentTable = $this->db->db_table_prefix . "_" . SqlHelper::DEPARTMENT;
        $this->majorTable = $this->db->db_table_prefix . "_" . SqlHelper::MAJOR;
        $this->gradeTable = $this->db->db_table_prefix . "_" . SqlHelper::GRADE;

        $this->studentTable = $this->db->db_table_prefix . "_" . SqlHelper::STUDENT;

        $this->scoreTable = $this->db->db_table_prefix . "_" . SqlHelper::SCORE;
        $this->courseTable = $this->db->db_table_prefix . "_" . SqlHelper::COURSE;
    }

    private function checkAccess()
    {
        if (!isset($_SESSION['ms_id']) || !isset($_SESSION['ms_user'])) {
            die(json_encode(array('error' => '请登录后再进行此操作'), JSON_UNESCAPED_UNICODE));
        } else if ($_SESSION["ms_identity"] != "Teacher") {
            die(json_encode(array('error' => "您无权访问此接口"), JSON_UNESCAPED_UNICODE));
        }
    }

    public function getPermission()
    {
        $this->checkAccess();
        return $this->db->selectQuery('permission', $this->teacherTable)
            ->andQueryList(array("teacherId" => $_SESSION["ms_id"], "teacherName" => $_SESSION["ms_user"]))->selectLimit(1, 1)->getFetchAssoc()[0]["permission"];
    }

    /*
     * 学生管理模块
     */

    public function addStudent($data)
    {

        $this->checkAccess();

        $permission = $this->getPermission();
        if ($permission != '0' && $permission != '1') {
            return json_encode(array("error" => "您没有执行此操作的权限"), JSON_UNESCAPED_UNICODE);
        }

        if (!is_array($data)) {
            if (!is_string($data)) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }

            if (strlen($data) == 0) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }

            $data = json_decode($data, true);
            if (!is_array($data)) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }
        }

        foreach ($data as $k => $v) {
            if (!array_key_exists($k, $this->studentModel)) {
                unset($data[$k]);
            }
        }

        $model = $this->studentModel;


        unset($model["departmentName"]);
        unset($model["majorName"]);

        unset($model["contact"]);
        unset($model["active"]);
        unset($model["gender"]);
        unset($model["both"]);
        unset($model["salt"]);
        unset($model["password"]);
        unset($model["address"]);
        unset($model["studentImg"]);
        unset($model["active"]);

        foreach ($model as $k => $v) {
            if (!isset($data[$k])) {
                return json_encode(array('error' => "data 缺少 $k 参数"), JSON_UNESCAPED_UNICODE);
            }
        }

        if (strlen($data['departmentId']) != 2) {
            return json_encode(array('error' => 'departmentId 参数错误,departmentId 参数需要2个字符 例：01'), JSON_UNESCAPED_UNICODE);
        } else if ($this->db->selectQuery('departmentId', $this->departmentTable)->andQueryList(array("departmentId" => $data["departmentId"]))->getSelectNum() == 0) {
            return json_encode(array("error" => "该院系不存在"), JSON_UNESCAPED_UNICODE);
        } else if (strlen($data['majorId']) != 2) {
            return json_encode(array('error' => 'majorId 参数错误,majorId 参数需要2个字符 例：01'), JSON_UNESCAPED_UNICODE);
        } else if ($this->db->selectQuery('*', $this->majorTable)->andQueryList(array("departmentId" => $data["departmentId"], "majorId" => $data["majorId"]))->getSelectNum() == 0) {
            return json_encode(array("error" => "该专业不存在"), JSON_UNESCAPED_UNICODE);
        } else if (strlen($data["grade"]) != 2) {
            return json_encode(array('error' => 'grade 参数错误,grade 参数需要2个字符 例：01'), JSON_UNESCAPED_UNICODE);
        } else if ($this->db->selectQuery('*', $this->gradeTable)->andQueryList(array("departmentId" => $data["departmentId"], "majorId" => $data["majorId"], "grade" => $data['grade']))->getSelectNum() == 0) {
            return json_encode(array('error' => '该年级未开设此专业'));
        } else if (strlen($data["years"]) != 1 || intval($data["years"]) > 9) {
            return json_encode(array('error' => 'years 参数错误,years 参数需要1个字符 例：3'), JSON_UNESCAPED_UNICODE);
        } else if (strlen($data["class"]) != 1 || intval($data["class"]) > 9) {
            return json_encode(array('error' => 'class 参数错误,class 参数需要1个字符 例：1'), JSON_UNESCAPED_UNICODE);
        } else if (strlen($data["classId"]) != 8) {
            return json_encode(array('error' => 'classId 参数错误,classId 参数需要8个字符 例：17305021'), JSON_UNESCAPED_UNICODE);
        } else if ($data["classId"] != $data["grade"] . $data["years"] . $data["departmentId"] . $data["majorId"] . $data["class"]) {
            return json_encode(array('error' => 'classId 编码格式错误, 例：[年级编号][学制][院系编号][专业编号][班级序号]'), JSON_UNESCAPED_UNICODE);
        } else if ($this->db->selectQuery("*", $this->classTable)->andQueryList(array("classId" => $data["classId"], "departmentId" => $data["departmentId"], "majorId" => $data["majorId"], "grade" => $data["grade"]))->getSelectNum() == 0) {
            return json_encode(array('error' => "该班级不存在"), JSON_UNESCAPED_UNICODE);
        } else if (strlen($data["seat"]) != 2 || intval($data["seat"]) > 99) {
            return json_encode(array('error' => 'seat 参数错误,seat 参数需要2个字符 例：01'), JSON_UNESCAPED_UNICODE);
        } else if (strlen($data["studentId"]) != 10) {
            return json_encode(array('error' => 'studentId 参数错误,studentId 参数需要10个字符 例：1730502127'), JSON_UNESCAPED_UNICODE);
        } else if ($data["studentId"] != $data["grade"] . $data["years"] . $data["departmentId"] . $data["majorId"] . $data["class"] . $data["seat"]) {
            return json_encode(array('error' => 'studentId 编码格式错误, 例：[年级编号][学制][院系编号][专业编号][班级序号][座位号]'), JSON_UNESCAPED_UNICODE);
        } else if ($this->db->selectQuery("*", $this->studentTable)->andQueryList(array("studentId" => $data["studentId"]))->getSelectNum() != 0) {
            return json_encode(array('error' => "此学号已被使用"), JSON_UNESCAPED_UNICODE);
        } else if (strlen($data["idCard"]) != 18 && strlen($data["idCard"]) != 15) {
            return json_encode(array('error' => "身份证格式错误"), JSON_UNESCAPED_UNICODE);
        } else {
            $data["departmentName"] = $this->db->selectQuery('departmentName', $this->departmentTable)->andQueryList(array("departmentId" => $data["departmentId"]))->getFetchAssoc()[0]["departmentName"];
            $data["majorName"] = $this->db->selectQuery('majorName', $this->majorTable)->andQueryList(array("departmentId" => $data["departmentId"], "majorId" => $data["majorId"]))->getFetchAssoc()[0]["majorName"];

            if (isset($data["studentImg"])) {
                $fileManger = new FileClass();

                $data["studentImg"] = $fileManger->uploadUserImage($data["studentImg"], $data['studentId'] . '_' . $data['studentName'], 'Student');
                if ($data["studentImg"] == null) {
                    return json_encode(array('error' => '图片上传失败，请稍后再试'), JSON_UNESCAPED_UNICODE);
                }
            }
        }


        $data["active"] = 0;

        if ($this->db->insertQuery($this->studentTable, $data)->insertExecute()->getAffectedRows() == 1) {

            $studentNum = $this->db->selectQuery('*', $this->studentTable)->andQueryList(array("classId" => $data["classId"]))->getSelectNum();

            $this->db->updateQuery($this->classTable, array("studentNum" => $studentNum))->andQueryList(array("classId" => $data["classId"]))->updateLimit(1)->updateExecute();

            return json_encode(array('success' => '学生添加成功'), JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode(array('error' => '学生添加失败,请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
        }
    }

    public function deleteStudent($data)
    {
        $this->checkAccess();
        $permission = $this->getPermission();
        if ($permission != '0' && $permission != '1') {
            return json_encode(array("error" => "您没有执行此操作的权限"), JSON_UNESCAPED_UNICODE);
        }

        if (!is_array($data)) {
            if (!is_string($data)) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }

            if (strlen($data) == 0) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }

            $data = json_decode($data, true);
            if (!is_array($data)) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }
        }

        foreach ($data as $k => $v) {
            if (!array_key_exists($k, $this->studentModel)) {
                unset($data[$k]);
            }
        }

        $model = array(
            "studentId" => null
        );

        foreach ($model as $k => $v) {
            if (!isset($data[$k])) {
                return json_encode(array('error' => "data 缺少 $k 参数"), JSON_UNESCAPED_UNICODE);
            }
        }

        if (strlen($data["studentId"]) != 10) {
            return json_encode(array('error' => 'studentId 参数错误,studentId 参数需要10个字符 例：1730502127'), JSON_UNESCAPED_UNICODE);
        } else if ($this->db->selectQuery("*", $this->studentTable)->andQueryList(array("studentId" => $data["studentId"]))->getSelectNum() == 0) {
            return json_encode(array('error' => "该学生不存在"), JSON_UNESCAPED_UNICODE);
        }

        $data["classId"] = substr($data["studentId"], 0, 8);

        if ($this->db->deleteQuery($this->studentTable)->andQueryList(array("studentId" => $data["studentId"]))->deleteLimit(1)->deleteExecute()->getAffectedRows() == 1) {
            $studentNum = $this->db->selectQuery('*', $this->studentTable)->andQueryList(array("classId" => $data["classId"]))->getSelectNum();

            $this->db->updateQuery($this->classTable, array("studentNum" => $studentNum))->andQueryList(array("classId" => $data["classId"]))->updateLimit(1)->updateExecute();

            $this->db->deleteQuery($this->scoreTable)->andQueryList(array("studentId" => $data["studentId"]))->deleteExecute();

            return json_encode(array('success' => "学生信息删除成功"), JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode(array('error' => "学生信息删除失败"), JSON_UNESCAPED_UNICODE);
        }

    }

    public function updateStudent($data, $info)
    {
        $this->checkAccess();
        $permission = $this->getPermission();
        if ($permission != '0' && $permission != '1') {
            return json_encode(array("error" => "您没有执行此操作的权限"), JSON_UNESCAPED_UNICODE);
        }

        if (!is_array($data)) {
            if (!is_string($data)) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }

            if (strlen($data) == 0) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }

            $data = json_decode($data, true);
            if (!is_array($data)) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }
        }

        if (!is_array($info)) {
            if (!is_string($info)) {
                die(json_encode(array("error" => "JSON info 解析失败"), JSON_UNESCAPED_UNICODE));
            }

            if (strlen($info) == 0) {
                die(json_encode(array("error" => "JSON info 解析失败"), JSON_UNESCAPED_UNICODE));
            }

            $info = json_decode($info, true);
            if (!$info) {
                die(json_encode(array("error" => "JSON info 解析失败"), JSON_UNESCAPED_UNICODE));
            }
        }

        $dataModel = array(
            'studentId' => null,
        );

        $infoModel = $this->studentModel;

        foreach ($dataModel as $k => $v) {
            if (!isset($data[$k])) {
                return json_encode(array('error' => "data 缺少 $k 参数"), JSON_UNESCAPED_UNICODE);
            }
        }

        foreach ($data as $k => $v) {
            if (!array_key_exists($k, $dataModel)) {
                unset($data[$k]);
            } else {
                unset($info[$k]);
            }
        }

        unset($infoModel['studentId']);
        unset($infoModel['salt']);
        unset($infoModel['grade']);
        unset($infoModel['years']);
        unset($infoModel['departmentId']);
        unset($infoModel['departmentName']);
        unset($infoModel['majorId']);
        unset($infoModel['majorName']);
        unset($infoModel['classId']);
        unset($infoModel['class']);
        unset($infoModel['seat']);

        foreach ($info as $k => $v) {
            if (!array_key_exists($k, $infoModel)) {
                unset($info[$k]);
            }
        }

        $query = $this->db->selectQuery('studentImg', $this->studentTable)->andQueryList($data)->selectLimit(1, 1);

        if ($query->getSelectNum() != 1) {
            return json_encode(array('error' => '此学生不存在'), JSON_UNESCAPED_UNICODE);
        }

        $oImg = "";
        $fileManger = new FileClass();

        if (isset($info['studentImg'])) {
            $oImg = $query->getFetchAssoc()[0]['studentImg'];

            $info["studentImg"] = $fileManger->uploadUserImage($info["studentImg"], $data['studentId'] . '_' . $this->db->selectQuery('studentName', $this->studentTable)->andQueryList($data)->selectLimit(1, 1)->getFetchAssoc()[0]['studentName'], 'Student');
            if ($info["studentImg"] == null) {
                return json_encode(array('error' => '图片上传失败，请稍后再试'), JSON_UNESCAPED_UNICODE);
            }
        }

        if (isset($info['password'])) {
            $salt = ''; // 随机加密密钥
            while (strlen($salt) < 6) {
                $x = mt_rand(0, 9);
                $salt .= $x;
            }
            $info['salt'] = $salt;
            $info['password'] = sha1($info['password'] . $salt); // sha1哈希加密
        }

        $query = $this->db->updateQuery($this->studentTable, $info)->andQueryList(array("studentId" => $data["studentId"]))->updateLimit(1)->updateExecute();

        if ($query->getAffectedRows() == 1) {
            if (isset($info['studentImg'])) {
                if (strlen($oImg) != 0) {
                    $res = $fileManger->deleteFile($oImg);
                    if (array_key_exists('error', json_decode($res, true))) {
                        return $res;
                    }
                    if (array_key_exists('warning', json_decode($res, true))) {
                        return json_encode(array('warning' => '信息修改成功,但' . json_decode($res, true)['warning']), JSON_UNESCAPED_UNICODE);
                    }
                }
            }
            if (isset($info['studentName'])) {
                $this->db->updateQuery($this->scoreTable, array("studentName" => $info["studentName"]))->andQuery("studentId", $data["studentId"])->updateExecute();
            }
            return json_encode(array('success' => '学生信息修改成功'), JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode(array('info' => '学生信息未变更'), JSON_UNESCAPED_UNICODE);
        }
    }

    public function getStudentList($data, $filter)
    {

        $this->checkAccess();

        $permission = $this->getPermission();
        $acceptList = array();

        if ($permission != '0' && $permission != '1') {

            foreach ($this->db->selectDistinctQuery('classId', $this->courseTable)->andQueryList(array('teacherId' => $_SESSION['ms_id']))->getFetchAssoc() as $k => $v) {
                array_push($acceptList, $v['classId']);
            }
        }

        if (!is_array($data)) {
            if (!is_string($data)) {
                die(json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE));
            }

            if (strlen($data) == 0) {
                $data = "{}";
            }

            $data = json_decode($data, true);
            if (!is_array($data)) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }
        }

        if (!is_array($filter)) {
            if (!is_string($filter)) {
                return json_encode(array("error" => "JSON filter 解析失败"), JSON_UNESCAPED_UNICODE);
            }

            if (strlen($filter) != 0) {
                $filter = json_decode($filter, true);
                if (!$filter) {
                    return json_encode(array("error" => "JSON filter 解析失败"), JSON_UNESCAPED_UNICODE);
                }
            } else {
                return json_encode(array('error' => '请传入查询参数'), JSON_UNESCAPED_UNICODE);
            }
        }

        $model = array(
            "studentName" => null,
            "studentId" => null,
            "gender" => null,
            "departmentId" => null,
            "departmentName" => null,
            "majorId" => null,
            "majorName" => null,
            "contact" => null,
            "grade" => null,
            "years" => null,
            "classId" => null,
            "class" => null,
            "seat" => null,
            "idCard" => null,
            "address" => null,
            "active" => null
        );

        foreach ($data as $k => $v) {
            if (!array_key_exists($k, $model)) {
                unset($data[$k]);
            }
        }

        foreach ($filter as $k => $v) {
            if (!array_key_exists($k, $this->filter)) {
                unset($filter[$k]);
            }
        }

        $page = 1;
        $num = 1;

        if (isset($filter['page'])) {
            $page = intval($filter['page']);

            if (isset($filter['num'])) {
                $num = intval($filter['num']);
            } else {
                $num = 10;
            }
        }

        if ($page == null || $num == null) {
            return json_encode(array('error' => 'filter page 或 num 参数不合法'), JSON_UNESCAPED_UNICODE);
        }

        $query = $this->db->selectQuery('studentId,studentName,gender,grade,years,departmentId,departmentName,majorId,majorName,class,classId,seat,idCard,`both`,address,contact,active,studentImg', $this->studentTable);

        if (isset($data['studentName'])) {
            $query->andLikeQuery('studentName', "%{$data['studentName']}%");
            unset($data['studentName']);
        }
        if (isset($data['studentId'])) {
            $query->andLikeQuery('studentId', "%{$data['studentId']}%");
            unset($data['studentId']);
        }
        if (isset($data['departmentName'])) {
            $query->andLikeQuery('departmentName', "%{$data['departmentName']}%");
            unset($data['departmentName']);
        }
        if (isset($data['majorName'])) {
            $query->andLikeQuery('majorName', "%{$data['majorName']}%");
            unset($data['majorName']);
        }
        if (isset($data['contact'])) {
            $query->andLikeQuery('contact', "%{$data['contact']}%");
            unset($data['contact']);
        }
        if (isset($data['idCard'])) {
            $query->andLikeQuery('idCard', "%{$data['idCard']}%");
            unset($data['idCard']);
        }
        if (isset($data['address'])) {
            $query->andLikeQuery('address', "%{$data['address']}%");
            unset($data['address']);
        }

        if (isset($data["classId"])) {
            if ($permission != '0' && $permission != '1') {
                if (!in_array($data["classId"], $acceptList)) {
                    return json_encode(array("error" => "您没有查看该班级学生的权限"), JSON_UNESCAPED_UNICODE);
                }
            } else {
                $query->andQuery('classId', "{$data['classId']}");
            }
            unset($data['address']);
        } else {
            if ($permission != '0' && $permission != '1') {
                $query->andLeftTripQuery_Or();
                foreach ($acceptList as $t) {
                    $query->orQueryList(array('classId' => $t));
                }
                $query->andRightTripQuery_Or();
            }
        }


        $query->andQueryList($data);

        return $query->orderBy('grade,departmentId,majorId,years,class,seat', 1)->selectLimit($page, $num)->getFetchAssocNumJson();
    }

    /*
     * 教工管理模块
     */

    public function addTeacher($data)
    {

        $this->checkAccess();

        $permission = $this->getPermission();
        if ($permission != '0' && $permission != '1') {
            return json_encode(array("error" => "您没有执行此操作的权限"), JSON_UNESCAPED_UNICODE);
        }

        if (!is_array($data)) {
            if (!is_string($data)) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }

            if (strlen($data) == 0) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }

            $data = json_decode($data, true);
            if (!is_array($data)) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }
        }


        foreach ($data as $k => $v) {
            if (!array_key_exists($k, $this->teacherModel)) {
                unset($data[$k]);
            }
        }

        $model = $this->teacherModel;

        unset($model["teacherId"]);
        unset($model["departmentName"]);
        unset($model["contact"]);
        unset($model["active"]);
        unset($model["gender"]);
        unset($model["both"]);
        unset($model["salt"]);
        unset($model["address"]);
        unset($model["teacherImg"]);
        unset($model["active"]);

        foreach ($model as $k => $v) {
            if (!isset($data[$k])) {
                return json_encode(array('error' => "data 缺少 $k 参数"), JSON_UNESCAPED_UNICODE);
            }
        }

        if ($permission == '1') {
            if ($data["permission"] == '0') {
                return json_encode(array("error" => "你不能添加权限比你更高的用户"), JSON_UNESCAPED_UNICODE);
            }
        }

        $data["teacherId"] = trim(guid(), '{}');

        if (strlen($data['departmentId']) != 2) {
            return json_encode(array('error' => 'departmentId 参数错误,departmentId 参数需要2个字符 例：01'), JSON_UNESCAPED_UNICODE);
        } else if ($this->db->selectQuery('departmentId', $this->departmentTable)->andQueryList(array("departmentId" => $data["departmentId"]))->getSelectNum() == 0) {
            return json_encode(array("error" => "该院系不存在"), JSON_UNESCAPED_UNICODE);
        } else if (strlen($data["idCard"]) != 18 && strlen($data["idCard"]) != 15) {
            return json_encode(array('error' => "身份证格式错误"), JSON_UNESCAPED_UNICODE);
        } else if ($this->db->selectQuery('*', $this->teacherTable)->andQueryList(array("email" => $data["email"]))->getSelectNum() != 0) {
            return json_encode(array('error' => "该email地址已注册过"), JSON_UNESCAPED_UNICODE);
        } else {
            $data["departmentName"] = $this->db->selectQuery('departmentName', $this->departmentTable)->andQueryList(array("departmentId" => $data["departmentId"]))->getFetchAssoc()[0]["departmentName"];
            if (isset($data["teacherImg"])) {
                $fileManger = new FileClass();

                $data["teacherImg"] = $fileManger->uploadUserImage($data["teacherImg"], $data['teacherId'] . '_' . $data['teacherName'], 'Teacher');
                if ($data["teacherImg"] == null) {
                    return json_encode(array('error' => '图片上传失败，请稍后再试'), JSON_UNESCAPED_UNICODE);
                }
            }
        }


        $salt = ''; // 随机加密密钥
        while (strlen($salt) < 6) {
            $x = mt_rand(0, 9);
            $salt .= $x;
        }
        $data['salt'] = $salt;
        $data['password'] = sha1($data['password'] . $salt); // sha1哈希加密


        $data["active"] = 0;

        if ($this->db->insertQuery($this->teacherTable, $data)->insertExecute()->getAffectedRows() == 1) {
            return json_encode(array('success' => '教工添加成功'), JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode(array('error' => '教工添加失败,请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
        }
    }

    public function getTeacherList($data, $filter)
    {
        $this->checkAccess();

        if (!is_array($data)) {
            if (!is_string($data)) {
                die(json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE));
            }

            if (strlen($data) == 0) {
                $data = "{}";
            }

            $data = json_decode($data, true);
            if (!is_array($data)) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }
        }

        if (!is_array($filter)) {
            if (!is_string($filter)) {
                return json_encode(array("error" => "JSON filter 解析失败"), JSON_UNESCAPED_UNICODE);
            }

            if (strlen($filter) != 0) {
                $filter = json_decode($filter, true);
                if (!$filter) {
                    return json_encode(array("error" => "JSON filter 解析失败"), JSON_UNESCAPED_UNICODE);
                }
            } else {
                return json_encode(array('error' => '请传入查询参数'), JSON_UNESCAPED_UNICODE);
            }
        }

        $model = array(
            "teacherName" => null,
            "departmentId" => null,
            "departmentName" => null,
            "gender" => null,
            "contact" => null,
            "idCard" => null,
            "both" => null,
            "permission" => null,
            "address" => null,
            "active" => null,
            "email" => null
        );

        foreach ($data as $k => $v) {
            if (!array_key_exists($k, $model)) {
                unset($data[$k]);
            }
        }

        foreach ($filter as $k => $v) {
            if (!array_key_exists($k, $this->filter)) {
                unset($filter[$k]);
            }
        }

        $page = 1;
        $num = 1;

        if (isset($filter['page'])) {
            $page = intval($filter['page']);

            if (isset($filter['num'])) {
                $num = intval($filter['num']);
            } else {
                $num = 10;
            }
        }

        if ($page == null || $num == null) {
            return json_encode(array('error' => 'filter page 或 num 参数不合法'), JSON_UNESCAPED_UNICODE);
        }

        $query = $this->db->selectQuery('teacherId,teacherName,email,gender,departmentId,departmentName,idCard,`both`,address,contact,active,teacherImg,permission', $this->teacherTable);

        if (isset($data['teacherName'])) {
            $query->andLikeQuery('teacherName', "%{$data['teacherName']}%");
            unset($data['teacherName']);
        }
        if (isset($data['departmentName'])) {
            $query->andLikeQuery('departmentName', "%{$data['departmentName']}%");
            unset($data['departmentName']);
        }
        if (isset($data['contact'])) {
            $query->andLikeQuery('contact', "%{$data['contact']}%");
            unset($data['contact']);
        }
        if (isset($data['idCard'])) {
            $query->andLikeQuery('idCard', "%{$data['idCard']}%");
            unset($data['idCard']);
        }
        if (isset($data['address'])) {
            $query->andLikeQuery('address', "%{$data['address']}%");
            unset($data['address']);
        }
        if (isset($data['email'])) {
            $query->andLikeQuery('email', "%{$data['email']}%");
            unset($data['email']);
        }


        $query->andQueryList($data);


        return $query->orderBy('departmentId', 1)->selectLimit($page, $num)->getFetchAssocNumJson();
    }

    public function updateTeacher($data, $info)
    {
        $this->checkAccess();
        $permission = $this->getPermission();
        if ($permission != '0' && $permission != '1') {
            return json_encode(array("error" => "您没有执行此操作的权限"), JSON_UNESCAPED_UNICODE);
        }

        if (!is_array($data)) {
            if (!is_string($data)) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }

            if (strlen($data) == 0) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }

            $data = json_decode($data, true);
            if (!is_array($data)) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }
        }

        if (!is_array($info)) {
            if (!is_string($info)) {
                die(json_encode(array("error" => "JSON info 解析失败"), JSON_UNESCAPED_UNICODE));
            }

            if (strlen($info) == 0) {
                die(json_encode(array("error" => "JSON info 解析失败"), JSON_UNESCAPED_UNICODE));
            }

            $info = json_decode($info, true);
            if (!$info) {
                die(json_encode(array("error" => "JSON info 解析失败"), JSON_UNESCAPED_UNICODE));
            }
        }

        $dataModel = array(
            'teacherId' => null,
        );
        $infoModel = $this->teacherModel;

        foreach ($info as $k => $v) {
            if (!array_key_exists($k, $infoModel)) {
                unset($info[$k]);
            }
        }

        unset($info['salt']);

        foreach ($data as $k => $v) {
            if (!array_key_exists($k, $dataModel)) {
                unset($data[$k]);
            } else {
                unset($info[$k]);
            }
        }


        if (isset($info["permission"])) {
            if ($data["teacherId"] == $_SESSION["ms_id"]) {
                return json_encode(array("error" => "你不能修改自己的权限"), JSON_UNESCAPED_UNICODE);
            }

            if ($permission == '1') {
                if ($info["permission"] == '0') {
                    return json_encode(array("error" => "你不能将用户权限提到比你自身更高的权限"), JSON_UNESCAPED_UNICODE);
                }
            }
        }


        $query = $this->db->selectQuery('teacherImg,permission', $this->teacherTable)->andQueryList($data)->selectLimit(1, 1);

        if ($query->getSelectNum() != 1) {
            return json_encode(array('error' => '此教工不存在'), JSON_UNESCAPED_UNICODE);
        }

        if ($permission != "0" && $query->getFetchAssoc()[0]['permission'] == "0") {
            return json_encode(array("error" => "你不能修改权限比你更高的用户信息"), JSON_UNESCAPED_UNICODE);
        }

        if (isset($info['departmentId']) && strlen($info['departmentId']) != 2) {
            return json_encode(array('error' => 'departmentId 参数错误,departmentId 参数需要2个字符 例：01'), JSON_UNESCAPED_UNICODE);
        } else if (isset($info['departmentId']) && $this->db->selectQuery('departmentId', $this->departmentTable)->andQueryList(array("departmentId" => $info["departmentId"]))->getSelectNum() == 0) {
            return json_encode(array("error" => "该院系不存在"), JSON_UNESCAPED_UNICODE);
        } else if (isset($info['idCard']) && strlen($info["idCard"]) != 18 && strlen($info["idCard"]) != 15) {
            return json_encode(array('error' => "身份证格式错误"), JSON_UNESCAPED_UNICODE);
        } else if (isset($info['email']) && $this->db->selectQuery('*', $this->teacherTable)->andQueryList(array("email" => $info["email"]))->getSelectNum() != 0) {
            return json_encode(array('error' => "该email地址已注册过"), JSON_UNESCAPED_UNICODE);
        } else {
            if (isset($info['departmentId'])) {
                $info["departmentName"] = $this->db->selectQuery('departmentName', $this->departmentTable)->andQueryList(array("departmentId" => $info["departmentId"]))->getFetchAssoc()[0]["departmentName"];
            }
        }

        $oImg = "";
        $fileManger = new FileClass();

        if (isset($info['teacherImg'])) {
            $oImg = $query->getFetchAssoc()[0]['teacherImg'];

            $info["teacherImg"] = $fileManger->uploadUserImage($info["teacherImg"], $data['teacherId'] . '_' . $this->db->selectQuery('teacherName', $this->teacherTable)->andQueryList($data)->selectLimit(1, 1)->getFetchAssoc()[0]['teacherName'], 'Teacher');
            if ($info["teacherImg"] == null) {
                return json_encode(array('error' => '图片上传失败，请稍后再试'), JSON_UNESCAPED_UNICODE);
            }
        }

        if (isset($info['password'])) {
            $salt = ''; // 随机加密密钥
            while (strlen($salt) < 6) {
                $x = mt_rand(0, 9);
                $salt .= $x;
            }
            $info['salt'] = $salt;
            $info['password'] = sha1($info['password'] . $salt); // sha1哈希加密
        }

        $query = $this->db->updateQuery($this->teacherTable, $info)->andQueryList(array("teacherId" => $data["teacherId"]))->updateLimit(1)->updateExecute();

        if ($query->getAffectedRows() == 1) {
            if (isset($info['teacherImg'])) {
                if (strlen($oImg) != 0) {
                    $res = $fileManger->deleteFile($oImg);
                    if (array_key_exists('error', json_decode($res, true))) {
                        return $res;
                    }
                    if (array_key_exists('warning', json_decode($res, true))) {
                        return json_encode(array('warning' => '信息修改成功,但' . json_decode($res, true)['warning']), JSON_UNESCAPED_UNICODE);
                    }
                }
            }
            if (isset($info["teacherName"])) {
                $this->db->updateQuery($this->scoreTable, array("teacherName" => $info["teacherName"]))->andQuery("teacherId", $data["teacherId"])->updateExecute();
                $this->db->updateQuery($this->courseTable, array("teacherName" => $info["teacherName"]))->andQuery("teacherId", $data["teacherId"])->updateExecute();
            }
            return json_encode(array('success' => '信息修改成功'), JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode(array('info' => '信息未变更'), JSON_UNESCAPED_UNICODE);
        }

    }

    public function deleteTeacher($data)
    {
        $this->checkAccess();
        $permission = $this->getPermission();
        if ($permission != '0' && $permission != '1') {
            return json_encode(array("error" => "您没有执行此操作的权限"), JSON_UNESCAPED_UNICODE);
        }

        if (!is_array($data)) {
            if (!is_string($data)) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }

            if (strlen($data) == 0) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }

            $data = json_decode($data, true);
            if (!is_array($data)) {
                return json_encode(array("error" => "JSON data 解析失败"), JSON_UNESCAPED_UNICODE);
            }
        }

        foreach ($data as $k => $v) {
            if (!array_key_exists($k, $this->teacherModel)) {
                unset($data[$k]);
            }
        }

        $model = array(
            "teacherId" => null
        );

        foreach ($model as $k => $v) {
            if (!isset($data[$k])) {
                return json_encode(array('error' => "data 缺少 $k 参数"), JSON_UNESCAPED_UNICODE);
            }
        }

        if ($data["teacherId"] == $_SESSION["ms_id"]) {
            return json_encode(array('error' => "您不能删除自己的账号"), JSON_UNESCAPED_UNICODE);
        }

        if ($this->db->selectQuery("*", $this->teacherTable)->andQueryList(array("teacherId" => $data["teacherId"]))->getSelectNum() == 0) {
            return json_encode(array('error' => "该教工不存在"), JSON_UNESCAPED_UNICODE);
        } else if ($permission == '1') {
            $p = $this->db->selectQuery("permission", $this->teacherTable)->andQueryList(array("teacherId" => $data["teacherId"]))->getFetchAssoc()[0]["permission"];
            if ($p == '0' || $p == $permission) {
                return json_encode(array('error' => "您无权删除权限比你更高或相同的账号"), JSON_UNESCAPED_UNICODE);
            }
        }

        if ($this->db->deleteQuery($this->teacherTable)->andQueryList(array("teacherId" => $data["teacherId"]))->deleteLimit(1)->deleteExecute()->getAffectedRows() == 1) {
            return json_encode(array('success' => "教工删除成功"), JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode(array('error' => "教工删除失败"), JSON_UNESCAPED_UNICODE);
        }
    }

}