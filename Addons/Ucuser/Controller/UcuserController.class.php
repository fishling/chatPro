<?php

namespace Addons\Ucuser\Controller;
use Home\Controller\AddonsController;
use Common\Model\UcuserModel;
use Com\TPWechat;
use Com\JsSdkPay;
use Com\ErrCode;

class UcuserController extends AddonsController{

              public function index(){
                $params['mp_id'] = $map['mp_id'] = get_mpid();
                $this->assign ( 'mp_id', $params['mp_id'] );
		        $map['id'] = I('id');

		      $uid = get_ucuser_uid();   //获取粉丝用户uid，一个神奇的函数，没初始化过就初始化一个粉丝
		      if($uid === false){
                  $this->error('只可在微信中访问');
              }
              $user = get_uid_ucuser($uid);                    //获取本地存储公众号粉丝用户信息

              $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
              $surl = get_shareurl();
              if(!empty($surl)){
                  $this->assign ( 'share_url', $surl );
              }

              $appinfo = get_mpid_appinfo ( $params ['mp_id'] );   //获取公众号信息
              $this->assign ( 'appinfo', $appinfo );

              $options['appid'] = $appinfo['appid'];    //初始化options信息
              $options['appsecret'] = $appinfo['secret'];
              $options['encodingaeskey'] = $appinfo['encodingaeskey'];
              $weObj = new TPWechat($options);

        $auth = $weObj->checkAuth();
        $js_ticket = $weObj->getJsTicket();
        if (!$js_ticket) {
            $this->error('获取js_ticket失败！错误码：'.$weObj->errCode.' 错误原因：'.ErrCode::getErrText($weObj->errCode));
        }
        $js_sign = $weObj->getJsSign($url);

        $this->assign ( 'js_sign', $js_sign );

        $fans = $weObj->getUserInfo($user['openid']);
        if($user['status'] != 2 && !empty($fans['openid'])){      //没有同步过用户资料，同步到本地数据
            $user = array_merge($user ,$fans);
            $user['status'] = 2;
            $model = D('Ucuser');
            $model->save($user);
        }

        if($user['login'] == 1){              //登录状态就显示微信的用户资料，未登录状态显示本地存储的用户资料
            if(!empty($fans['openid'])){
                $user = array_merge($user ,$fans);
            }
          }
        $this->assign ( 'user', $user );
		//$templateFile = $this->model ['template_list'] ? $this->model ['template_list'] : '';
		$this->display ( );
	}

    public function login(){
        $params['mp_id'] = $map['mp_id'] = get_mpid();
        $this->assign ( 'mp_id', $params['mp_id'] );
        $map['id'] = I('id');
        $uid = get_ucuser_uid();   //获取粉丝用户uid，一个神奇的函数，没初始化过就初始化一个粉丝
        if($uid === false){
            $this->error('只可在微信中访问');
        }
        $user = get_uid_ucuser($uid);                    //获取公众号粉丝用户信息
        $this->assign ( 'user', $user );

        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $surl = get_shareurl();
        if(!empty($surl)){
            $this->assign ( 'share_url', $surl );
        }

        $appinfo = get_mpid_appinfo ( $params ['mp_id'] );   //获取公众号信息
        $this->assign ( 'appinfo', $appinfo );

        $options['appid'] = $appinfo['appid'];    //初始化options信息
        $options['appsecret'] = $appinfo['secret'];
        $options['encodingaeskey'] = $appinfo['encodingaeskey'];
        $weObj = new TPWechat($options);

        $auth = $weObj->checkAuth();
        $js_ticket = $weObj->getJsTicket();
        if (!$js_ticket) {
            $this->error('获取js_ticket失败！错误码：'.$weObj->errCode.' 错误原因：'.ErrCode::getErrText($weObj->errCode));
        }
        $js_sign = $weObj->getJsSign($url);

        $this->assign ( 'js_sign', $js_sign );

        if (IS_POST) {

            $aMobile = I('post.mobile', '', 'op_t');
            $aPassword = I('post.password', '', 'op_t');
            $aRemember = I('post.remember', 0, 'intval');

            $ucuser = D('Common/Ucuser');
            $res = $ucuser->login($uid,$aMobile,$aPassword,$aRemember);
           if($res > 0){
                $this->success ( '登录成功', addons_url ( 'Ucuser://Ucuser/index' ) );
            }else{
                $this->error ( $ucuser->getError () );
            }

        } else { //显示登录页面
            $this->display();
        }

    }

