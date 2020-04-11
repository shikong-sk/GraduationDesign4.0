<?php

/*
 * 接口定义
 */

/*
 * 数据库基础接口
 */
interface SqlMethod{

    public function insert();

    public function update();

    public function delete();

    public function select();

}

/*
 * 用户基础接口
 */
interface UserMethod{

    public function login($user,$password);

    public function logout();

}