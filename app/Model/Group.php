<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/31 0031
 * Time: 10:06
 */

namespace App\Model;

use PhalApi\Model\NotORMModel as NotORM;
use App\Model\Rule;
use App\Common\Tree;
class Group extends NotORM
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
        $this->cache = \PhalApi\DI()->cache;
        $this->redis = \PhalApi\DI()->redis;
        // 加密向量
        $this->iv = \PhalApi\DI()->config->get('common.IV');
        // 页码设置
        $this->pagesize = \PhalApi\DI()->config->get('common.PAGESIZE');
    }

    public function getListItems( $page,$perpage,$id,$uid=0) {
        $rule=new Rule();
        $tree=new Tree();
//        $uid=\PhalApi\DI()->session->uid;
        $com=$this->di->user->where('id',$uid)->fetchOne('group_id');
//        $com=!isset($com) || empty($com)?'1 = 1':'company ='.$com;
        if($id!=''){
            $data= $this->getORM()
                ->select('*')
                ->where('id', $id)
                ->fetchAll();
        }else{
            $data= $this->getORM()
                ->select('*')
                ->where('state', '0')
                ->fetchAll();
        }


        if($com==20){
            $rule_list=$this->di->rule->select('id')->fetchPairs('id','id');
            $v['rules']=implode(',',$rule_list).',';
        }

        foreach ($data as $k=>$v){
            $list[]=array(
                'description'=>$v['description'],
                'name'=>$v['title'],
                'state'=>$v['state'],
                'id'=>$v['id'],
                'routes'=> $tree->list_to_treew($rule->GetRuleSelect(\App\stringToArray($v['rules']),$uid)),
            );
        }
        if (!empty($data)) {
            $rs = array('code'=>1,'msg'=>'000000','data'=>$list,'info'=>$list);
        } else {
            $rs = array('code'=>0,'msg'=>'000002','data'=>array(),'info'=>array());
        }
        return $rs;



    }


    public function  insertdb($data)
    {
        $cha=$this->getORM()->where('title',$data['title'])->fetchOne();
        if($cha==''){
            $reslut=$this->getORM()->insert($data);
            if($reslut){
                $rs = array('code'=>1,'msg'=>'添加成功！','data'=>$reslut,'info'=>[]);
            }
        }else{
            $rs = array('code'=>0,'msg'=>'角色已存在！','data'=>array(),'info'=>[]);
        }

        return $rs;
    }

    public function  editdb($id,$data)
    {
        $cha=$this->getORM()->where('id',$id)->fetchOne();

        if($cha!=''){
            $reslut=$this->getORM()->where('id',$id)->update($data);

            $rs = array('code'=>1,'msg'=>'编辑成功！','data'=>$reslut,'info'=>[]);

        }else{
            $rs = array('code'=>0,'msg'=>'角色不存在！','data'=>array(),'info'=>[]);
        }

        return $rs;
    }
    public function  deldb($id)
    {
        $cha=$this->getORM()->where('id',$id)->fetchOne();

        if($cha!=''){
            $reslut=$this->getORM()->where('id',$id)->delete();
            $rs = array('code'=>1,'msg'=>'删除成功！','data'=>$reslut,'info'=>[]);
        }else{
            $rs = array('code'=>0,'msg'=>'角色不存在！','data'=>array(),'info'=>[]);
        }

        return $rs;
    }
}