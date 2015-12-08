<?php
// +----------------------------------------------------------------------
// | UCToo [ Universal Convergence Technology ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.uctoo.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: patrick <contact@uctoo.com> <http://www.uctoo.com>
// +----------------------------------------------------------------------

namespace Mpbase\Model;
use Think\Model;

/**
 * Class AutoreplyModel 自动回复模型
 * @package Mpbase\Model
 * @auth patrick
 */
class AutoreplyModel extends Model {

    public function getArType($key = null){
        $array = array(1 => '关注自动回复', 2 => '消息自动回复', 3 => '关键词自动回复');
        return !isset($key)?$array:$array[$key];
    }
    public function addAr($data)
    {
        $res = $this->add($data);
        return $res;
    }

    public function getAr($where){
        $mp = $this->where($where)->find();
        return $mp;
    }

    public function getList($where){
        $list = $this->where($where)->select();
        return $list;
    }

    public function editAr($data)
    {
        $res = $this->save($data);
        return $res;
    }
}