    public function register(){
        $params['mp_id'] = $map['mp_id'] = get_mpid();
        $this->assign ( 'mp_id', $params['mp_id'] );
        $map['id'] = I('id');
        $uid = get_ucuser_uid();   //获取粉丝用户uid，一个神奇的函数，没初始化过就初始化一个粉丝
        if($uid === false){
            $this->error('只可在微信中访问');
        }
        $user = get_uid_ucuser($uid);                    //获取公众号粉丝用户信息
        $this->assign ( 'user', $user );

        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $surl = get_shareurl();
        if(!empty($surl)){
            $this->assign ( 'share_url', $surl );
        }

        $appinfo = get_mpid_appinfo ( $params ['mp_id'] );   //获取公众号信息
        $this->assign ( 'appinfo', $appinfo );

        $options['appid'] = $appinfo['appid'];    //初始化options信息
        $options['appsecret'] = $appinfo['secret'];
        $options['encodingaeskey'] = $appinfo['encodingaeskey'];
        $weObj = new TPWechat($options);

        $auth = $weObj->checkAuth();
        $js_ticket = $weObj->getJsTicket();
        if (!$js_ticket) {
            $this->error('获取js_ticket失败！错误码：'.$weObj->errCode.' 错误原因：'.ErrCode::getErrText($weObj->errCode));
        }
        $js_sign = $weObj->getJsSign($url);

        $this->assign ( 'js_sign', $js_sign );

        if (IS_POST) {

            $aMobile = I('post.mobile', '', 'op_t');
            $aPassword = I('post.password', '', 'op_t');

            $ucuser = D('Common/Ucuser');
            $res = $ucuser->register($uid,$aPassword,$aMobile);
            if($res > 0){
                $this->success ( '注册成功', addons_url ( 'Ucuser://Ucuser/login' ) );
            }else{
                $this->error ( $ucuser->getError () );
            }

        } else { //显示注册页面
            $this->display();
        }

    }

    public function profile(){
        $params['mp_id'] = $map['mp_id'] = get_mpid();
        $this->assign ( 'mp_id', $params['mp_id'] );
        $map['id'] = I('id');
        $uid = get_ucuser_uid();   //获取粉丝用户uid，一个神奇的函数，没初始化过就初始化一个粉丝
        if($uid === false){
            $this->error('只可在微信中访问');
        }
        $user = get_uid_ucuser($uid);                    //获取公众号粉丝用户信息

        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $surl = get_shareurl();
        if(!empty($surl)){
            $this->assign ( 'share_url', $surl );
        }

        $appinfo = get_mpid_appinfo ( $params ['mp_id'] );   //获取公众号信息
        $this->assign ( 'appinfo', $appinfo );

        $options['appid'] = $appinfo['appid'];    //初始化options信息
        $options['appsecret'] = $appinfo['secret'];
        $options['encodingaeskey'] = $appinfo['encodingaeskey'];
        $weObj = new TPWechat($options);

        $auth = $weObj->checkAuth();
        $js_ticket = $weObj->getJsTicket();
        if (!$js_ticket) {
            $this->error('获取js_ticket失败！错误码：'.$weObj->errCode.' 错误原因：'.ErrCode::getErrText($weObj->errCode));
        }
        $js_sign = $weObj->getJsSign($url);

        $this->assign ( 'js_sign', $js_sign );

        $fans = $weObj->getUserInfo($user['openid']);
        $this->assign ( 'user', $user );

        if (IS_POST) {
            $data['uid'] = $uid;
            $data['nickname'] = I('post.nickname', '', 'op_t');
            $data['mobile'] = I('post.mobile', '', 'op_t');
            $data['email'] = I('post.email', '', 'op_t');
            $data['sex'] = I('post.sex', '', 'intval');
            $data['qq'] = I('post.qq', '', 'op_t');
            $data['weibo'] = I('post.weibo', '', 'op_t');
            $data['signature'] = I('post.signature', '', 'op_t');

            $ucuser = D('Common/Ucuser');
            $res = $ucuser->save($data);
            if($res > 0){
                $this->success ( '更新资料成功', addons_url ( 'Ucuser://Ucuser/profile' ) );
            }else{
                $this->error ( $ucuser->getError () );
            }

        } else { //显示资料页面
           if($user['openid'] != $fans['openid']){        //本地保存的openid和公众平台获取的不同，不允许用户自己以外的人访问
               $this->error ( '无权访问用户资料',addons_url ( 'Ucuser://Ucuser/login' ),5 );
           }
            $this->display();
        }

    }

