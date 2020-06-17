<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/1 0001
 * Time: 09:16
 */

namespace App\Model;
use PhalApi\Model\NotORMModel as NotORM;
use App\Common\Tree;
use App\Common\Customer as CustomerCommon;
class Rule extends NotORM
{
    protected $di;
    protected $prefix;
    public function __construct()
    {
        $this->di = \PhalApi\DI()->notorm;
        $this->prefix = \PhalApi\DI()->config->get('common.PREFIX');
    }
    public function GetRuleList(){
        $model_list = $this->getORM()->where("status",1)->fetchAll();
        foreach ($model_list as $k=>$v){
            $newlist[]=array(
                'path'=>'path',
                'component'=>'component',
                'name'=>'name',
                'id'=>$v['id'],
                'pid'=>$v['pid'],
                'types'=>$v['types'],
                'level'=>$v['level'],
                'status'=>$v['status'],
                'meta'=>['title'=>$v['title'],'icon'=>'icon','roles'=>$v['roles']],

            );
        }
        $tree=new Tree();
        $data=$tree->list_to_tree($newlist);
        if (!empty($model_list)) {
            $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>$model_list);
        } else {
            $rs = array('code'=>0,'msg'=>'000002','data'=>array(),'info'=>array());
        }
        return $rs;
    }
    public function  GetRuleSelect($id,$uid){
        $customer_common = new CustomerCommon();
        $customer_level = $customer_common->GetCustomerNumByLevel($uid);

        $tree=new Tree();
        $first=$this->getORM()->where('id',$id)->fetchAll();
        foreach ($first as $k=>$v){
            if (array_key_exists($v['title'], $customer_level)) {
                $first2[]=['path'=>$v['path'],'component'=>'component','name'=>'name','id'=>$v['id'],'pid'=>$v['pid'],'types'=>$v['types'],'level'=>$v['level'],'status'=>$v['status'],'meta'=>['title'=>$v['title'],'value'=>$customer_level[$v['title']],'icon'=>'icon','roles'=>$v['roles']]];
            } else {
                $first2[]=['path'=>$v['path'],'component'=>'component','name'=>'name','id'=>$v['id'],'pid'=>$v['pid'],'types'=>$v['types'],'level'=>$v['level'],'status'=>$v['status'],'meta'=>['title'=>$v['title'],'icon'=>'icon','roles'=>$v['roles']]];
            }
        }

        return $first2;
    }


    public function GetColumnList(){
        $model_list = $this->getORM()->where("status",1)->fetchAll();
        foreach ($model_list as $k=>$v){
            $newlist[]=array(
                'path'=>'path',
                'component'=>'component',
                'name'=>'name',
                'id'=>$v['id'],
                'pid'=>$v['pid'],
                'count'=>$this->getORM()->where("pid",$v['id'])->count(),
                'types'=>$v['types'],
                'level'=>$v['level'],
                'status'=>$v['status'],
                'meta'=>['label'=>$v['title'],'icon'=>'icon','roles'=>$v['roles']],

            );
        }
        $tree=new Tree();
        $data=$tree->list_to_tree($newlist);
        if (!empty($model_list)) {
            $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>$model_list);
        } else {
            $rs = array('code'=>0,'msg'=>'000002','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    public function AddColumnList($data,$id){
        $model_list=$this->getORM()->select('id')->where(['pid'=>$data['pid'],'title'=>$data['title'],'path'=>$data['path']])->fetchOne();

        if($id!=''){
            $data=$this->getORM()->select('id')->where('id',$id)->update($data);
            $rs = array('code'=>1,'msg'=>'更新成功','data'=>$data,'info'=>$data);
            return $rs;
        }
        if($model_list){
            $data='';

        }else{
            $data=$this->getORM()->insert($data);
        }
        if ($data!='') {
            $rs = array('code'=>1,'msg'=>'插入成功','data'=>$data,'info'=>$data);
        } else {
            $rs = array('code'=>0,'msg'=>'插入失败,数据重复,请检查!','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    public function delColumn($pid,$id){
        $yz=$this->getORM()->select('id')->where(['pid'=>$id])->count();
        if($yz!='0'){
            $rs = array('code'=>0,'msg'=>'删除失败,里面有子类,不能删除!','data'=>array(),'info'=>array());
        }else{
            $msg=$this->di->customer_data->where('groupid',$id)->fetchOne();
            if(!empty($msg)){
                $rs = array('code'=>0,'msg'=>'删除失败,部门中有客户,不能删除!','data'=>array(),'info'=>array());
                return $rs;
            }
            $user_list=$this->di->user->where('new_groupid',$id)->count('id');
            if(!empty($user_list)){
                $rs = array('code'=>0,'msg'=>'删除失败,部门中有'.$user_list.'个用户,需切换后才能删除!','data'=>array(),'info'=>array());
                return $rs;
            }
            $data=$this->getORM()->where('id',$id)->delete();
            $rs = array('code'=>1,'msg'=>'删除成功!','data'=>$data,'info'=> $data);
        }

        return $rs;
    }


}