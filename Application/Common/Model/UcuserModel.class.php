<?php
// +----------------------------------------------------------------------
// | UCToo [ Universal Convergence Technology ]
// +----------------------------------------------------------------------
// | Copyright (c) 2014-2015 http://uctoo.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Patrick <contact@uctoo.com>
// +----------------------------------------------------------------------

namespace Common\Model;

use Think\Model;

require_once(APP_PATH . 'User/Conf/config.php');
require_once(APP_PATH . 'User/Common/common.php');
/**
 * 微信用户模型，也就是公众号粉丝
 */
class UcuserModel extends Model
{
    /* 用户模型自动完成 */
    protected $_auto = array(
        array('login', 0, self::MODEL_INSERT),
        array('reg_ip', 'get_client_ip', self::MODEL_INSERT, 'function', 1),
        array('reg_time', NOW_TIME, self::MODEL_INSERT),
        array('last_login_ip', 0, self::MODEL_INSERT),
        array('last_login_time', 0, self::MODEL_INSERT),
        array('update_time', NOW_TIME),
        array('status', 1, self::MODEL_INSERT),
        array('score1', 0, self::MODEL_INSERT),
        array('password', 'think_ucenter_md5', self::MODEL_BOTH, 'function', UC_AUTH_KEY),
    );

    protected $_validate = array(
        array('signature', '0,100', -1, self::EXISTS_VALIDATE, 'length'),

        /* 验证昵称 */
        array('nickname', '2,32', -33, self::EXISTS_VALIDATE, 'length'), //昵称长度不合法
        array('nickname', 'checkDenyNickname', -31, self::EXISTS_VALIDATE, 'callback'), //昵称禁止注册
        array('nickname', 'checkNickname', -32, self::EXISTS_VALIDATE, 'callback'),
        array('nickname', '', -30, self::EXISTS_VALIDATE, 'unique'), //昵称被占用

        /* 验证密码 */
        array('password', '6,30', -4, self::EXISTS_VALIDATE, 'length'), //密码长度不合法

        /* 验证手机号码 */
        array('mobile', '/^(1[3|4|5|8])[0-9]{9}$/', -9, self::EXISTS_VALIDATE), //手机格式不正确 TODO:
        array('mobile', 'checkDenyMobile', -10, self::EXISTS_VALIDATE, 'callback'), //手机禁止注册
        array('mobile', '', -11, self::EXISTS_VALIDATE, 'unique'), //手机号被占用

    );

    //protected $insertField = 'nickname,sex,birthday,qq,signature'; //新增数据时允许操作的字段
    //protected $updateField = 'nickname,sex,birthday,qq,signature,last_login_ip,login,update_time,last_login_role,show_role,status,tox_money,score,pos_province,pos_city,pos_district,pos_community'; //编辑数据时允许操作的字段

    public function getSex($key = null){
        $array = array(0 => '未知', 1 => '男性', 2 => '女性');
        return !isset($key)?$array:$array[$key];
    }

    /**
     * 检测用户名是不是被禁止注册
     * @param  string $nickname 昵称
     * @return boolean          ture - 未禁用，false - 禁止注册
     */
    protected function checkDenyNickname($nickname)
    {
        return true; //TODO: 暂不限制，下一个版本完善
    }

    protected function checkNickname($nickname)
    {
        //如果用户名中有空格，不允许注册
        if (strpos($nickname, ' ') !== false) {
            return false;
        }
        preg_match('/^(?!_|\s\')[A-Za-z0-9_\x80-\xff\s\']+$/', $nickname, $result);

        if (!$result) {
            return false;
        }
        return true;
    }

    /**
     * 检测手机是不是被禁止注册
     * @param  string $mobile 手机
     * @return boolean        ture - 未禁用，false - 禁止注册
     */
    protected function checkDenyMobile($mobile)
    {
        return true; //TODO: 暂不限制，下一个版本完善
    }

