<?php
// +----------------------------------------------------------------------
// | UCToo [ Universal Convergence Technology ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.uctoo.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: patrick <contact@uctoo.com> <http://www.uctoo.com>
// +----------------------------------------------------------------------

namespace Common\Model;

use Think\Model;

/**
 * 通用订单模型
 */
class OrderModel extends Model
{
    /* 通用订单模型自动完成 */
    protected $_auto = array(

        array('order_create_time', NOW_TIME, self::MODEL_INSERT),

    );

}
