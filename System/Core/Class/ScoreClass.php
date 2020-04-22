<?php

require_once(dirname(__FILE__) . '/SqlHelper.php');

class ScoreClass
{

    var $db;

    var $studentTable;

    var $teacherTable;
    var $scoreTable;

    var $courseTable;
    var $classTable;


    var $model = array(

        "courseId" => null,
        "courseName" => null,
        'teacherId' => null,
        'teacherName' => null,
        "studentId"=>null,
        "studentName"=>null,

        'score' => null,
        "flag"=>null

    );

    var $filter = array(
        'page' => null,
        'num' => null,
    );

    public function __construct()
    {
        $this->db = new SqlHelper();

        $this->scoreTable = $this->db->db_table_prefix . "_" . SqlHelper::SCORE;

        $this->studentTable = $this->db->db_table_prefix . "_" . SqlHelper::STUDENT;
        $this->teacherTable = $this->db->db_table_prefix . "_" . SqlHelper::TEACHER;

        $this->classTable = $this->db->db_table_prefix . "_" . SqlHelper::CLASSES;
        $this->courseTable = $this->db->db_table_prefix . "_" . SqlHelper::COURSE;



    }

    public function getScoreList($data, $filter)
    {
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

        foreach ($data as $k => $v) {
            if (!array_key_exists($k, $this->model)) {
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

        $query = $this->db->selectQuery('*', $this->scoreTable);

        if (isset($data['courseName'])) {
            $query->andLikeQuery('courseName', "%{$data['courseName']}%");
            unset($data['courseName']);
        }
        if (isset($data['teacherName'])) {
            $query->andLikeQuery('teacherName', "%{$data['teacherName']}%");
            unset($data['teacherName']);
        }
        if (isset($data['studentName'])) {
            $query->andLikeQuery('studentName', "%{$data['studentName']}%");
            unset($data['studentName']);
        }

        $query->andQueryList($data);


        return $query->orderBy('studentId,courseId', 1)->selectLimit($page, $num)->getFetchAssocNumJson();

    }

    public function addScore($data)
    {
        if (!isset($_SESSION['ms_id']) || !isset($_SESSION['ms_user'])) {
            return json_encode(array('error' => '请登录后再进行此操作'), JSON_UNESCAPED_UNICODE);
        } else if ($_SESSION['ms_identity'] != 'Teacher') {
            return json_encode(array('error' => '您没有此权限'), JSON_UNESCAPED_UNICODE);
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

        foreach ($data as $k => $v) {
            if (!array_key_exists($k, $this->model)) {
                unset($data[$k]);
            }
        }

        $model = $this->model;

        unset($model["courseName"]);
        unset($model["teacherName"]);
        unset($model["studentName"]);

        foreach ($model as $k => $v) {
            if (!isset($data[$k])) {
                return json_encode(array('error' => "data 缺少 $k 参数"), JSON_UNESCAPED_UNICODE);
            } else {
                switch ($k) {
                    case "courseId":
                        if (strlen($data[$k]) == 0) {
                            return json_encode(array('error' => 'data courseId 参数错误'), JSON_UNESCAPED_UNICODE);
                        } else if ($this->db->selectQuery('courseName', $this->courseTable)->andQueryList(array('courseId' => $data[$k]))->getSelectNum() == 0) {
                            return json_encode(array('error' => "该课程不存在"), JSON_UNESCAPED_UNICODE);
                        } else {
                            $data["courseName"] =
                                $this->db->selectQuery("courseName", $this->courseTable)->andQueryList(array('courseId' => $data[$k]))->selectLimit(1, 1)->getFetchAssoc()[0]['courseName'];
                        }
                        break;
                    case "teacherId":
                        if (strlen($data[$k]) == 0) {
                            return json_encode(array('error' => 'data teacherId 参数错误'), JSON_UNESCAPED_UNICODE);
                        } else if ($this->db->selectQuery('teacherName', $this->teacherTable)->andQueryList(array('teacherId' => $data[$k]))->getSelectNum() == 0) {
                            return json_encode(array('error' => "该教工不存在"), JSON_UNESCAPED_UNICODE);
                        } else {
                            $data["teacherName"] =
                                $this->db->selectQuery("teacherName", $this->teacherTable)->andQueryList(array('teacherId' => $data[$k]))->selectLimit(1, 1)->getFetchAssoc()[0]['teacherName'];
                        }
                        break;
                    case "studentId":
                        if (strlen($data[$k]) == 0) {
                            return json_encode(array('error' => 'data studentId 参数错误'), JSON_UNESCAPED_UNICODE);
                        } else if ($this->db->selectQuery('studentName', $this->studentTable)->andQueryList(array('studentId' => $data[$k]))->getSelectNum() == 0) {
                            return json_encode(array('error' => "该学生不存在"), JSON_UNESCAPED_UNICODE);
                        } else {
                            $data["studentName"] =
                                $this->db->selectQuery("studentName", $this->studentTable)->andQueryList(array('studentId' => $data[$k]))->selectLimit(1, 1)->getFetchAssoc()[0]['studentName'];
                        }
                        break;
                    case "flag":
                        if (strlen($data[$k]) != 1)
                        {
                            return json_encode(array('error' => 'data flag 参数错误, flag 参数需要一个字符,例：0'), JSON_UNESCAPED_UNICODE);
                        }
                        else if(intval($data[$k]) > 9){
                            return json_encode(array('error' => 'data flag 参数错误, flag 参数需要一个字符,例：0'), JSON_UNESCAPED_UNICODE);
                        }
                        else if ($data[$k] != "0" && $data[$k] != "1" && $data[$k] != "2") {
                            return json_encode(array('error' => "data flag 参数错误,例： 0：正考分数，1：补考分数，2：重修分数"), JSON_UNESCAPED_UNICODE);
                        }
                        break;
                }
            }
        }

        if($this->db->selectQuery("*",$this->courseTable)->andQueryList(array("courseId"=>$data["courseId"],"teacherId"=>$data["teacherId"],"classId"=>substr($data["studentId"],0,8)))->getSelectNum() == 0)
        {
            return json_encode(array('error' => '课程信息与学生信息不符'), JSON_UNESCAPED_UNICODE);
        }

        if($this->db->selectQuery('*',$this->scoreTable)->andQueryList(array("courseId"=>$data["courseId"],"teacherId"=>$data["teacherId"],"studentId"=>$data["studentId"],"flag"=>$data["flag"]))->getSelectNum() != 0)
        {
            return json_encode(array('error' => '该学生已登记过此课程分数'), JSON_UNESCAPED_UNICODE);
        }

        if ($this->db->insertQuery($this->scoreTable, $data)->insertExecute()->getAffectedRows() == 1) {
            return json_encode(array('success' => '分数登记成功'), JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode(array('error' => '分数登记失败,请检查参数是否正确'), JSON_UNESCAPED_UNICODE);
        }

    }

    public function updateScore($data)
    {
        if (!isset($_SESSION['ms_id']) || !isset($_SESSION['ms_user'])) {
            return json_encode(array('error' => '请登录后再进行此操作'), JSON_UNESCAPED_UNICODE);
        } else if ($_SESSION['ms_identity'] != 'Teacher') {
            return json_encode(array('error' => '您没有此权限'), JSON_UNESCAPED_UNICODE);
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

        foreach ($data as $k => $v) {
            if (!array_key_exists($k, $this->model)) {
                unset($data[$k]);
            }
        }

        unset($data["teacherName"]);
        unset($data["studentName"]);

        if (!isset($data['courseId'])) {
            return json_encode(array('error' => 'courseId 为必填参数'), JSON_UNESCAPED_UNICODE);
        }

        $model = array(
            "courseId" => null,
            "studentId"=>null,
            "teacherId"=>null,
            "flag"=>null
        );

        foreach ($model as $k => $v) {
            if (!isset($data[$k])) {
                return json_encode(array('error' => "data 缺少 $k 参数"), JSON_UNESCAPED_UNICODE);
            } else {
                switch ($k) {
                    case "courseId":
                        if (strlen($data[$k]) == 0) {
                            return json_encode(array('error' => 'data courseId 参数错误'), JSON_UNESCAPED_UNICODE);
                        } else if ($this->db->selectQuery('courseName', $this->courseTable)->andQueryList(array('courseId' => $data[$k]))->getSelectNum() == 0) {
                            return json_encode(array('error' => "该课程不存在"), JSON_UNESCAPED_UNICODE);
                        } else {
                            $data["courseName"] =
                                $this->db->selectQuery("courseName", $this->courseTable)->andQueryList(array('courseId' => $data[$k]))->selectLimit(1, 1)->getFetchAssoc()[0]['courseName'];
                        }
                        break;
                    case "teacherId":
                        if (strlen($data[$k]) == 0) {
                            return json_encode(array('error' => 'data teacherId 参数错误'), JSON_UNESCAPED_UNICODE);
                        } else if ($this->db->selectQuery('teacherName', $this->teacherTable)->andQueryList(array('teacherId' => $data[$k]))->getSelectNum() == 0) {
                            return json_encode(array('error' => "该教工不存在"), JSON_UNESCAPED_UNICODE);
                        } else {
                            $data["teacherName"] =
                                $this->db->selectQuery("teacherName", $this->teacherTable)->andQueryList(array('teacherId' => $data[$k]))->selectLimit(1, 1)->getFetchAssoc()[0]['teacherName'];
                        }
                        break;
                    case "studentId":
                        if (strlen($data[$k]) == 0) {
                            return json_encode(array('error' => 'data studentId 参数错误'), JSON_UNESCAPED_UNICODE);
                        } else if ($this->db->selectQuery('studentName', $this->studentTable)->andQueryList(array('studentId' => $data[$k]))->getSelectNum() == 0) {
                            return json_encode(array('error' => "该学生不存在"), JSON_UNESCAPED_UNICODE);
                        } else {
                            $data["studentName"] =
                                $this->db->selectQuery("studentName", $this->studentTable)->andQueryList(array('studentId' => $data[$k]))->selectLimit(1, 1)->getFetchAssoc()[0]['studentName'];
                        }
                        break;
                    case "flag":
                        if (strlen($data[$k]) != 1)
                        {
                            return json_encode(array('error' => 'data flag 参数错误, flag 参数需要一个字符,例：0'), JSON_UNESCAPED_UNICODE);
                        }
                        else if(intval($data[$k]) > 9){
                            return json_encode(array('error' => 'data flag 参数错误, flag 参数需要一个字符,例：0'), JSON_UNESCAPED_UNICODE);
                        }
                        else if ($data[$k] != "0" && $data[$k] != "1" && $data[$k] != "2") {
                            return json_encode(array('error' => "data flag 参数错误,例： 0：正考分数，1：补考分数，2：重修分数"), JSON_UNESCAPED_UNICODE);
                        }
                        break;
                }
            }
        }

        if($this->db->selectQuery("*",$this->courseTable)->andQueryList(array("courseId"=>$data["courseId"],"teacherId"=>$data["teacherId"],"classId"=>substr($data["studentId"],0,8)))->getSelectNum() == 0)
        {
            return json_encode(array('error' => '课程信息与学生信息不符'), JSON_UNESCAPED_UNICODE);
        }

        foreach ($data as $k => $v)
        {
            switch ($k)
            {
                case "score":
                    if(strlen($v) == 0)
                    {
                        return json_encode(array('error' => '分数不能为空'), JSON_UNESCAPED_UNICODE);
                    }
                    break;
            }
        }

        $score = $data["score"];
        unset($data["score"]);

        if($this->db->selectQuery("*",$this->scoreTable)->andQueryList($data)->getSelectNum() == 0)
        {
            return json_encode(array('error' => '该分数信息尚未登记'), JSON_UNESCAPED_UNICODE);
        }

        if ($this->db->updateQuery($this->scoreTable, array("score"=>$score))->andQueryList($data)->updateLimit(1)->updateExecute()->getAffectedRows() == 1) {
            return json_encode(array('success' => '分数信息更新成功'), JSON_UNESCAPED_UNICODE);
        } else {
            return json_encode(array('info' => '分数信息未更改'), JSON_UNESCAPED_UNICODE);
        }

    }

    public function deleteCourse($data)
    {
        if (!isset($_SESSION['ms_id']) || !isset($_SESSION['ms_user'])) {
            return json_encode(array('error' => '请登录后再进行此操作'), JSON_UNESCAPED_UNICODE);
        } else if ($_SESSION['ms_identity'] != 'Teacher') {
            return json_encode(array('error' => '您没有此权限'), JSON_UNESCAPED_UNICODE);
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

        foreach ($data as $k => $v) {
            if (!array_key_exists($k, $this->model)) {
                unset($data[$k]);
            }
        }
        if (!isset($data['courseId'])) {
            return json_encode(array('error' => 'courseId 为必填参数'), JSON_UNESCAPED_UNICODE);
        } else if (!isset($data['studentId'])) {
            return json_encode(array('error' => 'classId 为必填参数'), JSON_UNESCAPED_UNICODE);
        } else if (!isset($data['teacherId'])) {
            return json_encode(array('error' => 'teacherId 为必填参数'), JSON_UNESCAPED_UNICODE);
        } else if (!isset($data['flag'])) {
            return json_encode(array('error' => 'flag 为必填参数'), JSON_UNESCAPED_UNICODE);
        } else {
            if ($this->db->selectQuery('*', $this->scoreTable)->andQueryList(array('courseId' => $data['courseId'], 'studentId' => $data["studentId"],"teacherId"=>$data["teacherId"],"flag"=>$data["flag"]))->getSelectNum() == 0) {
                return json_encode(array('error' => '此分数信息不存在'), JSON_UNESCAPED_UNICODE);
            } else {
                if ($this->db->deleteQuery($this->scoreTable)->andQueryList(array('courseId' => $data['courseId'], 'studentId' => $data["studentId"],"teacherId"=>$data["teacherId"],"flag"=>$data["flag"]))->deleteExecute()->getAffectedRows() == 1) {
                    return json_encode(array('success' => '分数信息删除成功'), JSON_UNESCAPED_UNICODE);
                } else {
                    return json_encode(array('error' => '分数信息删除失败'), JSON_UNESCAPED_UNICODE);
                }
            }
        }
    }
}