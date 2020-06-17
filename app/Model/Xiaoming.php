<?php
namespace App\Model;
use PhalApi\Model\NotORMModel as NotORM;
use App\Model\Common as Common;
use App\Common\Admin as AdminCommon;
use App\Common\Customer as CustomerCommon;

class Xiaoming extends NotORM
{
    protected $di;
    protected $iv;
    protected $prefix;
    protected $redis;
    protected $config;
    protected $pagesize;
    public function __construct()
    {
        $this->di = \PhalApi\DI()->notorm;
        $this->config = \PhalApi\DI()->config;
        $this->prefix = \PhalApi\DI()->config->get('common.PREFIX');
        $this->redis = \PhalApi\DI()->redis;
        // 加密向量
        $this->iv = \PhalApi\DI()->config->get('common.IV');
        // 页码设置
        $this->pagesize = \PhalApi\DI()->config->get('common.PAGESIZE');
    }
    protected function getTableName($id) {
        return 'user';  // 手动设置表名为 my_user
    }
    public function GetAddUserStatus($uid){

        //设置令牌
        $token=\App\CreateOrderNo();
        //存到redis中
       $data=['uid'=>$uid,'token'=>$token,'adddate'=>date('Y-m-d H:i:s')];
       $this->di->user_token->insert($data);
       $status['token']=$token;
       $data=$this->get($uid,array('setlimit', 'getlimit'));
       if(reset($data)==0){
           $status['status']=1;
       }else {
           if(reset($data) <= end($data)) {
               $status['status']=0;
           }else{
               $status['status']=1;
           }
       }
        $rs = array('code'=>1,'msg'=>'加载成功!','data'=>$status,'info'=>[]);
        return $rs;
    }

}