<?php
// +----------------------------------------------------------------------
// | UCToo [ Universal Convergence Technology ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.uctoo.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: patrick <contact@uctoo.com> <http://www.uctoo.com>
// +----------------------------------------------------------------------

namespace Admin\Controller;

/**
 * 系统信息控制器
 * @author patrick <contact@uctoo.com>
 */
class SystemController extends AdminController
{

    /**
     * 系统信息首页
     * @author patrick <contact@uctoo.com>
     */
    public function index(){
        $this->meta_title = '系统信息首页';
        $this->display();
    }

}
