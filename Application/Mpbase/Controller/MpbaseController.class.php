<?php
// +----------------------------------------------------------------------
// | UCToo [ Universal Convergence Technology ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015 http://www.uctoo.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: patrick <contact@uctoo.com> <http://www.uctoo.com>
// +----------------------------------------------------------------------


namespace Admin\Controller;

use Admin\Builder\AdminConfigBuilder;
use Admin\Builder\AdminListBuilder;
use Admin\Builder\AdminTreeListBuilder;


class MpbaseController extends AdminController
{
    public function config()
    {
        $admin_config = new AdminConfigBuilder();
        $data = $admin_config->handleConfig();

        $admin_config->title('管理基本设置')
            ->keyBool('NEED_VERIFY', '公众号是否需要审核','默认无需审核')
            ->buttonSubmit('', '保存')->data($data);
        $admin_config->display();
    }

    public function autoreply($page=1,$r=20)
    {
        //读取数据
        $model = D('Mpbase/Autoreply');
        $map['status'] = array('EGT', 0);
        $map['uid'] = UID;
        $map['mp_id'] = get_mpid();

        $list = $model->where($map)->page($page, $r)->order('id asc')->select();
        int_to_string($list);  //设置status
        foreach ($list as &$val) {
            $val['type'] = $model->getArType($val['type']);
        }
        $totalCount = $model->count();
        //显示页面
        $builder = new AdminListBuilder();
        $builder
            ->title('自动回复设置')
            ->buttonNew(U('Mpbase/aredit'))
            ->setStatusUrl(U('setStatus'))->buttonEnable()->buttonDisable()->button('删除',array('class' => 'btn ajax-post tox-confirm', 'data-confirm' => '您确实要删除自动回复吗？', 'url' => U('ardel'), 'target-form' => 'ids'))
            ->keyId()->keyText('type','自动回复类型')->keyText('name', '名称')->keyStatus()->keyDoActionEdit('aredit?id=###')->keyDoAction('ardel?ids=###', '删除')
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }

