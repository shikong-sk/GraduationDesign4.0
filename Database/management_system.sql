-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2020-04-13 02:12:58
-- 服务器版本： 5.5.29-log
-- PHP 版本： 5.6.9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `management_system`
--

-- --------------------------------------------------------

--
-- 表的结构 `ms_class`
--

CREATE TABLE `ms_class` (
  `classId` char(8) NOT NULL COMMENT '班级编号',
  `grade` char(2) NOT NULL COMMENT '年级',
  `years` char(1) NOT NULL COMMENT '学制',
  `departmentId` char(2) NOT NULL COMMENT '院系编号',
  `departmentName` varchar(255) NOT NULL COMMENT '院系名称',
  `majorId` char(2) NOT NULL COMMENT '专业编号',
  `majorName` varchar(255) NOT NULL COMMENT '专业名称',
  `class` char(1) NOT NULL COMMENT '班号',
  `studentNum` char(2) NOT NULL COMMENT '学生人数'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `ms_course`
--

CREATE TABLE `ms_course` (
  `courseId` char(36) NOT NULL COMMENT '课程id',
  `courseName` varchar(255) NOT NULL COMMENT '课程名',
  `teacherId` char(36) NOT NULL COMMENT '教工id',
  `teacherName` varchar(255) NOT NULL COMMENT '教工姓名',
  `startTime` date NOT NULL COMMENT '课程开始时间',
  `endTime` date NOT NULL COMMENT '课程结束时间',
  `public` tinyint(1) NOT NULL COMMENT '公选课'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `ms_department`
--

CREATE TABLE `ms_department` (
  `departmentId` char(2) NOT NULL,
  `departmentName` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `ms_department`
--

INSERT INTO `ms_department` (`departmentId`, `departmentName`, `active`) VALUES
('01', '人文社科系', 1),
('02', '外语系', 1),
('03', '经管系', 1),
('04', '机电工程系', 1),
('05', '计算机系', 1),
('06', '艺术体育系', 1),
('07', '自然科学系', 1),
('08', '学前教育系', 1);

-- --------------------------------------------------------

--
-- 表的结构 `ms_grade`
--

CREATE TABLE `ms_grade` (
  `grade` char(2) NOT NULL COMMENT '年级',
  `departmentId` char(2) NOT NULL COMMENT '院系编号',
  `departmentName` varchar(255) NOT NULL COMMENT '院系名称',
  `majorId` char(2) NOT NULL COMMENT '专业编号',
  `majorName` varchar(255) NOT NULL COMMENT '专业名称',
  `classNum` char(1) NOT NULL COMMENT '班级数量'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `ms_major`
--

CREATE TABLE `ms_major` (
  `majorId` char(2) NOT NULL,
  `majorName` varchar(255) NOT NULL,
  `departmentId` char(2) NOT NULL,
  `departmentName` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `ms_score`
--

CREATE TABLE `ms_score` (
  `studentId` char(10) NOT NULL COMMENT '学生Id',
  `studentName` varchar(255) NOT NULL COMMENT '学生姓名',
  `courseId` char(36) NOT NULL COMMENT '课程Id',
  `courseName` varchar(255) NOT NULL COMMENT '课程名称',
  `teacherId` char(36) NOT NULL COMMENT '教工Id',
  `teacherName` varchar(255) NOT NULL COMMENT '教工姓名',
  `score` char(3) NOT NULL COMMENT '分数',
  `flag` tinyint(1) NOT NULL COMMENT '0:正考分数 1:补考分数 2:重修分数'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `ms_score`
--

INSERT INTO `ms_score` (`studentId`, `studentName`, `courseId`, `courseName`, `teacherId`, `teacherName`, `score`, `flag`) VALUES
('', '', '', '', '', '', '', 8);

-- --------------------------------------------------------

--
-- 表的结构 `ms_student`
--

CREATE TABLE `ms_student` (
  `studentId` char(10) NOT NULL COMMENT '学号',
  `studentName` varchar(15) NOT NULL COMMENT '学生姓名',
  `gender` char(1) NOT NULL COMMENT '性别',
  `both` date NOT NULL COMMENT '出生日期',
  `salt` char(6) NOT NULL COMMENT '加密盐值',
  `password` char(40) NOT NULL COMMENT '密码hash',
  `contact` char(20) NOT NULL COMMENT '联系方式',
  `grade` char(2) NOT NULL COMMENT '入学年级',
  `years` char(1) NOT NULL COMMENT '学制',
  `departmentId` char(2) NOT NULL COMMENT '院系编号',
  `departmentName` varchar(255) NOT NULL COMMENT '院系名称',
  `majorId` char(2) NOT NULL COMMENT '专业编号',
  `majorName` varchar(255) NOT NULL COMMENT '专业名称',
  `class` char(1) NOT NULL COMMENT '班号',
  `classId` char(8) NOT NULL COMMENT '班级编号',
  `seat` char(2) NOT NULL COMMENT '座号',
  `active` tinyint(1) NOT NULL COMMENT '激活状态',
  `idCard` char(18) NOT NULL COMMENT '身份证号码',
  `address` varchar(255) NOT NULL COMMENT '家庭住址',
  `studentImg` varchar(255) NOT NULL COMMENT '学生照片'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `ms_student`
--

INSERT INTO `ms_student` (`studentId`, `studentName`, `gender`, `both`, `salt`, `password`, `contact`, `grade`, `years`, `departmentId`, `departmentName`, `majorId`, `majorName`, `class`, `classId`, `seat`, `active`, `idCard`, `address`, `studentImg`) VALUES
('1730502127', 'test', '男', '1999-07-10', '943501', '3d3145923fd063b9ece9e2bc5dc22acfeed77cd6', '13600000000', '17', '3', '05', '', '02', '', '1', '17305021', '27', 1, '440000199907102912', '', './Storage/User/Student/1730502127_test_B249CA38-12AB-A1CA-CAB5-907E04B929E6.png');

-- --------------------------------------------------------

--
-- 表的结构 `ms_teacher`
--

CREATE TABLE `ms_teacher` (
  `teacherId` char(36) NOT NULL COMMENT '教工Id',
  `teacherName` varchar(255) NOT NULL COMMENT '教工姓名',
  `departmentId` char(2) NOT NULL COMMENT '院系Id',
  `departmentName` varchar(255) NOT NULL COMMENT '院系名称',
  `teacherImg` varchar(255) NOT NULL COMMENT '教工照片',
  `contact` char(20) NOT NULL COMMENT '联系方式',
  `gender` char(1) NOT NULL COMMENT '性别',
  `salt` char(6) NOT NULL COMMENT '加密盐值',
  `password` char(40) NOT NULL COMMENT '密码hash',
  `idCard` char(18) NOT NULL COMMENT '身份证号码',
  `email` varchar(50) NOT NULL COMMENT '邮箱地址',
  `permission` char(1) NOT NULL DEFAULT '2' COMMENT '教工权限 0:超级管理员 1:管理员 2:普通教工',
  `address` varchar(255) NOT NULL COMMENT '联系地址',
  `active` tinyint(1) NOT NULL,
  `both` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `ms_teacher`
--

INSERT INTO `ms_teacher` (`teacherId`, `teacherName`, `departmentId`, `departmentName`, `teacherImg`, `contact`, `gender`, `salt`, `password`, `idCard`, `email`, `permission`, `address`, `active`, `both`) VALUES
('1', '测试', '05', '', './Storage/User/Teacher/1_测试_668F80F4-AD98-7883-EEF6-D1660B3AEE44.png', '', '', '294167', '37e7e016fa6bc1a02a4ab29fdbb1010af974e97e', '', '', '2', '', 1, '0000-00-00');

--
-- 转储表的索引
--

--
-- 表的索引 `ms_class`
--
ALTER TABLE `ms_class`
  ADD UNIQUE KEY `uniqueClassData` (`classId`,`grade`,`years`,`departmentId`,`majorId`,`class`),
  ADD KEY `classId` (`classId`),
  ADD KEY `grade` (`grade`),
  ADD KEY `years` (`years`),
  ADD KEY `departmentId` (`departmentId`),
  ADD KEY `majorId` (`majorId`),
  ADD KEY `class` (`class`);

--
-- 表的索引 `ms_course`
--
ALTER TABLE `ms_course`
  ADD PRIMARY KEY (`courseId`),
  ADD KEY `courseId` (`courseId`),
  ADD KEY `teacherId` (`teacherId`),
  ADD KEY `public` (`public`);

--
-- 表的索引 `ms_department`
--
ALTER TABLE `ms_department`
  ADD PRIMARY KEY (`departmentId`) USING BTREE,
  ADD UNIQUE KEY `departmentId` (`departmentId`);

--
-- 表的索引 `ms_grade`
--
ALTER TABLE `ms_grade`
  ADD UNIQUE KEY `uniqueGradeData` (`grade`,`departmentId`,`majorId`) USING BTREE,
  ADD KEY `departmentId` (`departmentId`),
  ADD KEY `majorId` (`majorId`),
  ADD KEY `grade` (`grade`);

--
-- 表的索引 `ms_major`
--
ALTER TABLE `ms_major`
  ADD PRIMARY KEY (`majorId`,`departmentId`),
  ADD UNIQUE KEY `uniqueMajorData` (`majorId`,`departmentId`) USING BTREE,
  ADD KEY `majorId` (`majorId`),
  ADD KEY `departmentId` (`departmentId`),
  ADD KEY `active` (`active`);

--
-- 表的索引 `ms_score`
--
ALTER TABLE `ms_score`
  ADD UNIQUE KEY `uniqueScoreData` (`studentId`,`courseId`,`teacherId`,`flag`) USING BTREE,
  ADD KEY `courseId` (`courseId`),
  ADD KEY `teacherId` (`teacherId`),
  ADD KEY `flag` (`flag`),
  ADD KEY `studentId` (`studentId`) USING BTREE;

--
-- 表的索引 `ms_student`
--
ALTER TABLE `ms_student`
  ADD PRIMARY KEY (`studentId`),
  ADD UNIQUE KEY `studentId` (`studentId`),
  ADD UNIQUE KEY `uniqueStudentData` (`studentId`,`grade`,`years`,`departmentId`,`majorId`,`classId`,`class`,`seat`) USING BTREE,
  ADD KEY `gender` (`gender`),
  ADD KEY `grade` (`grade`),
  ADD KEY `deparment` (`departmentId`),
  ADD KEY `major` (`majorId`),
  ADD KEY `class` (`class`),
  ADD KEY `classId` (`classId`);

--
-- 表的索引 `ms_teacher`
--
ALTER TABLE `ms_teacher`
  ADD PRIMARY KEY (`teacherId`,`permission`) USING BTREE,
  ADD UNIQUE KEY `uniqueTeacherData` (`teacherId`,`departmentId`) USING BTREE,
  ADD KEY `teacherId` (`teacherId`),
  ADD KEY `departmentId` (`departmentId`),
  ADD KEY `permission` (`permission`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
