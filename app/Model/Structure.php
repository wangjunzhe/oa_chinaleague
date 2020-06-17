<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/30 0030
 * Time: 09:16
 */

namespace App\Model;

use PhalApi\Model\NotORMModel as NotORM;
use App\Model\Common as Common;
use App\Model\Group;
use App\Common\Admin as AdminCommon;
use App\Common\Customer as CustomerCommon;
use App\Common\Encryption;
use App\Common\Projectdata;
use App\Common\Customer;
class Structure extends NotORM
{
    protected $di;
    protected $iv;
    protected $prefix;
    protected $cache;
    protected $config;
    protected $pagesize;
    public function __construct()
    {
        $this->di = \PhalApi\DI()->notorm;
        $this->config = \PhalApi\DI()->config;
        $this->prefix = \PhalApi\DI()->config->get('common.PREFIX');
        $this->url_md = \PhalApi\DI()->config->get('common.key_url_md_5');
        $this->cache = \PhalApi\DI()->cache;
        $this->redis = \PhalApi\DI()->redis;
        // 加密向量
        $this->iv = \PhalApi\DI()->config->get('common.IV');
        // 页码设置
        $this->pagesize = \PhalApi\DI()->config->get('common.PAGESIZE');
    }
    //获取用户信息
    public function getUserInfo($id) {
        $data=$this->di->user->select('structure_id,group_id,mobile,post,realname,email,rztime,adress,worker,zztime,type,username')->where('id', $id)->fetchOne();
        $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>array());
        return $rs;
    }
    //新加角色
    public function insertstation($data){
        $c=$this->di->station->where(array("title"=>$data['title'],"project_num"=>$data['project_num']))->fetchOne();
        if($c){
            return '';
        }else{
            return $this->di->station->insert($data);
        }


    }
    //角色列表
    public function getStationList($project_id,$uid){
        $project_id = isset($project_id) && $project_id != 0 ? $project_id : 0;
        $uid = isset($uid) ? $uid : 1;
        if ($uid != 1) {
            $project_where['project_num'] = $project_id;
            $list=$this->di->station->where($project_where)->fetchAll();
        } else {
            $join_sql = $project_id != 0 ? "LEFT JOIN ".$this->prefix."project_side p ON s.project_num = p.id" : "";
            $join_field = $project_id != 0 ? ",p.title as project_name" : "";
            $sql = "SELECT s.id,s.title,s.creat_time".$join_field." FROM ".$this->prefix."station s ".$join_sql;

            $list = $this->di->station->queryAll($sql, []);
        }
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $list[$key]["member_num"] = $this->di->user->where(array("post"=>$value['id'],"type_id"=>$project_id))->count("id");
            }
        }
        return $list;
    }
    //删除角色
    public function DelStation($id){
        $list=$this->di->station->where('id',$id)->fetchAll();
        if($list){
            $data=$this->di->station->where('id',$id)->delete();
            \App\setlog( \PhalApi\DI()->session->uid,3,'删除岗位ID:'.$id,'成功','删除岗位','删除岗位');
            return $data;
        }else{
            \App\setlog( \PhalApi\DI()->session->uid,3,'删除岗位ID:'.$id,'失败','删除岗位','删除岗位');
            return '';
        }


    }
    //编辑角色
    public function editStation($id,$title){
        $list=$this->di->station->where(array("id"=>$id))->fetchAll();
        if($list){
            $data=$this->di->station->where(array("id"=>$id))->update(array('title'=>$title));
            \App\setlog( \PhalApi\DI()->session->uid,2,'编辑岗位ID:'.$id,'成功','编辑岗位名称改成'.$title,'编辑岗位');
            return $data;
        }else{
            \App\setlog( \PhalApi\DI()->session->uid,2,'编辑岗位ID:'.$id,'失败','编辑岗位名称改成'.$title,'编辑岗位');
            return '';
        }


    }
    //获取指定人员列表
    public function getperson($type,$id,$pid){
        $list=$this->di->user->select('realname,worker,status,structure_id,post,id')->fetchAll();
        $data=[];
        $i=0;
        foreach($list as $k=>$v){
            if($type=='0'){
                $list2=json_decode($v['structure_id'],true);
                if(count($list2)==2){
                    if($pid==$list2[0] && $id==$list2[1]){
                        $data['data_list'][]=array('pid'=>$list2[0],'pname'=>\App\getgroup($list2[0]),'id'=>$list2[1],'realname'=>$v['realname'],'worker'=>$v['worker'],'status'=>$v['status']);
                    }
                }else if(count($list2)==1){
                    if($pid==0 && $id==$list2[0]){
                        $data['data_list'][]=array('pid'=>0,'pname'=>\App\getgroup($list2[0]),'id'=>$list2[0],'realname'=>$v['realname'],'worker'=>$v['worker'],'status'=>$v['status']);
                    }
                }else if(count($list2)==3){
                    if($pid==$list2[1] && $id==$list2[2]){

                        $data['data_list'][]=array('pid'=>$list2[1],'pname'=>\App\getgroup($list2[1]),'id'=>$list2[2],'realname'=>$v['realname'],'worker'=>$v['worker'],'status'=>$v['status']);
                    }
                }
            }
        }
        if($type =='1'){
            $list=$this->di->user->select('realname,worker,status,structure_id,post,id')->where('post',$id)->fetchAll();
            foreach ($list as $k=>$v){

                $data['data_list'][]=array(

                    'realname'=>$v['realname'],
                    'worker'=>$v['worker'],
                    'status'=>$v['status'],
                    'post'=>$v['post'],

                );

            }

        }
        $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>array());
        return $rs;

    }
    //置顶
    public  function dotop($id,$uid,$is_top){
        $id=isset($id) && !empty($id) ? $id : "";
        $uid=  isset($uid) && !empty($uid) ? $uid : "";
        if($id==''||$uid==''){
            $re=array('code'=>0,'msg'=>'000003','data'=>[],'info'=>[]);
            return $re;
        }
        $cha=$this->di->customer->where('id',$id)->and('creatid',$uid)->fetchOne();

        if($cha){
            $this->di->customer->where('id',$id)->update(['is_top'=>$is_top]);
            \App\setlog( $uid,6,'置顶客户'.$id,'成功','置顶客户'.$id.'状态:'.$is_top,'置顶客户');
        }else{
            $bid=$this->di->share_join->where('cid',$id)->and('beshare_uid',$uid)->and('status',1)->fetchOne();
            if($bid['creat_id']==$uid){
                //分配或领取
                $this->di->customer->where('cid',$id)->update(['is_top'=>$is_top]);
                \App\setlog($uid,6,'置顶客户'.$id,'成功','置顶客户'.$id.'状态:'.$is_top,'置顶客户');
            }else{
                $this->di->share_customer->where('id',$bid['bid'])->update(['is_top'=>$is_top]);
                \App\setlog($uid,6,'置顶客户'.$id,'成功','置顶客户'.$id.'状态:'.$is_top,'置顶客户');
            }
        }

        $bid=  isset($bid) && !empty($bid) ? $bid : "";
        $this->redis->del('customer_info_'.$uid.'_'.$id.'_'.$bid,'customer');
        $re=array('code'=>1,'msg'=>'999998','data'=>[],'info'=>[]);
        return $re;

    }
    //用户列表
    public function Userlist($page, $perpage,$where){
        if($where){

                if($where=='冻结'){
                    $clist=$this->di->user->select('*')->where('status',0)->limit(($page - 1) * $perpage, $perpage)->fetchAll();
                    $class_data['num']=$this->di->user->select('*')->where('status',0)->count('id');
                }else if($where=='new_group'){
                    $clist=$this->di->user->select('*')->where('new_group',0)->and('status',0)->limit(($page - 1) * $perpage, $perpage)->fetchAll();
                    $class_data['num']=$this->di->user->select('*')->where('new_group',0)->and('status',0)->count('id');
                } else {
                    $clist=$this->di->user->select('*')->where('(username LIKE ? OR realname LIKE ? OR mobile LIKE ? OR adress LIKE ?)', '%'.$where.'%','%'.$where.'%','%'.$where.'%','%'.$where.'%')->and('is_delete',0)->limit(($page - 1) * $perpage, $perpage)->fetchAll();
                    $class_data['num']=$this->di->user->select('*')->where('(username LIKE ? OR realname LIKE ? OR mobile LIKE ?  OR adress LIKE ?)', '%'.$where.'%','%'.$where.'%','%'.$where.'%','%'.$where.'%')->and('is_delete',0)->count('id');
                }

//               $data=$this->di->user->select('*')->or('username LIKE ?','%'.$where.'%')->or('realname LIKE ?','%'.$where.'%')->or('mobile LIKE ?','%'.$where.'%')->or('num LIKE ?','%'.$where.'%')->or('adress LIKE ?','%'.$where.'%')->limit(($page - 1) * $perpage, $perpage)->fetchAll();

        }else{
            $clist=$this->di->user->where('is_delete',0)->limit(($page - 1) * $perpage, $perpage)->fetchAll();
            $class_data['num']=$this->di->user->where('is_delete',0)->count("id");
        }
        $class_data['class_list'] = $clist ? $clist : array();
        if ($class_data['num'] > 0) {
            $rs = array('code'=>1,'msg'=>'000000','data'=>$class_data,'info'=>$class_data);
        } else {
            $rs = array('code'=>0,'msg'=>'000002','data'=>array(),'info'=>array());
        }
        return $rs;
    }
    //删除用户
    public function delUser($id){
        if ($id == 1) {
            $rs = array('code' => 0, 'msg' => '超级管理员无法删除', 'data' => array(), 'info' => array());
            return $rs;
        }
        $msg = $this->di->customer->where('creatid', $id)->or("FIND_IN_SET(charge_person,'{$id}')")->count('id');
        if(!empty($msg)){
            $rs = array('code' => 0, 'msg' => '删除失败,该用户名下存在' . $msg . '个客户!需处理完才可删除!', 'data' => array(), 'info' => array());
            return $rs;
        }
        $del = $this->di->user->where('id', $id)->delete();
        $this->redis->flushAll();
        $rs= array('code'=>1,'msg'=>'修改成功','data'=>array(),'info'=>array());
        \App\setlog(\PhalApi\DI()->session->uid,3,'删除用户ID:'.$id,'成功','删除系统用户ID:'.$id,'删除用户');
        return $rs;
    }
    //新添加用户
    public function AddUserData($uid,$newdata){
        $uid_type=$this->di->structure->where('id',$uid)->fetchOne();
        $newdata['username']=trim($newdata['username']," ");
        //判断录入人身份
//        1.判断用户名是否重复
        $token=$this->redis->get_time(md5($uid),'token');
        if(empty($token) || $newdata['token'] != $token){
            $rs = array('code'=>0,'msg'=>'表单令牌已失效,请返回重试!','data'=>array(),'info'=>array());
            return $rs;
        }
        $status = $this->di->user->where('username',$newdata['username'])->fetchOne();
        if(!empty($status)){
            $rs = array('code'=>0,'msg'=>'您的用户名重复了,请更改后重试!','data'=>array(),'info'=>array());
            return $rs;
        }
        switch($newdata['type']){
            case 0:
                if(empty($newdata['structure_id']) && empty($newdata['group_id']) && empty( $newdata['post'])){
                    $rs = array('code'=>0,'msg'=>'部门/岗位/角色不能为空!','data'=>array(),'info'=>array());
                    return $rs;
                }
                $this->redis->del(md5($uid),'token');
                unset($newdata['token']);
                $res=$this->di->user->insert($newdata);
                \App\setlog($uid,1,'创建用户','成功','创建系统用户'.$newdata['username'],'创建用户');
                $rs = array('code'=>1,'msg'=>'创建成功','data'=>array(),'info'=>array());
                break;
            case 1:
                $type_id=$this->di->project_side->where('id',$newdata['type_id'])->fetchOne();
                if(empty($type_id)){
                    if($type_id['manage']!=0){

                    }
                }
                break;
            case 2:
                break;
            default:
                break;
        }

        return $rs;

    }
    //添加用户
    public function AddUser($uuid,$id,$data){
        if($data['email']!=''){
            $status=$this->di->user->select('id,mobile,username,email,is_delete')->or('mobile',$data['mobile'])->or('username',$data['username'])->or('email',$data['email'])->fetchOne();

        }else{
            $status=$this->di->user->select('id,mobile,username,email,is_delete')->or('mobile',$data['mobile'])->or('username',$data['username'])->or('flower_name',$data['flower_name'])->fetchOne();

        }
        if($id!=''){
            $status=$this->di->user->select('id,mobile,username,email,is_delete')->or('mobile',$data['mobile'])->or('username',$data['username'])->fetchAll();

            if(count($status)>1 ){
                $rs = array('code'=>0,'msg'=>'您的用户名或手机号重复了','data'=>array(),'info'=>array());
                return $rs;
            }
            if ($id == 1 && $data['status'] == 0) {
                $rs = array('code' => 0, 'msg' => '无法冻结管理员!', 'data' => array(), 'info' => array());
                return $rs;
            }
            $data['new_group']=end(json_decode($data['structure_id']));
            $res= $this->di->user->where('id',$id)->update($data);
            if($res){
                \App\setlog(\PhalApi\DI()->session->uid,2,'修改用户ID:'.$id,'成功','修改系统用户ID:'.$id,'修改系统用户成功');
                $rs = array('code'=>1,'msg'=>'修改成功','data'=>array(),'info'=>array());
            }else{
                $rs = array('code'=>0,'msg'=>'修改失败','data'=>array(),'info'=>array());
            }
            return $rs;
        }else{
            if($status!='' && $status['is_delete']==0){
                if($status['mobile']==$data['mobile'] && $data['mobile']!=''){
                    $rs = array('code'=>0,'msg'=>'您的手机号码重复了','data'=>array(),'info'=>array());
                    return $rs;
                }else if($status['username']==$data['username']){
                    $rs = array('code'=>0,'msg'=>'您的登录账号名称重复了','data'=>array(),'info'=>array());
                    return $rs;
                }else if($status['email']==$data['email'] && $data['email']!=''){
                    $rs = array('code'=>0,'msg'=>'您的邮箱重复了','data'=>array(),'info'=>array());
                    return $rs;
                }else if($status['flower_name']==$data['flower_name'] && $data['flower_name']!=''){
                    $rs = array('code'=>0,'msg'=>'您的花名重复了','data'=>array(),'info'=>array());
                    return $rs;
                }

            }
            if(isset($data['type']) && isset($data['type_id'])){
                if($data['type']==1 || $data['type']==2){

                    $list=$this->di->user->where('type',$data['type'])->and('type_id',$data['type_id'])->and('is_delete',0)->fetchOne();
                    if($list!=''){
                        $rs = array('code'=>0,'msg'=>'您绑定的项目方/代理商已被绑定','data'=>array(),'info'=>array());
                        return $rs;
                    }
                }

            }

            $res=$this->di->user->insert($data);

            if($res){
                $stru_id=end(json_decode($data['structure_id']));
                $this->redis->flushDB('list');
                $this->redis->flushDB('user');
                \App\setlog(\PhalApi\DI()->session->uid,1,'创建用户','成功','创建系统用户'.$data['username'],'创建用户');
                $rs = array('code'=>1,'msg'=>'创建成功','data'=>array(),'info'=>array());

            }else{
                $rs = array('code'=>0,'msg'=>'创建失败','data'=>array(),'info'=>array());

            }
            return $rs;
        }

//        $type=$data['type'];
//        $where=isset($id)?' AND id <> '.$id:'';
//        $status = $this->di->user->select('id,mobile,username,email,is_delete')->where('is_delete = 0'.$where.' AND is_delete = 0 AND type='.$type)->fetchAll();
//        if(!empty($status)){
//            $rs = array('code'=>0,'msg'=>'您的用户名或手机号重复了','data'=>array(),'info'=>array());
//            return $rs;
//        }
//        isset(\PhalApi\DI()->session->uid) && !empty(\PhalApi\DI()->session->uid) && ($uid=\PhalApi\DI()->session->uid) || ($uid=$uuid);
//        $user_info=$this->di->user->where('id',$id)->fetchOne();
//        $project_com=new Projectdata();
//        if(!empty($where)){
//            if($type==1){
//                //项目方主账号 项目方子账号
//                $new_data=$project_com->EditProjectvip($uid,$id,$data,$user_info);
//                return $new_data;
//            }else{
//                $res= $this->di->user->where('id',$id)->update($data);
//            }
//        }else{
//
//        }
//
//        if($res){
//            \App\setlog(\PhalApi\DI()->session->uid,2,'修改用户ID:'.$id,'成功','修改系统用户ID:'.$id,'修改系统用户成功');
//            $rs = array('code'=>1,'msg'=>'修改成功','data'=>array(),'info'=>array());
//        }else{
//            $rs = array('code'=>0,'msg'=>'修改失败','data'=>array(),'info'=>array());
//        }
//
//
//
//        return $rs;
//            if(isset($data['type']) && isset($data['type_id'])){
//                if($data['type']==1 || $data['type']==2){
//                    $list=$this->di->user->where('type',$data['type'])->and('type_id',$data['type_id'])->and('is_delete',0)->fetchOne();
//                    if($list!=''){
//                        $rs = array('code'=>0,'msg'=>'您绑定的项目方/代理商已被绑定','data'=>array(),'info'=>array());
//                        return $rs;
//                    }
//                }
//            }
//            $res=$this->di->user->insert($data);
//            if($res){
//                \App\setlog(\PhalApi\DI()->session->uid,1,'创建用户','成功','创建系统用户'.$data['username'],'创建用户');
//                $rs = array('code'=>1,'msg'=>'创建成功','data'=>array(),'info'=>array());
//            }else{
//                $rs = array('code'=>0,'msg'=>'创建失败','data'=>array(),'info'=>array());
//            }
//            return $rs;
//        }
    }
    //登录
