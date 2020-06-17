<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/4/8 0008
 * Time: 14:17
 */
namespace App\Common;
use App\Common\Admin as AdminCommon;
use PhalApi\Model\NotORMModel as NotORM;
use App\Common\Encryption;

/**
 * 项目方模块公共处理函数
 */
class Projectdata extends NotORM{
    protected $di;
    protected $prefix;
    public function __construct()
    {
        $this->di = \PhalApi\DI()->notorm;
        $this->prefix = \PhalApi\DI()->config->get('common.PREFIX');
    }
    public function EditProjectvip($uid,$id,$data,$user_info){
        //新建项目方判断
        $project_side=$this->di->project_side->select('manage,id')->where('id',$user_info['type_id'])->fetchOne();
        if($uid==1 && $user_info['creatid']==$uid){
            //总管理员创建人
            if($data['type_id']!=$user_info['type_id']){
                $rs = array('code'=>0,'msg'=>'您不能更改已绑定的项目方/部门','data'=>array(),'info'=>array());
                return $rs;
            }
            if($project_side['manage']==$id){
                $enc=new Encryption();
                $url='id='.$id.'&'.'type=1';
                $data['url']=$_SERVER['HTTP_ORIGIN'].'/crm/dist/#/login?'.$enc->encrypt_url($url,$this->url_md);
            }
        }else if($project_side['manage']==$uid){
            //项目方主管理员编辑
            if($data['type_id']!=$user_info['type_id']){
                $rs = array('code'=>0,'msg'=>'您不能更改已绑定的用户','data'=>array(),'info'=>array());
                return $rs;
            }
        }
        $this->di->user->where('id',$id)->update($data);
        $json=json_encode($data);
        \App\setlog(\PhalApi\DI()->session->uid,2,'修改用户ID:'.$id,'成功',$json,'修改系统用户成功');
        $rs = array('code'=>1,'msg'=>'更新成功!','data'=>$data,'info'=>array());
        return $rs;

    }
}