    /**
     * 初始化一个新用户
     * @param  string $uid member表uid
     * @param  string $mp_id 公众号mp_id
     * @param  string $openid 用户openid
     * @return integer          注册成功-用户uid，注册失败-错误编号
     */
    public function registerUser($uid ,$mp_id = '',$openid = '')
    {
        /* 在当前应用中注册用户 */
        if ($user = $this->create(array('uid' => $uid,'mp_id' => $mp_id,'openid' => $openid, 'status' => 1))) {
            $ucuid = $this->add($user);
            if (!$ucuid) {
                $this->error = '微会员信息注册失败，请重试！';
                return false;
            }
            sync_wxuser($mp_id,$openid);                                //初始化用户后同步一次用户资料
            return $uid;
        } else {
            return $this->getError(); //错误详情见自动验证注释
        }

    }

    /**
     * 注册一个新用户,其实已经注册了只是完善用户信息
     * @param  integer $uid 用户UID
     * @param  string $nickname 昵称
     * @param  string $password 用户密码
     * @param  string $email 用户邮箱
     * @param  string $mobile 用户手机号码
     * @return integer          注册成功-用户信息，注册失败-错误编号
     */
    public function register($uid ,$password, $mobile)
    {
        $user = $this->find($uid);
        $data = array(
            'uid' => $uid,
            'password' => $password,
            'mobile' => $mobile,
        );
        $data1 = array(
            'id' => $uid,
            'password' => $password,
            'mobile' => $mobile,
        );

            /* 完善用户信息 */
            if ( $this->create($data) && $this->save()) {
                if (UCenterMember()->create($data1) && UCenterMember()->save()){             //更新UcenterMember中的手机和密码
                    return true;
                }
            } else {
                return $this->getError(); //错误详情见自动验证注释
            }

    }

    /**
     * 登录指定用户
     * @param  integer $uid 用户UID
     * @param  string  $mobile 用户名
     * @param  string  $password 用户密码
     * @param bool $remember
     * @param int $role_id 有值代表强制登录这个角色
     * @return boolean      ture-登录成功，false-登录失败
     */
    public function login($uid,$mobile = '', $password = '', $remember = false,$role_id=0)
    {
        /* 检测是否在当前应用注册 */
        $map['uid'] = $uid;
        $map['mobile'] = $mobile;

        /* 获取用户数据 */
        $user = $this->where($map)->find();

        if($role_id!=0){
            $user['last_login_role']=$role_id;
        }else{
            if(!intval($user['last_login_role'])){
                $user['last_login_role']=$user['show_role'];
            }
        }

        $return = check_action_limit('input_password','ucuser',$user['uid'],$user['uid']);
        if($return && !$return['state']){
            return $return['info'];
        }

        if (is_array($user) && $user['status']) {
            /* 验证用户密码 */
            if (think_ucenter_md5($password, UC_AUTH_KEY) === $user['password']) {
                $this->updateLogin($user['uid']); //更新用户登录信息
                return $user['uid']; //登录成功，返回用户UID
            } else {

                return -2; //密码错误
            }
        } else {
            return -1; //用户不存在或被禁用
        }

        //以下程序运行不到

        session('temp_login_uid', $uid);
        session('temp_login_role_id', $user['last_login_role']);

        if ($user['status'] == 3 /*判断是否激活*/) {
            header('Content-Type:application/json; charset=utf-8');
            $data['status'] = 1;
            $data['url'] = U('Ucuser/Ucuser/activate');
            exit(json_encode($data));
        }

        if (1 > $user['status']) {
            $this->error = '用户未激活或已禁用！'; //应用级别禁用
            return false;
        }

        /* 登录用户 */
        $this->autoLogin($user, $remember);

        session('temp_login_uid',null);
        session('temp_login_role_id', null);

        return true;
    }

    /**
     * 注销当前用户
     * @return void
     */
    public function logout($uid = 0)
    {
        session('user_auth', null);
        session('user_auth_sign', null);
        cookie('UCTOO_LOGGED_USER', NULL);
        $data = array(
            'uid' => $uid,
            'login' => 0,                                              //登录状态设置为0
        );
        $this->save($data);
    }

