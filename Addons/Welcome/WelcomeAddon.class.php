<?php

namespace Addons\welcome;
use Common\Controller\Addon;

/**
 * 欢迎语插件
 * @author patrick
 */

    class WelcomeAddon extends Addon{
		
		public $custom_config = 'config.html';

        public $info = array(
            'name'=>'Welcome',
            'title'=>'欢迎语',
            'description'=>'用户关注公众号时发送的欢迎信息，支持文本，图片，图文的信息',
            'status'=>1,
            'author'=>'patrick',
            'version'=>'0.1'
        );

        public function install(){
            $install_sql = './Addons/Welcome/install.sql';
            if (file_exists ( $install_sql )) {
                execute_sql_file ( $install_sql );
            }
            return true;
        }

        public function uninstall(){
            $uninstall_sql = './Addons/Welcome/uninstall.sql';
            if (file_exists ( $uninstall_sql )) {
                execute_sql_file ( $uninstall_sql );
            }
            return true;
        }

        //实现的welcome钩子方法，对关注事件TPWechat::EVENT_SUBSCRIBE进行匹配，根据具体处理业务的addon数据或配置，组装回复给用户的内容
        public function welcome($params){

            if($params['mp_id']){
                $kmap['mp_id'] = $params['mp_id'];
                $kmap['type'] = "1";                                       //TODO:先只支持精确匹配，后续根据keyword_type字段增加模糊匹配
                $con = $params['weObj']->getRevContent();
                $welcome = M('Autoreply')->where($kmap)->find();

                if($welcome['keyword_id'] == 0){              //如果有指定模型，就用模型中的aim_id数据组装回复的内容

                    $reData = $welcome['content'];
                    $params['weObj']->text($reData);
                }
            }else{

            }
            // $params['weObj']->text("hello ");
        }
    }