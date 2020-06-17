<?php
namespace App\Common;
use App\Common\Admin as AdminCommon;
use PhalApi\Model\NotORMModel as NotORM;

/**
 * 客户模块公共处理函数
 */
class Customer extends NotORM
{
    protected $di;
    protected $prefix;
    public function __construct()
    {
        $this->di = \PhalApi\DI()->notorm;
        $this->prefix = \PhalApi\DI()->config->get('common.PREFIX');
    }



    /**
     * 客户操作日志
     * @author Wang Junzhe
     * @DateTime 2019-08-27T13:56:11+0800
     * @param    [int]                   $uid    [当前操作用户id]
     * @param    [int]                   $cid    [客户id]
     * @param    [int]                   $type   [日志类型(0私海新增1回归公海2分配3领取4共享5取消共享6导出7删除8公海新增9撞单10更改部门11移交客户15转移)]
     * @param    [string]                $action [操作内容]
     * @param    [string]                $note   [备注]
     */
    public function CustomerActionLog($uid,$cid,$type,$action,$note) {
        // 当前IP
        $ip = \PhalApi\Tool::getClientIp();
        $action_data = array(
            'uid' => $uid,
            'cid' => $cid,
            'type' => $type,
            'action' => $action,
            'note' => $note,
            'addtime' => time(),
            'updatetime' => time(),
            'ip' => $ip,
        );
        $action = $this->di->customer_log->insert($action_data);
        return $action;
    }