    public function ardel($ids = null){
        if (!$ids) {
            $this->error('请选择自动回复');
        }
        $model =  D('Mpbase/Autoreply');
        $res = $model->delete($ids);
        if ($res) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    public function aredit($id = null)
    {
        $model = D('Mpbase/Autoreply');
        if (IS_POST) {   //提交表单
            $data['uid'] = I('post.uid', '', 'op_t');
            $data['mp_id'] = I('post.mp_id', '', 'intval');
            $data['type'] = I('post.type', '', 'intval');
            $data['keyword_id'] = I('post.keyword_id', '', 'intval');
            $data['name'] = I('post.name', '', 'op_t');
            $data['content'] = I('post.content', '');
            $data['status'] = I('post.status', 1, 'intval');

            if ($id != 0) {
                $data['id'] = $id;
                $res = $model->editAr($data);
            } else {
                $res = $model->addAr($data);
            }
            $this->success(($id == 0 ? '添加' : '编辑') . '成功', $id == 0 ? U('', array('id' => $res)) : '');

        }else{   //显示表单
            //读取公众号信息
            if ($id != 0) {  //编辑
                $ar = $model->where(array('id' => $id))->find();

            } else {  //新增
                $ar = array('uid' => UID,'status' => 1,'mp_id' => get_mpid() );
            }

            //显示页面
            $builder = new AdminConfigBuilder();

                $builder->title($id != 0 ? '编辑自动回复' : '添加自动回复')
                    ->keyId()->keyHidden('uid', '', '')->keyHidden('mp_id', '', '')
                    ->keySelect('type', '类型', '请选择自动回复类型', $model->getArType(null))
                    ->keyText('keyword_id', '关键词ID', '关键词ID')
                    ->keyText('name', '名称', '名称')
                    ->keyTextArea('content', '自定义回复内容', '自定义回复内容')
                    ->keyStatus()
                    ->data($ar)
                    ->buttonSubmit(U('aredit?id=###'))->buttonBack()
                    ->display();
        }

    }

    public function index($page=1,$r=20)
    {
        //读取数据
        $model = D('Mpbase/MemberPublic');
        $map['status'] = array('EGT', 0);
        if ( !is_administrator() ) {  //管理员可以管理全部公众号
            $map['uid'] = UID;
        }
        $list = $model->where($map)->page($page, $r)->order('id asc')->select();
        int_to_string($list);  //设置status
        foreach ($list as &$val) {
            $val['u_name'] = D('Common/Member')->where('uid=' . $val['uid'])->getField('nickname');
            $val['type'] = $model->getMpType($val['type']);
        }
        $totalCount = $model->count();
        //显示页面
        $builder = new AdminListBuilder();
        $builder
            ->title('公众号列表')
            ->buttonNew(U('Mpbase/edit'))
            ->setStatusUrl(U('setStatus'))->buttonEnable()->buttonDisable()->button('删除',array('class' => 'btn ajax-post tox-confirm', 'data-confirm' => '您确实要删除公众号吗？（删除后对应的公众号配置将会清空，不可恢复，请谨慎删除！）', 'url' => U('del'), 'target-form' => 'ids'))
            ->keyId()->keyUid()->keyText('public_name', '名称')->keyText('wechat', '微信号')->keyText('public_id', '原始ID')->keyText('type','公众号类型')->keyStatus()->keyDoActionEdit('edit?id=###')->keyDoAction('del?ids=###', '删除')->keyDoAction('change?id=###', '切换为当前公众号')
            ->keyDoAction('Home/Index/help?id=###', '接口配置')
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();

    }

    /**
     * 删除公众号
     * @param null $id
     * @author patrick<contact@uctoo.com>
     */
    public function del($ids = null){
        if (!$ids) {
            $this->error('请选择公众号');
        }
        $model =  D('Mpbase/MemberPublic');
        $res = $model->delete($ids);
        if ($res) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    public function edit($id = null)
    {
        $model = D('Mpbase/MemberPublic');
        if (IS_POST) {   //提交表单
            $data['uid'] = I('post.uid', '', 'op_t');
            $data['public_name'] = I('post.public_name', '', 'op_t');
            $data['wechat'] = I('post.wechat', 1, 'op_t');
            $data['public_id'] = I('post.public_id', 1, 'op_t');
            $data['type'] = I('post.type', '', 'intval');
            $data['appid'] = I('post.appid', '', 'op_t');
            $data['secret'] = I('post.secret', '', 'op_t');
            $data['encodingaeskey'] = I('post.encodingaeskey', '', 'op_t');
            $data['mchid'] = I('post.mchid', '', 'op_t');
            $data['mchkey'] = I('post.mchkey', '', 'op_t');
            $data['notify_url'] = I('post.notify_url', '', 'op_t');
            $data['status'] = I('post.status', 1, 'intval');

            if ($id != 0) {
                $data['id'] = $id;
                $res = $model->editMp($data);
            } else {
                $res = $model->addMp($data);
            }
            $this->success(($id == 0 ? '添加' : '编辑') . '成功', $id == 0 ? U('', array('id' => $res)) : '');

        }else{   //显示表单
            //读取公众号信息
            if ($id != 0) {  //编辑
                $mp = $model->where(array('id' => $id))->find();
                if($mp['uid']){  //公众号有归属帐号
                }else{
                    !is_administrator()?  :$mp['uid'] = UID  ;
                } ;
            } else {  //新增
                $mp = array('uid' => UID,'status' => 1);
            }

            //显示页面
            $builder = new AdminConfigBuilder();
            if(is_administrator()){   //管理员可以设定公众号uid
                $builder->title($id != 0 ? '编辑公众号' : '添加公众号')
                    ->keyId()->keyUid('uid', '用户', '公众号管理员')->keyText('public_name', '名称', '公众号名称')->keyText('wechat', '微信号', '微信号')->keyText('public_id', '原始ID', '公众号原始ID')
                    ->keySelect('type', '类型', '请选择公众号类型', $model->getMpType(null))->keyText('appid', 'AppID', '应用ID')->keyText('secret', 'AppSecret', '应用密钥，需公众号管理员才能在mp.weixin.qq.com后台完整显示')
                    ->keyText('encodingaeskey', '消息加解密密钥', '安全模式下必填') ->keyText('mchid', '微信支付商户号', '微信支付必须配置') ->keyText('mchkey', '微信支付商户支付密钥', '微信支付必须配置')
                    ->keyText('notify_url', '接收微信支付异步通知回调地址', '微信支付必须配置，带http://的完整URL')
                    ->keyStatus()
                    ->data($mp)
                    ->buttonSubmit(U('edit'))->buttonBack()
                    ->display();
            }else{    //非管理员以登录uid作为公众号uid
                $builder->title($id != 0 ? '编辑公众号' : '添加公众号')
                    ->keyId()->keyReadOnly('uid', '用户', '公众号管理员')->keyText('public_name', '名称', '公众号名称')->keyText('wechat', '微信号', '微信号')->keyText('public_id', '原始ID', '公众号原始ID')
                    ->keySelect('type', '类型', '请选择公众号类型', $model->getMpType(null))->keyText('appid', 'AppID', '应用ID')->keyText('secret', 'AppSecret', '应用密钥，需公众号管理员才能在mp.weixin.qq.com后台完整显示')
                    ->keyText('encodingaeskey', '消息加解密密钥', '安全模式下必填') ->keyText('mchid', '微信支付商户号', '微信支付必须配置') ->keyText('mchkey', '微信支付商户支付密钥', '微信支付必须配置')
                    ->keyText('notify_url', '接收微信支付异步通知回调地址', '微信支付必须配置，带http://的完整URL')
                    ->keyStatus()
                    ->data($mp)
                    ->buttonSubmit(U('edit'))->buttonBack()
                    ->display();
            }

        }

    }

    public function change() {
        $map ['id'] = I ( 'id', 0, 'intval' );
        $info = D ( 'Mpbase/MemberPublic' )->where ( $map )->find ();
        get_mpid($map ['id']);                                               //设置当前下上文mp_id

        unset ( $map );
        $map ['uid'] = UID;
        $res =  M ( 'Member' )->where ( $map )->setField ( 'token', $info['public_id'] );


            $user = session('user_auth');
            $user['token'] = $info['public_id'];
            $user['public_name'] = $info['public_name'];
            session('user_auth', $user);
            session('user_auth_sign', data_auth_sign($user));
            $this->success('切换公众号成功！');


        redirect ( U ( 'index' ) );
    }
}