    /**
     * 更新用户登录信息
     * @param  integer $uid 用户ID
     */
    protected function updateLogin($uid)
    {
        $data = array(
            'uid' => $uid,
            'last_login_time' => NOW_TIME,
            'last_login_ip' => get_client_ip(1),
            'login' => 1,                                              //登录状态设置为1
        );
        $this->save($data);
    }

    /**
     * 自动登录用户
     * @param  integer $user 用户信息数组
     */
    private function autoLogin($user, $remember = false,$role_id=0)
    {

        /* 更新登录信息 */
        $data = array(
            'uid' => $user['uid'],
            'last_login_time' => NOW_TIME,
            'last_login_ip' => get_client_ip(1),
            'last_login_role'=>$user['last_login_role'],
        );
        $this->save($data);
        //判断角色用户是否审核
        $map['uid']=$user['uid'];
        $map['role_id']=$user['last_login_role'];
        $audit=D('UserRole')->where($map)->getField('status');
        //判断角色用户是否审核 end

        /* 记录登录SESSION和COOKIES */
        $auth = array(
            'uid' => $user['uid'],
            'last_login_time' => $user['last_login_time'],
            'role_id'=>$user['last_login_role'],
            'audit'=>$audit,
        );

        session('user_auth', $auth);
        session('user_auth_sign', data_auth_sign($auth));
        if ($remember) {
            $user1 = D('user_token')->where('uid=' . $user['uid'])->find();
            $token = $user1['token'];
            if ($user1 == null) {
                $token = build_auth_key();
                $data['token'] = $token;
                $data['time'] = time();
                $data['uid'] = $user['uid'];
                D('user_token')->add($data);
            }
        }

        if (!$this->getCookieUid() && $remember) {
            $expire = 3600 * 24 * 7;
            cookie('UCTOO_LOGGED_USER', $this->jiami($this->change() . ".{$user['uid']}.{$token}"), $expire);
        }
    }

    public function need_login()
    {
        if ($uid = $this->getCookieUid()) {
            $this->login($uid);
            return true;
        }
    }

    public function getCookieUid()
    {

        static $cookie_uid = null;
        if (isset($cookie_uid) && $cookie_uid !== null) {
            return $cookie_uid;
        }
        $cookie = cookie('UCTOO_LOGGED_USER');
        $cookie = explode(".", $this->jiemi($cookie));
        $map['uid'] = $cookie[1];
        $user = D('user_token')->where($map)->find();
        $cookie_uid = ($cookie[0] != $this->change()) || ($cookie[2] != $user['token']) ? false : $cookie[1];
        $cookie_uid = $user['time'] - time() >= 3600 * 24 * 7 ? false : $cookie_uid;
        return $cookie_uid;
    }


    /**
     * 加密函数
     * @param string $txt 需加密的字符串
     * @param string $key 加密密钥，默认读取SECURE_CODE配置
     * @return string 加密后的字符串
     */
    private function jiami($txt, $key = null)
    {
        empty($key) && $key = $this->change();

        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-=_";
        $nh = rand(0, 64);
        $ch = $chars[$nh];
        $mdKey = md5($key . $ch);
        $mdKey = substr($mdKey, $nh % 8, $nh % 8 + 7);
        $txt = base64_encode($txt);
        $tmp = '';
        $i = 0;
        $j = 0;
        $k = 0;
        for ($i = 0; $i < strlen($txt); $i++) {
            $k = $k == strlen($mdKey) ? 0 : $k;
            $j = ($nh + strpos($chars, $txt [$i]) + ord($mdKey[$k++])) % 64;
            $tmp .= $chars[$j];
        }
        return $ch . $tmp;
    }