    /**
     * 客户分配或领取操作
     * @author Wang Junzhe
     * @DateTime 2019-09-02T17:23:50+0800
     * @param    [array]                   $cid_arr     [操作的客户数组]
     * @param    [int]                     $uid         [当前操作用户ID]
     * @param    [int]                     $beshare_uid [被分享人]
     * @param    [int]                     $type        [类型2分配3领取]
     * @param    [int]                     $from_type   [来源类型1分配2领取3共享]
     * 
     */
    public function CustomerDistributeAction($cid_arr,$uid,$beshare_uid,$type,$from_type,$is_open) {
        if (empty($cid_arr)) {
            return false;
        } else {
            $is_open = isset($is_open) ? $is_open : 0;
            if ($type == 2) {
                $type_msg = "客户分配";
                $type_name = "分配";
                $action = "send";
            }
            if ($type == 3) {
                $type_msg = "客户领取";
                $type_name = "领取";
                $action = "receive";
            }
            $admin_common = new AdminCommon();
            // $username = $this->di->user->where(array("id"=>$uid))->fetchOne("username");
            $user_info = \App\Getkey($uid,array("realname","is_leader"));
            $username = $user_info['realname'];
            // $beshare_usernamme = $this->di->user->select("username,structure_id")->where(array("id"=>$beshare_uid))->fetchOne();
            $beshare_usernamme = \App\Getkey($beshare_uid,array("realname","structure_id","getlimit","setlimit"));
            $structure_id = json_decode($beshare_usernamme['structure_id']);
            $user_structure_id = array_pop($structure_id);

            // 判断当前用户私海数量
            if ($beshare_usernamme['setlimit'] != 0 && $beshare_usernamme['getlimit'] >= $beshare_usernamme['setlimit']) {
                // $err_msg[] = "客户".$type_name."失败，".$beshare_usernamme['realname']."的私海数量已超过限制！";
                if ($type == 2) {
                    $err_msg[] = $beshare_usernamme['realname']."私海数据已满".$beshare_usernamme['setlimit']."人，无法分配，分配失败！";
                } else {
                    $err_msg[] = "你的私海数据已满".$beshare_usernamme['setlimit']."人，无法领取，领取失败！";
                }
            } else {
                $beshare_limit = $beshare_usernamme['getlimit'];
                
                foreach ($cid_arr as $key => $value) {
                    
                    if (empty($key)) {
                        $err_msg[] = $type_msg."失败，客户参数有误！";
                        continue;
                    }
                    $customer_info = array();
                    $customer_info = $this->di->customer->select("*")->where(array("id"=>$key))->fetchOne();
                    if (empty($customer_info)) {
                        $err_msg[] = "客户：".$customer_info['cname'].$type_name."失败，客户信息不存在！";
                        continue;
                    }
                    // 判断私海数量
                    if ($beshare_usernamme['setlimit'] != 0 && $beshare_limit >= $beshare_usernamme['setlimit']) {
                        if ($type == 2) {
                            $err_msg[] = $beshare_usernamme['realname']."私海数据已满".$beshare_usernamme['setlimit']."人，无法分配，分配失败！";
                        } else {
                            $err_msg[] = "你的私海数据已满".$beshare_usernamme['setlimit']."人，无法领取，领取失败！";
                        }
                        continue;
                    }
                    $user_share_group = array();
                    $share_group = array();

                    if (empty($value)) {
                        # 分配或者领取创建者的数据
                        // 判断客户是否是公海客户
                        if ($customer_info['sea_type'] == 0) {
                            $err_msg[] = "客户".$customer_info['cname'].$type_name."失败，客户类型为私海！";
                            continue;
                        }

                        // 判断是否分配给自己本部门
                        if ($type == 2 || $user_info['is_leader'] != 0 || $uid == 1) {
                            // 查询客户原来的groupid
                            $user_share_group = $this->di->customer_data->select("s.share")->alias('cd')->leftJoin('structure', 's', 'cd.groupid = s.id')->where(array("cid"=>$key))->fetchOne();
                            $share_group = explode(",",$user_share_group['share']);
                            
                            if (!empty($user_share_group) && !in_array($user_structure_id,$share_group)) {
                                $err_msg[] = "客户：".$customer_info['cname'].$type_name."失败！只能".$type_name."本部门人员！";
                                continue;
                            }
                        }
                        // Step 1: 开启事务
                        $this->di->beginTransaction('db_master');
                        // 更新客户的创建人 保留原始创建人
                        $customer_update['creatid'] = $beshare_uid;
                        $customer_update['follow_person'] = $beshare_uid;
                        $customer_update['next_follow'] = 0;
                        $customer_update['sea_type'] = 0;
                        $charge_person_str = '/'.$customer_info['creatid'].',/';
                        $new_charge_person = $beshare_uid.',';
                        $customer_update['charge_person'] = preg_replace($charge_person_str, $new_charge_person, $customer_info['charge_person'], 1);
                        $customer_update_res = $this->di->customer->where(array("id"=>$key))->update($customer_update);
                        // 更新此客户的原始创建人
                        $old_publish = $this->di->customer_data->where(array("cid"=>$key))->fetchOne("ocreatid");
                        if (empty($old_publish)) {
                            $customer_data_update['ocreatid'] = $customer_info['creatid'];
                        }
                        $customer_data_update['groupid'] = $user_structure_id;
                        $this->di->customer_data->where(array("cid"=>$key))->update($customer_data_update);

                        $share_info = array();
                        // 如果有共享数据，更新共享数据的创建人
                        $share_info = $this->di->share_join->where(array("cid"=>$key))->fetchPairs("id","bid");
                        if (!empty($share_info)) {
                            $this->di->share_join->where(array("cid"=>$key))->update(array("creat_id"=>$beshare_uid));
                            $this->di->share_join->where(array("cid"=>$key,"share_uid"=>$customer_info['creatid']))->update(array("share_uid"=>$beshare_uid));
                            foreach ($share_info as $k => $v) {
                                $this->di->share_customer->where(array("id"=>$v))->update(array("creatid"=>$beshare_uid));
                            }
                        }
                        // (1)插入share_customer
                        $share_customer_data = $customer_info;
                        unset($share_customer_data['id']);
                        $share_customer_data['creatid'] = $beshare_uid;
                        $share_customer_data['charge_person'] = $beshare_uid.',';
                        $share_customer_data['next_follow'] = 0;//11-26要求去掉下次跟进
                        $share_customer_data['follow_up'] = 0;
                        $share_customer_data['follw_time'] = time();//11-29改为分享的日期 
                        $share_customer_data['myshare'] = 0;
                        $share_customer_data['getshare'] = 0;
                        $share_customer_data['is_top'] = 0;
                        $share_customer_data['sea_type'] = 0;//私海

                        $bid = $this->di->share_customer->insert($share_customer_data);
                        // (2)插入share_join
                        $share_data = array(
                            'cid' => $key,
                            'share_uid' => $uid,
                            'groupid' => $user_structure_id,
                            'beshare_uid' => $beshare_uid,
                            'from_type' => $from_type,
                            'status' => 1,
                            'addtime' => time(),
                            'updatetime' => time(),
                            'bid' => $bid,
                            'creat_id' => $beshare_uid,
                            'sea_type' => 0,
                        );
                        $share_id = $this->di->share_join->insert($share_data);

                        if ($customer_update_res) {
                            // 提交事务
                            $this->di->commit('db_master');
                            // 客户私海数量加一
                            \App\SetLimit($beshare_uid,1);
                            $beshare_limit++;
                            // 增加客户操作日志
                            $open_note = $is_open == 1 ? "【备注：录入时，系统自动公海启用，创建人改变】": "【备注：创建人改变】";
                            $type_content = $username."将客户：".$customer_info['cname'].$type_name."到".$beshare_usernamme['realname']."的私海".$open_note;
                            $this->CustomerActionLog($uid,$key,$type,'公海'.$type_msg,$type_content);
                            // 给最新创建人发送消息
                            if ($type == 2) {
                                // 推送消息
                                $msg_title = $username.'将客户：'.$customer_info['cname'].'分配给您!';
                                $msg_content = $username.'将客户：'.$customer_info['cname'].'分配给您,请及时处理！';
                                $admin_common->SendMsgNow($uid,$beshare_uid,$msg_title,$msg_content,0,"send","customer",$key);
                            }
                            $success_msg .= $customer_info['cname'].',';
                        } else {
                            // 回滚事务
                            $this->di->rollback('db_master');
                            $err_msg[] = "客户".$customer_info['cname'].$type_name."失败！";
                            continue;
                        }
                    } else {
                       
                        # 分配或者领取共享的数据
                        $share_join = $this->di->share_join->select("id,sea_type,bid,beshare_uid,groupid")->where(array("cid"=>$key,"bid"=>$value))->fetchOne();
                        if (empty($share_join)) {
                            $err_msg[] = "客户".$customer_info['cname'].$type_name."失败，数据有误！";
                            continue;
                        }
                        // 判断客户是否是公海客户
                        if ($share_join['sea_type'] == 0) {
                            $err_msg[] = "客户".$customer_info['cname'].$type_name."失败，客户类型为私海！";
                            continue;
                        }
                        // 判断是否分配给自己本部门
                        if ($type == 2 || $user_info['is_leader'] != 0 || $uid == 1) {
                            // 查询客户原来的groupid
                            $user_share_group = $this->di->structure->where(array("id"=>$share_join['groupid']))->fetchOne("share");
                            $share_group = explode(",",$user_share_group);
                            
                            if (!empty($user_share_group) && !in_array($user_structure_id,$share_group)) {
                                $err_msg[] = "客户：".$customer_info['cname'].$type_name."失败！只能".$type_name."本部门人员！";
                                continue;
                            }
                        }
                        // Step 1: 开启事务
                        $this->di->beginTransaction('db_master');
                        // (1)更新share_join
                        $share_data = array(
                            'share_uid' => $uid,
                            'groupid' => $user_structure_id,
                            'beshare_uid' => $beshare_uid,
                            'from_type' => $from_type,
                            'status' => 1,
                            'addtime' => time(),
                            'updatetime' => time(),
                            'sea_type' => 0,
                        );
                        $share_id = $this->di->share_join->where(array("id"=>$share_join['id']))->update($share_data);
                        // (2)更新share_customer
                        $share_customer_data['charge_person'] = $beshare_uid.',';
                        $share_customer_data['next_follow'] = 0;//11-26要求去掉下次跟进
                        $share_customer_data['follow_up'] = 0;
                        $share_customer_data['follw_time'] = time();//11-29改为分享的日期 
                        $share_customer_data['myshare'] = 0;
                        $share_customer_data['getshare'] = 0;
                        $share_customer_data['is_top'] = 0;
                        $share_customer_data['sea_type'] = 0;//私海

                        $bid = $this->di->share_customer->where(array("id"=>$value))->update($share_customer_data);
                        if ($bid && $share_id) {
                            // 提交事务
                            $this->di->commit('db_master');
                            // (3) 将原来的负责人更改为新负责人
                            $charge_person_str = '/,'.$share_join['beshare_uid'].',/';
                            $new_charge_person = ','.$beshare_uid.',';
                            $customer_update['charge_person'] = preg_replace($charge_person_str, $new_charge_person, $customer_info['charge_person'], 1);
                        
                            $this->di->customer->where(array("id"=>$key))->update($customer_update);
                            // 客户私海数量加一
                            \App\SetLimit($beshare_uid,1);
                            $beshare_limit++;
                            // (4) 增加操作日志
                            $open_note = $is_open == 1 ? "【备注：系统自动公海启用】": "";
                            $type_content = $username."将客户：".$customer_info['cname'].$type_name."到".$beshare_usernamme['realname']."的私海".$open_note;
                            $this->CustomerActionLog($uid,$key,$type,'公海'.$type_msg,$type_content);
                            // 给客户创建人发送消息
                            if ($beshare_uid  != $customer_info['creatid']) {
                                $admin_title = "您的客户：".$customer_info['cname']."被".$type_name;
                                $admin_content = "您的客户：".$customer_info['cname']."被".$username.$type_name."给：".$beshare_usernamme['realname'];
                                $admin_common->SendMsgNow(1,$customer_info['creatid'],$admin_title,$admin_content,0,$action,"customer",$key);
                            }
                            // 推送消息[分配]
                            if ($type == 2) {
                                // 推送消息
                                $msg_title = $username.'分配给您一个客户：'.$customer_info['cname'].'!';
                                $msg_content = $username.'分配给您一个客户'.$customer_info['cname'].',请及时处理！';
                                $admin_common->SendMsgNow($uid,$beshare_uid,$msg_title,$msg_content,0,"send","customer",$key);
                            }
                            $success_msg .= $customer_info['cname'].',';
                        } else {
                            // 回滚事务
                            $this->di->rollback('db_master');
                            $err_msg[] = "客户".$customer_info['cname'].$type_name."失败！";
                            continue;
                        }
                    }
                    
                }
            }
        }
        \PhalApi\DI()->redis->flushDB('customer');
        $data['success_msg'] = $success_msg;
        $data['err_msg'] = $err_msg;
        return $data;
    }

