<?php
namespace App\Api;
use PhalApi\Api;
use App\Domain\Xiaoming as Domain_Xiaoming;

/**
 * 小明接口
 * @author: dogstar <1184378894@qq.com> 2019-09-04
 */
class Xiaoming extends Api {
    public function getRules() {
        return array(
           'GetAddUserStatus'=>array(
               'uid' =>array('name'=>'uid','require'=>true,'desc'=>'用户id'),
           ),
        );
    }

  public function  GetAddUserStatus(){
        $Domain_Xiaoming=new Domain_Xiaoming;
        return  $Domain_Xiaoming->GetAddUserStatus($this->uid);
  }
}
