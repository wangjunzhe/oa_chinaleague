<?php
namespace App\Domain;

use App\Model\Structure as Dostru;
use App\Common\Category;
use App\Common\Tree;



Class Structure {
    public function getUserInfo($userId) {
        $model = new Dostru();
        return $model->getUserInfo($userId);
    }
    public function  AddUserData($uid,$newdata){
        $model = new Dostru();
//        人员性质0系统人员1项目方2代理商
        return $model->AddUserData($uid,$newdata);
    }
    /**
     * [getDataList 获取列表]
     * @return    [array]
     */
    public function getDataList($type='')
    {
        $this->redis=\PhalApi\DI()->redis;
        $model_list=0;
        if(empty($model_list)){
            $list=new Dostru();
            $cat = new Category('admin_structure', array('id', 'pid', 'name', 'title'));
            $da=$list->pernum();
            foreach ($da as $k=>$v){
                \PhalApi\DI()->notorm->structure->where('id',$k)->update(['num'=>$v]);
            }
            $data = $cat->getList('structure','', 0, 'id');
            foreach ($data as $k=>$v){
                if($v['director']!=''){
                    $dir_list=explode(',',$v['director']);
                    array_pop($dir_list);
                    foreach ($dir_list as $n=>$m){
                        $user[$k][]= \PhalApi\DI()->notorm->user->where('id',$m)->fetchPairs('id','realname');;
                    }
                }else{
                    continue;
                }
                $data[$k]['director']= $user[$k];
            }
            // 若type为tree，则返回树状结构
            $tree = new Tree();
            $model_list = $tree->list_to_tree($data, 'id', 'pid', 'child', 0, true, array(''));
            $this->redis->set_time('medel_list',$model_list,86400,'user');
        }

        $rs = array('code'=>1,'msg'=>'000000','data'=>$model_list,'info'=>array());
        return $rs;
    }
    //验证手机号是否撞单
    public function GetPhoneRepeat($uid,$phone,$wx,$group){
        $list=new Dostru();
        $data=$list->GetPhoneRepeat($uid,$phone,$wx,$group);
        return $data;
    }
    //验证手机号是否撞单
    public function GetPhoneRepeatList($uid,$group,$phone){
        $list=new Dostru();
        $data=$list->GetPhoneRepeatList($uid,$group,$phone);
        return $data;
    }
    public function insertdata($data){
        $cat=new Category();
        $data=$cat->add($data);
        return $data;
    }
    public function  deldata($cid){
        $cat=new Category();
        $data=$cat->del($cid);
        return $data;
    }

    public function edit($id,$uid,$data){
        $cat=new Category();
        $data=$cat->edit($id,$uid,$data);
        return $data;
    }
    public function delStruDir($id,$pid,$uid,$dir){
        $list=new Dostru();
        $data= $list->delStruDir($id,$pid,$uid,$dir);
        return $data;
    }
    public function instation($data){
        $list=new Dostru();
        $data= $list->insertstation($data);
        if (!empty($data)) {
            $rs = array('code'=>1,'msg'=>'999995','data'=>array(),'info'=>array());

        } else {
            $rs = array('code'=>0,'msg'=>'999996','data'=>$data,'info'=>$data);

        }
        return $rs;

    }

    public function getStationList($project_id,$uid){
        $list=new Dostru();
        $data=$list->getStationList($project_id,$uid);
        if (!empty($data)) {
            
            $rs = array('code'=>1,'msg'=>'999996','data'=>$data,'info'=>$data);
        } else {

            $rs = array('code'=>0,'msg'=>'999995','data'=>array(),'info'=>array());

        }
        return $rs;
    }
    public function DelStation($id){
        $list=new Dostru();
        $data=$list->DelStation($id);
        if ($data=='') {
            $rs = array('code'=>0,'msg'=>'999994','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>1,'msg'=>'999993','data'=>$data,'info'=>$data);


        }
        return $rs;
    }
    public function editStation($id,$title){
        $list=new Dostru();
        $data=$list->editStation($id,$title);
        if ($data!=='') {
            $rs = array('code'=>1,'msg'=>'999998','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'999997','data'=>$data,'info'=>$data);


        }
        return $rs;
    }

    public function GetUserList($page,$size,$where){
        $list=new Dostru();
        $data=$list->Userlist($page,$size,$where);
        return $data;
    }

    public  function delUser($id){
        $list = new Dostru();
        $data=$list->delUser($id);
        return $data;
    }
    public function AddUser($uid,$id,$data){
        $list=new Dostru();
        $data=$list->AddUser($uid,$id,$data);

        return $data;
    }
    //获取喜好字段
    public function GetLoveFiled($uid,$title,$type){
        $list=new Dostru();
        $data=$list->GetLoveFiled($uid,$title,$type);
        return $data;
    }
    //获取所有的花名
    public function GetAllFolwer($uid){
        $list=new Dostru();
        $data=$list->GetAllFolwer($uid);
        return $data;
    }
    //更改密码
    public function ChangePassword($uid,$old,$new){
        $list=new Dostru();
        $data=$list->ChangePassword($uid,$old,$new);
        return $data;
    }
    public function yz($user,$paw,$ip){
        $list=new Dostru();
        $data=$list->login($user,$paw,$ip);
        return $data;

    }
    public function PostAddcus($uid,$new,$data,$token){
        $list=new Dostru();
        $msg=$list->Addcus($uid,$new,$data,$token);
        return $msg;
    }

    public function EditCustomer($id,$uid,$bid,$newdata,$listdata){
        $list=new Dostru();
        $msg=$list->Editcus($id,$uid,$bid,$newdata,$listdata);
        return $msg;
    }
    public function Dofp($id,$uid,$type,$lid,$data){

        $list=new Dostru();
        $msg=$list->dofp($id,$uid,$type,$lid,$data);

        return $msg;
    }
    //导出日志
    public function CusExport($uid,$cid,$type,$quantity){
        $list=new Dostru();
        $msg=$list->CusExport($uid,$cid,$type,$quantity);
        return $msg;
    }
    //查看导出日志
    public function  GetCusExport($uid,$type,$pageno,$pagesize){
        $list=new Dostru();
        $msg=$list->GetCusExport($uid,$type,$pageno,$pagesize);
        return $msg;
    }
    //修改头像
    public function ChangeImg($type,$uid,$id,$img){
        $list=new Dostru();
        $msg=$list->ChangeImg($type,$uid,$id,$img);
        return $msg;
    }
    //置顶
    public function dotop($id,$uid,$is_top){
        $list=new Dostru();
        $msg=$list->dotop($id,$uid,$is_top);
        return $msg;
    }
    //获取指定人员列表
    public function getperson($type,$id,$pid){
        $list=new Dostru();
        $msg=$list->getperson($type,$id,$pid);
        return $msg;
    }

    //获取客户详情信息

    public function customerinfo($id,$uid,$bid){
        $list=new Dostru();
        $msg=$list->customerinfo($id,$uid,$bid);
        return $msg;

    }
    //获取跟进列表

    public function GetDoFpList($newData){
        $model = new Dostru();
        $info = $model->GetDoFpList($newData);
        return $info;
    }
    //删除跟进

    public function DeleFp($id,$uid){
        $model=new Dostru();
        $info=$model->DeleFp($id,$uid);
        return $info;
    }
    //批量更改部门
    public  function ChangeGroup($uid,$groupid,$cid_arr){
        $model =new Dostru();
        $info = $model->PostChangeGroup($uid,$groupid,$cid_arr);
        return $info;
    }
    //移交客户
    public function PostTransferAll($uid,$Transid,$cid_arr,$type){
        $model =new Dostru();
        $info = $model->PostTransferAll($uid,$Transid,$cid_arr,$type);
        return $info;
    }
    //添加开票
    public function AddTicket($data){
        $model = new Dostru();
        $info=$model->AddTicket($data);
        return $info;
    }
    //编辑开票
    public function EditTicket($data){
        $model = new Dostru();
        $info=$model->EditTicket($data);
        return $info;
    }
    //删除开票
    public function DeleTicket($uid,$id){
        $model = new Dostru();
        $info=$model->DeleTicket($uid,$id);
        return $info;
    }
    //添加退款
    public function AddRefund($data){
        $model = new Dostru();
        $info=$model->AddRefund($data);
        return $info;
    }
    //撞单列表
    public function ReqeatUserList($uid,$cid,$pageno,$pagesize){
        $model = new Dostru();
        $info=$model->ReqeatUserList($uid,$cid,$pageno,$pagesize);
        return $info;
    }
    //编辑退款
    public function EditRefund($data){
        $model = new Dostru();
        $info=$model->EditRefund($data);
        return $info;
    }
    //删除退款款

    public function DeleRefund($uid,$id){
        $model = new Dostru();
        $info=$model->DeleRefund($uid,$id);
        return $info;
    }
    public function GetInfoList($data){
        $model = new Dostru();
        $info = $model->GetInfoList($data);
        return $info;
    }
    public function GetCusLog($uid,$cid){
        $model = new Dostru();
        $info = $model->GetCusLog($uid,$cid);
        return $info;
    }
    public function getOrder($uid,$cid){
        $model = new Dostru();
        $info = $model->getOrder($uid,$cid);
        return $info;
    }
    public function PostOrderInfo($uid,$id,$type){
        $model=new Dostru();
        $info=$model->PostOrderInfo($uid,$id,$type);
        return $info;
    }
    //用户成交
    public function GetUserOrder($uid,$type){
        $model=new Dostru();
        $info=$model->GetUserOrder($uid,$type);
        return $info;
    }
}