//    public function login($user,$paw,$ip){
//        $cz=$this->di->user->select('username,password,salt,structure_id,post,sex,img,realname,id,status,group_id,is_delete,type,type_id,is_leader')->where('username',$user)->and('is_delete',0)->fetchOne();
//        if($cz){
//            $token=\App\user_md5($paw,$cz['salt'],$user);
//            if($token==$cz['password']){
//                if($cz['status']== 0){
//                    $re = array('code'=>0,'msg'=>'999986','data'=>array(),'info'=>array(),'token'=>[]);
//                }else{
//                    $cz['token']=$token;
//                    $cz['ip']=$ip;
//                    $pid=reset(json_decode($cz['structure_id']));
//                    $aid=$this->di->structure->where('id',$pid)->fetchOne('nrepeat');
//                    $cz['nrepeat']=$aid;
//                    unset($cz['password']);
//                    unset($cz['salt']);
//                    $group=new Group();
//                    $re=array('code'=>1,'msg'=>'999985','data'=>$cz,'info'=>$cz);
//                    $cz['roule']=$group->getListItems('','',$cz['group_id'],$cz['id']);
//                    \PhalApi\DI()->session->token=$token;
//                    \PhalApi\DI()->session->list=$cz;
//                    \PhalApi\DI()->session->uid=$cz['id'];
//                    $this->redis->set_time('user_info'.$cz['id'],$cz,28800,'user');
//                    \App\setlog($cz['id'],7,'登录成功','成功','登录成功'.$cz['username'],'登录成功');
//
//                }
//            }else{
//                $log=array('username'=>$user,'password'=>$paw,'ip'=>$ip,'create_time'=>time());
//                $time=time()-300;
//                $cishu=$this->di->userlogin->where(['ip'=>$ip,'username'=>$user])->where('create_time > ? AND create_time < ?',$time, time())->fetchAll();
//                if(count($cishu)>=10){
//                    $re=array('code'=>0,'msg'=>'999984','data'=>array(),'info'=>array(),'token'=>[]);
//                }else{
//                    $this->di->userlogin->insert($log);
//                    $re=array('code'=>0,'msg'=>'999983','data'=>array(),'info'=>array(),'token'=>[]);
//                }
//            }
//        }else{
//            $log=array('username'=>$user,'password'=>$paw,'ip'=>$ip,'create_time'=>time());
//            $this->di->userlogin->insert($log);
//            $re=array('code'=>0,'msg'=>'999983','data'=>array(),'info'=>array(),'token'=>[]);
//        }
//        return $re;
//    }
    //新登录
    public function login($user,$paw,$ip){
        $group=new Group();
        $cz=$this->di->user->where('username',$user)->and('is_delete',0)->fetchOne();
        if($cz){
            $token=\App\user_md5($paw,$cz['salt'],$user);
            if($token==$cz['password']){
                if($cz['status']== 0){
                    $re = array('code'=>0,'msg'=>'999986','data'=>array(),'info'=>array(),'token'=>[]);
                }else{

//                    $new_cz=$this->redis->get_forever($cz['id'],'userinfo');
                    $this->redis->set_forever($cz['id'],'','userinfo');
                    $pid=reset(json_decode($cz['structure_id']));
                    $stru_id=end(json_decode($cz['structure_id']));
//                    $aid=$this->di->structure->select('nrepeat,sea_type,is_sea,capacity,share')->where('id',$pid)->fetchOne();
                    $struid=$this->di->structure->select('nrepeat,sea_type,is_sea,capacity,share')->where('id',$stru_id)->fetchOne();
                    $cz['nrepeat']= $struid['nrepeat'];
                    $cz['sea_type']= $struid['sea_type'];
                    $cz['is_sea']= $struid['is_sea'];
                    $cz['capacity']= $struid['capacity'];
                    $cz['share']= $struid['share'];
                    $cz['token']=$token;
                    $cz['ip']=$ip;
                    $cz['roule']=$group->getListItems('','',$cz['group_id'],$cz['id']);
                    \PhalApi\DI()->session->token=$token;
                    \PhalApi\DI()->session->list=$cz;
                    \PhalApi\DI()->session->uid=$cz['id'];
                    unset($cz['password']);
                    unset($cz['salt']);
                    $this->redis->set_forever($cz['id'],$cz,'userinfo');
                    \App\setlog($cz['id'],7,'登录成功','成功','登录成功'.$cz['username'],'登录成功');
                    $re = array('code'=>1,'msg'=>'登录成功','data'=>$cz,'info'=>$cz,'token'=>[]);

                }
            }else{
                $log=array('username'=>$user,'password'=>$paw,'ip'=>$ip,'create_time'=>time());
                $time=time()-300;
                $cishu=$this->di->userlogin->where(['ip'=>$ip,'username'=>$user])->where('create_time > ? AND create_time < ?',$time, time())->fetchAll();
                if(count($cishu)>=5){
                    $re=array('code'=>0,'msg'=>'999984','data'=>array(),'info'=>array(),'token'=>[]);
                }else{
                    $this->di->userlogin->insert($log);
                    $re=array('code'=>0,'msg'=>'999983','data'=>array(),'info'=>array(),'token'=>[]);
                }
            }
        }else{
            $log=array('username'=>$user,'password'=>$paw,'ip'=>$ip,'create_time'=>time());
            $this->di->userlogin->insert($log);
            $re=array('code'=>0,'msg'=>'999983','data'=>array(),'info'=>array(),'token'=>[]);
        }
        return $re;
    }
    //修改密码
    public function ChangePassword($uid,$old,$new){
        $ming_pass=$new;
        $user_info=$this->di->user->select('salt,password,username')->where('id',$uid)->fetchOne();
        $token=\App\user_md5($old,$user_info['salt'],$user_info['username']);
        $new_token=\App\user_md5($new,$user_info['salt'],$user_info['username']);
        if($user_info['password']!=$token){
            $re=array('code'=>0,'msg'=>'原密码错误','data'=>array(),'info'=>array());
            return $re;
        }else{
            $list=$this->di->user->where('id',$uid)->update(['password'=>$new_token]);
            if($list){
                \App\setlog($uid,2,'修改密码','成功','修改密码成功!账号:'.$user_info['username'].'新密码:'.$ming_pass,'修改密码成功');
                $re=array('code'=>1,'msg'=>'密码修改成功','data'=>array(),'info'=>array());
                return $re;
            }
        }
    }
    //获取所有的花名
    public function GetAllFolwer($uid){
        $user_folwer=$this->di->user->select('id,flower_name')->where('is_delete',0)->and('status',1)->and('flower_name <> ?','')->fetchAll();
        $agent_folwer=$this->di->agent->select('id,flower_name')->where('status',1)->fetchAll();
        $data['user_folwer']=$user_folwer;
        $data['agent_flower']=$agent_folwer;
        $re=array('code'=>1,'msg'=>'查询成功','data'=>$data,'info'=>$data);
        return $re;
    }
    //添加客户
    public function Addcus($uid,$new,$data,$token){
        $cname = isset($new['cname']) && !empty($new['cname']) ? $new['cname'] : "";
        $cphone= isset($data['cphone']) && !empty($data['cphone']) ? trim($data['cphone']," ") : "";
        $ittnzy= isset($new['ittnzy']) && !empty($new['ittnzy']) ? $new['ittnzy'] : "";
        $ittnyx= isset($new['ittnyx']) && !empty($new['ittnyx']) ? $new['ittnyx'] : "";
        $ittnxm= isset($new['ittnxm']) && !empty($new['ittnxm']) ? $new['ittnxm'] : "";
        $ittngj= isset($new['ittngj']) && !empty($new['ittngj']) ? $new['ittngj'] : "";
        $budget= isset($new['budget']) && !empty($new['budget']) ? $new['budget'] : "";
        $graduate= isset($new['graduate']) && !empty($new['graduate']) ? $new['graduate'] : "";
        $graduatezy= isset($new['graduatezy']) && !empty($new['graduatezy']) ? $new['graduatezy'] : "";
        $tolink= isset($new['tolink']) && !empty($new['tolink']) ? $new['tolink'] : "";
        $attachment= isset($new['attachment']) && !empty($new['attachment']) ? $new['attachment'] : "";
        $note= isset($new['note']) && !empty($new['note']) ? $new['note'] : "";
        $creatid= isset($new['creatid']) && !empty($new['creatid']) ? $new['creatid'] : "";
        $field_list= isset($new['field_list']) && !empty($new['field_list']) ? $new['field_list'] : "";
        $next_follow= isset($new['next_follow']) && !empty($new['next_follow']) ? $new['next_follow'] : 0;
        //基础信息表
        $group= isset($data['group']) && !empty($data['group']) ? $data['group'] : "";
        $sex= isset($data['sex']) && !empty($data['sex']) ? $data['sex'] : "";
        $age= isset($data['age']) && !empty($data['age']) ? $data['age'] : "";
        $city= isset($data['city']) && !empty($data['city']) ? $data['city'] : "";
        $adress= isset($data['adress']) && !empty($data['adress']) ? $data['adress'] : "";
        $station= isset($data['station']) && !empty($data['station']) ? $data['station'] : "";
        $occupation= isset($data['occupation']) && !empty($data['occupation']) ? $data['occupation'] : "";
        $industry= isset($data['industry']) && !empty($data['industry']) ? $data['industry'] : "";
        $company= isset($data['company']) && !empty($data['company']) ? $data['company'] : "";
        $character= isset($data['character']) && !empty($data['character']) ? $data['character'] : "";
        $cphonetwo= isset($data['cphonetwo']) && !empty($data['cphonetwo']) ? trim($data['cphonetwo']," "):'';
        $cphonethree= isset($data['cphonethree']) && !empty($data['cphonethree']) ? trim($data['cphonethree']," ") :'';
        $telephone= isset($data['telephone']) && !empty($data['telephone']) ? trim($data['telephone']," ") :'';
        $formwhere= isset($data['formwhere']) && !empty($data['formwhere']) ? $data['formwhere'] : "";
        $formwhere2= isset($data['formwhere2']) && !empty($data['formwhere2']) ? $data['formwhere2'] : "";
        $formwhere3= isset($data['formwhere3']) && !empty($data['formwhere3']) ? $data['formwhere3'] : "";
        $wxnum= isset($data['wxnum']) && !empty($data['wxnum']) ? trim($data['wxnum']," ") : "";
        $cemail= isset($data['cemail']) && !empty($data['cemail']) ? $data['cemail'] : "";
        $qq= isset($data['qq']) && !empty($data['qq']) ? $data['qq'] : "";
        $invoice_company= isset($data['invoice_company']) && !empty($data['invoice_company']) ? $data['invoice_company'] : "";
        $taxpayer_num= isset($data['taxpayer_num']) && !empty($data['taxpayer_num']) ? $data['taxpayer_num'] : "";
        $bank= isset($data['bank']) && !empty($data['bank']) ? $data['bank'] : "";
        $open_bank= isset($data['open_bank']) && !empty($data['open_bank']) ? $data['open_bank'] : "";
        $bank_num= isset($data['bank_num']) && !empty($data['bank_num']) ? $data['bank_num'] : "";
        $bank_adress= isset($data['bank_adress']) && !empty($data['bank_adress']) ? $data['bank_adress'] : "";
        $legal_person= isset($data['legal_person']) && !empty($data['legal_person']) ? $data['legal_person'] : "";
        $business_license= isset($data['business_license']) && !empty($data['business_license']) ? $data['business_license'] : "";
        $agent_name= isset($data['agent_name']) && !empty($data['agent_name']) ? $data['agent_name'] : "";
        $agent_num= isset($data['agent_num']) && !empty($data['agent_num']) ? $data['agent_num'] : "";
        $agent_price= isset($data['agent_price']) && !empty($data['agent_price']) ? $data['agent_price'] : "0.00";
        $project_name= isset($data['project_name']) && !empty($data['project_name']) ? $data['project_name'] : "";
        $project_num= isset($data['project_num']) && !empty($data['project_num']) ? $data['project_num'] : "";
        $token2=$this->di->user_token->where('token',$token)->fetchOne('id');
        if (empty($cname)  || empty($creatid) ) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        if(empty($token2)){
            $rs = array('code'=>0,'msg'=>'表单录入超时,请返回重新提交!','data'=>array(),'info'=>array());
            return $rs;
        }
        $user_info2=$this->di->user->select('setlimit,getlimit')->where('id',$uid)->fetchOne();
        if($user_info2['setlimit']!=0 && $user_info2['setlimit']<=$user_info2['getlimit']){
            $rs = array('code' => 0, 'msg' => '私海客户数量超限,不能新增!', 'data' => array(), 'info' => array());
            return $rs;
        }
        $session_list=\PhalApi\DI()->session->list;
        $nrepeat=$session_list['nrepeat'];
        if($nrepeat==1 && $cphone==''){
            $rs = array('code'=>2,'msg'=>'手机号码不能为空!','data'=>[],'info'=>array());
            return $rs;
        }else if($nrepeat==2 && $wxnum==''){
            $rs = array('code'=>2,'msg'=>'微信号不能为空!','data'=>[],'info'=>array());
            return $rs;
        }
        $txarray=[$cphone,$cphonetwo,$cphonethree,$telephone,$wxnum];
        foreach ($txarray as $k=>$v){
            if(!empty($v)){
             if($nrepeat ==1){
                 $yz=$this->GetPhoneRepeat($uid,$v,'','');
             }else{
                 $yz=$this->GetPhoneRepeat($uid,'',$v,'');
             }
             if($yz['code']!=1){
//                 $rs = array('code'=>0,'msg'=>'存在撞单!','data'=>$yz['data'],'info'=>array());
                 return $yz;
             }else{
                 continue;
             }
            }else{
                continue;
            }
        }
        //判断是否撞单
        \PhalApi\DI()->notorm->beginTransaction('db_master');
        $coumterlist=array(
            'cname'=>$cname,
            'ittngj'=>$ittngj,
            'budget'=>$budget,
            'graduate'=>$graduate,
            'graduatezy'=>$graduatezy,
            'tolink'=>$tolink,
            'attachment'=>$attachment,
            'note'=>$note,
            'creatid'=>$creatid,
            'charge_person'=>$creatid.',',
            'follw_time'=>time(),
            'next_follow'=>$next_follow,
            'sea_type'=>$new['sea_type']
        );
        if($new['sea_type']==1){
            $coumterlist['follw_time']=0;
            //设置创建类型
            $creatPe=8;
            $creatPetext='公海新增';
        }else{
            $creatPe=0;
            $creatPetext='私海新增';
        }
        if (is_array($ittnzy) && $ittnzy!='') {
            foreach ($ittnzy as $k=>$v){
                if($v!=''){
                    $coumterlist['ittnzy'] .=$v.',' ;
                }
            }
        }
        if (is_array($ittnyx) && $ittnyx!='') {
                foreach ($ittnyx as $k=>$v){
                    if($v!=''){
                    $coumterlist['ittnyx'] .=$v.',' ;
                    }
                }
        }
        if (is_array($ittnxm) && $ittnxm!='') {
                foreach ($ittnxm as $k=>$v){
                    if($v!=''){
                    $coumterlist['ittnxm'] .=$v.',' ;
                    }
                }
        }
        if (is_array($field_list) && !empty($field_list)) {
            foreach ($field_list as $key => $value) {
                if (is_array($value)) {
                    $field_list[$key] = json_encode($value,JSON_UNESCAPED_UNICODE);
                }
            }
            $coumterlist_data = array_merge($coumterlist,$field_list);
        }
        $this->di->user_token->where('token',$token)->delete();
        $this->di->customer->insert($coumterlist_data);
        $cid=$this->di->customer->insert_id();
        if($agent_name=='Array'){
            $agent_name='';
            $agent_num='';
        }
        $coumterdata=array(
            'cid'=>$cid,
            'groupid'=>$group,
            'sex'=>$sex,
            'age'=>$age,
            'station'=>$station,
            'city'=>$city,
            'adress'=>$adress,
            'occupation'=>$occupation,
            'industry'=>$industry,
            'company'=>$company,
            'character'=>$character,
            'cphone'=>$cphone,
            'formwhere'=>$formwhere,
            'formwhere2'=>$formwhere2,
            'formwhere3'=>$formwhere3,
            'cphonetwo'=>$cphonetwo,
            'cphonethree'=>$cphonethree,
            'telephone'=>$telephone,
            'wxnum'=>$wxnum,
            'cemail'=>$cemail,
            'qq'=>$qq,
            'create_time'=>time(),
            'update_time'=>time(),
            'invoice_company'=>$invoice_company,
            'taxpayer_num'=> $taxpayer_num,
            'bank'=> $bank,
            'open_bank'=> $open_bank,
            'bank_num'=>$bank_num,
            'bank_adress'=>$bank_adress,
            'legal_person'=>$legal_person,
            'business_license'=>$business_license,
            'agent_name'=> $agent_name,
            'agent_num'=>$agent_num,
            'project_name'=>$project_name,
            'project_num'=> $project_num,
            'agent_price'=> $agent_price,

        );
        if($coumterdata['project_name']!=''){
            $coumterdata['project_name']=$project_name.',';
            if($coumterdata['project_num']==''){
                $coumterdata['project_name']='';
            }
        }

        $st=$this->di->customer_data->insert($coumterdata);
        $cid2=$this->di->customer_data->insert_id();
        if(empty($cid) || empty($cid2)){
            \PhalApi\DI()->notorm->rollback('db_master');
            $rs = array('code'=>0,'msg'=>'插入失败,已回滚','data'=>array(),'info'=>array());
        }else{
            \PhalApi\DI()->notorm->commit('db_master');
            if ($st) {
                $this->di->user->where('id',$uid)->updateCounter('getlimit', 1);
                \App\Cus_log($creatid,$cid,$creatPe,$creatPetext,'创建客户'.$cname);
                \App\setlog($creatid,1,'新增客户成功','成功','新增客户:'.$coumterlist['cname'],'新增成功');
                $this->redis = \PhalApi\DI()->redis;
                $this->redis->flushDB('project');
                $this->redis->set_forever($uid,'','userinfo');
                $this->redis->set_forever('member_number_limit_'.$uid,array(),'user');

                $rs = array('code'=>1,'msg'=>'999995','data'=>['cid'=>$cid],'info'=>array());
            } else {
                $rs = array('code'=>0,'msg'=>'000037','data'=>array(),'info'=>array());
            }
        }

        return $rs;

    }
    //编辑客户
    public function Editcus($id,$uid,$bid,$new,$data){
        $id=isset($id) && !empty($id) ? $id : "";
        $uid=  isset($uid) && !empty($uid) ? $uid : "";
        $cname = isset($new['cname']) && !empty($new['cname']) ? $new['cname'] : "";
        $ittnzy= isset($new['ittnzy']) && !empty($new['ittnzy']) ? $new['ittnzy'] : "";
        $ittnyx= isset($new['ittnyx']) && !empty($new['ittnyx']) ? $new['ittnyx'] : "";
        $ittnxm= isset($new['ittnxm']) && !empty($new['ittnxm']) ? $new['ittnxm'] : "";
        $ittngj= isset($new['ittngj']) && !empty($new['ittngj']) ? $new['ittngj'] : "";
        $budget= isset($new['budget']) && !empty($new['budget']) ? $new['budget'] : "";
        $graduate= isset($new['graduate']) && !empty($new['graduate']) ? $new['graduate'] : "";
        $graduatezy= isset($new['graduatezy']) && !empty($new['graduatezy']) ? $new['graduatezy'] : "";
        $tolink= isset($new['tolink']) && !empty($new['tolink']) ? $new['tolink'] : "";
        $attachment= isset($new['attachment']) && !empty($new['attachment']) ? $new['attachment'] : "";
        $next_follow= isset($new['next_follow']) && !empty($new['next_follow']) && $new['next_follow'] != 'null' && $new['next_follow'] != 'NULL' ? $new['next_follow'] : 0;
        $note= isset($new['note']) && !empty($new['note']) ? $new['note'] : "";
        $bid= isset($bid) && !empty($bid) ? $bid : 0;
        $creatid= isset($new['creatid']) && !empty($new['creatid']) ? $new['creatid'] : "";
        $field_list= isset($new['field_list']) && !empty($new['field_list']) ? $new['field_list'] : "";
        //基础信息表
        $group= isset($data['group']) && !empty($data['group']) ? $data['group'] : "";
        $sex= isset($data['sex']) && !empty($data['sex']) ? $data['sex'] : "";
        $age= isset($data['age']) && !empty($data['age']) ? $data['age'] : "";
        $adress= isset($data['adress']) && !empty($data['adress']) ? $data['adress'] : "";
        $city= isset($data['city']) && !empty($data['city']) ? $data['city'] : "";
        $station= isset($data['station']) && !empty($data['station']) ? $data['station'] : "";
        $occupation= isset($data['occupation']) && !empty($data['occupation']) ? $data['occupation'] : "";
        $industry= isset($data['industry']) && !empty($data['industry']) ? $data['industry'] : "";
        $company= isset($data['company']) && !empty($data['company']) ? $data['company'] : "";
        $character= isset($data['character']) && !empty($data['character']) ? $data['character'] : "";
        $cphonetwo= isset($data['cphonetwo']) && !empty($data['cphonetwo']) ? $data['cphonetwo'] : 1;
        $cphone2= isset($data['cphone']) && !empty($data['cphone']) ? trim($data['cphone'],' '):'';
        $cphonethree= isset($data['cphonethree']) && !empty($data['cphonethree']) ? $data['cphonethree'] : 1;
        $telephone= isset($data['telephone']) && !empty($data['telephone']) ? $data['telephone'] : 1;
        $formwhere2= isset($data['formwhere2']) && !empty($data['formwhere2']) ? $data['formwhere2'] : "";
        $formwhere3= isset($data['formwhere3']) && !empty($data['formwhere3']) ? $data['formwhere3'] : "";
        $wxnum= isset($data['wxnum']) && !empty($data['wxnum']) ? $data['wxnum'] : "";
        $cemail= isset($data['cemail']) && !empty($data['cemail']) ? $data['cemail'] : "";
        $qq= isset($data['qq']) && !empty($data['qq']) ? $data['qq'] : "";
        $invoice_company= isset($data['invoice_company']) && !empty($data['invoice_company']) ? $data['invoice_company'] : "";
        $taxpayer_num= isset($data['taxpayer_num']) && !empty($data['taxpayer_num']) ? $data['taxpayer_num'] : "";
        $bank= isset($data['bank']) && !empty($data['bank']) ? $data['bank'] : "";
        $open_bank= isset($data['open_bank']) && !empty($data['open_bank']) ? $data['open_bank'] : "";
        $bank_num= isset($data['bank_num']) && !empty($data['bank_num']) ? $data['bank_num'] : "";
        $bank_adress= isset($data['bank_adress']) && !empty($data['bank_adress']) ? $data['bank_adress'] : "";
        $legal_person= isset($data['legal_person']) && !empty($data['legal_person']) ? $data['legal_person'] : "";
        $business_license= isset($data['business_license']) && !empty($data['business_license']) ? $data['business_license'] : "";
        $project_name= isset($data['project_name']) && !empty($data['project_name']) ? $data['project_name'] : "";
        $project_num= isset($data['project_num']) && !empty($data['project_num']) ? $data['project_num'] : "";
        if ($id=='' || $uid==''|| empty($cname)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $user_info=$this->di->user->select('group_id,is_leader')->where('id',$uid)->fetchOne();
        $params[':status']=1;
        //1.查询客户表
        $sql='Select c.*,cs.* from crm_customer c LEFT JOIN crm_customer_data cs ON cs.cid=c.id where c.id = '.$id;
        $cus = $this->di->customer->queryAll($sql,$params);
        if(count($cus)!=1){
            $rs = array('code'=>0,'msg'=>'客户信息不存在','data'=>array(),'info'=>array());
            return $rs;
        }else{
            $cus_list=$cus[0];
            $cphone=$cus_list['cphone'];
            $wx=$cus_list['wxnum'];
            //判断是否是主管或管理员
            if($user_info['group_id']==20){
                $uid= $cus_list['creatid'];
            }
            $coumterlist=array(
                'cname'=>$cname,
                'ittngj'=>$ittngj,
                'budget'=>$budget,
                'graduate'=>$graduate,
                'graduatezy'=>$graduatezy,
                'tolink'=>$tolink,
                'attachment'=>$attachment,
                'note'=>$note,
                'next_follow'=>$next_follow,
            );
            if (is_array($ittnzy) && $ittnzy!='') {
                foreach ($ittnzy as $k=>$v){
                    if($v!=''){
                        $coumterlist['ittnzy'] .=$v.',' ;
                    }else{
                        continue;
                    }
                }
            }else{
                $coumterlist['ittnzy']='';
            }
            if (is_array($ittnyx) && $ittnyx!='') {
                foreach ($ittnyx as $k=>$v){
                    if($v!=''){
                        $coumterlist['ittnyx'] .=$v.',' ;
                    }else{
                        continue;
                    }
                }
            }else{
                $coumterlist['ittnyx']='';
            }
            if (is_array($ittnxm) && $ittnxm!='') {
                foreach ($ittnxm as $k=>$v){
                    if($v!=''){
                        $coumterlist['ittnxm'] .=$v.',' ;
                    }else{
                        continue;
                    }
                }
            }else{
                $coumterlist['ittnxm']='';
            }
            if (is_array($field_list) && !empty($field_list)) {
                foreach ($field_list as $key => $value) {
                    if (is_array($value)) {
                        $field_list[$key] = json_encode($value,JSON_UNESCAPED_UNICODE);
                    }
                }
                $coumterlist_data = array_merge($coumterlist,$field_list);
            }
            $session_list=\PhalApi\DI()->session->list;
            $nrepeat=$session_list['nrepeat'];
            if($nrepeat==1 && $cphone==''){
                $rs = array('code'=>2,'msg'=>'手机号码不能为空!','data'=>[],'info'=>array());
                return $rs;
            }else if($nrepeat==2 && $wxnum==''){
                $rs = array('code'=>2,'msg'=>'微信号不能为空!','data'=>[],'info'=>array());
                return $rs;
            }

            $cphone=empty($cphone)?$cphone2:$cphone;
            //查撞单
//            $list=$this->di->customer_data->select('cid,cphone,cphonetwo,cphonethree,telephone,id,create_time')->where("FIND_IN_SET('".$cphonetwo."',CONCAT_WS(',',cphone,cphonetwo,cphonethree,telephone)) ")->or(" FIND_IN_SET('".$telephone."',CONCAT_WS(',',cphone,cphonetwo,cphonethree,telephone)) ")->or(" FIND_IN_SET('".$cphonethree."',CONCAT_WS(',',cphone,cphonetwo,cphonethree,telephone)) ")->fetchAll();
//            if(count($list)>=1){
//                $array=['0'=>$list['cphone'],'1'=>$list['cphonetwo'],'2'=>$list['cphonethree'],'3'=>$list['telephone']];
//                foreach ($list as $v){
//                    if($v['cid']!=$id){
//                        $creatid_new=\App\GetFiledInfo('customer','creatid',$v['cid']);
//                        if(in_array($cphonetwo,$v)){
//
//                            $phone_array[0]=array(
//                                'cname'=>\App\GetFiledInfo('customer','cname',$v['cid']),
//                                'cphone'=>$cphonetwo,
//                                'realname'=>\App\GetFiledInfo('user','realname',$creatid_new),
//                                'sea_type'=>\App\GetFiledInfo('customer','sea_type',$v['cid'])==0?'私海':'公海',
//                                'cid'=>$v['cid'],
//                                'create_time'=>$v['create_time'],);
//                        }
//                        if(in_array($cphonethree,$v)){
//                            $phone_array[1]=array('cname'=>\App\GetFiledInfo('customer','cname',$v['cid']),
//                                'cphone'=>$cphonethree,
//                                'cid'=>$v['cid'],
//                                'realname'=>\App\GetFiledInfo('user','realname',$creatid_new),
//                                'sea_type'=>\App\GetFiledInfo('customer','sea_type',$v['cid'])==0?'私海':'公海',
//                                'create_time'=>$v['create_time'],);
//                        }
//                        if(in_array($telephone,$v)){
//                            $phone_array[2]=array('cname'=>\App\GetFiledInfo('customer','cname',$v['cid']),
//                                'cphone'=>$telephone,
//                                'cid'=>$v['cid'],
//                                'realname'=>\App\GetFiledInfo('user','realname',$creatid_new),
//                                'sea_type'=>\App\GetFiledInfo('customer','sea_type',$v['cid'])==0?'私海':'公海',
//                                'create_time'=>$v['create_time'],);
//                        }
//                    }
//                }
//                //查询账单记录 并赋值时间
//                if(is_array($phone_array)){
//                    if(count($phone_array)>=1){
//                        foreach ($phone_array as $k=>$v){
//                            $addtime=$this->di->customer_log->where('type',9)->and('uid',$uid)->and('cid',$v['cid'])->order('addtime desc')->fetchOne('addtime');
//                            $unixTime_1 =  $addtime; // 开始时间
//                            $unixTime_2 = time(); // 结束时间
//                            $timediff = abs($unixTime_2 - $unixTime_1);
//                            $remain = $timediff % 86400;
//                            $hours = intval($remain / 3600);
//                            if($hours>=1){
//                                //撞单记录
//                                \App\Cus_log($uid,$v['cid'],9,'撞单','客户手机号:'.$v['cphone']);
//                                $this->di->customer_data->where('cid',$v['cid'])->update(['zdnum' => new \NotORM_Literal("zdnum + 1")]);
//                                //如果两小时内连续撞单视为一次\\
//                            }
//                        }
//                        $rs = array('code'=>2,'msg'=>'999982','data'=>$phone_array,'info'=>array());
//                        return $rs;
//                    }
//
//                }
//
//            }


            $coumterdata=array(
                'sex'=>$sex,
                'age'=>$age,
                'city'=>$city,
                'adress'=>$adress,
                'station'=>$station,
                'occupation'=>$occupation,
                'industry'=>$industry,
                'company'=>$company,
                'character'=>$character,
                'cphonetwo'=>$cphonetwo==1?'':$cphonetwo,
                'cphonethree'=>$cphonethree==1?'':$cphonethree,
                'telephone'=>$telephone==1?'':$telephone,
                'formwhere2'=>$cphonetwo==1?'':$formwhere2,
                'formwhere3'=>$cphonetwo==1?'':$formwhere3,
                'cemail'=>$cemail,
                'qq'=>$qq,
                'update_time'=>time(),
                'invoice_company'=>$invoice_company,
                'taxpayer_num'=> $taxpayer_num,
                'bank'=> $bank,
                'open_bank'=> $open_bank,
                'bank_num'=> $bank_num,
                'bank_adress'=>$bank_adress,
                'legal_person'=>$legal_person,
                'business_license'=>$business_license,
                'project_name'=>$project_name,
                'project_num'=> $project_num,
            );
            if($nrepeat==1){
                $coumterdata['wxnum']=$wxnum;

            }else{
                if($cphone==''){
                    $coumterdata['cphone']=$cphone;
                }
                if($wx==''){
                    $coumterdata['wxnum']=$wxnum;
                }

            }
            // 保存到日志文件
//            \PhalApi\DI()->logger->log('SQL', $statement, array('s' => \PhalApi\DI()->request->getService()));
            if($coumterdata['project_name']!=''){
                $coumterdata['project_name']=$project_name.',';
            }
            $userorder=$this->di->customer_order->where('cid',$id)->fetchOne();//查询时否有成交记录
            if($userorder){
                unset($coumterdata['project_name']);
                unset($coumterdata['agent_num']);
                unset($coumterdata['agent_name']);
                unset($coumterdata['project_num']);

            }

            if($cus_list['creatid'] == $uid){
                $this->di->customer->where('id',$id)->update($coumterlist_data);//更改创建人数据
                if(!empty($bid)){
                    $this->di->share_customer->where('id',$bid)->update($coumterlist_data);
                }else{
                    $bid2=$this->di->share_join->where('cid',$id)->and('beshare_uid',$uid)->and('creat_id',$uid)->and('status',1)->fetchOne('bid');
                      if(!empty($bid2)){
                          isset($field_list['intentionally']) && !empty($field_list['intentionally']) ? $this->di->customer->where('id',$id)->update(['intentionally'=>$field_list['intentionally']]):0;
                          $this->di->share_customer->where('id',$bid2)->update($coumterlist_data);
                      }
                }
            }else{
                //否则则为负责人
                // $bid=$this->di->share_join->where('cid',$id)->and('beshare_uid',$uid)->and('creat_id','<>',$uid)->and('status',1)->fetchOne('bid');
                if(!empty($bid)){
                    isset($field_list['intentionally']) && !empty($field_list['intentionally']) ? $this->di->customer->where('id',$id)->update(['intentionally'=>$field_list['intentionally']]):0;
                    $this->di->share_customer->where('id',$bid)->update($coumterlist_data);
                }else{
                      $bid2=$this->di->share_join->where('cid',$id)->and('beshare_uid',$uid)->and('creat_id','<>',$uid)->and('status',1)->fetchOne('bid');
                      if(!empty($bid2)){
                          isset($field_list['intentionally']) && !empty($field_list['intentionally']) ? $this->di->customer->where('id',$id)->update(['intentionally'=>$field_list['intentionally']]):0;
                          $this->di->share_customer->where('id',$bid2)->update($coumterlist_data);
                      }else{
                            $re=array('code'=>1,'msg'=>'客户信息不存在或您无权编辑','data'=>[],'info'=>[]);
                            return $re;
                      }
                  
                   
                }
            }

            $this->di->customer_data->where('cid',$id)->update($coumterdata);
            $other=array_merge($coumterlist,$coumterdata);
            $result=json_encode(array_diff_assoc($cus_list,$other));
            \App\setlog($uid,2,'编辑客户成功',$result,'编辑客户成功:'.$coumterlist['cname'].'手机号:'.$cphone,'编辑客户');
            $bid=  isset($list['bid']) && !empty($list['bid']) ? $list['bid'] : "";
            $this->redis->del('member_number_limit_'.$uid,'user');
            $this->redis->del('customer_info_'.$uid.'_'.$id.'_'.$bid,'customer');
            $re=array('code'=>1,'msg'=>'编辑成功','data'=>[],'info'=>[]);
            return $re;
        }
    }
    //跟进客户
//
    //跟进客户
    public function dofp($id,$uid,$type,$lid,$list){
        $lid=isset($lid) && !empty($lid) ? $lid : "";//跟进列表id
        $id=isset($id) && !empty($id) ? $id :'';
        $uid=  isset($uid) && !empty($uid) ?$uid:'';
        $list['next_time']=  isset($list['next_time']) && !empty($list['next_time']) ? $list['next_time']: 0;//下次跟进时间
        $list['bid']=  isset($list['bid']) && !empty($list['bid']) ? $list['bid']: 0;//下次跟进时间
        $data_bid=isset($list['bid']) && !empty($list['bid']) ? $list['bid']:0;//bid
        $user_info=$this->di->user->where('id',$uid)->fetchOne('is_leader');
        $cus_info=$this->di->customer->where("FIND_IN_SET(".$uid.",charge_person)")->and('id',$id)->fetchOne();
        $admin_common = new AdminCommon();
        if($cus_info=='' && $user_info==0){
            $rs = array('code'=>0,'msg'=>'您无法跟进这个客户!','data'=>array(),'info'=>array());
            return $rs;
        }else{
            $list['uid']=$uid;
            $list['now_time']=time();
            $list['executor']=$list['executor']==''?$uid:$list['executor'];
            $list['cid']=$id;
            $gx=$this->di->customer->where('id',$id)->update(['follw_time'=>time()]);
            if($gx==1){
                //区分创建人和共享人 分配人 领取人
                if($cus_info['creatid']==$uid){
                    //创建人且用户不是领导
                    if ($list['next_time']) {
                        $this->di->customer->where('id', $id)->and('status', 1)->update(['follw_time' => time(), 'next_follow' => $list['next_time'], 'follow_person' => $list['uid'], 'follow_up' => new \NotORM_Literal("follow_up + 1")]);
                    }else {
                        $this->di->customer->where('id', $id)->and('status', 1)->update(['follw_time' => time(), 'follow_person' => $list['uid'], 'follow_up' => new \NotORM_Literal("follow_up + 1")]);
                    }
                    $to_user=$cus_info['creatid'];
                    \App\setlog($uid, 1, '新增跟进成功', '成功', '新增跟进成功客户id:'.$id, '创建人新建跟进成功');
                }else {
                    //不是创建人
                    $sql = "SELECT s.bid FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE  s.cid = {$id} AND s.creat_id <> {$uid} AND beshare_uid = {$uid} ";
                    $bid_array = $this->di->share_join->queryAll($sql,[]);
                    $bid_array=isset($bid_array[0]['bid'])?$bid_array[0]['bid']:'';
                    if($bid_array){
                        if (isset($list['next_time'])) {
                            $this->di->share_customer->where('id', $bid_array)->and('status', 1)->and('FIND_IN_SET(' . $uid . ',charge_person)')->update(['follw_time' => time(), 'next_follow' => $list['next_time'], 'follow_person' => $list['uid'], 'follow_up' => new \NotORM_Literal("follow_up + 1")]);
                            $this->di->customer->where('id', $id)->and('status', 1)->update(['follow_up' => new \NotORM_Literal("follow_up + 1")]);
                        }else {
                            $this->di->share_customer->where('id', $bid_array)->and('status', 1)->and('FIND_IN_SET(' . $uid . ',charge_person)')->update(['follw_time' => time(), 'follow_person' => $list['uid'], 'follow_up' => new \NotORM_Literal("follow_up + 1")]);
                            $this->di->customer->where('id', $id)->and('status', 1)->update(['follow_up' => new \NotORM_Literal("follow_up + 1")]);
                        }
                        $to_user=$cus_info['creatid'];
                        \App\setlog($uid, 1, '新增跟进成功', '成功', '新增跟进成功客户id:'.$id, '被共享人新建跟进成功');
                    }else{
                        if($data_bid){
                            $to_user=$this->di->share_join->where('bid',$data_bid)->fetchOne();
                            $to_user=isset($to_user['beshare_uid']) && !empty($to_user['beshare_uid'])?$to_user['beshare_uid']:$uid;
                            if (isset($list['next_time'])) {
                                $this->di->share_customer->where('id', $data_bid)->and('status', 1)->and('FIND_IN_SET(' . $uid . ',charge_person)')->update(['follw_time' => time(), 'next_follow' => $list['next_time'], 'follow_person' => $list['uid'], 'follow_up' => new \NotORM_Literal("follow_up + 1")]);
                                $this->di->customer->where('id', $id)->and('status', 1)->update(['follow_up' => new \NotORM_Literal("follow_up + 1")]);
                            }else {
                                $this->di->share_customer->where('id', $data_bid)->and('status', 1)->and('FIND_IN_SET(' . $uid . ',charge_person)')->update(['follw_time' => time(), 'follow_person' => $list['uid'], 'follow_up' => new \NotORM_Literal("follow_up + 1")]);
                                $this->di->customer->where('id', $id)->and('status', 1)->update(['follow_up' => new \NotORM_Literal("follow_up + 1")]);
                            }
                        }else{
                            $to_user=$this->di->customer->where('id',$id)->fetchOne('creatid');
                            $to_user=isset($to_user['creatid']) && !empty($to_user['creatid'])?$to_user['creatid']:$uid;
                            if ($list['next_time']) {
                                $this->di->customer->where('id', $id)->and('status', 1)->update(['follw_time' => time(), 'next_follow' => $list['next_time'], 'follow_person' => $list['uid'], 'follow_up' => new \NotORM_Literal("follow_up + 1")]);
                            }else {
                                $this->di->customer->where('id', $id)->and('status', 1)->update(['follw_time' => time(), 'follow_person' => $list['uid'], 'follow_up' => new \NotORM_Literal("follow_up + 1")]);
                            }
                        }
                        \App\setlog($uid, 1, '领导新增跟进成功', '成功', '领导新增跟进成功客户id:'.$id, '领导新建跟进成功');
                    }
                }

                $this->di->follw->insert($list);
                $msg_info = $this->di->msg->select("id")->where(array("type" => 1, "info_id" => $id, "table_name" => 'customer', "action" => "notice", "creatid" => $uid, "to_uid" => $to_user))->fetchOne();
                if (!empty($msg_info)) {
                    $this->di->msg->where(["id" => $msg_info['id']])->update(array("status" => 0));
                }else {
                    if(!empty($list['next_time'])){
                        $note = '新建客户:' . $list['cname'] . '的跟进';
                        $content = "您已成功新建客户:" .$list['cname'].'的跟进';
                        $admin_common->SendMsgNow($uid,$to_user, $note, $content, 1, "notice", 'customer', $id);
                    }


                }
                $rs = array('code'=>1,'msg'=>'新增跟进成功!','data'=>array(),'info'=>array());
                $bid=  isset($list['bid']) && !empty($list['bid']) ? $list['bid'] : "";
                $this->redis->flushDB('customer');
            }else{
                $rs = array('code'=>0,'msg'=>'新增跟进失败,系统时间错误!','data'=>array(),'info'=>array());
            }

            return $rs;

        }

    }
    //客户详情
    public function customerinfo($id, $uid, $bid)
    {
        $id = isset($id) && !empty($id) ? $id : "";
        $uid = isset($uid) && !empty($uid) ? $uid : "";
        $bid = isset($bid) && !empty($bid) ? $bid : "";
        $bumen = [];
        if ($id == '' || $uid == '') {
            $rs = array('code' => 0, 'msg' => '参数不正确!', 'data' => array(), 'info' => array());
            return $rs;
        }
        //角色
        $cus_need_filed = $this->di->customer->where('id', $id)->and('status', 1)->fetchOne();
        if ($cus_need_filed == '') {
            $rs = array('code' => 0, 'msg' => '参数不正确!', 'data' => array(), 'info' => array());
            return $rs;
        }
        else {
            $sea_type = $cus_need_filed['sea_type'];
            if(!empty($bid)){
                $sea_type = $this->di->share_join->where('bid',$bid)->fetchOne('sea_type');
            }

            $crid = $cus_need_filed['creatid'];
            $charge_person_no = $cus_need_filed['charge_person'];
        }
        $leader_id = $uid;
        $user_type = $this->di->user->select('structure_id,is_leader,group_id')->where('id', $uid)->and('is_delete', 0)->fetchOne();
        //查询用户的身份
        if ($user_type['group_id'] == 20 || $user_type['is_leader'] == 1 || $user_type['is_leader'] == 2) {
            $share_status = $this->di->customer->where(" FIND_IN_SET('{$uid}',charge_person)")->and('id', $id)->fetchOne();
            if ($share_status == '') {
                $charge_person_array = explode(',', $charge_person_no);
                if (!in_array($leader_id, $charge_person_array) && $user_type['group_id'] != 20) {
                    $sea_type = 1;
                }
                else {
                    $uid = $this->di->customer->where('id', $id)->and('status', 1)->fetchOne('creatid');
                    if ($bid != '') {
                        $crid = $this->di->share_customer->where('id', $bid)->fetchOne('charge_person');
                    }
                    else {
                        $crid = $uid;
                    }
                    //超管和主管

                }
            }
        }
        //超管 以创建人为主 普通员工 为创建人 普通员工 被分享人 主管
        //查看下一个和上一个客户带bid
        $prev_agent_id = $this->di->customer->where("status = 1 AND FIND_IN_SET('{$uid}', charge_person) AND `id`>'$id' ")->order("id ASC")->fetchOne("id");
        $next_agent_id = $this->di->customer->where("status = 1 AND FIND_IN_SET('{$uid}', charge_person) AND `id`<'$id' ")->order("id DESC")->fetchOne("id");
        $coumterlist['next_customer_id'] = $next_agent_id;
        $next_agent_bid = $this->di->share_join->where('cid', $next_agent_id)->and('beshare_uid', $uid)->fetchOne('bid');
        $coumterlist['next_customer_bid'] = $next_agent_bid;
        $coumterlist['prev_customer_id'] = $prev_agent_id;
        $prev_agent_bid = $this->di->share_join->where('cid', $prev_agent_id)->and('beshare_uid', $uid)->fetchOne('bid');
        $coumterlist['prev_customer_bid'] = $prev_agent_bid;
        //建立redis缓存
        $customer_info = $this->redis->get_time('customer_info_'.$uid.'_'.$id.'_'.$bid, 'customer');

        // $customer_info_time = $this->redis->get_time_ttl('customer_info_'.$uid.'_'.$id.'_'.$bid, 'customer');
        $customer_info_time = 0;
        $customer_info['redis_time']= $customer_info_time;
        if ($customer_info_time == 0) {

            $paid_total = $this->di->customer_order->where('cid', $id)->and('status', 1)->sum('total');
            //成交金额
            $refund_total = $this->di->customer_refund->where('cid', $id)->and('status', 1)->sum('refund_total');
            //退款金额
            $order = $this->di->customer_order->where('cid', $id)->and('uid', $uid)->fetchAll();
            //基础数据
            $data = $this->di->customer_data->where('cid', $id)->fetchOne();
            //  部门遍历
            $data['cphone'] = $sea_type == 1 ? \App\string_substr($data['cphone'], 3, 8) : $data['cphone'];
            $data['cphonetwo'] = ($sea_type == 1 && $data['cphonetwo'] != '') ? \App\string_substr($data['cphonetwo'], 3, 8) : $data['cphonetwo'];
            $data['cphonethree'] = ($sea_type == 1 && $data['cphonethree'] != '') ? \App\string_substr($data['cphonethree'], 3, 8) : $data['cphonethree'];
            $data['telephone'] = ($sea_type == 1 && $data['telephone'] != '') ? '***' . substr($data['telephone'], strlen($data['telephone']) - 3, strlen($data['telephone'])) : $data['telephone'];
            $data['wxnum'] = ($sea_type == 1 && $data['wxnum'] != '') ? '***' . substr($data['wxnum'], strlen($data['wxnum']) - 3, strlen($data['wxnum'])) : $data['wxnum'];
            $data['cemail'] = ($sea_type == 1 && $data['cemail'] != '') ? '***' . substr($data['cemail'], strlen($data['cemail']) - 3, strlen($data['cemail'])) : $data['cemail'];
            $data['creat_name'] = \App\GetFiledInfo('user', 'realname', $crid);
            $d = $this->di->structure->where('id', $data['groupid'])->fetchOne();
            if ($d['pid'] != 0) {
                $c = $this->di->structure->where('id', $d['pid'])->fetchOne();
                if ($c['pid'] == 0) {
                    $bumen = $c['name'] . ',' . $d['name'];
                    $bumenid = $c['id'] . ',' . $d['id'];
                }
                else {
                    $e = $this->di->structure->where('id', $c['pid'])->fetchOne();
                    if ($e['pid'] == 0) {
                        $bumen = $e['name'] . ',' . $c['name'] . ',' . $d['name'];
                        $bumenid = $e['id'] . ',' . $c['id'] . ',' . $d['id'];
                    }
                }
            }
            else {
                $bumen = $d['name'];
                $bumenid = $d['id'];
            }
            //部门遍历end
            //项目方/代理商
            if ($data['project_name'] != null) {
                $project_name = explode(',', trim($data['project_name'], ','));
                foreach ($project_name as $k => $v) {
                    $project_info = $this->di->project_side->select('number,flower_name')->where('id', $v)->fetchOne();
                    $project_name_array[] = array('id' => $v, 'number' => $project_info['number'], 'flower_name' => $project_info['flower_name'],);
                }
                $agent_name = array('id' => null, 'number' => null, 'flower_name' => null);
                $data['is_project'] = 1;
                $data['is_agent'] = null;
            }
            else if ($data['project_name'] == null && $data['agent_name'] != '') {
                $agent_info = $this->di->agent->select('id,flower_name')->where('number', $data['agent_num'])->fetchOne();
                $agent_name = array('id' => $agent_info['id'], 'number' => $data['agent_num'], 'flower_name' => $agent_info['flower_name']);
                $project_name_array[] = array('id' => null, 'number' => null, 'flower_name' => null);
                $data['is_project'] = null;
                $data['is_agent'] = 1;
            }
            else if ($data['project_name'] == null && $data['agent_name'] == null) {
                $agent_name = array('id' => null, 'number' => null, 'flower_name' => null);
                $project_name_array[] = array('id' => null, 'number' => null, 'flower_name' => null);
                $data['is_project'] = null;
                $data['is_agent'] = null;
            }
            //项目方/代理商 end
            //撞单判断
            $zd = $this->ReqeatUserList($uid, $id, 1, 10);
            $data['collision'] = $zd['data']['count_total'] == 0 ? null : $zd['data']['count_total'];
            //end
            if ($crid == $uid AND $bid == '') {
                $do_type = 1;
            }else if ($crid != $uid AND $bid != '') {
                $do_type = 3;
            }else if ($crid != $uid AND $bid == '') {
                $bid = $this->di->share_join->where('cid', $id)->and('beshare_uid', $uid)->fetchOne('bid');
                $cas = $this->di->share_customer->where('id', $bid)->and('status', 1)->fetchOne();
                $do_type = $cas != '' ? 3 : 1;
            }else if ($crid == $uid AND $bid != '') {
                $do_type = 2;
            }
            switch ($do_type) {
                case '1':
                    //所有人的跟进
                    $genjin = $this->di->follw->and('cid', $id)->and('status', 1)->order("now_time DESC")->fetchAll();
                    foreach ($genjin as $k => $v) {
                        $genjin[$k]['realname'] =$v['executor'];
                    }
                    //跟进end
                    //上课记录
                    $class_list = $this->di->edustudent->where('cid', $id)->and('status', 0)->fetchAll();
                    foreach ($class_list as $k => $v) {
                        $class_list[$k] = ['cname' => \App\GetFiledInfo('customer', 'cname', $v['cid']), 'order_num' => $v['order_num'], 'cphone' => $sea_type == 1 ? \App\string_substr($v['cphone'], 3, 8) : $v['cphone'], 'card_type' => $v['card_type'], 'cart_note' => $v['cart_note'], 'card_num' => $v['card_num'], 'sos_name' => $v['sos_name'], 'guanxi' => $v['guanxi'], 'sos_phone' => $v['sos_phone'], 'xm_name' => \App\GetFiledInfo('general_rules', 'title', $v['xm_id']), 'yuanxiao' => $v['yuanxiao'], 'xueli' => $v['xueli'], 'class_name' => $v['class_name'], 'class_teachername' => $v['class_teachername'], 'jiaowu_teacher' => \App\GetFiledInfo('user', 'realname', $v['jiaowu_teacher']), 'ziliao' => $v['ziliao'], 'student_id' => $v['student_id'], 'z_xm' => $v['z_xm'], 'beizhu' => $v['beizhu'], 'note' => $v['note'], 'creat_man' => \App\GetFiledInfo('user', 'realname', $v['creatid']), 'create_time' => $v['create_time'], 'updateid' => \App\GetFiledInfo('user', 'realname', $v['updateid']), 'update_time' => $v['update_time'],];
                    }
                    $data['student_class_list'] = $class_list;
                    $data['group_name'] = $bumen;
                    $data['group_id'] =$bumenid;
                    $data['groupid'] =$data['group_id'];
                    $data['agent_name'] = $agent_name;
                    $data['project_name'] = $project_name_array;
                    $data['follows_up'] = $genjin;
                    //跟进详情
                    $data['order'] = $order;
                    $data['paid_total'] = $paid_total;
                    $data['refund_total'] = $refund_total;
                    $data['orther'] = ['is_top' => $data['is_top'] == 0 ? '已置顶' : '未置顶', 'sea_type' => $sea_type == 0 ? '私海' : '公海', 'status' => $data['status'] == 0 ? '有效' : '无效', 'gongxiang' => '否', 'beigongxiang' => count(explode(',', $data['charge_person'])) > 2 ? '是' : '否', 'creat_name' => $data['creat_name'], 'creat_time' => $data['create_time'], 'updateman' => $data['creat_name'], 'updatetime' => $data['update_time'], 'last_follow' => $this->di->follw->where('cid', $id)->and('status', 1)->order('now_time desc')->fetchOne('now_time'), 'follow_sort' => $this->di->follw->where('cid', $id)->and('status', 1)->count(), 'next_follow' => $data['next_time']];
                    $coumterlist['data'] = array_merge($cus_need_filed, $data);
                    break;
                case '2':
                    $cas = $this->di->share_customer->where('id', $bid)->and('status', 1)->fetchOne();
                    $charge_person = str_ireplace(',', '', $cas['charge_person']);
                    $charge_person_name = \App\GetFiledInfo('user', 'realname', $charge_person);
                    $list2 = $this->di->share_join->where('bid', $bid)->and('status', 1)->fetchOne();
//                    $genjin = $this->di->follw->where('uid', $charge_person)->and('cid', $id)->and('status', 1)->order("now_time DESC")->fetchAll();
                    $genjin2 = $this->di->follw->where('uid', $list2['share_uid'])->and('cid', $id)->and('now_time <= ?', $list2['addtime'])->order("now_time DESC")->fetchAll();
                    $genjin3 = $this->di->follw->where('cid', $id)->and("bid = ".$bid." OR bid = 0")->order("now_time DESC")->fetchAll();
//                    $genjin3 = $this->di->follw->where('cid', $id)->and('bid', 0)->order("now_time DESC")->fetchAll();
                    $genjin=array_merge($genjin2,$genjin3);
                    $genjin=\App\array_sort($genjin,'id','asc');
                    foreach ($genjin as $k => $v) {

                        $genjin[$k]['realname'] = $v['executor'];
                    }
                    $cas = $this->di->share_customer->where('id', $bid)->and('status', 1)->fetchOne();
                    $cas['creat_name'] = $data['creat_name'];
                    //查询部门end
                    //上课记录
                    //创建人能看所有的上课记录
                    //分享人只能看自己的上课记录
                    $class_list = $this->di->edustudent->where('cid', $id)->and('status', 0)->and('creatid', $charge_person)->fetchAll();
                    foreach ($class_list as $k => $v) {
                        $class_list[$k] = ['cname' => \App\GetFiledInfo('customer', 'cname', $v['cid']), 'order_num' => $v['order_num'], 'cphone' => $v['cphone'], 'card_type' => $v['card_type'], 'cart_note' => $v['cart_note'], 'card_num' => $v['card_num'], 'sos_name' => $v['sos_name'], 'guanxi' => $v['guanxi'], 'sos_phone' => $v['sos_phone'], 'xm_name' => \App\GetFiledInfo('general_rules', 'title', $v['xm_id']), 'yuanxiao' => $v['yuanxiao'], 'xueli' => $v['xueli'], 'class_name' => $v['class_name'], 'class_teachername' => $v['class_teachername'], 'jiaowu_teacher' => \App\GetFiledInfo('user', 'realname', $v['jiaowu_teacher']), 'ziliao' => $v['ziliao'], 'student_id' => $v['student_id'], 'z_xm' => $v['z_xm'], 'beizhu' => $v['beizhu'], 'note' => $v['note'], 'creat_man' => $charge_person_name, 'create_time' => $v['create_time'], 'updateid' => $charge_person_name, 'update_time' => $v['update_time'],];
                    }
                    $data['student_class_list'] = $class_list;
                    $cas['group_name'] = $bumen;
                    $data['agent_name'] = $agent_name;
                    $data['project_name'] = $project_name_array;
                    $cas['group_id'] = $bumenid;
                    $data['follows_up'] = array_values($genjin);
                    //跟进详情
                    $data['order'] = $order;
                    //成交详情
                    $data['orther'] = ['is_top' => $cas['is_top'] == 0 ? '已置顶' : '未置顶', 'sea_type' => $sea_type == 0 ? '私海' : '公海', 'status' => $cas['status'] == 0 ? '有效' : '无效', 'gongxiang' => '否', 'beigongxiang' => count(explode(',', $data['charge_person'])) > 2 ? '是' : '否', 'creat_name' => $cas['creat_name'], 'creat_time' => $data['create_time'], 'updateman' => $data['creat_name'], 'updatetime' => $data['update_time'], 'last_follow' => $this->di->follw->where('cid', $id)->and('status', 1)->order('now_time desc')->fetchOne('now_time'), 'follow_sort' => $this->di->follw->where('cid', $id)->and('status', 1)->count(), 'next_follow' => $cas['next_follow']];
                    $data['paid_total'] = $paid_total;
                    $data['refund_total'] = $refund_total;
                    $coumterlist['data'] = array_merge($cas, $data);
                    break;
                case '3':
                    $info = $this->di->share_join->where('cid', $id)->and('beshare_uid')->fetchOne('bid');
                    $cas = $this->di->share_customer->where('id', $bid)->and('status', 1)->fetchOne();
                    $charge_person = str_ireplace(',', '', $cas['charge_person']);
                    $charge_person_name = \App\GetFiledInfo('user', 'realname', $charge_person);
                    $list2 = $this->di->share_join->where('bid', $bid)->and('status', 1)->fetchOne();
                    $genjin = $this->di->follw->where('uid', $charge_person)->and('cid', $id)->and('status', 1)->order("now_time DESC")->fetchAll();
                    foreach ($genjin as $k => $v) {
                        $genjin[$k]['realname'] = $v['executor'];
                    }
                    $cas = $this->di->share_customer->where('id', $bid)->and('status', 1)->fetchOne();
                    $cas['creat_name'] = $data['creat_name'];
                    //查询部门end
                    //上课记录
                    //创建人能看所有的上课记录
                    //分享人只能看自己的上课记录
                    $class_list = $this->di->edustudent->where('cid', $id)->and('status', 0)->and('creatid', $charge_person)->fetchAll();
                    foreach ($class_list as $k => $v) {
                        $class_list[$k] = ['cname' => \App\GetFiledInfo('customer', 'cname', $v['cid']), 'order_num' => $v['order_num'], 'cphone' => $v['cphone'], 'card_type' => $v['card_type'], 'cart_note' => $v['cart_note'], 'card_num' => $v['card_num'], 'sos_name' => $v['sos_name'], 'guanxi' => $v['guanxi'], 'sos_phone' => $v['sos_phone'], 'xm_name' => \App\GetFiledInfo('general_rules', 'title', $v['xm_id']), 'yuanxiao' => $v['yuanxiao'], 'xueli' => $v['xueli'], 'class_name' => $v['class_name'], 'class_teachername' => $v['class_teachername'], 'jiaowu_teacher' => \App\GetFiledInfo('user', 'realname', $v['jiaowu_teacher']), 'ziliao' => $v['ziliao'], 'student_id' => $v['student_id'], 'z_xm' => $v['z_xm'], 'beizhu' => $v['beizhu'], 'note' => $v['note'], 'creat_man' => $charge_person_name, 'create_time' => $v['create_time'], 'updateid' => $charge_person_name, 'update_time' => $v['update_time'],];
                    }
                    $data['student_class_list'] = $class_list;
                    $cas['group_name'] = $bumen;
                    $data['agent_name'] = $agent_name;
                    $data['project_name'] = $project_name_array;
                    $cas['group_id'] = $bumenid;
                    $data['follows_up'] = $genjin;
                    //跟进详情
                    $data['order'] = $order;
                    //成交详情
                    $data['orther'] = ['is_top' => $cas['is_top'] == 0 ? '已置顶' : '未置顶', 'sea_type' => $sea_type == 0 ? '私海' : '公海', 'status' => $cas['status'] == 0 ? '有效' : '无效', 'gongxiang' => '否', 'beigongxiang' => count(explode(',', $data['charge_person'])) > 2 ? '是' : '否', 'creat_name' => $cas['creat_name'], 'creat_time' => $data['create_time'], 'updateman' => $data['creat_name'], 'updatetime' => $data['update_time'], 'last_follow' => $this->di->follw->where('cid', $id)->and('status', 1)->order('now_time desc')->fetchOne('now_time'), 'follow_sort' => $this->di->follw->where('cid', $id)->and('status', 1)->count(), 'next_follow' => $cas['next_follow']];
                    $data['paid_total'] = $paid_total;
                    $data['refund_total'] = $refund_total;
                    $coumterlist['data'] = array_merge($cas, $data);
                    break;
                default:
                    break;
            }

            $this->redis->set_time('customer_info_'.$uid.'_'.$id.'_'.$bid,$coumterlist, 6000, 'customer');
            $coumterlist['redis_time']=$customer_info_time = $this->redis->get_time_ttl('customer_info_'.$uid.'_'.$id.'_'.$bid, 'customer');
            $rs = array('code' => 1, 'msg' => '000000', 'data' => $coumterlist, 'info' => array());
        }else{
            $rs = array('code' => 1, 'msg' => '000000', 'data' => $customer_info, 'info' => array());
        }
        return $rs;
    }
    //删除部门主管
    public function ReqeatUserList($uid,$cid,$pageno,$pagesize){
        $uid = isset($uid) && !empty($uid) ? intval($uid) :'';
        $cid = isset($cid) && !empty($cid) ? intval($cid) : '';
        $pageno = isset($pageno) ? intval($pageno) : 1;
        $pagesize = isset($pagesize) ? intval($pagesize) : $this->pagesize;
        $pagenum = ($pageno-1)*$pagesize;
        if($uid=='' || $cid=='' ){
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $data=$this->di->customer_log->select('id,cid,uid,type,action,addtime,ip')->where('type',9)->and('cid',$cid)->limit($pagenum,$pagesize)->fetchAll();
        if(!empty($data)){
            foreach ($data as $k=>$v){
                $list['newdata'][$k]=array(
                    'cname'=>\App\GetFiledInfo('customer','cname',$v['cid']),
                    'cphone'=>\App\GetFild('customer_data','cid',$v['cid'],'cphone'),
                    'uname'=>\App\GetFild('user','id',$v['uid'],'realname'),
                    'adress'=>\App\GetFild('customer_data','cid',$v['cid'],'city'),
                    'note'=>\App\GetFild('customer','id',$v['cid'],'note'),
                    'addtime'=>date("Y-m-d H:i:s",$v['addtime']),
                );

            }
        }else{
            $list['newdata'][]=[];
        }
        $list['count_total']=count($data);
        $rs = array('code'=>1,'msg'=>'000000','data'=>$list,'info'=> $list);
        return $rs;


    }
    //部门人数计算
    public function delStruDir($id,$pid,$uid,$dir){
        if(count($dir)>=1){
            foreach ($dir as $k=>$v){
                $user_info=$this->di->user->select('is_leader,structure_id,parent_id')->where('id',$v)->fetchOne();
                $structure_id = json_decode($user_info['structure_id']);
                if(in_array($id,$structure_id)){
                    if($pid==0){
                        //删除经理权限
                        $user_list[]=['id'=>$v,'is_leader'=>0,'parent_id'=> $user_info['parent_id']];
                    }else{
                        //删除主管权限
                        $user_list[]=['id'=>$v,'is_leader'=>0,'parent_id'=> $user_info['parent_id']];
                    }
                }else{
                    //不在本部门中
                    $string=$id.',';
                    if(($user_info['is_leader']==2 || $user_info['is_leader']==1) && $user_info['parent_id']==$string){
                        $user_list[]=['id'=>$v,'is_leader'=>$user_info['is_leader'],'parent_id'=> ''];
                    }else if(($user_info['is_leader']==2 || $user_info['is_leader']==1) && $user_info['parent_id']!=$string && $user_info['parent_id']!='' ){
                        $parent_id = str_ireplace($string,"",$user_info['parent_id']);
                        $user_list[]=['id'=>$v,'is_leader'=>3,'parent_id'=>$parent_id];
                    }else if($user_info['is_leader']==3  && $user_info['parent_id']==$string){
                        $user_list[]=['id'=>$v,'is_leader'=>0,'parent_id'=>''];
                    }else if($user_info['is_leader']==3  && $user_info['parent_id']!=$string){
                        $parent_id = str_ireplace($string,"",$user_info['parent_id']);
                        $user_list[]=['id'=>$v,'is_leader'=>3,'parent_id'=>$parent_id];
                    }
                }
            }

            if(count($user_list)>=1){
                foreach ($user_list as $k=>$v){
                    $alis=$this->di->user->where('id',$v['id'])->update(['is_leader'=>$v['is_leader'],'parent_id'=>$v['parent_id']]);
                }
            }

            if (!empty($alis)) {
                $rs = array('code'=>1,'msg'=>'修改成功!','data'=>array(),'info'=>array());
                return $rs;
            }
        }
    }
    //获取跟进列表
    public function pernum(){
        $list=$this->di->user->select('structure_id')->fetchAll();
        $strulist=$this->di->structure->fetchAll();
        $pernum_arr = array();
        foreach ($strulist as $k=>$v){
            $person_num = 0;
            foreach ($list as $m=>$n){
                $data=json_decode($n['structure_id']);
                $structure_id =array_pop($data);
                if ($structure_id == $v['id']) {
                    $person_num ++;
                }
            }
            $pernum_arr[$v['id']] = $person_num;

        }
        return  $pernum_arr;
    }
    //删除跟进  并记录日志
    public function GetDoFpList($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $type = isset($newData['type']) && !empty($newData['type']) ? intval($newData['type']) : 0;
        $keywords = isset($newData['keywords']) ? $newData['keywords'] : '';//搜索关键词
        $pageno = isset($newData['pageno']) ? intval($newData['pageno']) : 1;
        $where_arr = isset($newData['where_arr']) && !empty($newData['where_arr']) ? $newData['where_arr'] : array();
        $order_by = isset($newData['order_by']) && !empty($newData['order_by']) ? $newData['order_by'] : array();
        $pagesize = isset($newData['pagesize']) ? intval($newData['pagesize']) : $this->pagesize;
        $pagenum = ($pageno-1)*$pagesize;

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断用户角色(如果为部门领导则显示整个部门客户,如果为admin显示所有的客户)
        $user_info = $this->di->user->select("is_leader,status,structure_id,group_id")->where("id",$uid)->fetchOne();

        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }
        $params[':status'] = 1;
        if($user_info['group_id']==20){
            $follw_where = " 1 ";
        }else{
            $follw_where = " c.uid = ".$uid;
        }

        //高级搜索
        if (!empty($where_arr) && empty($keywords)) {
            $table_model = $this->config->get('common.TABLE_FIELD');
            $model_field = $table_model['cus_follow']['data_field'];
            $data_field_arr = array_keys($model_field);
            $senior_where = "";
            foreach ($where_arr as $key => $value) {
                if (is_array($value)) {
                    if ($value[0] == "" || $value[1] == "") {
                        continue;
                    }
                }
                if (!empty($value) || $value == 0) {

                    $key = in_array($key,$data_field_arr) ? 'c.'.$key : 'cd.'.$key;
                    if($key=='cd.id'){
                        $key='c.id';
                    }
                    # 其他
                    if (is_array($value) && is_numeric($value[0])) {
                        $senior_where .= " AND ( {$key} BETWEEN {$value[0]} AND {$value[1]} ) ";
                    } elseif (is_array($value)) {
                        $senior_where .= " AND ( UNIX_TIMESTAMP({$key}) between '{$value[0]}' AND '{$value[0]}' ) ";
                    } elseif (is_numeric($value)) {
                        if ($key == 'sc.charge_person') {
                            $senior_where .= " AND FIND_IN_SET('{$value}', charge_person) ";
                        }elseif (in_array($key,array("cd.cphone","cd.cphonetwo","cd.cphonethree"))) {
                            $senior_where .= " AND {$key} LIKE '{$value}%' ";
                        } else {
                            $senior_where .= " AND {$key} = {$value} ";
                        }
                    } else {
                        $senior_where .= " AND {$key} LIKE '%{$value}%' ";
                    }
                }
            }
        }else if($keywords !=''){
            $senior_where = " AND c.cname LIKE '%{$keywords}%' OR c.executor LIKE '%{$keywords}%' ";
        }

        $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        $follw_where1 = $follw_where." AND c.now_time > '{$beginToday}' AND c.now_time < '{$endToday}'  ";//今日新增公海
        $follw_where3 = $follw_where." AND INSTR('电话',c.types)";//今日回归公海
        $follw_where5 = $follw_where." AND INSTR('上门',c.types)";//今日回归公海
        $follw_where6 = $follw_where." AND INSTR('来访接待',c.types)";//今日回归公海
        $follw_where7 = $follw_where." AND INSTR('会议',c.types)";//今日回归公海
        $follw_where8 = $follw_where." AND INSTR('培训',c.types)";//今日回归公海
        $follw_where9 = $follw_where." AND INSTR('商务餐饮',c.types)";//今日回归公海
        $follw_where10 = $follw_where." AND INSTR('外出活动',c.types)";//今日回归公海
        $follw_where11 = $follw_where." AND NOT  FIND_IN_SET(c.types,'电话,外出活动,来访接待,会议,上门,培训,商务餐饮')";//今日回归公海
            # code...
        $total_arr = $this->redis->get_time('total_data_'.$uid,'customer');
        if ($total_arr=='') {
            # // 计算数量
            //（1）今日跟进数量
            $jr_count_sql = "SELECT c.id  FROM " . $this->prefix . "follw c LEFT JOIN " . $this->prefix . "customer cd ON c.cid = cd.id  LEFT JOIN " . $this->prefix . "user ct ON c.uid = ct.id WHERE " . $follw_where1 . "GROUP BY c.cid ";
            $customer_jr_num = $this->di->follw->queryAll($jr_count_sql, $params);
            $customer_jr_total = count($customer_jr_num);
            // (2)电话跟进数量
            $dhgh_count_sql = "SELECT c.id  FROM " . $this->prefix . "follw c LEFT JOIN " . $this->prefix . "customer cd ON c.cid = cd.id  LEFT JOIN " . $this->prefix . "user ct ON c.uid = ct.id WHERE " . $follw_where3 . "GROUP BY c.cid ";
            $customer_dhgh_num = $this->di->follw->queryAll($dhgh_count_sql, $params);
            $customer_dhgh_total = count($customer_dhgh_num);
            // (3)上门跟进数量
            $smgh_count_sql = "SELECT c.id  FROM " . $this->prefix . "follw c LEFT JOIN " . $this->prefix . "customer cd ON c.cid = cd.id  LEFT JOIN " . $this->prefix . "user ct ON c.uid = ct.id WHERE " . $follw_where5 . " GROUP BY c.cid ";
            $customer_smgh_num = $this->di->follw->queryAll($smgh_count_sql, $params);
            $customer_smgh_total = count($customer_smgh_num);
            // (4)来访接待跟进数量
            $lfgh_count_sql = "SELECT c.id FROM " . $this->prefix . "follw c LEFT JOIN " . $this->prefix . "customer cd ON c.cid = cd.id  LEFT JOIN " . $this->prefix . "user ct ON c.uid = ct.id WHERE " . $follw_where6 . " GROUP BY c.cid ";
            $customer_lfgh_num = $this->di->follw->queryAll($lfgh_count_sql, $params);
            $customer_lfgh_total = count($customer_lfgh_num);
            // (5)会议数量
            $hygh_count_sql = "SELECT c.id   FROM " . $this->prefix . "follw c LEFT JOIN " . $this->prefix . "customer cd ON c.cid = cd.id  LEFT JOIN " . $this->prefix . "user ct ON c.uid = ct.id WHERE " . $follw_where7 . " GROUP BY c.cid ";
            $customer_hygh_num = $this->di->follw->queryAll($hygh_count_sql, $params);
            $customer_hygh_total = count($customer_hygh_num);
            // (6)培训数量
            $pxgh_count_sql = "SELECT c.id  FROM " . $this->prefix . "follw c LEFT JOIN " . $this->prefix . "customer cd ON c.cid = cd.id  LEFT JOIN " . $this->prefix . "user ct ON c.uid = ct.id WHERE " . $follw_where8 . " GROUP BY c.cid ";
            $customer_pxgh_num = $this->di->follw->queryAll($pxgh_count_sql, $params);
            $customer_pxgh_total = count($customer_pxgh_num);
            // (7)商务餐饮数量
            $swgh_count_sql = "SELECT c.id   FROM " . $this->prefix . "follw c LEFT JOIN " . $this->prefix . "customer cd ON c.cid = cd.id  LEFT JOIN " . $this->prefix . "user ct ON c.uid = ct.id WHERE " . $follw_where9 . " GROUP BY c.cid ";
            $customer_swgh_num = $this->di->follw->queryAll($swgh_count_sql, $params);
            $customer_swgh_total = count($customer_swgh_num);
            // (8)外出活动数量
            $wcgh_count_sql = "SELECT c.id  FROM " . $this->prefix . "follw c LEFT JOIN " . $this->prefix . "customer cd ON c.cid = cd.id  LEFT JOIN " . $this->prefix . "user ct ON c.uid = ct.id WHERE " . $follw_where10 . " GROUP BY c.cid ";
            $customer_wcgh_num = $this->di->follw->queryAll($wcgh_count_sql, $params);
            $customer_wcgh_total = count($customer_wcgh_num);
            // (9)其他数量
            $qtgh_count_sql = "SELECT c.id  FROM " . $this->prefix . "follw c LEFT JOIN " . $this->prefix . "customer cd ON c.cid = cd.id  LEFT JOIN " . $this->prefix . "user ct ON c.uid = ct.id WHERE " . $follw_where11 . " GROUP BY c.cid ";
            $customer_qtgh_num = $this->di->follw->queryAll($qtgh_count_sql, $params);
            $customer_qtgh_total = count($customer_qtgh_num);
            if ($senior_where != '') {
                $all_count_sql = "SELECT c.id  FROM " . $this->prefix . "follw c LEFT JOIN " . $this->prefix . "customer cd ON c.cid = cd.id  LEFT JOIN " . $this->prefix . "user ct ON c.uid = ct.id WHERE " . $follw_where . $senior_where . " GROUP BY c.cid ";

            }
            else {
                $all_count_sql = "SELECT c.id  FROM " . $this->prefix . "follw c LEFT JOIN " . $this->prefix . "customer cd ON c.cid = cd.id  LEFT JOIN " . $this->prefix . "user ct ON c.uid = ct.id WHERE " . $follw_where . " GROUP BY c.cid ";

            }
            $customer_all_num = $this->di->follw->queryAll($all_count_sql, $params);
            $customer_all_total = count($customer_all_num);
            $total_data['customer_jr_total'] = $customer_jr_total;//今日跟进记录
            $total_data['customer_dhgh_total'] = $customer_dhgh_total;//
            $total_data['customer_smgh_total'] = $customer_smgh_total;//
            $total_data['customer_lfgh_total'] = $customer_lfgh_total;//
            $total_data['customer_hygh_total'] = $customer_hygh_total;//
            $total_data['customer_pxgh_total'] = $customer_pxgh_total;//
            $total_data['customer_swgh_total'] = $customer_swgh_total;//
            $total_data['customer_wcgh_total'] = $customer_wcgh_total;//
            $total_data['customer_qtgh_total'] = $customer_qtgh_total;//
//            $total_data['all_total'] =$customer_dhgh_total+$customer_smgh_total+$customer_lfgh_total+ $customer_qtgh_total+$customer_wcgh_total+$customer_swgh_total+$customer_pxgh_total+$customer_hygh_total;//
            $total_data['all_total'] = $customer_all_total;//
            $total_arr = $total_data;
            $this->redis->set_time('total_data_'.$uid,$total_data,6000,'customer');
        }
        // 关键词搜索
        if (!empty($keywords)) {
            $keywords_where = " AND c.cname LIKE '%{$keywords}%' ";
        } else {
            $keywords_where = "";
        }
        switch ($type) {
            case 1:
                # 今日跟进列表
                $sql = "SELECT c.*,ct.realname FROM ".$this->prefix."follw c LEFT JOIN ".$this->prefix."customer l ON c.cid=l.id LEFT JOIN ".$this->prefix."user ct ON c.uid = ct.id WHERE ".$follw_where1.$keywords_where.$senior_where." ORDER BY c.id DESC  LIMIT ".$pagenum.",".$pagesize;
                $count_sql = "SELECT count(c.id) as num FROM ".$this->prefix."follw c LEFT JOIN ".$this->prefix."user ct ON c.uid = ct.id "."WHERE".$follw_where1.$keywords_where.$senior_where;
                break;
            case 2:
                # 电话
                $sql = "SELECT c.*,ct.realname FROM ".$this->prefix."follw c LEFT JOIN ".$this->prefix."customer l ON c.cid=l.id LEFT JOIN ".$this->prefix."user ct ON c.uid = ct.id WHERE ".$follw_where3.$keywords_where.$senior_where." ORDER BY c.id DESC  LIMIT ".$pagenum.",".$pagesize;
                $count_sql = "SELECT count(c.id) as num FROM ".$this->prefix."follw c LEFT JOIN ".$this->prefix."user ct ON c.uid = ct.id "."WHERE".$follw_where3.$keywords_where.$senior_where;

                break;
            case 3:
                # 上门跟进数量
                $sql = "SELECT c.*,ct.realname FROM ".$this->prefix."follw c LEFT JOIN ".$this->prefix."customer l ON c.cid=l.id LEFT JOIN ".$this->prefix."user ct ON c.uid = ct.id WHERE ".$follw_where5.$keywords_where.$senior_where." ORDER BY c.id DESC  LIMIT ".$pagenum.",".$pagesize;
                $count_sql = "SELECT count(c.id) as num FROM ".$this->prefix."follw c LEFT JOIN ".$this->prefix."user ct ON c.uid = ct.id "."WHERE".$follw_where5.$keywords_where.$senior_where;
                break;
            case 4:
                # 电话
                $sql = "SELECT c.*,ct.realname FROM ".$this->prefix."follw c LEFT JOIN ".$this->prefix."customer l ON c.cid=l.id LEFT JOIN ".$this->prefix."user ct ON c.uid = ct.id WHERE ".$follw_where6.$keywords_where.$senior_where." ORDER BY c.id DESC  LIMIT ".$pagenum.",".$pagesize;
                $count_sql = "SELECT count(c.id) as num FROM ".$this->prefix."follw c LEFT JOIN ".$this->prefix."user ct ON c.uid = ct.id "."WHERE".$follw_where6.$keywords_where.$senior_where;
                break;
            case 5:
                # 电话
                $sql = "SELECT c.*,ct.realname FROM ".$this->prefix."follw c LEFT JOIN ".$this->prefix."customer l ON c.cid=l.id LEFT JOIN ".$this->prefix."user ct ON c.uid = ct.id WHERE ".$follw_where7.$keywords_where.$senior_where." ORDER BY c.id DESC  LIMIT ".$pagenum.",".$pagesize;
                $count_sql = "SELECT count(c.id) as num FROM ".$this->prefix."follw c LEFT JOIN ".$this->prefix."user ct ON c.uid = ct.id "."WHERE".$follw_where7.$keywords_where.$senior_where;
                break;
            case 6:
                # 电话
                $sql = "SELECT c.*,ct.realname FROM ".$this->prefix."follw c LEFT JOIN ".$this->prefix."customer l ON c.cid=l.id LEFT JOIN ".$this->prefix."user ct ON c.uid = ct.id WHERE ".$follw_where8.$keywords_where.$senior_where." ORDER BY c.id DESC  LIMIT ".$pagenum.",".$pagesize;
                $count_sql = "SELECT count(c.id) as num FROM ".$this->prefix."follw c LEFT JOIN ".$this->prefix."user ct ON c.uid = ct.id "."WHERE".$follw_where8.$keywords_where.$senior_where;
                break;
            case 7:
                # 电话
                $sql = "SELECT c.*,ct.realname FROM ".$this->prefix."follw c LEFT JOIN ".$this->prefix."customer l ON c.cid=l.id LEFT JOIN ".$this->prefix."user ct ON c.uid = ct.id WHERE ".$follw_where9.$keywords_where.$senior_where." ORDER BY c.id DESC  LIMIT ".$pagenum.",".$pagesize;
                $count_sql = "SELECT count(c.id) as num FROM ".$this->prefix."follw c LEFT JOIN ".$this->prefix."user ct ON c.uid = ct.id "."WHERE".$follw_where9.$keywords_where.$senior_where;
                break;
            case 8:
                # 电话
                $sql = "SELECT c.*,ct.realname FROM ".$this->prefix."follw c LEFT JOIN ".$this->prefix."customer l ON c.cid=l.id LEFT JOIN ".$this->prefix."user ct ON c.uid = ct.id WHERE ".$follw_where10.$keywords_where.$senior_where." ORDER BY c.id DESC  LIMIT ".$pagenum.",".$pagesize;
                $count_sql = "SELECT count(c.id) as num FROM ".$this->prefix."follw c LEFT JOIN ".$this->prefix."user ct ON c.uid = ct.id "."WHERE".$follw_where10.$keywords_where.$senior_where;
                break;
            case 9:
                # 电话
                $sql = "SELECT c.*,ct.realname FROM ".$this->prefix."follw c LEFT JOIN ".$this->prefix."customer l ON c.cid=l.id LEFT JOIN ".$this->prefix."user ct ON c.uid = ct.id WHERE ".$follw_where11.$keywords_where.$senior_where." ORDER BY c.id DESC  LIMIT ".$pagenum.",".$pagesize;
                $count_sql = "SELECT count(c.id) as num FROM ".$this->prefix."follw c LEFT JOIN ".$this->prefix."user ct ON c.uid = ct.id "."WHERE".$follw_where11.$keywords_where.$senior_where;
                break;
            default:
                # 全部跟进
                $sql = "SELECT c.*,ct.realname FROM ".$this->prefix."follw c LEFT JOIN ".$this->prefix."customer l ON c.cid=l.id LEFT JOIN ".$this->prefix."user ct ON c.uid = ct.id WHERE ".$follw_where.$keywords_where.$senior_where." ORDER BY c.id DESC LIMIT ".$pagenum.",".$pagesize;
                $count_sql = "SELECT count(c.id) as num FROM ".$this->prefix."follw c LEFT JOIN ".$this->prefix."user ct ON c.uid = ct.id "."WHERE".$follw_where.$keywords_where.$senior_where;
                break;
        }

        // 公海客户列表数据
        $customer_gh_list = $this->di->follw->queryAll($sql, $params);
        $customer_gh_num = $this->di->follw->queryAll($count_sql, $params);
        $customer_gh_data['customer_gh_list'] = !empty($customer_gh_list) ? $customer_gh_list : array();
        $customer_gh_data['customer_gh_num'] = !empty($customer_gh_num[0]['num']) ? intval($customer_gh_num[0]['num']) : 0;
        $customer_gh_data['total_count'] = !empty($total_arr) ? $total_arr : array();

        $rs = array('code'=>1,'msg'=>'000000','data'=>$customer_gh_data,'info'=>$customer_gh_data);
        return $rs;
    }
    // 批量更改部门
    public function DeleFp($id,$uid){
        $uid = isset($uid) && !empty($uid) ? intval($uid) :'';
        $id = isset($id) && !empty($id) ? intval($id) : '';
        if($uid==''||$id==''){
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $list=$this->di->follw->select('status')->where('id',$id)->and('uid',$uid)->fetchOne();

        if(isset($list['status'])&& $list['status']!=0){
            $list=$this->di->follw->where('id',$id)->update(['status'=>0]);
            if($list){
//                $li= $this->di->customer->where('id',$id)->update(['follw_time'=>$data['creat_time'],'follow_up' => new \NotORM_Literal("follow_up + 1")]);
                \App\setlog($uid,3,'删除跟进成功','成功','删除跟进成功列表id:'.$id,'删除跟进成功');
                $rs = array('code'=>1,'msg'=>'999998','data'=>array(),'info'=>array());
            }else{
                $rs = array('code'=>0,'msg'=>'999981','data'=>array(),'info'=>array());
            }
        }else{
            $rs = array('code'=>0,'msg'=>'999981','data'=>array(),'info'=>array());

        }
        return $rs;
    }
    //更改部门
    public function PostChangeGroup($uid,$groupid,$cid_arr) {
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($cid_arr)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($groupid)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $group_success='';
        $group_error='';
        if (count($cid_arr) > 1) {
            // 批量
            foreach ($cid_arr as $key => $value) {
                // 客户基本信息
                $customer_info = $this->di->customer->select("id,cname,creatid,charge_person")->where(array("id"=>$value,"status"=>1))->fetchOne();
                if (empty($customer_info)) {
                    continue;
                }
                $customer_data = $this->di->customer_data->select("groupid,cphone,wxnum")->where(array("cid"=>$cid))->fetchOne();
                if (!empty($customer_data['cphone'])) {
                    $field_key = "cphone";
                } else {
                    $field_key = "wxnum";
                }
                // 4-13 判断本部门中是否已存在这个手机号(xiaozhe加)
                $customer_datas = $this->di->customer_data->where(array($field_key=>$customer_data[$field_key],"groupid"=>$groupid))->fetchOne("cid");
                if (!empty($customer_datas)) {
                    $group_error .= $customer_info['cname'].',';
                    continue;
                }

                if ($customer_info['creatid'] == $uid) {
                    // 满足更改部门的条件
                    $throw_group = $this->di->customer_data->where(array("cid"=>$value))->update(array("groupid"=>$groupid));
                    if ($throw_group) {
                        // 更改部门成功
                        $group_success .= $customer_info['cname'].',';
                        // 增加更改部门操作日志
                        \App\Cus_log($uid,$value,10,'更改部门成功',$customer_info['cname'].'更改到部门'.$groupid);
                    } else {
                        // 更改部门失败
                        $group_error .= $customer_info['cname'].',';
                        \App\Cus_log($uid,$value,10,'更改部门失败',$customer_info['cname'].'更改到部门'.$groupid);
                    }
                }else{
                    // 更改部门失败
                    $group_error .= $customer_info['cname'].',';
                    \App\Cus_log($uid,$value,10,'更改部门失败',$customer_info['cname'].'更改到部门'.$groupid);
                }
            }

            $msg = "";
            if ($group_success) {
                $msg .= "客户:".trim($group_success,",")."成功更改部门；";
            }
            if ($group_error) {
                $msg .= "客户:".trim($group_error,",")."更改部门失败；";
            }
        } else {
            $cid = $cid_arr[0];
            // 客户基本信息
            $customer_info = $this->di->customer->select("id,cname,creatid")->where(array("id"=>$cid,"status"=>1))->fetchOne();

            if ($customer_info['creatid'] == $uid) {
                // 满足扔回公海的条件
                $throw_group = $this->di->customer_data->where(array("cid"=>$cid))->update(array("groupid"=>$groupid));
                if ($throw_group) {
                    // 扔回公海成功
                    $msg =  "客户:".$customer_info['cname']."成功更改部门！";
                    // 增加扔回公海操作日志
                    \App\Cus_log($uid,$cid,10,'更改部门成功',$customer_info['cname'].'更改到部门'.$groupid);
                    \App\setlog($uid,2,'更改部门成功','成功','更改部门成功客户id:'.$cid.'更改到部门:'.$groupid,'更改部门成功');
                } else {
                    // 扔回公海失败

                    \App\Cus_log($uid,$cid,10,'更改部门失败',$customer_info['cname'].'更改到部门'.$groupid);
                }
            }
        }
        if (empty($msg)) {
            $msg = "更改部门失败！您不是创建人!";
            $rs = array('code'=>0,'msg'=>$msg,'data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>1,'msg'=>$msg,'data'=>array(),'info'=>array());
        }
        return $rs;
    }
    //移交客户
   public function PostTransferAll($uid, $Transid, $cid_arr)
    {
        if (empty($cid_arr)) {
            //批量选择的客户id
            $rs = array('code' => 0, 'msg' => '000003', 'data' => array(), 'info' => array());
            return $rs;
        }
        if (empty($Transid)) {
            //被移交用户id
            $rs = array('code' => 0, 'msg' => '000003', 'data' => array(), 'info' => array());
            return $rs;
        }
        $group_success = '';
        $group_error = '';
        $user_info = $this->di->user->where('id', $Transid)->fetchOne('structure_id');
        $user_info2 = $this->di->user->where('id', $uid)->fetchOne();
        $user_info3 = $this->di->user->where('id', $Transid)->fetchOne();
        $pid = json_decode($user_info);

        $beshare_user_share=$this->di->structure->where('id',end($pid))->fetchOne('share');
        if(!empty($beshare_user_share) && $beshare_user_share != 'all'){
            $beshare_user_share_array=explode(',',$beshare_user_share);
            foreach ($beshare_user_share_array as $k=>$v){
                if(empty($v)){
                    unset($beshare_user_share_array[$k]);
                }
            }
        }else{
            $beshare_user_share_array=['0'=>end($pid)];
        }
        $beshare_user_info = \App\Getkey($Transid,array("realname","structure_id","share","capacity","setlimit","getlimit"));
//        $pid2=json_decode($user_info2['structure_id']);
        $customer=new Customer();
        $admin_common = new AdminCommon();

        //判断是否是管理员
        if (count($cid_arr) >= 1) {
            // 批量
            foreach ($cid_arr as $key => $value) {
                // 客户基本信息
                $customer_info = $this->di->customer->select("id,cname,creatid,charge_person")->where(array("id" => $value, "status" => 1))->fetchOne();
                $ocreatid = $this->di->customer_data->select('ocreatid,wxnum,cphone,cphonetwo,cphonethree,telephone,groupid')->where(array("cid" => $value))->fetchOne();
                if (empty($customer_info)) {
                    $err_msg[]= "客户:" .$customer_info['cname']."信息不存在,不能移交!";
                    continue;
                }
//                $creat_struct = $this->di->user->where('id', $customer_info['creatid'])->fetchOne('structure_id');
                $repeart_array=$customer->GetStructByPhone($ocreatid,$beshare_user_info['share']);
                if(isset($repeart_array['sh_arr']) && count($repeart_array['sh_arr'])>0){
                    foreach ($repeart_array['sh_arr'] as $k=>$v){
                        if($v['cid']!=$value){
                            $err_msg2[]= "客户:" .$customer_info['cname']."所在部门已存在,不能移交!";
                        }
                    }
                }
                if(count($err_msg2)>0){
                   $err_msg[]= $err_msg2;
                    continue; 
        
                }
                if($user_info2['group_id'] !=20 ){
                    
                    if(!in_array($ocreatid['groupid'],$beshare_user_share_array)){
                            $err_msg[]= "客户:" . $customer_info['cname'] . "只能同部门移交!";
                            continue;
                    }
                    if ($uid != $customer_info['creatid']) {
                        if( $user_info2['is_leader']==0){
                            $err_msg[]=  "客户:" . $customer_info['cname'] .'无权限操作。说明：（1）只有创建人、创建人的主管、系统管理员可以移交创建人。（2）移交创建人只能部门内部移交。';
                            continue;
                        }
                    }
                }
                if ($Transid == $customer_info['creatid']) {
                    $success_msg[]= "客户:" . $customer_info['cname'] . " 创建人为被移交人,无需移交!";
                    continue;
                }
                if($user_info3['setlimit']!=0 ){
                    if($user_info3['setlimit'] <= $user_info3['getlimit']){
                        if($user_info2['group_id'] !=20){
                            $err_msg[]=$user_info3['realname'] . '的私海数据已满'.$user_info3['setlimit'].'人,无法移交，移交失败！';
                            continue;
                        }
                    }
                }
                \PhalApi\DI()->notorm->beginTransaction('db_master');
                $chargeperson = explode(',', $customer_info['charge_person']);
                foreach ($chargeperson as $k => $v) {
                    if ((!empty($v) && $v == $uid) || $v==$customer_info['creatid']) {
                        unset($chargeperson[$k]);
                    }else if (empty($v)) {
                        unset($chargeperson[$k]);
                    }else if ($v == $Transid) {
                        $Tshare = $this->di->share_join->select('bid,id')->where('beshare_uid', $Transid)->and('cid', $value)->and('creat_id', $uid)->fetchOne();
                        if (!empty($Tshare)) {
                            $this->di->share_customer->where('id', $Tshare['bid'])->delete();
                            $this->di->share_join->where('id', $Tshare['id'])->delete();
                        }
                        unset($chargeperson[$k]);
                    }
                }
                if (!empty($chargeperson) && is_array($chargeperson)) {
                    $tranbid = $Transid . ',' . implode(',', $chargeperson) . ',';//重新拼接负责人字符串
                }else {
                    $tranbid = $Transid . ',';//重新拼接负责人字符串
                }
                if ($user_info2['group_id'] == '20' || $user_info2['is_leader'] != '0' || $customer_info['creatid'] == $uid) {
                    //超级管理员
                    $join_list = $this->di->share_join->and('cid', $value)->fetchAll();//查询分享表中分配的客户数据
                    $turealname = \App\GetFiledInfo('user', 'realname', $Transid);
                    $cus = $this->di->customer->where(array("id" => $value))->update(array("creatid" => $Transid, "charge_person" => $tranbid));
                    //更新主表中的负责人和创建人
                    if (count($join_list) >= 1) {
                        //第一步,删除无效数据
                        foreach ($join_list as $k => $v) {
                            $new_join['creat_id'] = $Transid;
                            $new_join['share_uid'] = $Transid;
                            if ($Transid == $v['beshare_uid']) {
                                //如果被分配人的id等于移交人id的话
                                //删除分享数据
                                $this->di->share_join->where('id', $v['id'])->delete();
                                $this->di->share_customer->where('id', $v['bid'])->delete();
                            }
                            else {
                                $this->di->share_join->where('id', $v['id'])->update($new_join);
                                $new_join_list = $this->di->share_customer->where('id', $v['bid'])->update(['creatid' => $Transid]);
                                if ($new_join_list === false) {
                                    \PhalApi\DI()->notorm->rollback('db_master');
                                    \App\Cus_log($uid, $value, 11, '移交失败', $user_info2['realname'].' 将客户:'.$customer_info['cname'] . '移交客户给 ' . $turealname . '失败,已回滚');
                                }
                            }
                        }
                    }
                    if ($cus !== false) {
                        if ($ocreatid['ocreatid'] == 0) {
                            $this->di->customer_data->where('cid', $value)->update(['groupid'=>end($pid),'ocreatid' => $customer_info['creatid']]);
                        }else{
                            $this->di->customer_data->where('cid', $value)->update(['groupid'=>end($pid)]);
                        }
                        \App\SetLimit($Transid,'1');
                        \App\SetLimit($uid,-1);
                        \App\SetLimit($customer_info['creatid'],-1);
                        \PhalApi\DI()->notorm->commit('db_master');
                        $group_success .= $customer_info['cname'] . ',';
                        $success_msg[] ="客户:" . $customer_info['cname'] . "成功移交！";
                        $msg ="客户:" . $customer_info['cname'] . "成功移交！";
                        \App\Cus_log($uid, $value, 11, '移交客户成功', $user_info2['realname'].' 将客户:'.$customer_info['cname'] . '移交给 ' . $turealname  . '成功');
                        $content = $user_info2['realname'].' 将客户:'.$customer_info['cname'] . '移交给 ' . $turealname  . '成功';
                        $admin_common->SendMsgNow($uid,$Transid,$msg,$content, 1, "change", 'customer', $value);
                    }
                    else {
                        \PhalApi\DI()->notorm->rollback('db_master');//更新失败回滚
                        \App\Cus_log($uid, $value, 11, '移交失败', $user_info2['realname'].' 将客户:'.$customer_info['cname'] . '移交给 ' . $turealname . '失败!');
                        $err_msg[]=  "客户:" . $customer_info['cname'] .'查找不到基础数据!移交失败,已回撤!';
                        continue;
                    }
                }else {
                    //不是的话 不能移交
                    \PhalApi\DI()->notorm->rollback('db_master');
                    $group_error .= $customer_info['cname'] . ',';
                    \App\Cus_log($uid, $value, 10, '移交失败', '客户:' . $customer_info['cname'] . '移交失败');
                    $err_msg[]=  "客户:" . $customer_info['cname'] .'查找不到基础数据!移交失败,已回撤!';
                    continue;
                }
            }
            $data_msg = array(
                'success' => $success_msg,
                'error' => $err_msg
            );

        }
        $this->redis->flushDB('customer');
        /** @var TYPE_NAME $rs */
        $rs =array('code' => 1, 'msg' => '000000', 'data' => $data_msg, 'info' => array());
        return $rs;
    }
    //编辑开票信息
    public function AddTicket($data){
        $uid = isset($data['uid']) && !empty($data['uid']) ? intval($data['uid']) :'';
        $cid = isset($data['cid']) && !empty($data['cid']) ? intval($data['cid']) : '';
        $order_no = isset($data['order_no']) && !empty($data['order_no']) ? $data['order_no'] : '';
        $project_name = isset($data['project_name']) && !empty($data['project_name']) ? $data['project_name'] : '';
        $agent_name = isset($data['agent_name']) && !empty($data['agent_name']) ? $data['agent_name'] : '';
        $project_contract = isset($data['project_contract']) && !empty($data['project_contract']) ? $data['project_contract'] : '';
        $title = isset($data['title']) && !empty($data['title']) ? $data['title'] : '';
        $total = isset($data['total']) && !empty($data['total']) ? $data['total'] : '';
        $invoice_company = isset($data['invoice_company']) && !empty($data['invoice_company']) ? $data['invoice_company'] : '';
        $taxpayer_num = isset($data['taxpayer_num']) && !empty($data['taxpayer_num']) ? $data['taxpayer_num'] : '';
        $open_types = isset($data['open_types']) && !empty($data['open_types']) ? $data['open_types'] : '';
        $bank = isset($data['bank']) && !empty($data['bank']) ? $data['bank'] : '';
        $open_bank = isset($data['open_bank']) && !empty($data['open_bank']) ? $data['open_bank'] : '';
        $bank_num = isset($data['bank_num']) && !empty($data['bank_num']) ? $data['bank_num'] : '';
        $bank_adress = isset($data['bank_adress']) && !empty($data['bank_adress']) ? $data['bank_adress'] : '';
        $bank_phone = isset($data['bank_phone']) && !empty($data['bank_phone']) ? $data['bank_phone'] : '';
        $tickets_open_bank = isset($data['tickets_open_bank']) && !empty($data['tickets_open_bank']) ? $data['tickets_open_bank'] : '';
        $tickets_company = isset($data['tickets_company']) && !empty($data['tickets_company']) ? $data['tickets_company'] : '';
        $tickets_num = isset($data['tickets_num']) && !empty($data['tickets_num']) ? $data['tickets_num'] : '';
        $tickets_bank = isset($data['tickets_bank']) && !empty($data['tickets_bank']) ? $data['tickets_bank'] : '';
        $tickets_bank_num = isset($data['tickets_bank_num']) && !empty($data['tickets_bank_num']) ? $data['tickets_bank_num'] : '';
        $tickets_bank_adress = isset($data['tickets_bank_adress']) && !empty($data['tickets_bank_adress']) ? $data['tickets_bank_adress'] : '';
        $tickets_phone = isset($data['tickets_phone']) && !empty($data['tickets_phone']) ? $data['tickets_phone'] : '';
        $notes = isset($data['notes']) && !empty($data['notes']) ? $data['notes'] : '';
        $file = isset($data['file']) && !empty($data['file']) ? $data['file'] : '';
        $tickets_time = isset($data['tickets_time']) && !empty($data['tickets_time']) ? $data['tickets_time'] : '';
        if($uid=='' || $cid==''  || $total=='' ){
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        //查询是否存在订单号,存在则不能添加
        $status=$this->di->customer_invoice->select('id')->where('order_no',$order_no)->fetchOne();
        if($status){
            \App\setlog($uid,1,'新建开票','失败','开票信息已存在,不能新建!开票id:'.$status['id'],'新建开票失败');
            $rs = array('code'=>0,'msg'=>'开票信息已存在,不能新建!','data'=>array(),'info'=>array());
            return $rs;
        }
        $newdata=array(
            'uid'=>$uid,
            'cid'=>$cid,
            'order_no'=>$order_no,
            'project_name'=>$project_name,
            'agent_name'=>$agent_name,
            'project_contract'=>$project_contract,
            'title'=>$title,
            'total'=>$total,
            'invoice_company'=>$invoice_company,
            'taxpayer_num'=>$taxpayer_num,
            'bank'=>$bank,
            'open_bank'=>$open_bank,
            'open_types'=>$open_types,
            'bank_num'=>$bank_num,
            'bank_adress'=>$bank_adress,
            'bank_phone' =>$bank_phone,
            'tickets_company'=>$tickets_company,
            'tickets_num'=>$tickets_num,
            'tickets_open_bank'=>$tickets_open_bank,
            'tickets_bank'=>$tickets_bank,
            'tickets_bank_num' =>$tickets_bank_num,
            'tickets_bank_adress'=>$tickets_bank_adress,
            'tickets_phone'=>$tickets_phone,
            'notes'=>$notes,
            'file'=>$file,
            'tickets_time' =>$tickets_time,
            'status'=>1,
            'addtime' =>time(),
        );

        $status=$this->di->customer_invoice->insert($newdata);
        if($status){
            \App\setlog($uid,1,'新建开票','成功','新建开票成功!开票客户id:'.$cid,'新建开票成功');
            $rs = array('code'=>1,'msg'=>'999995','data'=>array(),'info'=>array());

        }else{
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
        }
        return $rs;
    }
    //删除开票信息
    public function EditTicket($data){
        $uid = isset($data['uid']) && !empty($data['uid']) ? intval($data['uid']) :'';
        $id = isset($data['id']) && !empty($data['id']) ? intval($data['id']) : '';
        $project_name = isset($data['project_name']) && !empty($data['project_name']) ? $data['project_name'] : '';
        $agent_name = isset($data['agent_name']) && !empty($data['agent_name']) ? $data['agent_name'] : '';
        $project_contract = isset($data['project_contract']) && !empty($data['project_contract']) ? $data['project_contract'] : '';
        $title = isset($data['title']) && !empty($data['title']) ? $data['title'] : '';
        $total = isset($data['total']) && !empty($data['total']) ? $data['total'] : '';
        $invoice_company = isset($data['invoice_company']) && !empty($data['invoice_company']) ? $data['invoice_company'] : '';
        $taxpayer_num = isset($data['taxpayer_num']) && !empty($data['taxpayer_num']) ? $data['taxpayer_num'] : '';
        $bank = isset($data['bank']) && !empty($data['bank']) ? $data['bank'] : '';
        $open_types = isset($data['open_types']) && !empty($data['open_types']) ? $data['open_types'] : '';
        $open_bank = isset($data['open_bank']) && !empty($data['open_bank']) ? $data['open_bank'] : '';
        $bank_num = isset($data['bank_num']) && !empty($data['bank_num']) ? $data['bank_num'] : '';
        $bank_adress = isset($data['bank_adress']) && !empty($data['bank_adress']) ? $data['bank_adress'] : '';
        $bank_phone = isset($data['bank_phone']) && !empty($data['bank_phone']) ? $data['bank_phone'] : '';
        $tickets_company = isset($data['tickets_company']) && !empty($data['tickets_company']) ? $data['tickets_company'] : '';
        $tickets_open_bank = isset($data['tickets_open_bank']) && !empty($data['tickets_open_bank']) ? $data['tickets_open_bank'] : '';
        $tickets_num = isset($data['tickets_num']) && !empty($data['tickets_num']) ? $data['tickets_num'] : '';
        $tickets_bank = isset($data['tickets_bank']) && !empty($data['tickets_bank']) ? $data['tickets_bank'] : '';
        $tickets_bank_num = isset($data['tickets_bank_num']) && !empty($data['tickets_bank_num']) ? $data['tickets_bank_num'] : '';
        $tickets_bank_adress = isset($data['tickets_bank_adress']) && !empty($data['tickets_bank_adress']) ? $data['tickets_bank_adress'] : '';
        $tickets_phone = isset($data['tickets_phone']) && !empty($data['tickets_phone']) ? $data['tickets_phone'] : '';
        $notes = isset($data['notes']) && !empty($data['notes']) ? $data['notes'] : '';
        $file = isset($data['file']) && !empty($data['file']) ? $data['file'] : '';
        $tickets_time = isset($data['tickets_time']) && !empty($data['tickets_time']) ? $data['tickets_time'] : '';
        if($uid=='' || $id==''  || $total=='' ){
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        $newdata=array(
            'project_name'=>$project_name,
            'agent_name'=>$agent_name,
            'project_contract'=>$project_contract,
            'title'=>$title,
            'total'=>$total,
            'invoice_company'=>$invoice_company,
            'taxpayer_num'=>$taxpayer_num,
            'bank'=>$bank,
            'open_bank'=>$open_bank,
            'bank_num'=>$bank_num,
            'bank_adress'=>$bank_adress,
            'bank_phone' =>$bank_phone,
            'open_types' =>$open_types,
            'tickets_company'=>$tickets_company,
            'tickets_open_bank'=>$tickets_open_bank,
            'tickets_num'=>$tickets_num,
            'tickets_bank'=>$tickets_bank,
            'tickets_bank_num' =>$tickets_bank_num,
            'tickets_bank_adress'=>$tickets_bank_adress,
            'tickets_phone'=>$tickets_phone,
            'notes'=>$notes,
            'file'=>$file,
            'tickets_time'=>$tickets_time,
            'updatetime' =>time(),
        );

        $status=$this->di->customer_invoice->where('id',$id)->update($newdata);
        if($status){
            \App\setlog($uid,2,'编辑开票','成功','编辑开票成功!开票列表id:'.$id.'开票金额:'.$total,'编辑开票成功');
            $rs = array('code'=>1,'msg'=>'999995','data'=>array(),'info'=>array());

        }else{
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
        }
        return $rs;
    }
    //添加退款
    public function DeleTicket($uid,$id){
        $uid = isset($uid) && !empty($uid) ? intval($uid) :'';
        $id = isset($id) && !empty($id) ? intval($id) : '';
        if($uid=='' || $id==''){
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $list=$this->di->customer_invoice->where('id',$id)->fetchOne();

        if($list && $list['uid']==$uid){
            $status=$this->di->customer_order->where('order_no',$list['order_no'])->fetchOne();
            if($status==1){
                $this->di->customer_order->where('id',$status['id'])->update(['invoice'=>0]);
                //更新成交信息表中的开票状态
            }
            $this->di->customer_invoice->where('id',$id)->update(['status'=>0]);
            \App\setlog($uid,3,'删除开票','成功','删除开票成功!开票列表id:'.$id,'删除开票成功');
            $rs = array('code'=>1,'msg'=>'999993','data'=>array(),'info'=>array());
        }else{
            $rs = array('code'=>1,'msg'=>'999994','data'=>array(),'info'=>array());
        }

        return $rs;
    }
    //撞单列表
    public function AddRefund($data){
        $uid = isset($data['uid']) && !empty($data['uid']) ? intval($data['uid']) :'';
        $cid = isset($data['cid']) && !empty($data['cid']) ? intval($data['cid']) : '';
        $order_no = isset($data['order_no']) && !empty($data['order_no']) ? $data['order_no'] : '';
        $project_name = isset($data['project_name']) && !empty($data['project_name']) ? $data['project_name'] : '';
        $project_contract = isset($data['project_contract']) && !empty($data['project_contract']) ? $data['project_contract'] : '';
        $agent_name = isset($data['agent_name']) && !empty($data['agent_name']) ? $data['agent_name'] : '';
        $agent_contract = isset($data['agent_contract']) && !empty($data['agent_contract']) ? $data['agent_contract'] : '';
        $invoice_company = isset($data['invoice_company']) && !empty($data['invoice_company']) ? $data['invoice_company'] : '';
        $project = isset($data['project']) && !empty($data['project']) ? $data['project'] : '';
        $refund_note = isset($data['refund_note']) && !empty($data['refund_note']) ? $data['refund_note'] : '';
        $opens_account = isset($data['opens_account']) && !empty($data['opens_account']) ? $data['opens_account'] : '';
        $opens_bank = isset($data['opens_bank']) && !empty($data['opens_bank']) ? $data['opens_bank'] : '';
        $opens_num = isset($data['opens_num']) && !empty($data['opens_num']) ? $data['opens_num'] : '';
        $refund_type = isset($data['refund_type']) && !empty($data['refund_type']) ? $data['refund_type'] : '';
        $refund_time = isset($data['refund_time']) && !empty($data['refund_time']) ? $data['refund_time'] : '';
        $refund_total = isset($data['refund_total']) && !empty($data['refund_total']) ? $data['refund_total'] : '';
        $notes = isset($data['note']) && !empty($data['note']) ? $data['note'] : '';
        $file = isset($data['file']) && !empty($data['file']) ? $data['file'] : '';
        if($uid=='' || $cid=='' || $invoice_company==''){
            $rs = array('code'=>0,'msg'=>'开票公司不能为空!','data'=>array(),'info'=>array());
            return $rs;
        }
        $newdata=array(
            'uid' =>$uid,
            'cid'=>$cid,
            'order_no'=>$order_no,
            'project_name'=>$project_name,
            'project_contract'=>$project_contract,
            'agent_name'=>$agent_name,
            'agent_contract'=>$agent_contract,
            'invoice_company'=>$invoice_company,
            'project'=>$project,
            'refund_note'=>$refund_note,
            'opens_account'=>$opens_account,
            'opens_bank'=>$opens_bank,
            'opens_num'=>$opens_num,
            'refund_type'=>$refund_type,
            'refund_time'=>$refund_time,
            'refund_total'=>$refund_total,
            'note'=>$notes,
            'file'=>$file,
            'addtime'=>time(),
            'status'=>1
        );

        $status=$this->di->customer_refund->insert($newdata);
        if($status){
            $this->di->customer_order->where('order_no',$order_no)->and('status',1)->update(['status'=>2]);
            \App\setlog($uid,1,'添加退款成功','成功','添加退款成功!客户id:'.$cid.'退款金额:'.$refund_total,'添加退款成功');
            $rs = array('code'=>1,'msg'=>'999995','data'=>array(),'info'=>array());

        }else{
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
        }
        return $rs;
    }
    //编辑退款
    public function EditRefund($data){
        $uid = isset($data['uid']) && !empty($data['uid']) ? intval($data['uid']) :'';
        $id = isset($data['id']) && !empty($data['id']) ? intval($data['id']) : '';
        $order_no = isset($data['order_no']) && !empty($data['order_no']) ? $data['order_no'] : '';
        $project_name = isset($data['project_name']) && !empty($data['project_name']) ? $data['project_name'] : '';
        $project_contract = isset($data['project_contract']) && !empty($data['project_contract']) ? $data['project_contract'] : '';
        $agent_name = isset($data['agent_name']) && !empty($data['agent_name']) ? $data['agent_name'] : '';
        $agent_contract = isset($data['agent_contract']) && !empty($data['agent_contract']) ? $data['agent_contract'] : '';
        $invoice_company = isset($data['invoice_company']) && !empty($data['invoice_company']) ? $data['invoice_company'] : '';
        $project = isset($data['project']) && !empty($data['project']) ? $data['project'] : '';
        $refund_note = isset($data['refund_note']) && !empty($data['refund_note']) ? $data['refund_note'] : '';
        $opens_account = isset($data['opens_account']) && !empty($data['opens_account']) ? $data['opens_account'] : '';
        $opens_bank = isset($data['opens_bank']) && !empty($data['opens_bank']) ? $data['opens_bank'] : '';
        $opens_num = isset($data['opens_num']) && !empty($data['opens_num']) ? $data['opens_num'] : '';
        $refund_type = isset($data['refund_type']) && !empty($data['refund_type']) ? $data['refund_type'] : '';
        $refund_time = isset($data['refund_time']) && !empty($data['refund_time']) ? $data['refund_time'] : '';
        $refund_total = isset($data['refund_total']) && !empty($data['refund_total']) ? $data['refund_total'] : '';
        $note = isset($data['note']) && !empty($data['note']) ? $data['note'] : '';
        $file = isset($data['file']) && !empty($data['file']) ? $data['file'] : '';
        if($uid=='' || $id=='' ){
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $newdata=array(
            'order_no'=>$order_no,
            'project_name'=>$project_name,
            'project'=>$project,
            'project_contract'=>$project_contract,
            'agent_name'=>$agent_name,
            'agent_contract'=>$agent_contract,
            'invoice_company'=>$invoice_company,
            'refund_note'=>$refund_note,
            'opens_account'=>$opens_account,
            'opens_bank'=>$opens_bank,
            'opens_num'=>$opens_num,
            'refund_type'=>$refund_type,
            'refund_time'=>$refund_time,
            'refund_total'=>$refund_total,
            'note'=>$note,
            'file'=>$file,
            'updatetime'=>time(),
        );

        $status=$this->di->customer_refund->where('id',$id)->update($newdata);
        if($status){
            $this->di->customer_order->where('order_no',$order_no)->and('status',1)->update(['status'=>2]);
            \App\setlog($uid,2,'编辑退款成功','成功','编辑退款成功!客户id:'.$id.'退款金额:'.$refund_total,'编辑退款成功');
            $rs = array('code'=>1,'msg'=>'999995','data'=>array(),'info'=>array());

        }else{
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
        }
        return $rs;
    }
    //删除退款
    public function DeleRefund($uid,$id){
        $uid = isset($uid) && !empty($uid) ? intval($uid) :'';
        $id = isset($id) && !empty($id) ? intval($id) : '';
        if($uid=='' || $id==''){
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $list=$this->di->customer_refund->where('id',$id)->and('uid',$uid)->fetchOne();
        if($list){
            $list=$this->di->customer_refund->where('id',$id)->delete();
            \App\setlog($uid,3,'删除退款信息成功','成功','删除退款成功!列表id:'.$id,'删除退款成功');
            $rs = array('code'=>1,'msg'=>'999993','data'=>array(),'info'=>array());
        }else{
            $rs = array('code'=>0,'msg'=>'999994','data'=>array(),'info'=>array());
        }

        return $rs;
    }
    //客户详情接口中的成交开票和退款
    public function GetInfoList($newData){
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $cid = isset($newData['cid']) && !empty($newData['cid']) ? intval($newData['cid']) : 0;
        $type = isset($newData['type']) && !empty($newData['type']) ? intval($newData['type']) : 0;
        $pageno = isset($newData['pageno']) ? intval($newData['pageno']) : 1;
        $pagesize = isset($newData['pagesize']) ? intval($newData['pagesize']) : $this->pagesize;
        $pagenum = ($pageno-1)*$pagesize;
        if (empty($uid) ) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        // 判断用户角色(如果为部门领导则显示整个部门客户,如果为admin显示所有的客户)
        $user_info = $this->di->user->select("is_leader,status,structure_id")->where("id",$uid)->fetchOne();
        // 账户被禁用
        if ($user_info['status'] == 0) {
            $rs = array('code'=>0,'msg'=>'000053','data'=>array(),'info'=>array());
            return $rs;
        }
        // 账户未激活
        if ($user_info['status'] == 2) {
            $rs = array('code'=>0,'msg'=>'999986','data'=>array(),'info'=>array());
            return $rs;
        }
        //如果是创建人的话
        $creat_id=$this->di->customer->where('creatid',$uid)->fetchOne();
        if($creat_id){
            $where = " c.cid = '{$cid}' AND  c.status = 1";
        }else{
            $where = " c.cid = '{$cid}' AND   c.deal_id = '{$uid}'  AND  c.status = 1";
        }

        switch ($type) {

            case 1:
                # 成交情况
                $sql = "SELECT c.*,l.title as jianzhang FROM ".$this->prefix."customer_order c LEFT JOIN ".$this->prefix."general_rules l ON l.id=c.general_id LEFT JOIN ".$this->prefix."customer cd ON c.cid = cd.id WHERE ".$where . " ORDER BY c.deal_time DESC LIMIT " . $pagenum . "," . $pagesize;
                // $sql = "SELECT * FROM " . $this->prefix . "customer_order WHERE " .$where . " ORDER BY deal_time DESC LIMIT " . $pagenum . "," . $pagesize;
                $count_sql = "SELECT count(c.id) as num FROM " . $this->prefix . "customer_order c WHERE " .  $where;
                $customer_list = $this->di->customer_order->queryAll($sql);
                $customer_num = $this->di->customer_order->queryAll($count_sql);
                break;
            case 2:
                $where = " c.cid = '{$cid}' AND  c.status = 1";
                # 开票情况
                $sql = "SELECT c.*,cd.cname,ct.id as chengjiaoid FROM " . $this->prefix . "customer_invoice c LEFT JOIN ".$this->prefix."customer cd ON cd.id=c.cid  LEFT JOIN ".$this->prefix."customer_order ct ON c.order_no = ct.order_no  WHERE " .$where . " ORDER BY c.tickets_time DESC LIMIT " . $pagenum . "," . $pagesize;
                $count_sql = "SELECT count(c.id) as num FROM " . $this->prefix . "customer_invoice c WHERE " .  $where;
                $customer_list = $this->di->customer_invoice->queryAll($sql);
                $customer_num = $this->di->customer_invoice->queryAll($count_sql);
                break;
            case 3:
                # 退款情况
                $where = " c.cid = '{$cid}' AND  c.status = 1";
                $sql = "SELECT c.*,cd.cname FROM " . $this->prefix . "customer_refund c LEFT JOIN ".$this->prefix."customer cd ON cd.id=c.cid WHERE " .$where . " ORDER BY c.addtime DESC LIMIT " . $pagenum . "," . $pagesize;
                $count_sql = "SELECT count(c.id) as num FROM " . $this->prefix . "customer_refund c WHERE " .  $where;
                $customer_list = $this->di->customer_refund->queryAll($sql);
                $customer_num = $this->di->customer_refund->queryAll($count_sql);
                break;
            default:
                return false;
                break;
        }

        $customer_data['customer_list'] = !empty($customer_list) ? $customer_list : array();
        $customer_data['customer_num'] = !empty($customer_num[0]['num']) ? $customer_num[0]['num'] : 0;
        $customer_data['total_count'] = !empty($total_arr) ? $total_arr : array();
        $rs = array('code'=>1,'msg'=>'000000','data'=>$customer_data,'info'=>$customer_data);

        return $rs;

    }
    //获取订单
    public function getOrder($uid,$cid){
        # 成交情况
        $where = " c.cid = '{$cid}' AND c.uid = '{$uid}' AND c.status = 1";
        $sql = "SELECT c.*,l.title as jianzhang ,ct.*FROM ".$this->prefix."customer_order c LEFT JOIN ".$this->prefix."general_rules l ON l.id=c.general_id LEFT JOIN ".$this->prefix."customer cd ON c.cid = cd.id  LEFT JOIN ".$this->prefix."customer_data ct ON c.cid = ct.cid WHERE ".$where;
//                $sql = "SELECT * FROM " . $this->prefix . "customer_order WHERE " .$where . " ORDER BY deal_time DESC LIMIT " . $pagenum . "," . $pagesize;
        $count_sql = "SELECT count(c.id) as num FROM " . $this->prefix . "customer_order c WHERE " .  $where;

        $customer_list = $this->di->customer_order->queryAll($sql);
        $customer_num = $this->di->customer_order->queryAll($count_sql);
        $customer_data['customer_list'] = !empty($customer_list) ? $customer_list : array();
        $customer_data['customer_num'] = !empty($customer_num[0]['num']) ? $customer_num[0]['num'] : 0;
        $customer_data['total_count'] = !empty($total_arr) ? $total_arr : array();
        $rs = array('code'=>1,'msg'=>'000000','data'=>$customer_data,'info'=>$customer_data);
        return $rs;

    }
    //获取操作日志
    public function GetCusLog($uid,$cid){
        $uid = isset($uid) && !empty($uid) ? intval($uid) : '';
        $cid = isset($cid) && !empty($cid) ? intval($cid) : '';
        if($uid=='' || $cid==''){
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        $where1 = " c.cid = '{$cid}' AND c.type <> 9";
        $sql = "SELECT c.*,cd.cname as cll,l.realname,c.action as ac,c.note as action FROM ".$this->prefix."customer_log c LEFT JOIN ".$this->prefix."customer cd ON cd.id=c.cid LEFT JOIN ".$this->prefix."user l ON c.uid=l.id  WHERE " . $where1." ORDER BY c.id DESC ";

        $customer_list = $this->di->customer_log->queryAll($sql);
//        foreach ($customer_list as $k=>$v){
//            $customer_list[$k]['action']=preg_replace("/\\d+/",'****', $v['action']);
//        }
        $customer_data['customer_list'] = !empty($customer_list) ? $customer_list : array();
        $rs = array('code'=>1,'msg'=>'000000','data'=>$customer_data,'info'=>$customer_data);
        return $rs;
    }
    //获取客户订单详情
    public function PostOrderInfo($uid,$id,$type){
        $uid = isset($uid) && !empty($uid) ? intval($uid) : 0;
        $id = isset($id) && !empty($id) ? intval($id) : 0;
        $type = isset($type) && !empty($type) ? intval($type) : 0;
        if (empty($uid) ) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        // 判断用户角色(如果为部门领导则显示整个部门客户,如果为admin显示所有的客户)
        $user_info = $this->di->user->select("is_leader,status,structure_id")->where("id",$uid)->fetchOne();
        // 账户被禁用
        if ($user_info['status'] == 0) {
            $rs = array('code'=>0,'msg'=>'000053','data'=>array(),'info'=>array());
            return $rs;
        }
        // 账户未激活
        if ($user_info['status'] == 2) {
            $rs = array('code'=>0,'msg'=>'999986','data'=>array(),'info'=>array());
            return $rs;
        }
        $where = " c.id = '{$id}'  AND c.status = 1";
        switch ($type) {

            case 1:
                # 成交情况
                $sql = "SELECT c.*,l.title as jianzhang,cs.realname as chengjiaoren ,cf.name as chengjiaobumen FROM ".$this->prefix."customer_order c LEFT JOIN ".$this->prefix."general_rules l ON l.id=c.general_id LEFT JOIN ".$this->prefix."customer cd ON c.cid = cd.id LEFT JOIN ".$this->prefix."user cs ON c.deal_id = cs.id LEFT JOIN ".$this->prefix."structure cf ON c.structure_id = cf.id WHERE ".$where;

                $customer_list = $this->di->customer_order->queryAll($sql);
                break;
            case 2:
                # 开票情况
                $sql = "SELECT c.*,cd.cname,cs.realname as chuangjianren FROM " . $this->prefix . "customer_invoice c LEFT JOIN ".$this->prefix."customer cd ON cd.id=c.cid LEFT JOIN ".$this->prefix."user cs ON c.uid = cs.id WHERE " .$where;
                $customer_list = $this->di->customer_invoice->queryAll($sql);

                break;
            case 3:
                # 退款情况
                $sql = "SELECT c.*,cd.cname FROM " . $this->prefix . "customer_refund c LEFT JOIN ".$this->prefix."customer cd ON cd.id=c.cid WHERE " .$where;
                $customer_list = $this->di->customer_refund->queryAll($sql);
                break;
            default:
                return false;
                break;
        }
        $customer_data['customer_list'] = !empty($customer_list) ? $customer_list : array();

        $rs = array('code'=>1,'msg'=>'000000','data'=>$customer_data,'info'=>$customer_data);

        return $rs;
    }
    //获取客户订单
    public function GetUserOrder($uid,$type){
        $uid = isset($uid) && !empty($uid) ? intval($uid) : 0;
        $type = isset($type) && !empty($type) ? intval($type) : 1;
        if (empty($uid) ) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        //客户名称 成交编号  客户电话
        $params=[];
//        $data=$this->di->customer_order->where('deal_id',$uid)->and('status',1)->fetchAll();
        $student_where=" cd.deal_id = ".$uid." AND cd.status = 1";
        $sql = "SELECT cd.*,c.cphone,rs.title as xm_name ,rs.education,sc.title as school_name ,ma.title as margi_title FROM ".$this->prefix."customer_order cd LEFT JOIN ".$this->prefix."customer_data c ON c.cid = cd.cid LEFT JOIN ".$this->prefix."general_rules rs ON rs.id = cd.general_id LEFT JOIN ".$this->prefix."school sc ON sc.id = rs.school_id  LEFT JOIN ".$this->prefix."major ma ON ma.id = rs.major_id  WHERE ".$student_where;

        $project_stu_list = $this->di->project_side->queryAll($sql, $params);
        $rs = array('code'=>1,'msg'=>'000000','data'=>$project_stu_list,'info'=>$project_stu_list);
        return $rs;

    }
    //导出日志
    public function CusExport($uid,$cid,$type,$quantity){
        $uid = isset($uid) && !empty($uid) ? intval($uid) : 0;
        $cid = isset($cid) && !empty($cid) ? $cid : 0;
        $type = isset($type) && !empty($type) ? $type : 1;
        $quantity = isset($quantity) && !empty($quantity) ? intval($quantity) : 0;
        if (empty($uid) || empty($cid) || empty($quantity)) {
            $rs = array('code'=>0,'msg'=>'参数有误,uid和cid以及数量不能为空','data'=>array(),'info'=>array());
            return $rs;
        }
        if($type==1){
            $typename='私海';
        }else{
            $typename='公海';
        }
        $newdata=array(
            'uid'=>$uid,
            'type'=>4,
            'ip'=>\PhalApi\Tool::getClientIp(),
            'operation'=>'导出'.$quantity.'条客户记录',
            'sqldata'=>'成功',
            'contont'=>$cid,
            'title'=>$type.','.$typename.',导出成功',
            'creat_time'=>date('Y-m-d H:i:s',time()),
        );

        $this->di->dolog->insert($newdata);
        $rs = array('code'=>1,'msg'=>'导出成功','data'=>array(),'info'=>array());
        return $rs;
    }
    //获取客户导出日志
    public function GetCusExport($uid,$type,$pageno,$pagesize){
        $uid = isset($uid) && !empty($uid) ? intval($uid) :'';
        $type = isset($type) && !empty($type) ? intval($type) : '1';
        $pageno = isset($pageno) ? intval($pageno) : 1;
        $pagesize = isset($pagesize) ? intval($pagesize) : $this->pagesize;
        $pagenum = ($pageno-1)*$pagesize;
        if (empty($uid) || empty($type)) {
            $rs = array('code'=>0,'msg'=>'参数有误,uid和type以及数量不能为空','data'=>array(),'info'=>array());
            return $rs;
        }
        if($type==1){
            $title=$type.',私海,导出成功';
            $list=$this->di->dolog->where('type',4)->and('title',$title)->limit($pagenum,$pagesize)->fetchAll();
            $newdata['total']=$this->di->dolog->where('type',4)->and('title',$title)->count();
        }else if($type==2){
            $title=$type.',公海,导出成功';
            $list=$this->di->dolog->where('type',4)->and('title',$title)->limit($pagenum,$pagesize)->fetchAll();
            $newdata['total']=$this->di->dolog->where('type',4)->and('title',$title)->count();
        }else{

            $list=$this->di->dolog->where('type',4)->or('title','1,私海,导出成功')->or('title','2,私海,导出成功')->limit($pagenum,$pagesize)->fetchAll();
            $newdata['total']=$this->di->dolog->where('type',4)->or('title','1,私海,导出成功')->or('title','2,私海,导出成功')->count();
        }

        if($list){
            $customer_list = array();
            foreach ($list as $key => $value) {
                $cus_new_list = array();
                $cus_list=explode(',',$value['contont']);
                foreach ($cus_list as $k=>$v){
                    $cus_new_list[]=array(
                        'cname'=>\App\GetFiledInfo('customer','cname',$v),
                        'cphone'=>\App\GetFild('customer_data','cid',$v,'cphone'),
                    );
                }
                $customer_list[$key]['user_name'] = \App\GetFiledInfo('user','realname',$value['uid']);
                $customer_list[$key]['creat_time'] = $value['creat_time'];
                $customer_list[$key]['note'] = $value['operation'];
                $customer_list[$key]['cus_info_list'] = $cus_new_list;
            }
            $newdata['list'] = $customer_list;

            // foreach($list as $k=>$v){
            //     $cus_list=explode(',',$v['contont']);
            //     foreach ($cus_list as $n=>$m){
            //         $cus_new_list[]=array(
            //             'cname'=>\App\GetFiledInfo('customer','cname',$m),
            //             'cphone'=>\App\GetFild('customer_data','cid',$m,'cphone'),
            //         );
            //     }

            //     $newdata['list'][]=array(
            //         'user_name'=>\App\GetFiledInfo('user','realname',$v['uid']),
            //         'creat_time'=>$v['creat_time'],
            //         'note'=>$v['operation'],
            //         'cus_info_list'=>$cus_new_list,
            //     );
            // }
        }else{
            $newdata[]=[];
        }

        $rs = array('code'=>1,'msg'=>'000000','data'=>$newdata,'info'=>$newdata);
        return $rs;


    }
    //修改头像
    public function ChangeImg($type,$uid,$id,$img){
        $uid = isset($uid) && !empty($uid) ? intval($uid) : 0;
        $type = isset($type) && !empty($type) ? intval($type) : 0;
        $id = isset($id) && !empty($id) ? intval($id) : 0;
        $img = isset($img) && !empty($img) ? $img : '';
        if (empty($uid) || empty($id) || empty($type) || $img=='') {
            $rs = array('code'=>0,'msg'=>'参数有误,uid和type以及数量不能为空','data'=>array(),'info'=>array());
            return $rs;
        }
        switch ($type){
            case 1:
                $msg=$this->di->customer_data->where('id',$id)->update(['cimg'=>$img]);
                if($msg){
                    $rs = array('code'=>1,'msg'=>'修改成功','data'=>array(),'info'=>array());

                }else{
                    $rs = array('code'=>0,'msg'=>'修改失败','data'=>array(),'info'=>array());
                }
                return $rs;
                break;
            default:
                break;
        }
    }
    //获取喜好字段
    public function GetLoveFiled($uid,$title,$type){
        $status=$this->di->model_field->where('modelid',$type)->and('field',$title)->fetchOne();
        if($status){
            $field_list=preg_replace('/["\[\]]/', '',$status['setting']);
            $field_list_array=explode(',',$field_list);
            if(is_array( $field_list_array)){foreach( $field_list_array as $k=>$value){
                $data[$value]=['count'=>$this->di->customer->where($title,$value)->and('FIND_IN_SET('.$uid.',charge_person)')->count()];
            }}
            array_multisort(array_column($data,'count'),SORT_DESC,$data);
            foreach (array_keys($data) as $k=>$value){
                $newdata[$k]='"'.$value.'"';
            };
            $newdata='['.implode(',',$newdata).']';
            return $newdata;
        }else{

        }

    }
    //验证是否存在重复手机号
    public function GetPhoneRepeat($uid,$phone,$wx,$group){
        $uid = isset($uid) && !empty($uid) ? intval($uid) : 0;
        $phone = isset($phone) && !empty($phone) ? trim($phone," "): 0;
        $wx = isset($wx) && !empty($wx) ? $wx:0;
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'参数有误,uid和phone以及数量不能为空','data'=>array(),'info'=>array());
            return $rs;
        }
        $pid=isset($group) && !empty($group)?reset($group):reset(json_decode(\App\GetFiledInfo('user','structure_id',$uid)));
        $group_id=isset($group) && !empty($group)?end($group):end(json_decode(\App\GetFiledInfo('user','structure_id',$uid)));
        $share=$this->di->structure->where('id',$group_id)->fetchOne();
        $nrepeat=$this->di->structure->where('id',$pid)->fetchOne();
        $capacity=$share['capacity'];
        if($nrepeat['nrepeat']==1){
            if(!empty($phone)){

                $where_join=" FIND_IN_SET('".$phone."',CONCAT_WS(',',A.cphone,A.cphonetwo,A.cphonethree,A.telephone)) ";

            }else if (!empty($wx)){
                $where_join=" A.wxnum = ".'"'.$wx.'"';
            }else if(empty($wx) && empty($phone) ){
                $rs = array('code'=>1,'msg'=>'微信号或手机号不能为空','data'=>array(),'info'=>array());
                return $rs;
            }
        }else{
            if(!empty($phone) && empty($wx)){
                $where_join=" FIND_IN_SET('".$phone."',CONCAT_WS(',',A.cphone,A.cphonetwo,A.cphonethree,A.telephone)) ";
            }else if(!empty($phone) && !empty($wx)){
                $where_join=" FIND_IN_SET('".$phone."',CONCAT_WS(',',A.cphone,A.cphonetwo,A.cphonethree,A.telephone)) ";
                $where_join=$where_join." OR A.wxnum = ".'"'.$wx.'"';
            }else if(!empty($wx) && empty($phone)){
                $where_join=" A.wxnum = ".'"'.$wx.'"';
            }else{
                $rs = array('code'=>0,'msg'=>'微信号不能为空','data'=>array(),'info'=>array());
                return $rs;
            }

        }

        $where2=$where_join;

        switch ($capacity){
            case 1:
                //中台
                if(!empty($share['share']) && $share['sea_type']==1 && $share['share']!='all,'){
                    $where2.= " AND FIND_IN_SET(A.`groupid`,"."'".$share['share']."'".")";
                    $where_join.= " AND FIND_IN_SET(B.`groupid`,"."'".$share['share']."'".")";

                }else if($share['share']=='all,'){
                    $where2 .= " AND A.`groupid` =" . $group_id;
                    $where_join .= " AND B.`groupid` =" . $group_id;
                }else if(empty($share['share']) && $share['sea_type']==1) {
                    $where2 .= " AND A.`groupid` =" . $group_id;
                    $where_join .= " AND B.`groupid` =" . $group_id;
                }
                $rs=$this->SetrepeatData($capacity,$where2,$where_join,$uid,$phone,$wx,$share['share']);
                break;
            case 2:
                //前台
                $rs=$this->SetrepeatData($capacity,$where2,$where_join,$uid,$phone,$wx,$share['share']);
                break;
            case 3:
                //渠道
                $rs=$this->SetrepeatData($capacity,$where2,$where_join,$uid,$phone,$wx,$share['share']);
                break;
            case 4:
                //后台
                $rs=$this->SetrepeatData($capacity,$where2,$where_join,$uid,$phone,$wx,$share['share']);
                break;
            default:
                //未设置部门的都按前台算
                $rs=$this->SetrepeatData($capacity,$where2,$where_join,$uid,$phone,$wx,$share['share']);
                break;
        }
        return $rs;
    }
    //执行撞单查询
    public function SetrepeatData($type,$where2,$where_join,$uid,$phone,$wx,$share){

        $list_data=$this->di->customer_data->select('A.cid,A.cphone,A.cphonetwo,A.cphonethree,A.telephone,A.id,A.create_time,A.wxnum,A.groupid,B.sea_type')->alias('A')->leftJoin('customer', 'B', 'A.cid = B.id')->where($where2)->order('A.create_time ASC')->fetchAll();
        $list_share = $this->di->share_join->select('A.cid,A.cphone,A.cphonetwo,A.cphonethree,A.telephone,A.id,A.create_time,A.wxnum,B.*')->alias('B')->leftJoin('customer_data', 'A', 'A.cid = B.cid')->where($where_join)->and('B.status = 1')->order('A.create_time ASC')->fetchAll();
        $list=array_merge($list_data,$list_share);
        switch ($type){
            case 1:
                //中台撞单->提醒
                //中台
                if (count($list) > 0) {
                    $rs = $this->Getyouxian($list, $phone, $wx, $uid, $type, $share);
                }
                else {
                    $rs = array('code' => 1, 'msg' => '无撞单!', 'data' => [], 'info' => array());
                }
                return $rs;
                break;
            case 2:

                if(count($list)>0){

                  $rs= $this->Getyouxian($list,$phone,$wx,$uid,$type,$share);
                }else{
                    $rs = array('code'=>1,'msg'=>'无撞单!','data'=>[],'info'=>array());
                }
                return $rs;
                //前台
                break;
            case 3:
                if (count($list) > 0) {
                    $rs = $this->Getyouxian($list, $phone, $wx, $uid, $type, $share);
                }
                else {
                    $rs = array('code' => 1, 'msg' => '无撞单!', 'data' => [], 'info' => array());
                }
                return $rs;
                break;
            case 4:
                if (count($list) > 0) {
                    $rs = $this->Getyouxian($list, $phone, $wx, $uid, $type, $share);
                }
                else {
                    $rs = array('code' => 1, 'msg' => '无撞单!', 'data' => [], 'info' => array());
                }
                return $rs;
                break;
            default:

                if(count($list_data)>0){
                    $rs= $this->tixing($list_data,$phone,$wx,$uid,$type,$share);
                }else{
                    $rs = array('code'=>1,'msg'=>'无撞单!','data'=>[],'info'=>array());
                }
                return $rs;
                //未设置部门的都按前台算
                break;
        }


    }
    //验证是否存在重复手机号
    public function GetPhoneRepeatList($uid,$group,$phone){
        $uid = isset($uid) && !empty($uid) ? intval($uid) : 0;
        $phone = isset($phone) && !empty($phone) ? trim($phone," "): 0;
//        $group =\App\GetFiledInfo('structure','share',$group);
        $group ='all';
        $admin_common = new Customer();
        $phone_arr = array(
            'cphone' =>$phone,
            'cphonetwo' => $phone,
            'cphonethree' => $phone,
            'telephone' => $phone,
            'wxnum' => $phone
        );

        $list=$admin_common->GetStructByPhone($phone_arr,$group);

        if(count($list)>1){
            if(!empty($list['sh_arr'])){
                foreach ($list['sh_arr'] as $k=>$v){
                    $fpr=$this->di->user->where("FIND_IN_SET(id,'".$v['charge_person']."') ")->fetchPairs('id','realname');
                    $fpr=implode(',',$fpr);
                    $tx_follw_uid=$this->di->follw->where('cid',$v['cid'])->order('now_time desc')->group('uid')->fetchPairs('id','uid');
                    $tx_follw_uid=!empty($tx_follw_uid)?$tx_follw_uid:'';
                    $last_follw=!empty($tx_follw_uid)?\App\GetFiledInfo('user','realname',reset($tx_follw_uid)):'没有跟进人';
                    $fpr_zg=$this->di->share_join->where('cid',$v['cid'])->and('from_type',1)->fetchOne('share_uid');
                    $fpr_zg=empty($fpr_zg)?'':\App\GetFiledInfo('user','realname',$fpr_zg);
                    $group_name_model=\App\GetStruMedel($v['groupid']);
                    $phone_array[] = array(
                        'id'=>"{$k}",
                        'cname' => $v['cname'],
                        'creatname' => \App\GetFiledInfo('user', 'realname', $v['creatid']),
                        'sea_type' => $v['sea_type'] == 0 ? '私海' : '公海',
                        'cphone' => !empty($wx) ? $wx : $phone,
                        'cid' => $v['cid'],
                        'distributor' => $fpr_zg,
                        'executor' => $fpr,
                        'last_follw' => $last_follw,
                        'bumen' => $group_name_model,
                        'create_time' => $v['create_time'],
                        'clinet'=>$this->GetGX($list['gx_arr'],$v['cid'])
                    );

                }
            }
            $rs = array('code'=>2,'msg'=>'999982','data'=>$phone_array,'info'=>array());
        }else{
            $rs = array('code'=>1,'msg'=>'无重复手机号!','data'=>[],'info'=>array());
        }
        //查询撞单记录 并赋值时间
        return $rs;
    }
    //
    public function  GetGX($gx,$cid){
        if(!empty($gx)){
            foreach ($gx as $n=>$m){
                if($cid==$m['cid']){
                    $group_name_model=\App\GetStruMedel($m['share_groupid']);
                    $gx_array[]=array(
                        'beshare_name'=>\App\GetFiledInfo('user','realname',$m['beshare_uid']),
                        'sea_type'=>$m['sea_type']==0?'私海':'公海',
                        'bumen'=>$group_name_model,//共享的部门
                        'create_time'=>$m['addtime'],
                    );
                }
            }
        }
        return  $gx_array;
    }
    //获取数据优先级
    public function Getyouxian($data, $phone, $wx, $uid, $type, $share)
    {
        //            1:中台 2:前台 3:渠道 4:后台
        $structure_id = $this->di->user->where('id', $uid)->fetchOne('structure_id');
        $pid = end(json_decode($structure_id));
        $sea_type = $this->di->structure->where('id', $pid)->fetchOne('sea_type');
        if ($sea_type == 1) {
            if ($share == '' || $share == 'all,' || $share != 'all') {
                $stru_array = ['0' => $pid];
            }
            else {
                $stru_array = explode(',', $share);
            }
        }
        else {
            $stru_array = ['0' => $pid];
        }
        foreach ($data as $k => $v) {
            if (!empty($v['cid'])) {
                $cp = $this->di->structure->where('id', $v['groupid'])->fetchOne('capacity');
                switch ($cp) {
                    case 3:
                        $qd[] = $v;
                        break;
                    case 1:
                        $zt[] = $v;
                        break;
                    case 2:
                        $qt[] = $v;
                        break;
                    case 4:
                        $ht[] = $v;
                        break;
                    default:
                        $qt[] = $v;
                        //不存在的话按照前台算
                        break;
                }
            }

        }

        switch ($type) {
            case 2:
                  //前台
                //判断本部门是否有数据    
                if (!empty($qt)) {
                    foreach ($qt as $k => $v) {
                        if (array_key_exists('bid', $v) && !empty($v['bid'])) {
                            //共享数据
                            if (in_array($v['groupid'], $stru_array)) {
                                  $benbumen_share[] = $v;
                                //type =3 本部门存在共享数据.不允许录入
                            }
                            else {
                                  $waibumen_share[] = $v;
                                //type =4 共享给其他前台的数据不允许录入可以共享
                            }
                        }
                        else {
                             if (in_array($v['groupid'], $stru_array)) {
                                $benbumen_old[] = $v;
                                //type =3 本前台部门私海(创建)存在不允许录入
                            }
                            else {
                              
                                $waibumen_old[] = $v;
                              //type =4 其他前台私海存在不允许录入可以共享

                            }
                            //创建数据
                        }
                    }

                    if(!empty($benbumen_old)){
                        foreach ($benbumen_old as $n=>$m){
                            if($m['sea_type']==1){
                                if($m['creatid']==$uid){
                                    $benbumen_data[]=$m;
                                    $rs = $this->GetNewdata($benbumen_data, $phone, $wx, $uid, '1');
                                    return $rs;
                                }else{
                                    $benbumen_data[]=$m;
                                    $rs = $this->GetNewdata($benbumen_data, $phone, $wx, $uid, '1');
                                    return $rs;
                                }
                            }
                        }
                        $rs = $this->GetNewdata($benbumen_old, $phone, $wx, $uid, '3');
                        return $rs;
                    }else if(!empty($benbumen_share)){

                        foreach ($benbumen_share as $key => $value) {
                            if($value['sea_type']==1){
                                 //type =1 其他部门共享给本部门的数据此数据在本部门公海不允许录入可以启用
                                $benbumen_data_share[]=$value;
                                $rs = $this->GetNewdata($benbumen_data_share, $phone, $wx, $uid, '1');
                                 return $rs;
                            }else{
                                //type =3 本前台部门私海(创建)存在不允许录入
                                $benbumen_data_share[]=$value;
                                $rs = $this->GetNewdata($benbumen_data_share, $phone, $wx, $uid, '3');
                                return $rs;
                            }
                        }
                        
                    }else if(!empty($waibumen_old)){
                        $rs = $this->GetNewdata($waibumen_old, $phone, $wx, $uid, '4');
                        return $rs;
                    }
                      
                }
                //渠道优先级最大,如果有共享数据或者有创建数据
                if (isset($qd) && count($qd) > 0 && (!empty($waibumen_share) || !empty($waibumen_old))) {
                    // type=6  渠道存在,可以共享

                       $rs = $this->GetNewdata($qd, $phone, $wx, $uid, '6');
                       return $rs; 
                }else if (isset($qd) && count($qd) > 0) {
                    // type=7  中台存在,可以共享
                    $rs = $this->GetNewdata($qd, $phone, $wx, $uid, '7');
                    return $rs;
                }else if (isset($zt) && count($zt) > 0 && (!empty($waibumen_share) || !empty($waibumen_old))) {
                    // type=7  中台存在,可以共享
                        $rs = $this->GetNewdata($zt, $phone, $wx, $uid, '7');
                        return $rs;
                }else if (isset($zt) && count($zt) > 0) {
                    // type=7  中台存在,可以共享
                    $rs = $this->GetNewdata($zt, $phone, $wx, $uid, '7');
                    return $rs;
                }else if (isset($ht) && count($ht) > 0) {
                    // type=8  后台存在,不能共享
                    $rs = $this->GetNewdata($ht, $phone, $wx, $uid, '8');
                    return $rs;
                }
                break;
            default:
                $rs = $this->GetOrtherMsg($stru_array, $data, $phone, $wx, $uid);
                return $rs;
                break;
        }

    }

    public function GetOrtherMsg($stru_array, $data, $phone, $wx, $uid)
    {
        if (!empty($data)) {

            foreach ($data as $k => $v) {
                if (array_key_exists('bid', $v) && !empty($v['bid'])) {
                    //共享数据
                    if (in_array($v['groupid'], $stru_array)) {
                        $benbumen_share[] = $v;
                        //type =3 本部门存在共享数据.不允许录入
                    }
                    else {
                        $waibumen_share[] = $v;
                        //type =4 共享给其他前台的数据不允许录入可以共享
                    }
                }
                else {
                    if (in_array($v['groupid'], $stru_array)) {

                        $benbumen_old[] = $v;
                        //type =3 本前台部门私海(创建)存在不允许录入
                    }
                    else {

                        $waibumen_old[] = $v;
                        //type =4 其他前台私海存在不允许录入可以共享
                    }
                    //创建数据
                }
            }

            if (!empty($benbumen_old)) {
                foreach ($benbumen_old as $k => $v) {
                    if ($v['sea_type'] == 1 ) {
                        if(!empty($benbumen_share)){
                            foreach ($benbumen_share as $n => $m) {
                                if ($m['sea_type'] == 1) {
                                    if($v['beshare_uid']==$uid){

                                        $rs = $this->GetNewdata($m, $phone, $wx, $uid, '1');
                                        return $rs;
                                    }
                                }
                            }
                        }
                        $benbumen_old_data[]=$v;
                        $rs = $this->GetNewdata($benbumen_old_data, $phone, $wx, $uid, '1');
                        return $rs;
                    }else if($v['sea_type'] == 0 && !empty($benbumen_share)){

                        foreach ($benbumen_share as $key => $value) {
                            if ($value['sea_type'] == 1) {
                                if($value['beshare_uid']==$uid){
                                    $benbumen_data[]=$value;
                                    $rs = $this->GetNewdata($benbumen_data, $phone, $wx, $uid, '1');
                                    return $rs;
                                }else{
                                    //type =1 其他部门共享给本部门的数据此数据在本部门公海不允许录入可以启用
                                    $benbumen_data[]=$value;
                                    $rs = $this->GetNewdata($benbumen_data, $phone, $wx, $uid, '1');
                                    return $rs;
                                }
                            }else {
                                //type =3 本前台部门私海(创建)存在不允许录入
                                $rs = $this->GetNewdata($benbumen_share, $phone, $wx, $uid, '3');
                                return $rs;
                            }
                        }
                    }else{
                        $rs = $this->GetNewdata($benbumen_old, $phone, $wx, $uid, '3');
                        return $rs;
                    }


                }
            }
            else if (!empty($benbumen_share)) {
                foreach ($benbumen_share as $key => $value) {
                    if ($value['sea_type'] == 1) {
                        //type =1 其他部门共享给本部门的数据此数据在本部门公海不允许录入可以启用
                        $rs = $this->GetNewdata($benbumen_share, $phone, $wx, $uid, '1');
                        return $rs;
                    }
                    else {
                        //type =3 本前台部门私海(创建)存在不允许录入
                        $rs = $this->GetNewdata($benbumen_share, $phone, $wx, $uid, '3');
                        return $rs;
                    }
                }

            }
            else if (!empty($waibumen_old)) {
                $rs = $this->GetNewdata($waibumen_old, $phone, $wx, $uid, '10');
                return $rs;
            }

        }
    }


    //获取提醒
    public function tixing($list, $phone, $wx, $uid, $type)
    {
        if (count($list) >= 1) {
            foreach ($list as $k => $v) {
                if (!empty($v['cid'])) {
                    $new_list[$v['cid']] = $v;
                }
            }
            foreach ($new_list as $k => $v) {
                $creatid_new = isset($v['creat_id']) ? $v['creat_id'] : \App\GetFiledInfo('customer', 'creatid', $v['cid']);
                $fpr = $this->di->share_join->where('cid', $v['cid'])->and('from_type', 1)->fetchOne('share_uid');
                $fzr = $this->di->customer->where('id', $v['cid'])->and('sea_type', 0)->fetchOne('charge_person');
                $tx_follw_uid = $this->di->follw->where('cid', $v['cid'])->order('now_time desc')->group('uid')->fetchPairs('id', 'uid');
                $share_count = $this->di->share_join->where("cid = {$v['cid']} AND to_days(FROM_UNIXTIME(addtime,'%Y-%m-%d')) = to_days(now())")->count('id');
                if (!empty($fzr)) {
                    $user = $this->di->user->select('realname')->where("FIND_IN_SET(`id`,'" . $fzr . "')")->fetchAll();
                    $fzr2 = \App\arr2str($user);
                }
                $fpr = empty($fpr) ? '' : \App\GetFiledInfo('user', 'realname', $fpr);
                $tx_follw_uid = !empty($tx_follw_uid) ? $tx_follw_uid : '';
                $last_follw = !empty($tx_follw_uid) ? \App\GetFiledInfo('user', 'realname', reset($tx_follw_uid)) : '未跟进';
                $phone_array[] = array(
                    'cname' => \App\GetFiledInfo('customer', 'cname', $v['cid']),
                    'creatname' => \App\GetFiledInfo('user', 'realname', $creatid_new),
                    'creatid' => $creatid_new,
                    'sea_type' => $v['sea_type'] == 0 ? '私海' : '公海',
                    'type' => $type,
                    'cphone' => !empty($wx)?$wx:$phone,
                    'cid' => $v['cid'],
                    'distributor' => $fpr,
                    'executor' => $fzr2,
                    'last_follw' => $last_follw,
                    'create_time' => $v['create_time'],
                    'share_status' => $share_count,
                    'bid'=>isset($v['bid'])?$v['bid']:0,
                );
            }
            foreach ($phone_array as $k => $v) {
                $addtime = $this->di->customer_log->where('type', 9)->and('uid', $uid)->and('cid', $v['cid'])->order('addtime desc')->fetchOne('addtime');
                $unixTime_1 = $addtime; // 开始时间
                $unixTime_2 = time(); // 结束时间
                $timediff = abs($unixTime_2 - $unixTime_1);
                $remain = $timediff % 86400;
                $hours = intval($remain / 3600);
                if ($hours >= 1) {
                    //撞单记录
                    \App\Cus_log($uid, $v['cid'], 9, '撞单', '客户手机号/微信:' . $v['cphone']);
                    $this->di->customer_data->where('cid', $v['cid'])->update(['zdnum' => new \NotORM_Literal("zdnum + 1")]);
                }
            }
            $rs = array('code' => 2, 'msg' => '999982', 'data' => $phone_array, 'info' => array());
        }
        else {
            $rs = array('code' => 1, 'msg' => '无撞单!', 'data' => [], 'info' => array());
        }
        return $rs;
    }
    //筛选数据
    public function GetNewdata($data, $phone, $wx, $uid, $type)
    {
        $getyear = $this->di->admin_config->where(array("title" => "REPEAT_DATA"))->fetchOne('value');
        $last_time = time() - $getyear * 31536000;
        $first_time = time();
        if (empty($data)) {
            $data = ['phone' => $phone, 'uid' => $uid, 'cus_type' => $type, 'type' => 0];
            $rs = array('code' => 2, 'msg' => '999982', 'data' => $data, 'info' => array());
            return $rs;
        }
        foreach ($data as $k => $v) {
            if ($last_time <= $v['create_time'] && $v['create_time'] <= $first_time) {
                $year_nei[] = $v;
            }
            else if ($last_time >= $v['create_time']) {
                $year_wai[] = $v;
            }
        }

        if (isset($year_nei) && count($year_nei) > 0) {
            if (count($year_nei) == 1) {
                $rs = $this->tixing($year_nei, $phone, $wx, $uid, $type);
                return $rs;
            }
            else {
                $ages = array();
                foreach ($year_nei as $user) {
                    $ages[] = $user['create_time'];
                }
                array_multisort($ages, SORT_ASC, $year_nei);
                $year_nei_new[] = reset($year_nei);
                $rs = $this->tixing($year_nei_new, $phone, $wx, $uid, $type);
                return $rs;

            }
        }
        else if (isset($year_wai) && count($year_wai) > 0 && count($year_nei) <= 0) {

                $ages = array();
                foreach ($year_wai as $user) {
                    $ages[] = $user['create_time'];
                }
                array_multisort($ages, SORT_DESC, $year_wai);
                $year_wai_new[] = reset($year_wai);

                $rs = $this->tixing($year_wai_new, $phone, $wx, $uid, $type);
                return $rs;


        }
    }


}