    /**
     * 解密函数
     * @param string $txt 待解密的字符串
     * @param string $key 解密密钥，默认读取SECURE_CODE配置
     * @return string 解密后的字符串
     */
    private function jiemi($txt, $key = null)
    {
        empty($key) && $key = $this->change();

        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-=_";
        $ch = $txt[0];
        $nh = strpos($chars, $ch);
        $mdKey = md5($key . $ch);
        $mdKey = substr($mdKey, $nh % 8, $nh % 8 + 7);
        $txt = substr($txt, 1);
        $tmp = '';
        $i = 0;
        $j = 0;
        $k = 0;
        for ($i = 0; $i < strlen($txt); $i++) {
            $k = $k == strlen($mdKey) ? 0 : $k;
            $j = strpos($chars, $txt[$i]) - $nh - ord($mdKey[$k++]);
            while ($j < 0) {
                $j += 64;
            }
            $tmp .= $chars[$j];
        }

        return base64_decode($tmp);
    }

    private function change()
    {
        preg_match_all('/\w/', C('DATA_AUTH_KEY'), $sss);
        $str1 = '';
        foreach ($sss[0] as $v) {
            $str1 .= $v;
        }
        return $str1;
    }

    /**
     * 同步登陆时添加用户信息
     * @param $uid
     * @param $info
     * @return mixed
     * autor:xjw129xjt
     */
    public function addSyncData($uid, $info)
    {

        $data1['nickname'] = mb_substr($info['nickname'], 0, 11, 'utf-8');
        //去除特殊字符。
        $data1['nickname'] = preg_replace('/[^A-Za-z0-9_\x80-\xff\s\']/', '', $data1['nickname']);
        empty($data1['nickname']) && $data1['nickname'] = $this->rand_nickname();
        $data1['nickname'] .= '_' . $this->rand_nickname();
        $data1['sex'] = $info['sex'];
        $data = $this->create($data1);
        $data['uid'] = $uid;
        $res = $this->add($data);
        return $res;
    }

    public function rand_nickname()
    {
        $nickname = $this->create_rand(4);
        if ($this->where(array('nickname' => $nickname))->select()) {
            $this->rand_nickname();
        } else {
            return $nickname;
        }
    }