    public function logout($uid = 0){
        if($uid == 0){
            $uid = get_ucuser_uid();
        }
        $ucuser = D('Common/Ucuser');
        $ucuser->logout($uid);
        $this->success ( '已退出登录', addons_url ( 'Ucuser://Ucuser/index' ) );
    }

public function forget(){

        $params['mp_id'] = $map['mp_id'] = get_mpid();
        $this->assign ( 'mp_id', $params['mp_id'] );
        $map['id'] = I('id');
        $uid = get_ucuser_uid();   //获取粉丝用户uid，一个神奇的函数，没初始化过就初始化一个粉丝
        if($uid === false){
            $this->error('只可在微信中访问');
        }

        if (IS_POST) {
            $aMobile = I('post.mobile', '', 'op_t');
            $verify = I('post.verify', '', 'op_t');
            $password = I('post.password', '', 'op_t');
            $repassword = I('post.repassword', '', 'op_t');

            //确认两次输入的密码正确
            if ($password != $repassword) {
                $this->error('两次输入的密码不一致');
            }
            //读取SESSION中的验证信息
            $mobile = session('reset_password_mobile');
           //提交修改密码和接收验证码的手机号码不一致
            if ($aMobile != $mobile) {
                $this->error('提交修改密码和接收验证码的手机号码不一致');
            }

            $res = D('Verify')->checkVerify($aMobile, "mobile", $verify, 0);
            //确认验证信息正确
            if(!$res){
                echo '验证码错误';
                return false;
            }else{
                echo true;
            }

            //将新的密码写入数据库
            $data1 = array('uid' => $uid, 'mobile' => $aMobile, 'password' => $password);
            $model = D('Common/Ucuser');
            $data1 = $model->create($data1);
            if (!$data1) {
                $this->error('密码格式不正确');
            }
            $result = $model->where(array('uid' => $uid))->save($data1);
            if ($result === false) {
                $this->error('数据库写入错误');
            }

            //将新的密码写入数据库
            $data = array('id' => $uid, 'mobile' => $aMobile, 'password' => $password);
            $model = UCenterMember();
            $data = $model->create($data);
            if (!$data) {
                $this->error('密码格式不正确');
            }
            $result = $model->where(array('id' => $uid))->save($data);
            if ($result === false) {
                $this->error('数据库写入错误');
            }

            //显示成功消息
            $this->success('密码重置成功',  addons_url ( 'Ucuser://Ucuser/login' ) );

        }

            $this->display();
    }
    
    /**
     * checkVerify 检测验证码
     * @author:patrick contact@uctoo.com
     *
     */
    public function checkVerify()
    {
        $aMobile = I('post.mobile', '', 'op_t');
        $verify = I('post.verify', '', 'op_t');
        $aUid = I('uid', 0, 'intval');

       $res = D('Verify')->checkVerify($aMobile, "mobile", $verify, 0);

       if (!$res) {
          echo '验证码错误';
          return false;
       }else{
          echo true;
       }
    }
}