    /**
     * 公共分享操作
     * @author Wang Junzhe
     * @DateTime 2019-09-10T15:49:22+0800
     * @param    [int]                      $uid        [用户ID]
     * @param    [int]                      $id         [信息ID]
     * @param    [array]                    $share_uid  [分享人uid数组]
     * @param    [string]                   $table_name [表名称]
     */
    public function PublicShareInfo($uid,$id,$share_uid,$table_name) {
        $admin_common = new AdminCommon();
        $table_arr = array("agent"=>1,"teacher"=>2,"project_side"=>3);
        $table_type_name = array("agent"=>"代理商","teacher"=>"师资","project_side"=>"项目方");
        $field_type_name = array("agent"=>"flower_name","teacher"=>"name","project_side"=>"flower_name");
        $field = $field_type_name[$table_name];
        // 基本信息
        $info = $this->di->$table_name->select("charge_person,creatid,".$field)->where(array("id"=>$id,"status"=>1))->fetchOne();
        if (empty($info)) {
            $rs = array('code'=>0,'msg'=>'000050','data'=>array(),'info'=>array());
            return $rs;
        }
        $username = $this->di->user->where(array("id"=>$uid,"status"=>1))->fetchOne("username");
        $max_share_num = \PhalApi\DI()->config->get('common.MAX_SHARE_NUM');//最大分享数
        $charge_person_arr = explode(",",trim($info['charge_person'],","));
        array_shift($charge_person_arr);
        $total_person = count($share_uid) + count($charge_person_arr);
        // 判断主表负责人个数【除自己外是否小于5则允许共享】
        if ( (count($charge_person_arr) < $max_share_num) || ($total_person > $max_share_num) ) {
            // (5)增加操作日志
            foreach ($share_uid as $key => $value) {
                if (!in_array($value, $charge_person_arr)) {
                    $charge_person_str .= $value.",";
                    $share_user_name = $this->di->user->where(array("id"=>$value,"status"=>1))->fetchOne("username");
                    $note = $username."共享".$table_type_name[$table_name]."给:".$share_user_name;
                    $this->PublicShareLog($id,$uid,$value,$table_arr[$table_name],'share',$note);
                    // 推送消息
                    $msg_title = $username.'共享给您一个'.$table_type_name[$table_name].":".$info[$field];
                    $msg_content = $username.'共享给您一个'.$table_type_name[$table_name].":".$info[$field].",请及时处理";

                    $admin_common->SendMsgNow($uid,$value,$msg_title,$msg_content,0,"share",$table_name,$id);
                }
            }
            $update_sql = "UPDATE ".$this->prefix.$table_name." SET `charge_person`=CONCAT(charge_person,'{$charge_person_str}') WHERE id = ".$id;
            $res = $this->di->customer->executeSql($update_sql);
            if ($res) {
                $rs = array('code'=>1,'msg'=>'000056','data'=>array(),'info'=>array());
            } else {
                $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
            }
            
        } else {
            $rs = array('code'=>0,'msg'=>'000055','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    /**
     * 取消共享操作
     * @author Wang Junzhe
     * @DateTime 2020-03-14T14:33:33+0800
     * @param    [int]                   $uid       [当前用户id]
     * @param    [int]                   $cid       [客户id]
     * @param    [array]                 $customer_info [客户信息]
     * 
     */
    public function PublishCancleShare($uid,$cid,$customer_info) {
        $user_info = $this->di->user->select("realname,structure_id,is_leader")->where(array('id'=>$uid))->fetchOne();
        // Step 1: 开启事务
        $this->di->beginTransaction('db_master');
        $share_bid = $this->di->share_join->where(array("beshare_uid"=>$uid,"cid"=>$cid,"creat_id"=>$customer_info['creatid']))->fetchOne("bid");
        $share_update = $this->di->share_join->where(array("bid"=>$share_bid))->delete();
        $share_customer_update = $this->di->share_customer->where(array("id"=>$share_bid))->delete();
        $this->di->follw->where(array("bid"=>$share_bid))->update(array("bid"=>0));
        if ($share_update && $share_customer_update) {
            // 提交事务
            $this->di->commit('db_master');
            $charge_person_str = ','.$uid.',';
            $creatid_str = $customer_info['creatid'].',';
            // (3)更新客户分享表负责人
            $customer_update['charge_person'] = str_replace($charge_person_str,",",$customer_info['charge_person']);
            if ($customer_update['charge_person'] == $creatid_str) {
                // 如果负责人只剩下创建人自己
                $customer_update['myshare'] = 0;
            }
            $this->di->customer->where("id",$cid)->update($customer_update);
            // (4)增加操作日志
            $note = $user_info['realname']."取消客户：".$customer_info['cname']."的共享！";
            $customer_common->CustomerActionLog($uid,$cid,5,$note,$note);
            // (5)发送消息，给创建人发送消息
            $msg_title = "您的客户".$customer_info['cname']."被"."取消共享";
            $msg_content = "您的客户".$customer_info['cname']."被".$user_info['realname']."取消共享";
            $admin_common->SendMsgNow($uid,$customer_info['creatid'],$msg_title,$msg_content,0,"cancle","customer",$cid);
            $rs = array('code'=>1,'msg'=>'000058','data'=>array(),'info'=>array());
        } else {
            // 回滚事务
            $this->di->rollback('db_master');
            $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
        }
    }

    /**
     * 公共[取消]分享操作日志记录
     * @author Wang Junzhe
     * @DateTime 2019-09-11T10:48:29+0800
     * @param    [int]                   $id     [信息ID]
     * @param    [int]                   $share_uid   [分享人]
     * @param    [int]                   $beshare_uid [被分享人]
     * @param    [int]                   $type        [类型][1代理商2师资3项目方4教务]
     * @param    [string]                $action      [操作][add|share|cancle|change]
     * @param    [string]                $note        [备注]
     */
    public function PublicShareLog($id,$share_uid,$beshare_uid,$type,$action,$note) {
        if (empty($id) || empty($share_uid) || empty($beshare_uid)) {
            return false;
        }
        $ip = \PhalApi\Tool::getClientIp();
        $share_log_data = array(
            'info_id' => $id,
            'share_uid' => $share_uid,
            'beshare_uid' => $beshare_uid,
            'type' => $type,
            'action' => $action,
            'note' => $note,
            'addtime' => time(),
            'ip' => $ip
        );
        $action = $this->di->share_log->insert($share_log_data);
        return $action;
    }
    
    /**
     * 查询用户各个会员级别的客户数据
     * @author Wang Junzhe
     * @DateTime 2020-03-16T11:24:09+0800
     * @param    [int]                   $uid [当前用户id]
     */
    public function GetCustomerNumByLevel($uid) {
        // 查询所有的会员级别
        $level_list_str = $this->di->model_field->where("id",42)->fetchOne("setting");
        $level_list = json_decode($level_list_str);
        // $customer_list = $this->di->customer->where("status = 1 AND sea_type = 0 AND FIND_IN_SET('{$uid}', charge_person)")->group("intentionally")->fetchPairs("intentionally","COUNT(id)");
        $params[':status'] = 1;
        $sql = " SELECT
            intentionally,
            COUNT(id) as num
        FROM
            (
                (
                    SELECT
                        sc.intentionally,
                        sc.id
                    FROM
                        ".$this->prefix."customer sc
                    WHERE
                       sea_type = 0
                    AND creatid = '{$uid}'
                )
                UNION ALL
                    (
                        SELECT
                            sc.intentionally,
                            sc.id
                        FROM
                            ".$this->prefix."share_join s
                        LEFT JOIN crm_share_customer sc ON s.bid = sc.id
                        WHERE
                            s.sea_type = 0 AND
                             creatid <> '{$uid}'
                        AND beshare_uid = '{$uid}'
                    )
            ) a
        GROUP BY
            a.intentionally";
        $customer_list = $this->di->customer->queryAll($sql,$params);
        $temp_key = array_column($customer_list,'intentionally');  //键值
        $customer_list = array_combine($temp_key,$customer_list) ;
        $customer_level = array();
        foreach ($level_list as $key => $value) {
            $customer_level[$value] = !empty($customer_list[$value]['num']) ? $customer_list[$value]['num'] : 0;
        }
        return $customer_level;
    }

    /**
     * 根据手机号查询存在部门
     * @author Wang Junzhe
     * @DateTime 2020-05-04T17:21:21+0800
     * @param    [string]                   $phone [手机号]
     * @param    [int]                      $share_groups [被分享人部门]
     * 
     */
    public function GetStructByPhone($data_info,$share_groups) {
        $sh_arr = array();
        $gx_arr = array();
        if (substr_count($share_groups,"all")==0 && !empty($share_groups)) {
            $sh_customer_where = " AND FIND_IN_SET(cd.groupid,'".$share_groups."') ";
            $gx_customer_where = " AND FIND_IN_SET(s.groupid,'".$share_groups."') ";
        } else {
            $sh_customer_where = "";
            $gx_customer_where = "";
        }
        $phone_arr = array(
            'cphone' => $data_info['cphone'],
            'cphonetwo' => $data_info['cphonetwo'],
            'cphonethree' => $data_info['cphonethree'],
            'telephone' => $data_info['telephone'],
            'wxnum' => $data_info['wxnum']
        );
        foreach ($phone_arr as $key => $value) {
            if (!empty($value) && in_array($key,array("cphone","cphonetwo","cphonethree"))) {
                $sh_customer = $this->di->customer_data->select("c.id as cid,c.sea_type,cd.groupid,cd.create_time,c.creatid,c.charge_person,c.cname")->alias('cd')->leftJoin('customer', 'c', 'c.id = cd.cid')->where("FIND_IN_SET('".$data_info[$key]."',CONCAT_WS(',',cphone,cphonetwo,cphonethree))".$sh_customer_where)->fetchAll();
                $gx_sql = "SELECT s.cid,s.bid,s.sea_type,s.groupid,s.beshare_uid,s.from_type,s.addtime,s.groupid as share_groupid FROM crm_customer_data cd LEFT JOIN crm_share_join s ON cd.cid = s.cid WHERE FIND_IN_SET('".$data_info[$key]."',CONCAT_WS(',',cphone,cphonetwo,cphonethree))".$gx_customer_where;

                $gx_customer = $this->di->customer->queryAll($gx_sql, array());
            } elseif (!empty($value) && in_array($key,array("telephone","wxnum"))) {
                $sh_customer = $this->di->customer_data->select("c.cname,c.id as cid,c.sea_type,cd.groupid,cd.create_time,c.creatid,c.charge_person")->alias('cd')->leftJoin('customer', 'c', 'c.id = cd.cid')->where("cd.{$key} ='".$data_info[$key]."'$sh_customer_where")->fetchAll();
                $gx_sql = "SELECT s.cid,s.bid,s.sea_type,s.groupid,s.beshare_uid,s.from_type,s.groupid as share_groupid,s.addtime FROM crm_customer_data cd LEFT JOIN crm_share_join s ON cd.cid = s.cid WHERE cd.{$key} ='{$data_info[$key]}'".$gx_customer_where;
                $gx_customer = $this->di->customer->queryAll($gx_sql, array());
            } else {
                continue;
            }
            if (!empty($sh_customer[0]['cid'])) $sh_arr += $sh_customer;
            if (!empty($gx_customer[0]['cid'])) $gx_arr += $gx_customer;
        }
        $data_arr = array(
            'sh_arr' => $sh_arr,
            'gx_arr' => $gx_arr
        );
        return $data_arr;
    }

    /**
     * 根据中台/前台类型判断报备规则
     * 1:中台 2:前台 3:渠道 4:后台
     * @author Wang Junzhe
     * @DateTime 2020-05-04T17:30:36+0800
     * @param    [int]                   $former_type  [当前用户类型]
     * @param    [int]                   $current_type [被分享人类型]
     */
    public function GetReportRule($former_type,$current_type) {
        $type_arr = array("","中台","前台","渠道","后台");
        if ($former_type == 4) {
            $err_msg = $type_arr[$former_type]."与".$type_arr[$current_type]."之间不允许报备！";
        } elseif ($current_type == 2) {
            # 共享给前台 包含前台内部共享
            $err_msg = "";
        } elseif ($former_type == $current_type) {
            $err_msg = $type_arr[$former_type]."之间不允许报备！";
        }  else {
            $err_msg = $type_arr[$former_type]."与".$type_arr[$current_type]."之间不允许报备！";
        }
        /**switch ($former_type) {
            case 1:
                # 中台
                if ($current_type == $former_type) {
                    $err_msg = "中台之间不允许报备！";
                }
                if ($current_type == 3) {
                    $err_msg = "中台与渠道之间不允许报备！";
                }
                break;
            case 2:
                # 前台
                if ($current_type == 1) {
                    $err_msg = "前台不允许报备给中台！";
                }
                if ($current_type == 2) {
                    $err_msg = "前台不允许报备给渠道！";
                }
                break;
            case 3:
                # 渠道
                if ($current_type == $former_type) {
                    $err_msg = "渠道之间不允许报备！";
                }
                if ($current_type == 1) {
                    $err_msg = "渠道与中台之间不允许报备！";
                }
                break;
            default:
                # 后台
                if ($current_type == $former_type) {
                    $err_msg = "后台之间不允许报备！";
                }
                if ($current_type == 1) {
                    $err_msg = "后台不允许报备给中台！";
                }
                if ($current_type == 2) {
                    $err_msg = "后台不允许报备给渠道！";
                }
                break;
        }**/
        return $err_msg;
    }

    /**
     * 共享客户操作
     * @author Wang Junzhe
     * @DateTime 2020-05-05T16:01:03+0800
     * @param    [array]                 $customer_info   [客户信息]
     * @param    [int]                   $cid             [客户cid]
     * @param    [int]                   $uid             [当前uid]
     * @param    [int]                   $beshare_uid     [被分享人uid]
     * @param    [int]                   $beshare_groupid [被分享人部门id]
     * @param    [int]                   $is_repeat [1撞单]
     * 
     */
    public function DoShareCustomer($customer_info,$cid,$uid,$beshare_uid,$beshare_groupid,$is_repeat) {
        $admin_common = new AdminCommon();
        if ($uid != $customer_info['creatid']) {
            # 二次共享
            $share_uid = $customer_info['creatid'];
        } else {
            $share_uid = $uid;
        }
        $share_realname = \App\Getkey($share_uid,"realname");
        $beshare_realname = \App\Getkey($beshare_uid,"realname");
        // Step 1: 开启事务
        $this->di->beginTransaction('db_master');
        // (2)插入share_customer
        $share_customer_data = $customer_info;
        unset($share_customer_data['id']);
        $share_customer_data['myshare'] = 0;
        $share_customer_data['getshare'] = 1;
        $share_customer_data['is_top'] = 0;
        $share_customer_data['charge_person'] = $beshare_uid.',';
        $share_customer_data['follow_up'] = 0;
        $share_customer_data['next_follow'] = 0;//11-26要求共享去掉下次跟进
        $share_customer_data['follw_time'] = time();//11-29改为分享的日期 

        $share_bid = $this->di->share_customer->insert($share_customer_data);
        $share_bid = $this->di->share_customer->insert_id();
        // (3)插入share_join
        $share_data = array(
            'cid' => $cid,
            'share_uid' => $share_uid,
            'beshare_uid' => $beshare_uid,
            'groupid' => $beshare_groupid,
            'from_type' => 3,//分享
            'status' => 1,
            'addtime' => time(),
            'updatetime' => time(),
            'bid' => $share_bid,
            'sea_type' => 0,
            'creat_id' => $customer_info['creatid'],
        );
        $share_id = $this->di->share_join->insert($share_data);
        $share_id = $this->di->share_join->insert_id();
        // (4)更新主表负责人
        if ( $share_bid && $share_id ) {
            // 提交事务
            $this->di->commit('db_master');
            if ($is_repeat == 1) {
                $note = "【备注：系统自动共享】";
            }elseif ($is_repeat == 2) {
                $note = "【备注：系统自动公海启用】";
            } else {
                $note = "";
            }
            // 增加客户操作日志
            $gx_msg = $share_realname."将客户：".$customer_info['cname']."共享给：".$beshare_realname.$note;
            $this->CustomerActionLog($uid,$cid,4,'共享客户',$gx_msg);
            $success_person = $beshare_uid;
            // 给被分享人推送消息
            $msg_title = $share_realname.'共享给您一个客户：'.$customer_info['cname'].'！';
            $msg_content = $share_realname."共享给您一个客户：".$customer_info['cname'].",请及时处理！";
            $admin_common->SendMsgNow($share_uid,$beshare_uid,$msg_title,$msg_content,0,"share","customer",$cid);
            // 更改客户负责人
            $beshare_uid_str = $beshare_uid.",";
            $customer_update_sql = "UPDATE ".$this->prefix."customer SET `charge_person`=CONCAT(charge_person,'{$beshare_uid_str}') WHERE id = ".$cid;

            $this->di->customer->executeSql($customer_update_sql);
            // 客户私海数量加一
            \App\SetLimit($beshare_uid,1);
        } else {
            $err_person = $beshare_uid; 
            // 回滚事务
            $this->di->rollback('db_master');
        }
        $data_arr['success_person'] = $success_person;
        $data_arr['err_person'] = $err_person;
        return $data_arr;
    }

    /**
     * 共享客户操作
     * @author Wang Junzhe
     * @DateTime 2020-05-05T16:01:03+0800
     * @param    [array]                 $customer_info   [客户信息]
     * @param    [int]                   $cid             [客户cid]
     * @param    [int]                   $uid             [当前uid]
     * @param    [int]                   $beshare_uid     [被分享人uid]
     * @param    [int]                   $beshare_groupid [被分享人部门id]
     * @param    [int]                   $is_repeat [1撞单]
     *
     */
    public function DoShareCustomerNew($customer_info,$cid,$uid,$beshare_uid,$beshare_groupid) {
        $admin_common = new AdminCommon();
        $share_uid = $uid;
        $share_realname = '系统管理员';
        $beshare_realname = \App\Getkey($beshare_uid,"realname");
        // Step 1: 开启事务
        $this->di->beginTransaction('db_master');
        // (2)插入share_customer
        $share_customer_data = $customer_info;
        unset($share_customer_data['id']);
        $share_customer_data['myshare'] = 0;
        $share_customer_data['getshare'] = 1;
        $share_customer_data['is_top'] = 0;
        $share_customer_data['charge_person'] = $beshare_uid.',';
        $share_customer_data['follow_up'] = 0;
        $share_customer_data['next_follow'] = 0;//11-26要求共享去掉下次跟进
        $share_customer_data['follw_time'] = time();//11-29改为分享的日期

        $share_bid = $this->di->share_customer->insert($share_customer_data);
        $share_bid = $this->di->share_customer->insert_id();
        // (3)插入share_join
        $share_data = array(
            'cid' => $cid,
            'share_uid' => 1,
            'beshare_uid' => $beshare_uid,
            'groupid' => $beshare_groupid,
            'from_type' => 3,//分享
            'status' => 1,
            'addtime' => time(),
            'updatetime' => time(),
            'bid' => $share_bid,
            'sea_type' => 1,
            'creat_id' => 1,
        );
        $share_id = $this->di->share_join->insert($share_data);
        $share_id = $this->di->share_join->insert_id();
        // (4)更新主表负责人
        if ( $share_bid && $share_id ) {
            // 提交事务
            $this->di->commit('db_master');
            // 增加客户操作日志

            $note = "【备注：系统自动共享并回归公海】";
            // 增加客户操作日志
            $gx_msg = $share_realname."将客户：".$customer_info['cname']."共享给：".$beshare_realname.$note;
            $this->CustomerActionLog($uid,$cid,4,'共享客户',$gx_msg);
            $success_person = $beshare_uid;
            // 给被分享人推送消息
//            $msg_title = $share_realname.'共享给您一个客户：'.$customer_info['cname'].'！';
//            $msg_content = $share_realname."共享给您一个客户：".$customer_info['cname'].",请及时处理！";
//            $admin_common->SendMsgNow($share_uid,$beshare_uid,$msg_title,$msg_content,0,"share","customer",$cid);
            // 更改客户负责人
            $beshare_uid_str = $beshare_uid.",";
            $customer_update_sql = "UPDATE ".$this->prefix."customer SET `charge_person`=CONCAT(charge_person,'{$beshare_uid_str}') WHERE id = ".$cid;

            $this->di->customer->executeSql($customer_update_sql);
            // 客户私海数量加一
//            \App\SetLimit($beshare_uid,1);
        } else {
            $err_person = $beshare_uid;
            // 回滚事务
            $this->di->rollback('db_master');
        }
        $data_arr['success_person'] = $success_person;
        $data_arr['err_person'] = $err_person;
        return $data_arr;
    }
    
    /**
     * 共享客户启用操作
     * @author Wang Junzhe
     * @DateTime 2020-05-05T17:51:12+0800
     * @param    [array]                 $customer_info    [客户信息]
     * @param    [int]                   $cid              [原始客户cid]
     * @param    [int]                   $bid              [共享数据bid]
     * @param    [int]                   $uid              [当前uid]
     * @param    [int]                   $beshare_uid      [被分享人uid]
     * @param    [int]                   $beshare_groupid  [被分享人部门id]
     */
    public function DoSeaOpenCustomer($customer_info,$cid,$bid,$uid,$beshare_uid,$beshare_groupid) {
        $admin_common = new AdminCommon();
        if ($bid != 0) {
            // 原始被分享人
            $old_beshare = $this->di->share_join->select("beshare_uid,creat_id")->where(array("cid"=>$cid,"bid"=>$bid,"sea_type"=>1))->fetchOne();
            if (empty($old_beshare)) {
                return false;
            }
            // （1）取消原来公海数据的共享
            $share_update = $this->di->share_join->where(array("bid"=>$bid))->delete();
            $share_customer_update = $this->di->share_customer->where(array("id"=>$bid))->delete();
            $this->di->follw->where(array("bid"=>$bid))->update(array("bid"=>0));
            if ($share_update && $share_customer_update) {
                // 更新原始客户数据的负责人
                $charge_person_str = ','.$old_beshare['beshare_uid'].',';
                $customer_update_sql = "UPDATE ".$this->prefix."customer SET `charge_person`=REPLACE(charge_person,'{$charge_person_str}',',') WHERE id = ".$cid;
                $this->di->customer->executeSql($customer_update_sql);
                // 给原始负责人发送消息
                $old_username = \App\Getkey($old_beshare['beshare_uid'],"realname");
                $old_customer_name = $this->di->customer->where(array("id"=>$cid))->fetchOne("cname");
                $msg_title = '您共享给'.$old_username.'的客户：'.$old_customer_name.','.$old_username.'已不再跟进！';
                $msg_content = '您共享给'.$old_username.'的客户：'.$old_customer_name.','.$old_username.'已不再跟进！';
                $admin_common->SendMsgNow(1,$old_beshare['creat_id'],$msg_title,$msg_content,0,"cancle","customer",$cid);
            }
            // （2）将新数据共享给最新负责人
            $rs = $this->DoShareCustomer($customer_info,$customer_info['id'],$uid,$beshare_uid,$beshare_groupid,2);
            return $rs;
        } else {
            // 自己部门创建的但是扔回公海了 -- 启用
            $share_realname = \App\Getkey($uid,"realname");
            $beshare_realname = \App\Getkey($beshare_uid,"realname");
            // Step 1: 开启事务
            $this->di->beginTransaction('db_master');
            // 更新客户的创建人 保留原始创建人
            $customer_update['creatid'] = $beshare_uid;
            $customer_update['next_follow'] = 0;
            $customer_update['sea_type'] = 0;
            $charge_person_str = '/'.$customer_info['creatid'].',/';
            $new_charge_person = $beshare_uid.',';
            $customer_update['charge_person'] = preg_replace($charge_person_str, $new_charge_person, $customer_info['charge_person'], 1);
            $customer_update_res = $this->di->customer->where(array("id"=>$cid))->update($customer_update);
            // 更新此客户的原始创建人
            $old_publish = $this->di->customer_data->where(array("cid"=>$cid))->fetchOne("ocreatid");
            if (empty($old_publish)) {
                $customer_data_update['ocreatid'] = $customer_info['creatid'];
            }
            $customer_data_update['groupid'] = $beshare_groupid;
            $this->di->customer_data->where(array("cid"=>$cid))->update($customer_data_update);
            $share_info = array();
            // 如果有共享数据，更新共享数据的创建人
            $share_info = $this->di->share_join->where(array("cid"=>$cid))->fetchPairs("id","bid");
            if (!empty($share_info)) {
                $this->di->share_join->where(array("cid"=>$cid))->update(array("creat_id"=>$beshare_uid));
                $this->di->share_join->where(array("cid"=>$cid,"share_uid"=>$customer_info['creatid']))->update(array("share_uid"=>$beshare_uid));
                foreach ($share_info as $k => $v) {
                    $this->di->share_customer->where(array("id"=>$v))->update(array("creatid"=>$beshare_uid));
                }
            }
            if ($customer_update_res) {
                // 提交事务
                $this->di->commit('db_master');
                // 客户私海数量加一
                \App\SetLimit($beshare_uid,1);
                // 增加客户操作日志
                // $gx_msg = $share_realname."将客户：".$customer_info['cname']."共享给：".$beshare_realname."【备注：公海启用】";
                $gx_msg = $share_realname."把客户：".$customer_info['cname']."共享给".$beshare_realname."，客户从公海启用到了私海。【备注：系统自动，公海启用，创建人改变】";
                $this->CustomerActionLog($uid,$cid,4,'共享客户',$gx_msg);
                $success_person = $beshare_uid;
                // 给被分享人推送消息
                $msg_title = $share_realname.'共享给您一个客户：'.$customer_info['cname'].'！';
                $msg_content = $share_realname."共享给您一个客户：".$customer_info['cname'].",请及时处理！";
                $admin_common->SendMsgNow($uid,$beshare_uid,$msg_title,$msg_content,0,"share","customer",$cid);
            } else {
                // 回滚事务
                $err_person = $beshare_uid; 
                // 回滚事务
                $this->di->rollback('db_master');
            }
            $data_arr['success_person'] = $success_person;
            $data_arr['err_person'] = $err_person;
            return $data_arr;
        }
    }

}