    function create_rand($length = 8)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $password;
    }

    /**
     * 设置角色用户默认基本信息
     * @param $role_id
     * @param $uid
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function initUserRoleInfo($role_id,$uid){
        $roleModel=D('Role');
        $roleConfigModel=D('RoleConfig');
        $authGroupAccessModel=D('AuthGroupAccess');
        D('UserRole')->where(array('role_id'=>$role_id,'uid'=>$uid))->setField('init',1);
        //默认用户组设置
        $role=$roleModel->where(array('id'=>$role_id))->find();
        if($role['user_groups']!=''){
            $role=explode(',',$role['user_groups']);

            //查询已拥有用户组
            $have_user_group_ids=$authGroupAccessModel->where(array('uid'=>$uid))->select();
            $have_user_group_ids=array_column($have_user_group_ids,'group_id');
            //查询已拥有用户组 end

            $authGroupAccess['uid']=$uid;
            $authGroupAccess_list=array();
            foreach($role as $val){
                if($val!=''&&!in_array($val,$have_user_group_ids)){//去除已拥有用户组
                    $authGroupAccess['group_id']=$val;
                    $authGroupAccess_list[]=$authGroupAccess;
                }
            }
            unset($val);
            $authGroupAccessModel->addAll($authGroupAccess_list);
        }
        //默认用户组设置 end

        $map['role_id']=$role_id;
        $map['name']=array('in',array('score','rank'));
        $config=$roleConfigModel->where($map)->select();
        $config=array_combine(array_column($config,'name'),$config);



        //默认积分设置
        if(isset($config['score']['value'])){
            $value=json_decode($config['score']['value'],true);
            $data=$this->getUserScore($role_id,$uid,$value);
            $user=$this->where(array('uid'=>$uid))->find();
            foreach($data as $key=>$val){
                if($val>0){
                    if(isset($user[$key])){
                        $this->where(array('uid'=>$uid))->setInc($key,$val);
                    }else{
                        $this->where(array('uid'=>$uid))->setField($key,$val);
                    }
                }
            }
            unset($val);
        }
        //默认积分设置 end

        //默认头衔设置
        if(isset($config['rank']['value'])&&$config['rank']['value']!=''){
            $ranks=explode(',',$config['rank']['value']);
            if(count($ranks)){
                //查询已拥有头衔
                $rankUserModel=D('RankUser');
                $have_rank_ids=$rankUserModel->where(array('uid'=>$uid))->select();
                $have_rank_ids=array_column($have_rank_ids,'rank_id');
                //查询已拥有头衔 end

                $reason=json_decode($config['rank']['data'],true);
                $rank_user['uid']=$uid;
                $rank_user['create_time']=time();
                $rank_user['status']=1;
                $rank_user['is_show']=1;
                $rank_user['reason']=$reason['reason'];
                $rank_user_list=array();
                foreach($ranks as $val){
                    if($val!=''&&!in_array($val,$have_rank_ids)){//去除已拥有头衔
                        $rank_user['rank_id']=$val;
                        $rank_user_list[]=$rank_user;
                    }
                }
                unset($val);
                $rankUserModel->addAll($rank_user_list);
            }
        }
        //默认头衔设置 end
    }

    //默认显示哪一个角色的个人主页设置
    public function initDefaultShowRole($role_id,$uid)
    {
        $userRoleModel=D('UserRole');

        $roles=$userRoleModel->where(array('uid'=>$uid,'status'=>1,'role_id'=>array('neq',$role_id)))->select();
        if(!count($roles)){
            $data['show_role']=$role_id;
            //执行member表默认值设置
            $this->where(array('uid'=>$uid))->save($data);
        }
    }
    //默认显示哪一个角色的个人主页设置 end

    /**
     * 获取用户初始化后积分值
     * @param $role_id 当前初始化角色
     * @param $uid 初始化用户
     * @param $value 初始化角色积分配置值
     * @return array
     * @author 郑钟良<zzl@ourstu.com>
     */
    private function getUserScore($role_id,$uid,$value)
    {
        $roleConfigModel=D('RoleConfig');
        $userRoleModel=D('UserRole');

        $map['role_id']=array('neq',$role_id);
        $map['uid']=$uid;
        $map['init']=1;
        $role_list=$userRoleModel->where($map)->select();
        $role_ids=array_column($role_list,'role_id');
        $map_config['role_id']=array('in',$role_ids);
        $map_config['name']='score';
        $config_list=$roleConfigModel->where($map_config)->field('value')->select();
        $change=array();
        foreach($config_list as &$val){
            $val=json_decode($val['value'],true);
        }
        unset($val);
        unset($config_list[0]['score1']);
        foreach($value as $key=>$val){
            $config_list=list_sort_by($config_list,$key,'desc');
            if($val>$config_list[0][$key]){
                $change[$key]=$val-$config_list[0][$key];
            }else{
                $change[$key]=0;
            }
        }
        return $change;
    }





    public function getErrorMessage($error_code = null)
    {

        $error = $error_code == null ? $this->error : $error_code;
        switch ($error) {
            case -1:
                $error = '用户名长度必须在16个字符以内！';
                break;
            case -2:
                $error = '用户名被禁止注册！';
                break;
            case -3:
                $error = '用户名被占用！';
                break;
            case -4:
                $error = '密码长度必须在6-30个字符之间！';
                break;
            case -41:
                $error = '用户旧密码不正确';
                break;
            case -5:
                $error = '邮箱格式不正确！';
                break;
            case -6:
                $error = '邮箱长度必须在1-32个字符之间！';
                break;
            case -7:
                $error = '邮箱被禁止注册！';
                break;
            case -8:
                $error = '邮箱被占用！';
                break;
            case -9:
                $error = '手机格式不正确！';
                break;
            case -10:
                $error = '手机被禁止注册！';
                break;
            case -11:
                $error = '手机号被占用！';
                break;
            case -12:
                $error = '用户名必须以中文或字母开始，只能包含拼音数字，字母，汉字！';
                break;
            case -31:
                $error = '昵称禁止注册';
                break;
            case -33:
                $error = '昵称长度不合法';
                break;
            case -32:
                $error = '昵称不合法';
                break;
            case -30:
                $error = '昵称已被占用';
                break;

            default:
                $error = '未知错误';
        }
        return $error;
    }
}
