<?php
namespace App\Model;
use PhalApi\Model\NotORMModel as NotORM;
use App\Model\Common as Common;
use App\Common\Admin as AdminCommon;
use App\Common\Customer as CustomerCommon;

class Xiaozhe extends NotORM 
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

    // 01 代理商-新增
    public function PostAgentAdd($uid,$newData,$field_list,$other_contacts) {
        $agent_name = $newData['title'] ? $newData['title'] : "";
        $full_name = $newData['full_name'] ? $newData['full_name'] : "";
        $mobile = $newData['mobile'] ? $newData['mobile'] : "";
        $identifier = $newData['identifier'] ? $newData['identifier'] : "";
        $flower_name = $newData['flower_name'] ? $newData['flower_name'] : "";

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($agent_name) || empty($full_name) || empty($mobile)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断用户角色
        $user_info = $this->di->user->select("username,realname,is_leader,status,structure_id")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000062','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断代理商是否存在
        $agent_where = " identifier = '{$identifier}' OR flower_name = '{$flower_name}' ";
        $agent_info = $this->di->agent->select("identifier,flower_name")->where($agent_where)->fetchOne();
        if (!empty($agent_info)) {
            $msg_code = $agent_info['flower_name'] == $newData['flower_name'] ? '000074' : '000070';
            $rs = array('code'=>0,'msg'=>$msg_code,'data'=>array(),'info'=>array());
            return $rs;
        }
        $admin_common = new AdminCommon();
        $customer_common = new CustomerCommon();
        $number = \App\CreateOrderNo();
        $newData['number'] = 'DLS'.$number;
        $newData['publisher'] = $user_info['realname'];
        $newData['creatid'] = $uid;
        $newData['charge_person'] = $uid.',';
        $newData['sort'] = 0;
        $newData['status'] = 1;
        $newData['addtime'] = time();
        $newData['updatetime'] = time();
        $newData['follow_time'] = time();//最后跟进日期默认为当前
        $newData['follow_up'] = 0;//跟进次数默认为0

        $agent_data = $admin_common->GetFieldListArr($newData,$field_list);
        $agent_id = $this->di->agent->insert($agent_data);
        $agent_id = $this->di->agent->insert_id();
        if ($agent_id) {
            $note = $user_info['realname']."新增代理商：".$agent_name;
            $customer_common->PublicShareLog($agent_id,$uid,$uid,1,'add',$note);
            \App\setlog($uid,1,'新增代理商ID：'.$agent_id,'成功','新增代理商ID：'.$agent_id,'新增代理商');
            // 清除所有代理商缓存
            $this->redis->set_time('agnet_list','',3000,'agent');

            // 如果有其他联系人
            if (!empty($other_contacts)) {
                foreach ($other_contacts as $key => $value) {
                    $value['agent_id'] = $agent_id;
                    $value['addtime'] = time();
                    $this->di->agent_links->insert($value);
                }
            }

            $rs = array('code'=>1,'msg'=>'000000','data'=>array(),'info'=>array());
        } else {
            \App\setlog($uid,1,'新增代理商失败','失败','新增代理商失败','新增代理商');
            $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 01-1 代理商-编辑-删除其他联系人
    public function PostDeleteOtherContacts($uid,$id) {
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $user_info = $this->di->user->select("realname")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }
        // 删除代理商
        $edit_info = $this->di->agent_links->where(array("id"=>$id))->delete();
        $note = $user_info['realname']."删除代理商联系人";
        if ($edit_info) {
            $msg = "成功";
            $rs = array('code'=>1,'msg'=>'000005','data'=>array(),'info'=>array());
        } else {
            $msg = "失败";
            $rs = array('code'=>0,'msg'=>'000035','data'=>array(),'info'=>array());
        }
        \App\setlog($uid,3,$note,$msg,$note,'删除代理商联系人');
        return $rs;
        
    }

    // 02 代理商-编辑
    public function PostEditAgent($uid,$id,$newData,$field_list,$other_contacts) {
        $identifier = $newData['identifier'] ? $newData['identifier'] : "";
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        // 判断代理商是否存在
        $agent_where = " identifier = '{$identifier}' AND id <> {$id}  ";
        $agent_info = $this->di->agent->select("identifier,flower_name,creatid")->where($agent_where)->fetchOne();
        if (!empty($agent_info)) {
            $rs = array('code'=>0,'msg'=>'000070','data'=>array(),'info'=>array());
            return $rs;
        }

        $agent_info = $this->di->agent->select("creatid,publisher")->where(array("id"=>$id,"status"=>1))->fetchOne();
        if (empty($agent_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }

        // 只允许创建人修改
        if ($agent_info['creatid'] != $uid) {
            $rs = array('code'=>0,'msg'=>'000061','data'=>array(),'info'=>array());
            return $rs;
        }
        $admin_common = new AdminCommon();
        $newData['updatetime'] = time();
        $agent_data = $admin_common->GetFieldListArr($newData,$field_list);

        // 写入缓存
        // $this->redis->set_time('agent_info_'.$id,$agent_data,6000,'agent');

        $update_info = $this->di->agent->where(array("id"=>$id))->update($agent_data);
        $note = '编辑代理商ID：'.$id;
        if ($update_info) {
            // 清除所有代理商缓存
            $this->redis->set_time('agnet_list','',3000,'agent');
            $this->redis->set_time('agent_info_'.$id,'',3000,'agent');
            // 如果有其他联系人
            if (!empty($other_contacts)) {
                foreach ($other_contacts as $key => $value) {
                    if (!empty($value['id'])) {
                        $link_id = $value['id'];
                        unset($value['id']);
                        $this->di->agent_links->where(array("id"=>$link_id,"agent_id"=>$id))->update($value);
                    } else {
                        $value['agent_id'] = $id;
                        $value['addtime'] = time();
                        $this->di->agent_links->insert($value);
                    }
                }
            }
            $rs = array('code'=>1,'msg'=>'000000','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
        }
        $msg = $rs['code'] == 1 ? '成功' : '失败';
        \App\setlog($uid,2,$note,$msg,$note,'编辑代理商');
        return $rs;
    }

    // 03 代理商-列表
    public function GetAgentList($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $type = isset($newData['type']) ? $newData['type'] : 2;
        $follow_type = isset($newData['follow_type']) ? $newData['follow_type'] : 0;
        
        $start_time = isset($newData['start_time']) && !empty($newData['start_time']) ? $newData['start_time'] : 0;
        $end_time = isset($newData['end_time']) && !empty($newData['end_time']) ? $newData['end_time'] : 0;
        $keywords = isset($newData['keywords']) && !empty($newData['keywords']) ? $newData['keywords'] : "";
        $where_arr = isset($newData['where_arr']) && !empty($newData['where_arr']) ? $newData['where_arr'] : array();
        $order_by = isset($newData['order_by']) && !empty($newData['order_by']) ? $newData['order_by'] : array();

        $pageno = isset($newData['pageno']) ? intval($newData['pageno']) : 1;
        $pagesize = isset($newData['pagesize']) ? intval($newData['pagesize']) : $this->pagesize;
        $pagenum = ($pageno-1)*$pagesize;

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        $user_info = $this->di->user->select("is_leader,status,type,type_id")->where("id",$uid)->fetchOne();

        $agent_where = " status = 1 ";
        $agent_date_where = " a.status = 1 ";


        if ($user_info['type'] == 2 && !empty($user_info['type_id'])) {
            // 代理商
            $agent_where .= " AND id = {$user_info['type_id']} ";
            $agent_date_where .= " AND a.id = {$user_info['type_id']} ";
        } elseif ($uid == 1) {
            // 超级管理员
            $agent_where .= "";
            $agent_date_where .= "";
        } else {
            // 普通用户 -- 我的和别人共享给我的
            $agent_where .= " AND FIND_IN_SET('{$uid}', charge_person) ";
            $agent_date_where .= " AND FIND_IN_SET('{$uid}', a.charge_person) ";
        }

        $agent_total = $this->di->agent->where($agent_where)->count("id");//全部数量
        $company_num = $this->di->agent->where($agent_where." AND type = 0")->count("id");// 公司个数
        $person_num = $this->di->agent->where($agent_where." AND type = 1")->count("id");// 个人个数

        $data['agent_total'] = $agent_total ? $agent_total : 0;
        $data['company_num'] = $company_num ? $company_num : 0;
        $data['person_num'] = $person_num ? $person_num : 0;

        // 关键词搜索
        if (!empty($keywords)) {
            $agent_where .= " AND concat(title,mobile) LIKE '%{$keywords}%' ";
            $agent_date_where .= " AND concat(a.title,a.mobile) LIKE '%{$keywords}%' ";

        }

        // 高级搜索
        if (!empty($where_arr)) {
            $senior_where = "";
            $having_where = "";
            $num_arr = array("stu_deal_num","stu_total_num","money_total","yf_money_total","df_money_total");
            foreach ($where_arr as $key => $value) {
                if (is_array($value)) {
                    if ($value[0] == "" || $value[1] == "") {
                        continue;
                    }
                }
                if (!empty($value) || $value == 0) {
                    if (!in_array($key,$num_arr)) {
                        $key = 'a.'.$key;
                        # 其他
                        if (is_array($value) && is_numeric($value[0])) {
                            $senior_where .= " AND ( {$key} BETWEEN {$value[0]} AND {$value[1]} ) ";
                        } elseif (is_array($value)) {
                            $senior_where .= " AND ( UNIX_TIMESTAMP({$key}) between '{$value[0]}' AND '{$value[0]}' ) ";
                        } elseif (is_numeric($value)) {
                            if ($key == 'charge_person') {
                                $senior_where .= " AND FIND_IN_SET('{$value}', charge_person) ";
                            } else {
                                $senior_where .= " AND {$key} = {$value} ";
                            }
                        } else {
                            $senior_where .= " AND {$key} LIKE '%{$value}%' ";
                        }
                    } else {
                        if (is_array($value)) {
                            $having_where .= " AND ( {$key} BETWEEN {$value[0]} AND {$value[1]} ) ";
                        } else {
                            $having_where .= " AND ( {$key} = {$value} ) ";
                        }
                    }
                }
            }
            if (!empty($having_where)) {
                $having_where = substr($having_where,4);
                $having_where = " HAVING ".$having_where;
            } else {
                $having_where = "";
            }
        }

        // 排序
        if (empty($order_by) || $order_by[1] == 0) {
            $order_str = " a.id DESC ";
        } else {
            $order_field = "a.".$order_by[0];
            $order_type = $order_by[1] == 1 ? "DESC" : "ASC";
            $order_str = " {$order_field} {$order_type} ";
        }

        if ($type == 0 || $type == 1) {
            // 类型
            $agent_where .= " AND type = {$type}";
            $agent_date_where .= " AND a.type = {$type}";

        }

        if ($follow_type != 0) {
            switch ($follow_type) {
                case 1:
                    # 今日应联系
                    $agent_date_where .= " AND to_days(FROM_UNIXTIME(a.next_follow,'%Y-%m-%d')) = to_days(now()) ";
                    break;
                case 2:
                    # 今日新增
                    $agent_date_where .= " AND to_days(FROM_UNIXTIME(a.addtime,'%Y-%m-%d')) = to_days(now()) ";
                    break;
                case 3:
                    # 今日跟进过
                    $agent_date_where .= " AND to_days(FROM_UNIXTIME(a.follow_time,'%Y-%m-%d')) = to_days(now()) ";
                    break;
                case 4:
                    # 一周跟进过
                    $mark = "<=";
                    $mark_day = 7;
                    break;
                case 5:
                    # 一周未跟进
                    $mark = ">=";
                    $mark_day = 7;
                    break;
                case 6:
                    # 30天未跟进
                    $mark = ">=";
                    $mark_day = 30;
                    break;
                case 7:
                    # 60天未跟进
                    $mark = ">=";
                    $mark_day = 60;
                    break;
                case 8:
                    # 90天未跟进
                    $mark = ">=";
                    $mark_day = 90;
                    break;
                default:
                    # 全部
                    break;
            }
            if (!empty($mark) && !empty($mark_day)) {
                $end_date         = date('Y-m-d 23:59:59',time()); 
                $agent_date_where .= " AND DATEDIFF('{$end_date}', FROM_UNIXTIME( a.follow_time,  '%Y-%m-%d %H:%i:%s' ) ) {$mark} {$mark_day} ";
            }
        }

        $search_field = "a.id,a.number,a.title,a.flower_name,a.type,a.group,a.full_name,a.sex,a.mobile,a.weixin,a.email,a.follow_up,a.follow_time,a.next_follow,a.addtime,a.charge_person,a.publisher,a.creatid,a.old_creatid,a.post,a.scale,a.company_level,a.qudaojibie,a.leibie,a.dlszuoji,a.identifier";
        
        /**10-30增加预计回款搜索**/
        if ($start_time && $end_time) {
            $agent_date_where .= " AND ( UNIX_TIMESTAMP(af.may_pay_date) between '{$start_time}' AND '{$end_time}' ) ";
            $sql = "SELECT ".$search_field.",count(o.id) as stu_deal_num,count(cd.id) as stu_total_num,SUM(cd.agent_price) as money_total,SUM(cd.paid) as yf_money_total,SUM(cd.obligations) as df_money_total FROM ".$this->prefix."agent a LEFT JOIN ".$this->prefix."customer_data cd ON a.flower_name = cd.agent_name  LEFT JOIN ".$this->prefix."customer_order o ON a.id = o.agent_id LEFT JOIN ".$this->prefix."agent_payment af ON o.agent_id = af.agent_id WHERE ".$agent_date_where." GROUP BY a.id ORDER BY ".$order_str." LIMIT ".$pagenum.",".$pagesize;
            $count_sql = "SELECT a.id FROM ".$this->prefix."agent a LEFT JOIN ".$this->prefix."customer_data cd ON a.flower_name = cd.agent_name LEFT JOIN ".$this->prefix."agent_payment af ON a.id = af.agent_id WHERE ".$agent_date_where." GROUP BY a.id ";
            $agent_list = $this->di->agent->queryAll($sql, array());
            $agent_num = $this->di->agent->queryAll($count_sql,array());
            $total_num = count($agent_num);
        } else {
            // $agent_list = $this->di->agent->select("*")->where($agent_where.$senior_where)->order("sort DESC,addtime DESC")->limit($pagenum,$pagesize)->fetchRows();
            // $total_num = $this->di->agent->where($agent_where.$senior_where)->count("id");// 全部代理
            $sql = "SELECT ".$search_field.",count(o.id) as stu_deal_num,count(cd.id) as stu_total_num,SUM(cd.agent_price) as money_total,SUM(cd.paid) as yf_money_total,SUM(cd.obligations) as df_money_total FROM ".$this->prefix."agent a LEFT JOIN ".$this->prefix."customer_data cd ON a.flower_name = cd.agent_name LEFT JOIN ".$this->prefix."customer_order o ON a.id = o.agent_id WHERE".$agent_date_where.$senior_where." GROUP BY a.id ".$having_where." ORDER BY ".$order_str." LIMIT ".$pagenum.",".$pagesize;
            // var_dump($sql);die;
            // 计算数量
            $count_sql = "SELECT a.id,count(co.id) as stu_deal_num,count(cd.id) as stu_total_num,SUM(cd.agent_price) as money_total,SUM(cd.paid) as yf_money_total,SUM(cd.obligations) as df_money_total FROM ".$this->prefix."agent a LEFT JOIN ".$this->prefix."customer_data cd ON a.flower_name = cd.agent_name LEFT JOIN ".$this->prefix."customer_order co ON a.id = co.agent_id WHERE".$agent_date_where.$senior_where." GROUP BY a.id ".$having_where;
            $agent_list = $this->di->agent->queryAll($sql, array());
            $total_num_arr = $this->di->agent->queryAll($count_sql,array());
            $total_num = count($total_num_arr);
        }
        
        
        if (!empty($agent_list)) {
            $list = array();
            foreach ($agent_list as $key => $value) {
                // 原始创建人
                if (!empty($value['old_creatid'])) {
                    $agent_list[$key]['old_publisher'] = $this->di->user->where(array("id"=>$value['old_creatid']))->fetchOne("realname");
                }

                $money_total += $value['money_total'];
                $total_paid += $value['yf_money_total'];
                $total_obligations += $value['df_money_total'];

                // 预计回款日期
                $return_date = $this->di->agent_payment->where(array("agent_id"=>$value['id']))->order("may_pay_date ASC")->limit(5)->fetchPairs("id","may_pay_date");
                $agent_list[$key]['return_date'] = $return_date;

                // 其他联系人
                $other_contacts = $this->di->agent_links->select("id,full_name,mobile,birthday,sex,weixin,email,qq,telphone,post")->where(array("agent_id"=>$value['id']))->order("addtime DESC")->limit(5)->fetchRows();
                $agent_list[$key]['other_contacts'] = !empty($other_contacts) ? $other_contacts : array();
            }
            $data['agent_list'] = $agent_list ? $agent_list : array();
            $data['agent_num'] = $total_num;
            $data['money_total'] = $money_total;
            $data['total_paid'] = $total_paid;
            $data['total_obligations'] = $total_obligations;
            
            $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>$data);
        } else {
            $rs = array('code'=>1,'msg'=>'000002','data'=>$data,'info'=>$data);
        }
        return $rs;
    }

    // 04 代理商-详情
    public function GetAgentInfo($uid,$id) {
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        $agent_info = $this->redis->get_time('agent_info_'.$id,'agent');
        if (empty($agent_info)) {
            $agent_info = $this->di->agent->select("*")->where(array("id"=>$id,"status"=>1))->fetchOne();
            $this->redis->set_time('agent_info_'.$id,$agent_info,6000,'agent');
        }
        if ($uid == 1) {
            $uid = $agent_info['creatid'];
            $user_where = "";
        } else {
            $user_where = " AND FIND_IN_SET('{$uid}', charge_person) ";
        }
        if (!empty($agent_info)) {
            $num_info = $this->di->customer_data->select("SUM(agent_price) as total_price,SUM(paid) as total_paid,SUM(obligations) as total_obligations")->where(array("agent_num"=>$agent_info['number'],"agent_name"=>$agent_info['flower_name']))->fetchOne();
            // var_dump($num_info);die;
            $agent_info['total_price'] = $num_info['total_price'];
            $agent_info['total_paid'] = $num_info['total_paid'];
            $agent_info['total_obligations'] = $num_info['total_obligations'];

            // 原始创建人
            if (!empty($agent_info['old_creatid'])) {
                $agent_info['old_publisher'] = $this->di->user->where(array("id"=>$agent_info['old_creatid']))->fetchOne("realname");
            }
            
            // 上一个下一个
            $prev_agent_id = $this->di->agent->where("status = 1 ".$user_where." AND `id`<'$id' ")->order("id DESC")->fetchOne("id");
            $next_agent_id = $this->di->agent->where("status = 1 ".$user_where." AND `id`>'$id' ")->order("id ASC")->fetchOne("id");
            $data['next_agent_id'] = $next_agent_id;
            $data['prev_agent_id'] = $prev_agent_id;
            
            $data['agent_info'] = $agent_info;
            // 日志
            $log_list = $this->di->share_log->select("note,addtime")->where(array("type"=>1,"info_id"=>$id,"share_uid"=>$uid))->fetchRows();
            $data['agent_log'] = $log_list;
            // 其他联系人
            $other_contacts = $this->di->agent_links->select("id,full_name,mobile,birthday,sex,weixin,email,qq,telphone,post")->where(array("agent_id"=>$id))->order("addtime DESC")->limit(5)->fetchRows();
            $data['other_contacts'] = !empty($other_contacts) ? $other_contacts : array();

            $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>$data);
        } else {
            $rs = array('code'=>1,'msg'=>'000004','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 05 代理商-删除
    public function PostDeleteAgent($uid,$id) {
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $user_info = $this->di->user->select("realname")->where("id",$uid)->fetchOne();
        $agent_info = $this->di->agent->select("title,creatid")->where(array("id"=>$id,"status"=>1))->fetchOne();
        if ($agent_info['creatid'] != $uid && $uid != 1) {
            $rs = array('code'=>0,'msg'=>'000051','data'=>array(),'info'=>array());
            return $rs;
        }
        $customer_common = new CustomerCommon();
        $recycle = $this->config->get('common.IS_DELETE');
        if ($recycle) {
            $note = '彻底删除代理商ID：'.$id;
            // 删除代理商
            $edit_info = $this->di->agent->where("id",$id)->delete();
        } else {
            $note = '物理删除代理商ID：'.$id;
            $edit_info = $this->di->agent->where("id",$id)->update(array("status"=>0));
        }
        if ($edit_info) {
            $notes = $user_info['realname']."删除代理商：".$agent_info['title'];
            $customer_common->PublicShareLog($id,$uid,$uid,1,'delete',$notes);
            $rs = array('code'=>1,'msg'=>'000005','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'000035','data'=>array(),'info'=>array());
        }
        $msg = $rs['code'] == 1 ? '成功' : '失败';
        \App\setlog($uid,3,$note,$msg,$note,'删除代理商');

        return $rs;
    }

    // 06 代理商-共享
    public function PostShareAgentInfo($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $id = isset($newData['id']) && !empty($newData['id']) ? intval($newData['id']) : 0;
        $share_uid = isset($newData['share_uid']) && !empty($newData['share_uid']) ? $newData['share_uid'] : array();
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($share_uid) || empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $customer_common = new CustomerCommon();
        $rs = $customer_common->PublicShareInfo($uid,$id,$share_uid,"agent");
        return $rs;
    }

    // 07 代理商-移交
    public function PostChangeCreater($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $new_uid = isset($newData['new_uid']) && !empty($newData['new_uid']) ? intval($newData['new_uid']) : 0;
        $agentid_arr = isset($newData['agentid_arr']) && !empty($newData['agentid_arr']) ? $newData['agentid_arr'] : array();

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($new_uid) || empty($agentid_arr)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $username = $this->di->user->where(array("id"=>$uid,"status"=>1))->fetchOne("realname");
        $new_user_info = $this->di->user->select("realname")->where("id",$new_uid)->fetchOne();
        // Step 1: 开启事务
        $this->di->beginTransaction('db_master');
        $customer_common = new CustomerCommon();
        $admin_common = new AdminCommon();
        foreach ($agentid_arr as $key => $value) {
            // 代理商信息
            $agent_info = $this->di->agent->select("title,flower_name,number,creatid,charge_person")->where(array("id"=>$value,"status"=>1))->fetchOne();

            if (empty($agent_info)) {
                continue;
            }
            if ($agent_info['creatid'] == $uid || $uid == 1) {
                // 更改创建人
                $charge_person_str = '/'.$agent_info['creatid'].',/';
                $new_charge_person = $new_uid.',';
                $charge_person_new = preg_replace($charge_person_str, $new_charge_person, $agent_info['charge_person'], 1);
                $update_data['creatid'] = $new_uid;
                $update_data['publisher'] = $new_user_info['realname'];
                $update_data['charge_person'] = $charge_person_new;
                // 合同移交
                $this->di->agent_contract->where(array("agent_id"=>$value))->update($update_data);
                $update_data['old_creatid'] = $agent_info['creatid'];
                // 项目移交
                $this->di->agent_general->where(array("agent_id"=>$value))->update(array("creatid"=>$new_uid,"publisher"=>$new_user_info['realname']));
                $this->di->agent_general_adjust->where(array("agent_id"=>$value))->update(array("creatid"=>$new_uid,"publisher"=>$new_user_info['realname']));
                // 开票
                $this->di->agent_invoice->where(array("agent_id"=>$value))->update(array("creatid"=>$new_uid,"publisher"=>$new_user_info['realname']));
                // 付款
                $this->di->agent_payment->where(array("agent_id"=>$value))->update(array("creatid"=>$new_uid,"publisher"=>$new_user_info['realname']));
                // 退款
                $this->di->agent_refund->where(array("agent_id"=>$value))->update(array("creatid"=>$new_uid,"publisher"=>$new_user_info['realname']));
                // 增加移交日志
                $action = 'change';
                $note = $username."将代理商移交给".$new_user_info['realname'];
                $success .= $agent_info['title'].',';
                // 增加移交消息推送
                $msg_title = $username."将代理商".$agent_info['flower_name']."移交给您！";
                $msg_content = $username."将代理商".$agent_info['flower_name']."移交给您,请及时处理！";
                $admin_common->SendMsgNow($uid,$new_uid,$msg_title,$msg_content,0,"change","agent",$value);
            } else {
                // 共享给我的，取消自己的共享
                if (strpos($agent_info['charge_person'], $uid)) {
                    $charge_person_str = '/'.$uid.',/';
                    $new_charge_person = $new_uid.',';
                    $charge_person_new = preg_replace($charge_person_str, $new_charge_person, $agent_info['charge_person'], 1);
                    // 增加取消共享日志
                    $action = 'cancle';
                    $note = $username."取消代理商".$agent_info['title']."共享";
                    $cancel_success .= $agent_info['title'].',';
                } else {
                    $error .= $agent_info['title'].',';
                    continue;
                }
            }

            $update_data['charge_person'] = $charge_person_new;
            $res = $this->di->agent->where("id",$value)->update($update_data);

            if ($res) {
                // 提交事务
                $this->di->commit('db_master');
                // 增加移交
                $customer_common->PublicShareLog($value,$uid,$new_uid,1,$action,$note);
                
            } else {
                // 回滚事务
                $this->di->rollback('db_master');
            }
        }

        $msg = "";
        if ($success) {
            $msg .= "代理商：".trim($success,",")."成功移交！";
        }
        if ($cancel_success) {
            $msg .= "代理商：".trim($cancel_success,",")."成功取消共享！";
        }
        if ($error) {
            $msg .= "代理商：".trim($error,",")."移交失败！";
        }

        if (empty($msg)) {
            $rs = array('code' => 0, 'msg' => '000054', 'data' => array(), 'info' => array());
        } else {
            $rs = array('code' => 1, 'msg' => $msg, 'data' => array(), 'info' => array());
        }
        return $rs;
    }

    // 08 代理商-学员列表
    public function GetStudentList($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $id = isset($newData['id']) && !empty($newData['id']) ? intval($newData['id']) : 0;
        $type = isset($newData['type']) && !empty($newData['type']) ? $newData['type'] : 0;
        $start_time = isset($newData['start_time']) && !empty($newData['start_time']) ? $newData['start_time'] : 0;
        $end_time = isset($newData['end_time']) && !empty($newData['end_time']) ? $newData['end_time'] : 0;
        $keywords = isset($newData['keywords']) && !empty($newData['keywords']) ? $newData['keywords'] : "";
        $where_arr = isset($newData['where_arr']) && !empty($newData['where_arr']) ? $newData['where_arr'] : array();
        $pageno = isset($newData['pageno']) ? intval($newData['pageno']) : 1;
        $pagesize = isset($newData['pagesize']) ? intval($newData['pagesize']) : $this->pagesize;
        $pagenum = ($pageno-1)*$pagesize;

        $user_info = $this->di->user->select("is_leader,status,type,type_id,group_id")->where("id",$uid)->fetchOne();
        // 判断是否隐藏手机号
        if ($user_info['type'] == 2 && !empty($user_info['type_id'])) {
            # 代理商账号
            $show_phone = \APP\GetPhoneType($user_info['group_id']);
        }
        if ($id == 0) {
            # 学员管理
            if ($user_info['type'] == 2 && !empty($user_info['type_id'])) {
                # 代理商账号
                $agent_num = $this->di->agent->where(array("id"=>$user_info['type_id'],"status"=>1))->fetchOne("number");
                $student_where = " cd.agent_num = '{$agent_num}' ";
            } else {
                # 系统用户
                $agent_where['status'] = 1;
                if ($uid != 1) {
                    $agent_where['creatid'] = $uid;
                    $agent_list = $this->di->agent->where($agent_where)->fetchPairs("number","flower_name");
                    if (empty($agent_list)) {
                        return $rs = array('code' => 1, 'msg' => '000002', 'data' => $data, 'info' => $data);
                    }
                    $agent_number_arr = array_keys($agent_list);
                    $agent_num_str = implode(",", $agent_number_arr);
                    $student_where = " FIND_IN_SET(cd.agent_num, '{$agent_num_str}') ";
                } else {
                    $student_where = " 1 ";
                }
                
            }
            
            
            $params = array();
            $total_arr = $this->redis->get_time('agent_stu_num','agent');
            if (empty($total_arr) || empty($keywords)) {
                // 总学员
                $total_count_sql = "SELECT c.id FROM ".$this->prefix."customer c LEFT JOIN ".$this->prefix."customer_data cd ON cd.id = c.id WHERE ".$student_where;
                $agent_stu_total = $this->di->customer_data->queryAll($total_count_sql,$params);
                $total_arr['total'] = count($agent_stu_total);
                // 总成交学员
                $cj_count_sql = "SELECT co.id FROM ".$this->prefix."customer_order co LEFT JOIN ".$this->prefix."customer_data cd ON cd.id = co.cid WHERE ".$student_where;
                $agent_cj_total = $this->di->customer_data->queryAll($cj_count_sql,$params);
                $total_arr['cj_total'] = count($agent_cj_total);

                // 总未成交
                $total_arr['wcj_total'] = intval($total_arr['total']) - intval($total_arr['cj_total']);

                // 今日成交学员
                $jrcj_count_sql = "SELECT co.id FROM ".$this->prefix."customer_order co LEFT JOIN ".$this->prefix."customer_data cd ON cd.id = co.cid WHERE ".$student_where." AND to_days(FROM_UNIXTIME(co.deal_time,'%Y-%m-%d')) = to_days(now()) ";
                $agent_jrcj_total = $this->di->customer_data->queryAll($jrcj_count_sql,$params);
                $total_arr['jrcj_total'] = count($agent_jrcj_total);

                // 本周成交
                $start_date         =  date('Y-m-d', (time() - ((date('w') == 0 ? 7 : date('w')) - 1) * 24 * 3600));
                $end_date           =  date('Y-m-d', (time() + (7 - (date('w') == 0 ? 7 : date('w'))) * 24 * 3600)); 
                $bzcj_count_sql = "SELECT co.id FROM ".$this->prefix."customer_order co LEFT JOIN ".$this->prefix."customer_data cd ON cd.id = co.cid WHERE ".$student_where." AND FROM_UNIXTIME( co.deal_time,  '%Y-%m-%d %H:%i:%s' ) BETWEEN '$start_date' AND '$end_date' ";
                $agent_bzcj_total = $this->di->customer_data->queryAll($bzcj_count_sql,$params);
                $total_arr['bzcj_total'] = count($agent_bzcj_total);

                // 本月成交
                $days = array(mktime(0, 0, 0, date('m'), 1, date('Y')),mktime(23, 59, 59, date('m'), date('t'), date('Y')));
                $start_date         =  date('Y-m-d H:i:s',$days[0]);
                $end_date           =  date('Y-m-d H:i:s',$days[1]);
                $bycj_count_sql = "SELECT co.id FROM ".$this->prefix."customer_order co LEFT JOIN ".$this->prefix."customer_data cd ON cd.id = co.cid WHERE ".$student_where." AND FROM_UNIXTIME( co.deal_time,  '%Y-%m-%d %H:%i:%s' ) BETWEEN '$start_date' AND '$end_date' ";
                $agent_bycj_total = $this->di->customer_data->queryAll($bycj_count_sql,$params);
                $total_arr['bycj_total'] = count($agent_bycj_total);

                // 总付款金额
                $zfk_count_sql = "SELECT SUM(agent_price) as num FROM ".$this->prefix."customer_data cd WHERE ".$student_where;
                $agent_zfk_total = $this->di->customer_data->queryAll($zfk_count_sql,$params);
                $total_arr['zfk_total'] = $agent_zfk_total[0]['num'];

                // 代付款金额
                $dfk_count_sql = "SELECT SUM(obligations) as num FROM ".$this->prefix."customer_data cd WHERE ".$student_where;
                $agent_dfk_total = $this->di->customer_data->queryAll($dfk_count_sql,$params);
                $total_arr['dfk_total'] = $agent_dfk_total[0]['num'];
                
                // 已付款金额
                $yfk_count_sql = "SELECT SUM(paid) as num FROM ".$this->prefix."customer_data cd WHERE ".$student_where;
                $agent_yfk_total = $this->di->customer_data->queryAll($yfk_count_sql,$params);
                $total_arr['yfk_total'] = $agent_yfk_total[0]['num'];

                $this->redis->set_time('agent_stu_num',$total_arr,6000,'agent');
            }
            $data['total_count'] = $total_arr;
            switch ($type) {
                case 1:
                    # 总成交
                    $deal_where = " AND co.deal_time <> 0 ";
                    break;
                case 2:
                    # 今日成交
                    $start_date         = date('Y-m-d 00:00:00',time()); //今天
                    $end_date           = date('Y-m-d 23:59:59',time());
                    $deal_where = " AND FROM_UNIXTIME( co.deal_time,  '%Y-%m-%d %H:%i:%s' ) BETWEEN '$start_date' AND '$end_date' ";

                    break;
                case 3:
                    # 本周成交
                    $start_date         =  date('Y-m-d', (time() - ((date('w') == 0 ? 7 : date('w')) - 1) * 24 * 3600));
                    $end_date           =  date('Y-m-d', (time() + (7 - (date('w') == 0 ? 7 : date('w'))) * 24 * 3600));
                    $deal_where = " AND FROM_UNIXTIME( co.deal_time,  '%Y-%m-%d %H:%i:%s' ) BETWEEN '$start_date' AND '$end_date' ";

                    break;
                case 4:
                    # 本月成交
                    $days = array(mktime(0, 0, 0, date('m'), 1, date('Y')),mktime(23, 59, 59, date('m'), date('t'), date('Y')));
                    $start_date         =  date('Y-m-d H:i:s',$days[0]);
                    $end_date           =  date('Y-m-d H:i:s',$days[1]);
                    $deal_where = " AND FROM_UNIXTIME( co.deal_time,  '%Y-%m-%d %H:%i:%s' ) BETWEEN '$start_date' AND '$end_date' ";
                    break;
                case 5:
                    # 未成交
                    $deal_where = " AND co.deal_time = 0 ";
                    break;
                default:
                    # 全部
                    $deal_where = "";
                    break;
            }
            if (!empty($keywords)) {
                # 关键词搜索
                $student_where .= " AND concat(cd.agent_name,c.cname) LIKE '%{$keywords}%' ";
            }
            if ($uid == 1) {
                $student_where = " c.status = 1 ";
            }

            // 高级搜索
            if (!empty($where_arr)) {
                $customer_field_arr = array("cname","intentionally","charge_person");
                foreach ($where_arr as $key => $value) {
                    if (is_array($value)) {
                        if ($value[0] == "" || $value[1] == "") {
                            continue;
                        }
                    }
                    if (in_array($key,$customer_field_arr)) {
                        $key = "c.".$key;
                    } elseif ($key == "deal_time") {
                        $key = "co.".$key;
                    } else {
                        $key = "cd.".$key;
                    } 
                    if (!empty($value) || $value == 0) {
                        # 其他
                        if (is_array($value) && is_numeric($value[0])) {
                            $student_where .= " AND ( {$key} BETWEEN {$value[0]} AND {$value[1]} ) ";
                        } elseif (is_array($value)) {
                            $student_where .= " AND ( UNIX_TIMESTAMP({$key}) between '{$value[0]}' AND '{$value[0]}' ) ";
                        } elseif (is_numeric($value)) {
                            $student_where .= " AND {$key} = {$value} ";
                        }  else {
                            $student_where .= " AND {$key} LIKE '%{$value}%' ";
                        }
                    }
                }
            }

            // 时间
            if( $type == 0 && $start_time != 0 && $end_time != 0){
                $student_where .=" AND ( UNIX_TIMESTAMP(cf.may_pay_date) between '{$start_time}' AND '{$end_time}' ) ";
            }

            if (!empty($deal_where)) {
                # 成交
                $sql = "SELECT cd.cid,c.cname,cd.cphone,c.intentionally,c.charge_person,cd.create_time,cd.agent_num,cd.agent_name,cd.agent_price,cd.paid,cd.obligations,co.agent_id,co.id as order_id FROM ".$this->prefix."customer c LEFT JOIN ".$this->prefix."customer_data cd ON cd.cid = c.id LEFT JOIN ".$this->prefix."customer_order co ON cd.id = co.cid LEFT JOIN ".$this->prefix."agent_payment cf ON cf.deal_uid = cd.id WHERE cd.agent_name <> '' AND ".$student_where.$deal_where." ORDER BY c.id DESC LIMIT ".$pagenum.",".$pagesize;
                $count_sql = "SELECT cd.cid FROM ".$this->prefix."customer c LEFT JOIN ".$this->prefix."customer_data cd ON cd.cid = c.id LEFT JOIN ".$this->prefix."customer_order co ON cd.id = co.cid LEFT JOIN ".$this->prefix."agent_payment cf ON cf.deal_uid = cd.id WHERE cd.agent_name <> '' AND ".$student_where.$deal_where;
            } else {
                $sql = "SELECT cd.cid,c.cname,cd.cphone,c.intentionally,c.charge_person,cd.create_time,cd.agent_num,cd.agent_name,cd.agent_price,cd.paid,cd.obligations,co.id as order_id FROM ".$this->prefix."customer c LEFT JOIN ".$this->prefix."customer_data cd ON cd.cid = c.id LEFT JOIN ".$this->prefix."customer_order co ON cd.id = co.cid LEFT JOIN ".$this->prefix."agent_payment cf ON cf.deal_uid = cd.id WHERE cd.agent_name <> '' AND ".$student_where." ORDER BY c.id DESC LIMIT ".$pagenum.",".$pagesize;
                $count_sql = "SELECT cd.cid FROM ".$this->prefix."customer c LEFT JOIN ".$this->prefix."customer_data cd ON cd.cid = c.id LEFT JOIN ".$this->prefix."customer_order co ON cd.id = co.cid LEFT JOIN ".$this->prefix."agent_payment cf ON cf.deal_uid = cd.id WHERE cd.agent_name <> '' AND ".$student_where;
            }
            // var_dump($count_sql);
            // var_dump($sql);die;
        } else {
            # 详情学员列表
            $agent_info = $this->di->agent->select("id,flower_name,number")->where(array("id"=>$id,"status"=>1))->fetchOne();
            if (empty($agent_info)) {
                $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
                return $rs;
            }
            $params[':agent_num'] = $agent_info['number'];
            $params[':agent_name'] = $agent_info['flower_name'];
            $student_where = " cd.agent_num = :agent_num AND cd.agent_name = :agent_name ";
            $sql = "SELECT cd.cid,c.cname,cd.cphone,c.intentionally,c.charge_person,cd.create_time,cd.agent_num,cd.agent_name,cd.agent_price,cd.paid,cd.obligations,co.id as order_id FROM ".$this->prefix."customer_data cd LEFT JOIN ".$this->prefix."customer c ON cd.cid = c.id LEFT JOIN ".$this->prefix."customer_order co ON cd.cid = co.cid WHERE ".$student_where."ORDER BY cd.update_time DESC LIMIT ".$pagenum.",".$pagesize;
            $count_sql = "SELECT cd.cid as num FROM ".$this->prefix."customer_data cd LEFT JOIN ".$this->prefix."customer c ON cd.cid = c.id WHERE ".$student_where;
        }
        $agent_stu_list = $this->di->agent->queryAll($sql, $params);
        $agent_stu_num = $this->di->agent->queryAll($count_sql,$params);
        $data['agent_stu_list'] = $agent_stu_list ? $agent_stu_list : array();
        $data['agent_stu_num'] = count($agent_stu_num);
        if (!empty($agent_stu_list)) {
            foreach ($agent_stu_list as $key => $value) {
                // 成交时间
                $agent_stu_list[$key]['deal_time'] = $this->di->customer_order->where(array("id"=>$value['order_id']))->fetchOne("deal_time");

                $agent_info = $this->di->agent->select("id")->where(array("number"=>$value['agent_num']))->fetchOne();
                $agent_stu_list[$key]['agent_id'] = $agent_info['id'];
                // 预计回款日期
                $agent_stu_list[$key]['return_date'] = $this->di->agent_payment->where(array("agent_id"=>$agent_info['id'],"deal_uid"=>$value['cid']))->order("may_pay_date ASC")->limit(5)->fetchPairs("id","may_pay_date");
                // 如果隐藏手机号
                if ($show_phone) {
                    $agent_stu_list[$key]['cphone'] = substr($value['cphone'], 0, 3) . '*****' . substr($value['cphone'], 8, strlen($value['cphone']));
                }
            }
            $data['agent_stu_list'] = $agent_stu_list ? $agent_stu_list : array();
            $rs = array('code' => 1, 'msg' => '000000', 'data' => $data, 'info' => $data);
        } else {
            $rs = array('code' => 1, 'msg' => '000002', 'data' => $data, 'info' => $data);
        }
        return $rs;
    }

    // 09 合同[代理商]-新增
    public function PostAddAgentContract($uid,$newData,$field_list,$general_list) {
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断用户角色
        $user_info = $this->di->user->select("realname")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000062','data'=>array(),'info'=>array());
            return $rs;
        }

        $admin_common = new AdminCommon();
        $number = \App\CreateOrderNo();
        $newData['number'] = 'DLSHT'.$number;
        $newData['publisher'] = $user_info['realname'];
        $newData['creatid'] = $uid;
        $newData['charge_person'] = $uid.',';
        $newData['sort'] = 0;
        $newData['status'] = 1;
        $newData['addtime'] = time();
        $newData['updatetime'] = time();

        $agent_contract_data = $admin_common->GetFieldListArr($newData,$field_list);
        // Step 1: 开启事务
        $this->di->beginTransaction('db_master');
        $contract_id = $this->di->agent_contract->insert($agent_contract_data);
        $contract_id = $this->di->agent_contract->insert_id();
        if ($contract_id) {
            $note = '新增代理商合同ID：'.$contract_id;
            if (!empty($general_list)) {
                $general_arr = array();
                foreach ($general_list as $key => $value) {
                    $value['contract_id'] = $contract_id;
                    $value['agent_id'] = $newData['agent_id'];
                    $value['number'] = 'DLSHT'.$number;
                    $value['status'] = 1;
                    $value['creatid'] = $uid;
                    $value['publisher'] = $user_info['realname'];
                    $value['addtime'] = time();
                    $value['updatetime'] = time();
                    $contract_general_id = $this->di->agent_general->insert($value);
                    if ($contract_general_id) {
                        $general_arr[] = $contract_general_id;
                    }
                }
            } else {
                $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
                return $rs;
            }
            if ($contract_id && (count($general_list) == count($general_arr)) ) {
                // 提交事务
                \App\setlog($uid,1,$note,'成功',$note,'新增代理商合同');
                $this->di->commit('db_master');
                $rs = array('code'=>1,'msg'=>'000000','data'=>array(),'info'=>array());
            } else {
                // 回滚事务
                $this->di->rollback('db_master');
                \App\setlog($uid,1,$note,'失败',$note,'新增代理商合同');
                $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
            }
        } else {
            $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 10 合同[代理商]-列表
    public function GetAgentContractList($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $agent_id = isset($newData['agent_id']) && !empty($newData['agent_id']) ? intval($newData['agent_id']) : 0;
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($agent_id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        $contract_where['creatid'] = $uid;
        $contract_where['agent_id'] = $agent_id;
        $contract_where['status'] = 1;

        $contract_list = $this->di->agent_contract->select("id,number,person,company,contract_status,start_time,end_time,addtime")->where($contract_where)->order("sort DESC,addtime DESC")->fetchRows();
        if (!empty($contract_list)) {
            $contract_num = $this->di->agent_contract->where($contract_where)->count("id");
            $data['contract_list'] = $contract_list ? $contract_list : array();
            $data['num'] = $contract_num ? $contract_num : 0;
            $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>$data);
        } else {
            $rs = array('code'=>1,'msg'=>'000002','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 11 合同[代理商]-详情
    public function GetAgentContractInfo($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $id = isset($newData['id']) && !empty($newData['id']) ? intval($newData['id']) : 0;
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        $contract_info = $this->redis->get_time('contract_info_'.$id,'agent');
        if (empty($contract_info)) {
            $contract_info = $this->di->agent_contract->select("*")->where(array("id"=>$id,"status"=>1))->fetchOne();
            $this->redis->set_time('contract_info_'.$id,$contract_info,6000,'agent');
        }
        $general_list = $this->di->agent_general->select("id,general_id,title,xuefei,bm_price,zl_price,cl_price,sq_price,total_moeny,agent_money,note")->where(array("contract_id"=>$id,"number"=>$contract_info['number']))->order("updatetime DESC")->fetchRows();
        $contract_info['general_list'] = !empty($general_list) ? $general_list : array();
        if (!empty($contract_info)) {
            $rs = array('code'=>1,'msg'=>'000000','data'=>$contract_info,'info'=>$contract_info);
        } else {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 12 签约项目查看[代理商]
    public function GetAgentGeneralInfo($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $agent_id = isset($newData['agent_id']) && !empty($newData['agent_id']) ? intval($newData['agent_id']) : 0;
        $general_id = isset($newData['general_id']) && !empty($newData['general_id']) ? intval($newData['general_id']) : 0;

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($agent_id) || empty($general_id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        $general_info = $this->redis->get_time('general_info'.$id,'agent');
        if (empty($general_info)) {
            $general_info = $this->di->agent_general->select("id,general_id,title,xuefei,bm_price,zl_price,cl_price,sq_price,total_moeny,agent_money,note,publisher,addtime,updateman,updatetime,contract_id")->where(array("general_id"=>$general_id,"status"=>1))->fetchOne();
            if (!empty($general_info)) {
                $general_info['number'] = $this->di->agent_contract->where(array("id"=>$general_info['contract_id']))->fetchOne("number");
            }
            $this->redis->set_time('general_info'.$id,$general_info,6000,'agent');
        }

        if (!empty($general_info)) {
            $rs = array('code'=>1,'msg'=>'000000','data'=>$general_info,'info'=>$general_info);
        } else {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 13 签约项目列表[代理商]
    public function GetAgentGeneralList($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $agent_id = isset($newData['agent_id']) && !empty($newData['agent_id']) ? intval($newData['agent_id']) : 0;

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($agent_id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }


        $general_list = $this->di->agent_general->select("id,title,contract_id,xuefei,bm_price,bm_price,zl_price,cl_price,sq_price,total_moeny,agent_money,note")->where(array("agent_id"=>$agent_id,"status"=>1))->order("updatetime DESC")->fetchRows();
        if (empty($general_list)) {
            $rs = array('code'=>1,'msg'=>'000002','data'=>array(),'info'=>array());
            return $rs;
        }

        $agent_general_list = array();
        foreach ($general_list as $key => $value) {
            $agent_general_list[$value['contract_id']]['contract_id'] = $value['contract_id'];
            $contract_number = $this->di->agent_contract->where(array("agent_id"=>$agent_id,"id"=>$value['contract_id']))->fetchOne("number");
            $agent_general_list[$value['contract_id']]['contract_number'] = $contract_number;
            $agent_general_list[$value['contract_id']]['value'][] = $value;
        }

        $rs = array('code'=>1,'msg'=>'000000','data'=>$agent_general_list,'info'=>$agent_general_list);
        return $rs;
    }

    // 14 合同调整[代理商]
    public function PostContractAdjust($uid,$newData,$general_list) {
        $agent_id = isset($newData['agent_id']) && !empty($newData['agent_id']) ? intval($newData['agent_id']) : 0;
        $contract_id = isset($newData['contract_id']) && !empty($newData['contract_id']) ? intval($newData['contract_id']) : 0;

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($agent_id) || empty($contract_id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $new_user_info = $this->di->user->select("realname")->where("id",$uid)->fetchOne();
        $contract_info = $this->di->agent_contract->select("number,creatid,publisher,addtime")->where(array("agent_id"=>$agent_id,"id"=>$contract_id,"status"=>1))->fetchOne();
        if (empty($contract_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }

        // Step 1: 开启事务
        $this->di->beginTransaction('db_master');
        foreach ($general_list as $key => $value) {
            $agent_general_info = $this->di->agent_general->where(array("contract_id"=>$contract_id,"agent_id"=>$agent_id,"general_id"=>$value['general_id']))->fetchOne("title");
            if (empty($agent_general_info)) {
                $general_update = array(
                    "contract_id" => $contract_id,
                    "agent_id" => $agent_id,
                    "number" => $contract_info['number'],
                    "general_id" => $value['general_id'],
                    "title" => $value['title'],
                    "xuefei" => $value['new_xuefei'],
                    "bm_price" => $value['new_bm_price'],
                    "zl_price" => $value['new_zl_price'],
                    "cl_price" => $value['new_cl_price'],
                    "sq_price" => $value['new_sq_price'],
                    "total_moeny" => $value['new_total_moeny'],
                    "agent_money" => $value['new_agent_money'],
                    "note" => $value['note'],
                    "creatid" => $uid,
                    "publisher" => $contract_info['publisher'],
                    "addtime" => time(),
                    "updatetime" => time(),
                    "updateman" => $new_user_info['realname']
                );
                $res2 = $this->di->agent_general->insert($general_update);
                $res2 = $this->di->agent_general->insert_id();
            } else {
                $general_update = array(
                    "xuefei" => $value['new_xuefei'],
                    "bm_price" => $value['new_bm_price'],
                    "zl_price" => $value['new_zl_price'],
                    "cl_price" => $value['new_cl_price'],
                    "sq_price" => $value['new_sq_price'],
                    "total_moeny" => $value['new_total_moeny'],
                    "agent_money" => $value['new_agent_money'],
                    "note" => $value['note'],
                    "updatetime" => time(),
                    "updateman" => $new_user_info['realname']
                );
                $res2 = $this->di->agent_general->where(array("contract_id"=>$contract_id,"agent_id"=>$agent_id,"general_id"=>$value['general_id']))->update($general_update);
            }
            $value['contract_id'] = $contract_id;
            $value['agent_id'] = $agent_id;
            $value['number'] = $contract_info['number'];
            $value['status'] = 1;
            $value['creatid'] = $contract_info['creatid'];
            $value['publisher'] = $contract_info['publisher'];
            $value['addtime'] = $contract_info['addtime'];
            $value['updateman'] = $new_user_info['realname'];
            $value['updatetime'] = time();
            unset($value['id']);
            $res = $this->di->agent_general_adjust->insert($value);
            $res = $this->di->agent_general_adjust->insert_id();
            if ($res && $res2) {
                $adjust_id_str .= $res.',';
            }
        }
        $newData['adjust_id'] = $adjust_id_str;
        $newData['status'] = 1;
        $newData['updatetime'] = time();
        $newData['updateman'] = $new_user_info['realname'];
        $contract_adjust_res = $this->di->agent_contract_adjust->insert($newData);
        $contract_update_data = array(
            "money" => $newData['new_money'],
            "updatetime" => time(),
            "updateman" => $new_user_info['realname'],
            "note" => $newData['note'],
            "file" => $newData['file']
        );
        $this->di->agent_contract->where(array("id"=>$contract_id,"agent_id"=>$agent_id))->update($contract_update_data);
        $note = '调整代理商合同ID：'.$contract_id;
        if ($contract_adjust_res) {
            // 提交事务
            $this->di->commit('db_master');
            \App\setlog($uid,2,$note,'成功',$note,'调整代理商合同');
            // 更新合同缓存
            $contract_redis_info = $this->redis->get_time('contract_info_'.$contract_id,'agent');
            if (!empty($contract_redis_info)) {
                $contract_redis_info['money'] = $newData['new_money'];
                $contract_redis_info['updatetime'] = time();
                $contract_redis_info['updateman'] = $new_user_info['realname'];
                $contract_redis_info['note'] = $newData['note'];
                $contract_redis_info['file'] = $newData['file'];
                $this->redis->set_time('contract_info_'.$contract_id,$contract_redis_info,6000,'agent');
            }
            $rs = array('code'=>1,'msg'=>'000000','data'=>array(),'info'=>array());
        } else {
            // 回滚事务
            $this->di->rollback('db_master');
            \App\setlog($uid,2,$note,'失败',$note,'调整代理商合同');
            $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 15 合同调整列表[代理商]
    public function GetContractAdjustList($uid,$newData) {
        $agent_id = isset($newData['agent_id']) && !empty($newData['agent_id']) ? intval($newData['agent_id']) : 0;

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($agent_id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        $contract_list = $this->di->agent_contract_adjust->select("id,contract_id,money,new_money,adjust_money,updatetime")->where(array("agent_id"=>$agent_id,"status"=>1))->order("updatetime DESC")->limit(20)->fetchRows();
        if (!empty($contract_list)) {
            foreach ($contract_list as $key => $value) {
                // 合同编号
                $contract_list[$key]['number'] = $this->di->agent_contract->where("id",$value['contract_id'])->fetchOne("number");
            }
            $rs = array('code'=>1,'msg'=>'000000','data'=>$contract_list,'info'=>$contract_list);
        } else {
            $rs = array('code'=>1,'msg'=>'000002','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 16 合同调整详情[代理商]
    public function GetContractAdjustInfo($newData) {
        $id = isset($newData['id']) && !empty($newData['id']) ? intval($newData['id']) : 0;
        $contract_id = isset($newData['contract_id']) && !empty($newData['contract_id']) ? intval($newData['contract_id']) : 0;
        if (empty($id) || empty($contract_id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        // 调整合同信息
        $contract_adjust_info = $this->di->agent_contract_adjust->select("agent_id,adjust_id,money,new_money,adjust_money,note,file,updatetime,updateman")->where(array("id"=>$id,"contract_id"=>$contract_id,"status"=>1))->fetchOne();
        if (empty($contract_adjust_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }
        // 合同信息
        $contract_info = $this->di->agent_contract->select("title as contract_name,number,publisher,updateman,addtime,updatetime")->where(array("id"=>$contract_id))->fetchOne();
        if (empty($contract_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }

        $contract_info = array_merge($contract_info,$contract_adjust_info);

        $adjust_id_str = trim($contract_info['adjust_id'],",");
        $adjust_id_arr = explode(",",$adjust_id_str);
        if (!empty($adjust_id_arr)) {
            $general_list = array();
            foreach ($adjust_id_arr as $key => $value) {
                $general_info = $this->di->agent_general_adjust->select("general_id,title,xuefei,bm_price,zl_price,cl_price,sq_price,total_moeny,agent_money,new_xuefei,new_bm_price,new_zl_price,new_cl_price,new_sq_price,new_total_moeny,new_agent_money,note")->where(array("id"=>$value))->fetchOne();
                $general_list[$key] = $general_info;
            }
            $contract_info['general_list'] = !empty($general_list) ? $general_list : array();

        }
        $rs = array('code'=>1,'msg'=>'000000','data'=>$contract_info,'info'=>$contract_info);
        return $rs;
    }

    // 17 付款记录-新建[代理商]
    public function PostAgentPaymentAdd($uid,$newData,$field_list) {
        $agent_id = $newData['agent_id'] ? $newData['agent_id'] : 0;
        $contract_id = $newData['contract_id'] ? $newData['contract_id'] : 0;
        $deal_uid = $newData['deal_uid'] ? $newData['deal_uid'] : 0;
        $order_no = $newData['order_no'] ? $newData['order_no'] : "";

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($agent_id) || empty($contract_id) || empty($deal_uid)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        // 判断用户角色
        $user_info = $this->di->user->select("realname")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000062','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断合同信息
        $customer_number = $this->di->agent_contract->where(array("id"=>$contract_id,"status"=>1))->fetchOne("number");
        if (empty($customer_number)) {
            $rs = array('code'=>0,'msg'=>'000050','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断是否已存在付款记录
        $payment_info = $this->di->agent_payment->where(array("agent_id"=>$agent_id,"contract_id"=>$contract_id,"deal_uid"=>$deal_uid,"order_no"=>$order_no,"status"=>1))->fetchOne("title");
        if (!empty($payment_info)) {
            $rs = array('code'=>0,'msg'=>'000071','data'=>array(),'info'=>array());
            return $rs;
        }
        $admin_common = new AdminCommon();
        $number = \App\CreateOrderNo();
        $newData['number'] = 'DLSFK'.$number;
        $newData['contract_number'] = $customer_number;
        if ($newData['pay_state'] == 1) {
            $newData['pay_date'] = time();
        }
        $newData['publisher'] = $user_info['realname'];
        $newData['creatid'] = $uid;
        $newData['sort'] = 0;
        $newData['status'] = 1;
        $newData['addtime'] = time();
        $newData['updatetime'] = time();
        $payment_data = $admin_common->GetFieldListArr($newData,$field_list);
        $payment_id = $this->di->agent_payment->insert($payment_data);
        $payment_id = $this->di->agent_payment->insert_id();
        $note = '新建代理商付款记录ID：'.$payment_id;
        if ($payment_id) {
            $customer_update_arr['paid'] = $this->di->agent_payment->where(array("agent_id"=>$agent_id,"deal_uid"=>$deal_uid,"pay_state"=>1,"status"=>1))->sum("pay_money");
            $customer_update_arr['obligations'] = $this->di->agent_payment->where(array("agent_id"=>$agent_id,"deal_uid"=>$deal_uid,"pay_state"=>0,"status"=>1))->sum("pay_money");
            // 付款金额
            $this->di->customer_data->where(array("cid"=>$deal_uid))->update($customer_update_arr);
            // 清除代理商缓存
            $this->redis->set_time('agent_info_'.$id,$agent_info,6000,'agent');
            \App\setlog($uid,1,$note,'成功',$note,'新建代理商付款记录');
            $rs = array('code'=>1,'msg'=>'000000','data'=>array(),'info'=>array());
        } else {
            \App\setlog($uid,1,$note,'失败',$note,'新建代理商付款记录');
            $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 18 付款记录-编辑[代理商]
    public function PostAgentPaymentEdit($uid,$id,$newData,$field_list) {
        $deal_uid = $newData['deal_uid'] ? $newData['deal_uid'] : 0;
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        $payment_info = $this->di->agent_payment->select("creatid,publisher")->where(array("id"=>$id))->fetchOne();
        if (empty($payment_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }

        // 只允许创建人修改
        if ($payment_info['creatid'] != $uid) {
            $rs = array('code'=>0,'msg'=>'000061','data'=>array(),'info'=>array());
            return $rs;
        }
        $admin_common = new AdminCommon();
        $newData['updatetime'] = time();
        if ($newData['pay_state'] == 1) {
            $newData['pay_date'] = time();
        }
        $payment_data = $admin_common->GetFieldListArr($newData,$field_list);
        $update_info = $this->di->agent_payment->where(array("id"=>$id))->update($payment_data);
        $note = '编辑代理商付款记录ID：'.$id;
        if ($update_info) {
            $customer_update_arr['paid'] = $this->di->agent_payment->where(array("agent_id"=>$newData['agent_id'],"deal_uid"=>$deal_uid,"pay_state"=>1,"status"=>1))->sum("pay_money");
            $customer_update_arr['obligations'] = $this->di->agent_payment->where(array("agent_id"=>$newData['agent_id'],"deal_uid"=>$deal_uid,"pay_state"=>0,"status"=>1))->sum("pay_money");
            // 付款金额
            $this->di->customer_data->where(array("cid"=>$deal_uid))->update($customer_update_arr);
            \App\setlog($uid,2,$note,'成功',$note,'编辑代理商付款记录');
            $payment_data['id'] = $id;
            // 写入缓存
            $this->redis->set_time('agent_payment_info_'.$id,$payment_data,6000,'agent');
            $rs = array('code'=>1,'msg'=>'000000','data'=>array(),'info'=>array());
        } else {
            \App\setlog($uid,2,$note,'失败',$note,'编辑代理商付款记录');
            $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 19 付款记录-列表[代理商]
    public function GetAgentPaymentList($agent_id,$type) {
        if (empty($agent_id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        // 代理商信息
        $agent_info = $this->di->agent->where(array("id"=>$agent_id,"status"=>1))->fetchOne("title");
        if (empty($agent_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }

        if ($type != 2) {
            $payment_where['pay_state'] = $type;
        }

        $payment_where['agent_id'] = $agent_id;

        $payment_list = $this->di->agent_payment->select("*")->where($payment_where)->fetchRows();
        if (!empty($payment_list)) {
            foreach ($payment_list as $key => $value) {
                // 成交客户名称
                $cname = $this->di->customer->where(array("id"=>$value['deal_uid']))->fetchOne("cname");
                $payment_list[$key]['cname'] = $cname;
            }
            $payment_num = $this->di->agent_payment->where(array("agent_id"=>$agent_id))->count("id");
            $data['payment_list'] = $payment_list;
            $data['payment_num'] = $payment_num ? $payment_num : 0;
            $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>$data);
        } else {
            $rs = array('code'=>1,'msg'=>'000002','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 20 付款记录-详情[代理商]
    public function GetAgentPaymentInfo($id) {
        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        $payment_info = $this->redis->get_time('agent_payment_info_'.$id,'agent');
        if (empty($payment_info)) {
            $payment_info = $this->di->agent_payment->select("*")->where(array("id"=>$id,"status"=>1))->fetchOne();
            $this->redis->set_time('agent_payment_info_'.$id,$payment_info,6000,'agent');
        }
        if (!empty($payment_info)) {
            $rs = array('code'=>1,'msg'=>'000000','data'=>$payment_info,'info'=>$payment_info);
        } else {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 21 收票-新增[代理商]
    public function PostAddAgentInvoice($uid,$newData,$field_list) {
        $agent_id = $newData['agent_id'] ? $newData['agent_id'] : 0;
        $payment_number = $newData['payment_number'] ? $newData['payment_number'] : "";

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($agent_id) || empty($payment_number)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断用户角色
        $user_info = $this->di->user->select("realname")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000062','data'=>array(),'info'=>array());
            return $rs;
        }
        
        // 判断代理商信息
        $agent_info = $this->di->agent->where(array("id"=>$agent_id,"status"=>1))->fetchOne("title");
        if (empty($agent_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断付款信息是否存在
        $payment_info = $this->di->agent_payment->where(array("number"=>$payment_number,"status"=>1))->fetchOne("contract_id");
        if (empty($payment_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断是否已存在开票信息
        $invoice_info = $this->di->agent_invoice->where(array("payment_number"=>$payment_number,"agent_id"=>$agent_id,"status"=>1))->fetchOne("number");
        if (!empty($invoice_info)) {
            $rs = array('code'=>0,'msg'=>'000072','data'=>array(),'info'=>array());
            return $rs;
        }
        // 开票编号
        $admin_common = new AdminCommon();
        $number = \App\CreateOrderNo();
        $newData['number'] = 'DLSKP'.$number;
        
        $newData['publisher'] = $user_info['realname'];
        $newData['creatid'] = $uid;
        $newData['sort'] = 0;
        $newData['status'] = 1;
        $newData['addtime'] = time();
        $newData['updatetime'] = time();

        $invoice_data = $admin_common->GetFieldListArr($newData,$field_list);
        $invoice_id = $this->di->agent_invoice->insert($invoice_data);
        $invoice_id = $this->di->agent_invoice->insert_id();
        $note = '新建代理商收票ID：'.$invoice_id;
        if ($invoice_id) {
            // 将付款记录更改为已开票状态
            $this->di->agent_payment->where(array("number"=>$payment_number,"status"=>1))->update(array("invoice_state"=>1,"updatetime"=>time(),"updateman"=>$user_info['realname']));
            \App\setlog($uid,1,$note,'成功',$note,'新建代理商收票');
            $rs = array('code'=>1,'msg'=>'000000','data'=>array(),'info'=>array());
        } else {
            \App\setlog($uid,1,$note,'失败',$note,'新建代理商收票');
            $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 22 收票-编辑[代理商]
    public function PostEditAgentInvoice($uid,$id,$newData,$field_list) {
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断用户角色
        $user_info = $this->di->user->select("realname")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000062','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断是否存在开票信息
        $invoice_info = $this->di->agent_invoice->select("creatid")->where(array("id"=>$id,"status"=>1))->fetchOne();
        if (empty($invoice_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }

        // 只允许创建人修改
        if ($invoice_info['creatid'] != $uid) {
            $rs = array('code'=>0,'msg'=>'000061','data'=>array(),'info'=>array());
            return $rs;
        }

        $admin_common = new AdminCommon();
        $newData['updatetime'] = time();
        $newData['updateman'] = $user_info['realname'];
        $invoice_data = $admin_common->GetFieldListArr($newData,$field_list);
        $update_info = $this->di->agent_invoice->where(array("id"=>$id))->update($invoice_data);
        $note = '编辑代理商收票ID：'.$id;
        if ($update_info) {
            \App\setlog($uid,2,$note,'成功',$note,'编辑代理商收票');
            $invoice_data['id'] = $id;
            // 写入缓存
            $this->redis->set_time('agent_invoice_info_'.$id,$invoice_data,6000,'agent');
            $rs = array('code'=>1,'msg'=>'000000','data'=>array(),'info'=>array());
        } else {
            \App\setlog($uid,2,$note,'失败',$note,'编辑代理商收票');
            $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
        }
        return $rs;
    }
    
    // 23 开票-列表[代理商]
    public function GetAgentInvoiceList($agent_id) {
        if (empty($agent_id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        // 代理商信息
        $agent_info = $this->di->agent->where(array("id"=>$agent_id,"status"=>1))->fetchOne("title");
        if (empty($agent_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }
        // 开票列表
        $invoice_list = $this->di->agent_invoice->select("*")->where(array("agent_id"=>$agent_id,"status"=>1))->order("updatetime DESC")->limit($this->pagesize)->fetchRows();
        if (!empty($invoice_list)) {
            $invoice_num = $this->di->agent_invoice->where(array("agent_id"=>$agent_id,"status"=>1))->count("id");
            $data['invoice_list'] = $invoice_list;
            $data['invoice_num'] = $invoice_num ? $invoice_num : 0;
            $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>$data);
        } else {
            $rs = array('code'=>1,'msg'=>'000002','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 24 开票-详情[代理商]
    public function GetAgentInvoiceInfo($id) {
        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $invoice_info = $this->redis->get_time('agent_invoice_info_'.$id,'agent');
        if (empty($invoice_info)) {
            $invoice_info = $this->di->agent_invoice->select("*")->where(array("id"=>$id,"status"=>1))->fetchOne();
            $this->redis->set_time('agent_invoice_info_'.$id,$invoice_info,6000,'agent');
        }
        if (!empty($invoice_info)) {
            $rs = array('code'=>1,'msg'=>'000000','data'=>$invoice_info,'info'=>$invoice_info);
        } else {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 25 退款-新增[代理商]
    public function PostAgentRefundAdd($uid,$newData,$field_list) {
        $agent_id = $newData['agent_id'] ? $newData['agent_id'] : 0;
        $payment_number = $newData['payment_number'] ? $newData['payment_number'] : "";

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($agent_id) || empty($payment_number)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断用户角色
        $user_info = $this->di->user->select("realname")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000062','data'=>array(),'info'=>array());
            return $rs;
        }
        
        // 判断退款信息是否存在
        $refund_info = $this->di->agent_refund->where(array("payment_number"=>$payment_number,"agent_id"=>$agent_id,"status"=>1))->fetchOne("number");
        if (!empty($refund_info)) {
            $rs = array('code'=>0,'msg'=>'000073','data'=>array(),'info'=>array());
            return $rs;
        }

        // 退款编号
        $admin_common = new AdminCommon();
        
        $newData['publisher'] = $user_info['realname'];
        $newData['creatid'] = $uid;
        $newData['sort'] = 0;
        $newData['status'] = 1;
        $newData['addtime'] = time();
        $newData['updatetime'] = time();

        $refund_data = $admin_common->GetFieldListArr($newData,$field_list);
        $refund_id = $this->di->agent_refund->insert($refund_data);
        $refund_id = $this->di->agent_refund->insert_id();
        $note = '新建代理商退款ID：'.$refund_id;
        if ($refund_id) {
            // 将付款记录更改为已退款状态
            $this->di->agent_payment->where(array("number"=>$payment_number,"status"=>1))->update(array("status"=>2,"updatetime"=>time(),"updateman"=>$user_info['realname']));
            $payment_info = $this->di->agent_payment->select("deal_uid")->where(array("number"=>$payment_number,"status"=>1))->fetchOne();
            // 减少已付款
            $money = floatval($newData['refund_money']);
            $this->di->customer_data->where(array("cid"=>$payment_info['deal_uid']))->updateCounter('paid', -$money);
            \App\setlog($uid,1,$note,'成功',$note,'新建代理商退款');
            $rs = array('code'=>1,'msg'=>'000000','data'=>array(),'info'=>array());
        } else {
            \App\setlog($uid,1,$note,'失败',$note,'新建代理商退款');
            $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 26 退款-编辑[代理商]
    public function PostAgentRefundEdit($uid,$id,$newData,$field_list) {
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断用户角色
        $user_info = $this->di->user->select("realname")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000062','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断是否存在退款信息
        $refund_info = $this->di->agent_refund->select("creatid")->where(array("id"=>$id,"status"=>1))->fetchOne();
        if (empty($refund_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }

        // 只允许创建人修改
        if ($refund_info['creatid'] != $uid) {
            $rs = array('code'=>0,'msg'=>'000061','data'=>array(),'info'=>array());
            return $rs;
        }

        $admin_common = new AdminCommon();
        $newData['updatetime'] = time();
        $newData['updateman'] = $user_info['realname'];
        $refund_data = $admin_common->GetFieldListArr($newData,$field_list);
        $update_info = $this->di->agent_refund->where(array("id"=>$id))->update($refund_data);
        $note = '编辑代理商退款ID：'.$id;
        if ($update_info) {
            $refund_data['id'] = $id;
            // 写入缓存
            $this->redis->set_time('agent_refund_info_'.$id,$refund_data,6000,'agent');
            \App\setlog($uid,2,$note,'成功',$note,'编辑代理商退款');

            $rs = array('code'=>1,'msg'=>'000000','data'=>array(),'info'=>array());
        } else {
            \App\setlog($uid,2,$note,'失败',$note,'编辑代理商退款');
            $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 27 退款-列表[代理商]
    public function GetAgentRefundList($agent_id) {
        if (empty($agent_id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        // 开票列表
        $refund_list = $this->di->agent_refund->select("*")->where(array("agent_id"=>$agent_id,"status"=>1))->order("updatetime DESC")->limit($this->pagesize)->fetchRows();
        if (!empty($refund_list)) {
            $refund_num = $this->di->agent_refund->where(array("agent_id"=>$agent_id,"status"=>1))->count("id");
            $data['refund_list'] = $refund_list;
            $data['refund_num'] = $refund_num ? $refund_num : 0;
            $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>$data);
        } else {
            $rs = array('code'=>1,'msg'=>'000002','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 28 退款-详情[代理商]
    public function GetAgentRefundInfo($id) {
        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $refund_info = $this->redis->get_time('agent_refund_info_'.$id,'agent');
        if (empty($refund_info)) {
            $refund_info = $this->di->agent_refund->select("*")->where(array("id"=>$id,"status"=>1))->fetchOne();
            $this->redis->set_time('agent_refund_info_'.$id,$refund_info,6000,'agent');
        }
        if (!empty($refund_info)) {
            $rs = array('code'=>1,'msg'=>'000000','data'=>$refund_info,'info'=>$refund_info);
        } else {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 29 师资新增
    public function PostAddTeacher($uid,$newData,$field_list) {
        $name = isset($newData['name']) && !empty($newData['name']) ? $newData['name'] : "";
        $phone = isset($newData['phone']) && !empty($newData['phone']) ? $newData['phone'] : "";


        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        $user_info = $this->di->user->select("realname")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000062','data'=>array(),'info'=>array());
            return $rs;
        }

        // 查询是否存在该师资信息
        $teacher_info = $this->di->teacher->select("id,status")->where(array("name"=>$name,"phone"=>$phone))->fetchOne();
        if (!empty($teacher_info)) {
            $rs = array('code'=>0,'msg'=>'000060','data'=>array(),'info'=>array());
            return $rs;
        }
        $customer_common = new CustomerCommon();
        $admin_common = new AdminCommon();

        $newData['sort'] = 0;
        // $newData['status'] = 1;
        $newData['creatid'] = $uid;
        $newData['publisher'] = $user_info['realname'];
        $newData['addtime'] = time();
        $newData['updatetime'] = time();
        $newData['charge_person'] = $uid.",";
        $newData['follow_time'] = time();//最后跟进日期默认为当前
        $newData['follow_up'] = 0;//跟进次数默认为0
        $newData['next_follow'] = 0;//跟进次数默认为0

        $teacher_data = $admin_common->GetFieldListArr($newData,$field_list);
        $teacher_id = $this->di->teacher->insert($teacher_data);
        $teacher_id = $this->di->teacher->insert_id();
        $note = '新增师资ID：'.$teacher_id;
        if ($teacher_id) {
            \App\setlog($uid,1,$note,'成功',$note,'新增师资');
            // 师资新增
            $note = $user_info['realname']."新增师资";
            $customer_common->PublicShareLog($teacher_id,$uid,$uid,2,'add',$note);
            $rs = array('code'=>1,'msg'=>'000000','data'=>array(),'info'=>array());
        } else {
            \App\setlog($uid,1,$note,'失败',$note,'新增师资');
            $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 30 师资列表
    public function GetTeacherList($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $type = isset($newData['type']) ? $newData['type'] : 0;
        $follow_type = isset($newData['follow_type']) ? $newData['follow_type'] : 0;
        $keywords = isset($newData['keywords']) && !empty($newData['keywords']) ? $newData['keywords'] : "";
        $where_arr = isset($newData['where_arr']) && !empty($newData['where_arr']) ? $newData['where_arr'] : array();
        $pageno = isset($newData['pageno']) ? intval($newData['pageno']) : 1;
        $pagesize = isset($newData['pagesize']) ? intval($newData['pagesize']) : $this->pagesize;
        $pagenum = ($pageno-1)*$pagesize;

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        $user_info = $this->di->user->select("is_leader,status,structure_id")->where("id",$uid)->fetchOne();
        $status_where = " status != 0 ";
        if ($uid != 1) {
            if ($user_info['is_leader'] == 1) {
                # 部门主管
                $structure_id = json_decode($user_info['structure_id']);
                $user_structure_id = array_pop($structure_id);
                $teacher_where = " AND structure_id = {$user_structure_id} ";
            } else {
                // 普通用户 -- 我的和别人共享给我的
                $teacher_where = " AND ( creatid = '{$uid}' OR FIND_IN_SET('{$uid}', charge_person) ) ";
            }
        } else {
            $teacher_where = "";
        }
        // 计算数量
        $total_num = $this->di->teacher->where($status_where.$teacher_where)->count("id");// 全部师资
        $having_num = $this->di->teacher->where(" status = 1 ".$teacher_where)->count("id");// 进行中
        $ending_num = $this->di->teacher->where(" status = 2 ".$teacher_where)->count("id");// 无效师资

        if ($type != 0) {
            $status_where = " status = {$type} ";
        }

        if (!empty($keywords)) {
            $status_where .= " AND name LIKE '%{$keywords}%' ";
        }

        if ($follow_type != 0) {
            switch ($follow_type) {
                case 1:
                    # 今日应联系
                    $teacher_where .= " AND to_days(FROM_UNIXTIME(next_follow,'%Y-%m-%d')) = to_days(now()) ";
                    break;
                case 2:
                    # 今日新增
                    $teacher_where .= " AND to_days(FROM_UNIXTIME(addtime,'%Y-%m-%d')) = to_days(now()) ";
                    break;
                case 3:
                    # 今日跟进过
                    $teacher_where .= " AND to_days(FROM_UNIXTIME(follow_time,'%Y-%m-%d')) = to_days(now()) ";
                    break;
                case 4:
                    # 一周跟进过
                    $mark = "<=";
                    $mark_day = 7;
                    break;
                case 5:
                    # 一周未跟进
                    $mark = ">=";
                    $mark_day = 7;
                    break;
                case 6:
                    # 30天未跟进
                    $mark = ">=";
                    $mark_day = 30;
                    break;
                case 7:
                    # 60天未跟进
                    $mark = ">=";
                    $mark_day = 60;
                    break;
                case 8:
                    # 90天未跟进
                    $mark = ">=";
                    $mark_day = 90;
                    break;
                default:
                    # 全部
                    break;
            }
            if (!empty($mark) && !empty($mark_day)) {
                $end_date         = date('Y-m-d 23:59:59',time()); 
                $teacher_where .= " AND DATEDIFF('{$end_date}', FROM_UNIXTIME( follow_time,  '%Y-%m-%d %H:%i:%s' ) ) {$mark} {$mark_day} ";
            }
        }

        // 高级搜索
        if (!empty($where_arr)) {
            foreach ($where_arr as $key => $value) {
                if (is_array($value)) {
                    if ($value[0] == "" || $value[1] == "") {
                        continue;
                    }
                }
                if (!empty($value)) {
                    if (is_array($value) && is_numeric($value[0])) {
                        $teacher_where .= " AND ( {$key} BETWEEN {$value[0]} AND {$value[1]} ) ";
                    } elseif (is_array($value)) {
                        $teacher_where .= " AND ( UNIX_TIMESTAMP({$key}) between '{$value[0]}' AND '{$value[0]}' ) ";
                    } elseif (is_numeric($value)) {
                        $teacher_where .= " AND {$key} = {$value} ";
                    } else {
                        $teacher_where .= " AND {$key} LIKE '%{$value}%' ";
                    }
                }
            }
        }

        $teacher_list = $this->di->teacher->select("*")->where($status_where.$teacher_where)->order("sort DESC,addtime DESC")->limit($pagenum,$pagesize)->fetchRows();
        $teacher_num = $this->di->teacher->where($status_where.$teacher_where)->count("id");
        
        $data['count']['total_num'] = $total_num;
        $data['count']['having_num'] = $having_num;
        $data['count']['ending_num'] = $ending_num;

        if (!empty($teacher_list)) {
            $data['teacher_list'] = $teacher_list ? $teacher_list : array();
            $data['teacher_num'] = $teacher_num;
            $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>$data);
        } else {
            $rs = array('code'=>1,'msg'=>'000002','data'=>$data,'info'=>$data);
        }
        return $rs;
    }

    // 31 师资编辑
    public function PostEditTeacher($uid,$id,$newData,$field_list) {
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        // 查询是否存在该师资信息
        $publisher = $this->di->teacher->where(array("id"=>$id))->fetchOne("creatid");
        if (empty($publisher)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }
        // 只允许创建人修改
        if ($publisher != $uid) {
            $rs = array('code'=>0,'msg'=>'000061','data'=>array(),'info'=>array());
            return $rs;
        }
        $admin_common = new AdminCommon();
        $newData['updatetime'] = time();
        $teacher_data = $admin_common->GetFieldListArr($newData,$field_list);
        
        $update_info = $this->di->teacher->where(array("id"=>$id))->update($teacher_data);
        $note = '编辑师资ID：'.$id;
        if ($update_info) {
            \App\setlog($uid,2,$note,'成功',$note,'编辑师资');

            $rs = array('code'=>1,'msg'=>'000000','data'=>array(),'info'=>array());
        } else {
            \App\setlog($uid,2,$note,'失败',$note,'编辑师资');

            $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 32 [批量]删除师资
    public function PostDeleteTeacher($uid,$id) {
        $id = $id ? $id : array();
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        $user_info = $this->di->user->select("realname")->where("id",$uid)->fetchOne();
        $customer_common = new CustomerCommon();
        $recycle = $this->config->get('common.IS_DELETE');
        

        foreach ($id as $key => $value) {
            $note = '删除师资ID：'.$value;
            $teacher_info = $this->di->teacher->select("name,creatid")->where(array("id"=>$value))->fetchOne();
            if (empty($teacher_info) || ($teacher_info['creatid'] != $uid)) {
                continue;
            }
            if ($recycle) {
                // 删除师资信息
                $edit_info = $this->di->teacher->where("id",$value)->delete();
                // 删除师资上课信息
                // 删除师资付款记录
                // 删除师资日志
                // $this->di->customer_log->where("cid",$id)->delete();
                // $customer_common->CustomerActionLog($uid,$id,7,'删除客户-永久','彻底删除客户');
                \App\setlog($uid,3,$note,'成功',$note,'删除师资');
                $notes = $user_info['realname']."删除师资：".$teacher_info['name'];
                $customer_common->PublicShareLog($value,$uid,$uid,2,'delete',$notes);
            } else {
                // $customer_common->CustomerActionLog($uid,$id,7,'删除客户-回收','回收客户');
                $edit_info = $this->di->teacher->where("id",$id)->update(array("status"=>0));
                \App\setlog($uid,3,$note,'失败',$note,'删除师资');

            }
            if ($edit_info) {
                $success_msg .= $teacher_info['name'].',';
            }

        }
        if ($success_msg) {
            
            $msg = "师资：".trim($success_msg,",")."删除成功！";
            $rs = array('code'=>1,'msg'=>$msg,'data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'000035','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 33 师资详情
    public function GetTeacherInfo($uid,$id) {
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }


        $teacher_info = $this->di->teacher->select("*")->where(array("id"=>$id))->fetchOne();
        if (empty($teacher_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }

        if ($uid == 1) {
            $uid = $teacher_info['creatid'];
        }

        // 师资上课记录
        $educlass_list = $this->di->educlass->select("id,class_num,xm,zhuti,class_name,class_date,class_adress,fankui,note,status")->where(array("edu_uid"=>$id,"is_delete"=>0))->order("id DESC")->limit(20)->fetchRows();
        // 师资薪酬
        $edusalary_list = $this->di->edusalary->select("id,class_num,pay_time,pay_money,pay_type,askforman_name")->where(array("edu_uid"=>$id,"status"=>0))->order("id DESC")->limit(20)->fetchRows();
        foreach ($edusalary_list as $key => $value) {
            $edusalary_list[$key]['class_date'] = $this->di->educlass->where(array("class_num"=>$value['class_num'],"is_delete"=>0))->fetchOne("class_date");
        }
        // 师资操作日志
        $log_list = $this->di->share_log->select("note,addtime")->where(array("type"=>2,"info_id"=>$teacher_info['id'],"share_uid"=>$uid))->fetchRows();

        // 上一个下一个
        $prev_agent_id = $this->di->teacher->where("status <> 0 AND FIND_IN_SET('{$uid}', charge_person) AND `id`<'$id' ")->order("id DESC")->fetchOne("id");
        $next_agent_id = $this->di->teacher->where("status <> 0 AND FIND_IN_SET('{$uid}', charge_person) AND `id`>'$id' ")->order("id ASC")->fetchOne("id");
        $data['next_agent_id'] = $next_agent_id;
        $data['prev_agent_id'] = $prev_agent_id;

        $data['teacher_info'] = $teacher_info;
        $data['educlass_list'] = $educlass_list;
        $data['edusalary_list'] = $edusalary_list;
        $data['teacher_log'] = $log_list;
        
        $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>$data);
        return $rs;
    }

    // 34 师资共享
    public function PostShareTeacher($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $id = isset($newData['id']) && !empty($newData['id']) ? intval($newData['id']) : 0;
        $share_uid = isset($newData['share_uid']) && !empty($newData['share_uid']) ? $newData['share_uid'] : array();
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($share_uid) || empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $customer_common = new CustomerCommon();
        $rs = $customer_common->PublicShareInfo($uid,$id,$share_uid,"teacher");
        return $rs;
    }

    // 35 师资移交
    public function PostChangeTeacher($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $new_uid = isset($newData['new_uid']) && !empty($newData['new_uid']) ? intval($newData['new_uid']) : 0;
        $id_arr = isset($newData['id_arr']) && !empty($newData['id_arr']) ? $newData['id_arr'] : array();

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($new_uid) || empty($id_arr)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $username = $this->di->user->where(array("id"=>$uid,"status"=>1))->fetchOne("realname");
        $new_user_info = $this->di->user->select("realname")->where("id",$new_uid)->fetchOne();

        // Step 1: 开启事务
        $this->di->beginTransaction('db_master');
        $customer_common = new CustomerCommon();
        $admin_common = new AdminCommon();

        foreach ($id_arr as $key => $value) {
            // 师资信息
            $teacher_info = $this->di->teacher->select("name,creatid,charge_person")->where(array("id"=>$value,"status"=>1))->fetchOne();
            if (empty($teacher_info)) {
                continue;
            }
            if ($teacher_info['creatid'] == $uid) {
                // 更改创建人
                $charge_person_new = str_replace("{$uid},","{$new_uid},",$teacher_info['charge_person']);

                $update_data['creatid'] = $new_uid;
                $update_data['publisher'] = $new_user_info['realname'];
                $update_data['charge_person'] = $charge_person_new;

                // 增加移交日志
                $action = 'change';
                $note = $username."将师资移交给".$new_user_info['realname'];
                $success .= $teacher_info['name'].',';

                // 增加移交消息推送
                $msg_title = $username."将师资".$teacher_info['name']."移交给您！";
                $msg_content = $username."将师资".$teacher_info['name']."移交给您,请及时处理！";
                $admin_common->SendMsgNow($uid,$new_uid,$msg_title,$msg_content,0,"change","teacher",$value);

            } else {
                // 共享给我的，取消自己的共享
                if (strpos($teacher_info['charge_person'], $uid)) {
                    $charge_person_new = str_replace(",{$uid},",",",$teacher_info['charge_person']);
                    // 增加取消共享日志
                    $action = 'cancle';
                    $note =  $username."取消师资".$teacher_info['name']."的共享";
                    $cancel_success .= $teacher_info['name'].',';
                } else {
                    $error .= $teacher_info['name'].',';
                    continue;
                }
            }
            // 师资移交
            $update_data['charge_person'] = $charge_person_new;
            $res = $this->di->teacher->where("id",$value)->update($update_data);

            if ($res) {
                // 提交事务
                $this->di->commit('db_master');
                $customer_common->PublicShareLog($value,$uid,$new_uid,2,$action,$note);
                // 更改日志表
            } else {
                // 回滚事务
                $this->di->rollback('db_master');
            }
        }

        $msg = "";
        if ($success) {
            $msg .= "师资：".trim($success,",")."成功移交！";
        }
        if ($cancel_success) {
            $msg .= "师资：".trim($cancel_success,",")."成功取消共享！";
        }
        if ($error) {
            $msg .= "师资：".trim($error,",")."移交失败！";
        }

        if (empty($msg)) {
            $rs = array('code' => 0, 'msg' => '000054', 'data' => array(), 'info' => array());
        } else {
            $rs = array('code' => 1, 'msg' => $msg, 'data' => array(), 'info' => array());
        }
        return $rs;
    }

    // 36 消息列表
    public function GetMsgList($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $keywords = isset($newData['keywords']) && !empty($newData['keywords']) ? $newData['keywords'] : "";
        $where_arr = isset($newData['where_arr']) && !empty($newData['where_arr']) ? $newData['where_arr'] : array();
        $pageno = isset($newData['pageno']) ? intval($newData['pageno']) : 1;
        $pagesize = isset($newData['pagesize']) ? intval($newData['pagesize']) : $this->pagesize;
        $pagenum = ($pageno-1)*$pagesize;

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        $status_where = " status != 2 AND to_uid = '{$uid}' ";
        if (!empty($keywords)) {
            $status_where .= " AND title LIKE '%{$keywords}%' ";
        }

        // 高级搜索
        if (!empty($where_arr)) {
            foreach ($where_arr as $key => $value) {
                if (is_array($value)) {
                    if ($value[0] == "" || $value[1] == "") {
                        continue;
                    }
                }
                if (!empty($value) || $value == 0) {
                    if (is_array($value) && is_numeric($value[0])) {
                        $status_where .= " AND ( {$key} BETWEEN {$value[0]} AND {$value[1]} ) ";
                    } elseif (is_array($value)) {
                        $status_where .= " AND ( UNIX_TIMESTAMP({$key}) between '{$value[0]}' AND '{$value[0]}' ) ";
                    } elseif (is_numeric($value)) {
                        $status_where .= " AND {$key} = {$value} ";
                    } else {
                        $status_where .= " AND {$key} LIKE '%{$value}%' ";
                    }
                }
            }
        }

        $msg_list = $this->di->msg->select("*")->where($status_where)->order("addtime DESC")->limit($pagenum,$pagesize)->fetchRows();
        $msg_num = $this->di->msg->where($status_where)->count("id");
        // 未读消息数量
        $wd_msg_num = $this->di->msg->where(array("status"=>0,"to_uid"=>$uid))->count("id");
        if (!empty($msg_list)) {
            $data['msg_list'] = $msg_list ? $msg_list : array();
            $data['msg_num'] = $msg_num;
            $data['wd_msg_num'] = $wd_msg_num;
            $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>$data);
        } else {
            $rs = array('code'=>1,'msg'=>'000002','data'=>$data,'info'=>$data);
        }
        return $rs;
    }

    // 37 消息详情
    public function GetMsgInfo($id) {
        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        $msg_info = $this->redis->get_time('msg_info_'.$id,'msg');
        if (empty($msg_info)) {
            $msg_info = $this->di->msg->select("*")->where(array("id"=>$id))->fetchOne();
            $this->redis->set_time('msg_info_'.$id,$msg_info,6000,'msg');
        }
        if (!empty($msg_info)) {
            $rs = array('code'=>1,'msg'=>'000000','data'=>$msg_info,'info'=>$msg_info);
        } else {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
        }
        return $rs;  
    }

    // 38 消息批量删除
    public function PostDeleteMsg($uid,$id_arr) {
        $id_arr = $id_arr ? $id_arr : array();
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($id_arr)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        $customer_common = new CustomerCommon();
        $recycle = $this->config->get('common.IS_DELETE');
        foreach ($id_arr as $key => $value) {
            $msg_info = $this->di->msg->select("title,creatid")->where(array("id"=>$value))->fetchOne();
            
            if ($recycle) {
                // 删除消息信息
                $edit_info = $this->di->msg->where("id",$value)->delete();
                // 增加操作记录
                // 
            } else {
                // 扔回收站
                $edit_info = $this->di->msg->where("id",$value)->update(array("status"=>2));
                // 增加操作记录
                // 
            }
        }
        $rs = array('code'=>1,'msg'=>'000005','data'=>array(),'info'=>array());
        return $rs;       
    }

    // 39 消息批量已读
    public function PostReadMsg($uid,$id_arr) {
        $id_arr = $id_arr ? $id_arr : array();
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($id_arr)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        foreach ($id_arr as $key => $value) {
            $this->di->msg->where("id",$value)->update(array("status"=>1,"end_time"=>time()));
        }
        $rs = array('code'=>1,'msg'=>'000000','data'=>array(),'info'=>array());
        return $rs;  
    }

    // 40 成交属性分析
    public function GetStatisticsList($newData) {
        $type = isset($newData['type']) && !empty($newData['type']) ? intval($newData['type']) : 999;
        $start_time = isset($newData['start_time']) && !empty($newData['start_time']) ? $newData['start_time'] : "";
        $end_time = isset($newData['end_time']) && !empty($newData['end_time']) ? $newData['end_time'] : "";

        if ($start_time && $end_time) {
            $time_where = " AND FROM_UNIXTIME( o.deal_time ,  '%Y-%m-%d %H:%i:%s' ) BETWEEN '$start_time' AND '$end_time' ";
        } else {
            switch ($type) {
                case 1:
                    # 30天
                    $start              = strtotime(date("Y-m-d",strtotime("-30 day")));
                    $start_time         = date('Y-m-d H:i:s',$start); //今天
                    $end_time           = date('Y-m-d 23:59:59',time());
                    break;
                case 2:
                    # 3个月
                    $start              = strtotime(date("Y-m-d",strtotime("-3 month")));
                    $start_time         = date('Y-m-d H:i:s',$start); //今天
                    $end_time           = date('Y-m-d 23:59:59',time());
                    break;
                case 3:
                    # 半年
                    $start              = strtotime(date("Y-m-d",strtotime("-6 month")));
                    $start_time         = date('Y-m-d H:i:s',$start); //今天
                    $end_time           = date('Y-m-d 23:59:59',time());
                    break;
                case 4:
                    # 本年
                    //本年开始时间
                    $start_time = date("Y",time())."-1"."-1";
                    //本年结束时间
                    $end_time = date("Y",time())."-12"."-31";
                    break;
                default:
                    $start_time = "";
                    $end_time = "";
                    break;
            }

            $time_where = $start_time && $end_time ? " AND FROM_UNIXTIME( o.deal_time ,  '%Y-%m-%d %H:%i:%s' ) BETWEEN '$start_time' AND '$end_time' " : "";
        }
        // 查询所有的成交
        $sql = "SELECT o.cid,cd.sex,cd.age,cd.occupation,gr.education,o.total as money,o.deal_time,gr.title,s.title as school_name,md.title as redirect_name,gr.province FROM ".$this->prefix."customer_order o LEFT JOIN ".$this->prefix."general_rules gr ON o.`general_id` = gr.id LEFT JOIN ".$this->prefix."customer_data cd ON o.cid = cd.cid LEFT JOIN ".$this->prefix."school s ON gr.school_id = s.id LEFT JOIN ".$this->prefix."major_direction md ON gr.redirect_id = md.id WHERE o.deal_time <> 0 ".$time_where;

        if ($type != 999) {
           $deal_list = $this->redis->get_time('deal_list_'.$type,'list');
           if (empty($deal_list)) {
               $deal_list = $this->di->customer_data->queryAll($sql,array());
               $this->redis->set_time('deal_list_',$deal_list,600,'list');
           }
        } else {
            $deal_list = $this->di->customer_data->queryAll($sql,array());
        }

        $sex_name_arr = array('','男','女');
        $money_config = $this->config->get('common.MONEY_ARR');
        $age_config = $this->config->get('common.AGE_ARR');

        $echars_list = $this->redis->get_time('echars_list_'.$type,'list');
        if ($type != 999 && !empty($echars_list)) {
            $data['sex_list'] = $echars_list['sex_list'];
            $data['education_list'] = $echars_list['education_list'];
            $data['work_list'] = $echars_list['work_list'];
            $data['money_list'] = $echars_list['money_list'];
            $data['month_list'] = $echars_list['month_list'];
            $data['general_list'] = $echars_list['general_list'];
            $data['yuanxi_list'] = $echars_list['yuanxi_list'];
            $data['direction_list'] = $echars_list['direction_list'];
            $data['area_list'] = $echars_list['area_list'];
        } else {
            $sex_list = array();
            $age_list = array();
            $education_list = array();
            $work_list = array();
            $money_list = array();
            $month_list = array();         
            $general_list = array();
            $yuanxi_list = array();
            $direction_list = array();
            $area_list = array();

            foreach ($deal_list as $key => $value) {
                // 性别
                $sex_list[$sex_name_arr[$value['sex']]]['name'] = $sex_name_arr[$value['sex']];
                $sex_list[$sex_name_arr[$value['sex']]]['value'] += 1;

                // 年龄
                $birth_time = strtotime($value['age']);
                $age = \App\getAge($birth_time);
                $age_name = "";
                foreach ($age_config as $mk => $mv) {
                    if ( ($mv['max'] == 0) && ($age >= $mv['min']) ) {
                        $age_name = $mv['name'];
                        break;
                    } elseif ( ($age >= $mv['min']) && ($age < $mv['max']) ) {
                        $age_name = $mv['name'];
                        break;
                    } else {
                        $age_name = "其他";
                        break;
                    }
                }
                $age_list[$age_name]['name'] = $age_name;
                $age_list[$age_name]['value'] += 1;

                
                // 学历
                $education_list[$value['education']]['name'] = $value['education'];
                $education_list[$value['education']]['value'] += 1;

                // 职业
                if ($value['occupation'] == "") {
                    $value['occupation'] = "其他";
                }
                $work_list[$value['occupation']]['name'] = $value['occupation'];
                $work_list[$value['occupation']]['value'] += 1;

                // 成交费用
                $money_group_name = "";
                foreach ($money_config as $mk => $mv) {
                    if ( ($mv['max'] == 0) && ($value['money'] >= $mv['min']) ) {
                        $money_group_name = $mv['name'];
                        break;
                    } elseif ( ($value['money'] >= $mv['min']) && ($value['money'] < $mv['max']) ) {
                        $money_group_name = $mv['name'];
                        break;
                    } else {
                        $money_group_name = "其他";
                        break;
                    }
                }
                $money_list[$money_group_name]['name'] = $money_group_name;
                $money_list[$money_group_name]['value'] += 1; 
                
                // 成交月份
                $month = date('n',$value['deal_time']);
                if ($month == "" || $month == 0) {
                    $month = "其他";
                }
                $month_list[$month]['name'] = $month.'月';
                $month_list[$month]['value'] += 1;

                // 成交简章
                $general_list[$value['title']]['name'] = $value['title'];
                $general_list[$value['title']]['value'] += 1;

                // 成交院校
                $yuanxi_list[$value['school_name']]['name'] = $value['school_name'];
                $yuanxi_list[$value['school_name']]['value'] += 1;

                // 成交方向
                $direction_list[$value['redirect_name']]['name'] = $value['redirect_name'];
                $direction_list[$value['redirect_name']]['value'] += 1;

                // 成交地区
                $area_list[$value['province']]['name'] = $value['province'];
                $area_list[$value['province']]['value'] += 1;          
            }

            $data['sex_list'] = array_values($sex_list);
            $data['age_list'] = array_values($age_list);
            $data['education_list'] = array_values($education_list);
            $data['work_list'] = array_values($work_list);
            $data['money_list'] = array_values($money_list);
            $data['month_list'] = array_values($month_list);
            $data['general_list'] = array_values($general_list);
            $data['yuanxi_list'] = array_values($yuanxi_list);
            $data['direction_list'] = array_values($direction_list);
            $data['area_list'] = array_values($area_list);

            // 写入缓存
            $this->redis->set_time('echars_list_',$type,600,'list');
        }

        $data['total'] = count($deal_list);
        return $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>$data);
    }

    // 41 公共新建跟进记录
    public function PostAddPublicFollow($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        $user_info = $this->di->user->select("realname")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000062','data'=>array(),'info'=>array());
            return $rs;
        }

        $table_arr = array("agent","project_side","teacher");
        $field_arr = array("flower_name","flower_name","name");
        $model_name_arr = array("代理商","一手项目方","师资");
        $table_name = $table_arr[$newData['type']];
        $field_name = $field_arr[$newData['type']];
        $newData['publisher'] = $user_info['realname'];
        $newData['addtime'] = time();
        $newData['updatetime'] = time();

        $follow_id = $this->di->public_follow->insert($newData);
        $follow_id = $this->di->public_follow->insert_id();
        $info = $this->di->$table_name->where(array("id"=>$newData['info_id']))->fetchOne($field_name);
        $note = "新增".$model_name_arr[$newData['type']]."跟进记录";
        if ($follow_id) {
            // 更新表中最后跟进记录以及提醒日期
            $update_info['follow_time'] = time();
            $update_info['next_follow'] = $newData['next_time'];
            $update_info['follow_up'] = new \NotORM_Literal("follow_up + 1");
            $this->di->$table_name->where(array("id"=>$newData['info_id']))->update($update_info);
            $note .= "：ID".$follow_id;
            \App\setlog($uid,1,$note,'成功',$note,'新增跟进记录');
            // 添加消息
            $msg_info = $this->di->msg->select("id")->where(array("type"=>1,"info_id"=>$newData['info_id'],"table_name"=>$table_name,"action"=>"notice","creatid"=>$uid,"to_uid"=>$uid))->fetchOne();
            if (!empty($msg_info)) {
                $this->di->msg->where(array("id"=>$msg_info['id']))->update(array("status"=>0));
            } else {
                $admin_common = new AdminCommon();
                $content = "您的".$model_name_arr[$newData['type']].":".$info."尚未跟进，请及时处理！";
                $admin_common->SendMsgNow($uid,$uid,$content,$content,1,"notice",$table_name,$newData['info_id']);
            }
            $rs = array('code'=>1,'msg'=>'000000','data'=>array(),'info'=>array());
        } else {
            \App\setlog($uid,1,$note,'失败',$note,'新增跟进记录');
            $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 42 公共跟进记录列表
    public function GetPublicFollowList($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $type = isset($newData['type']) ? $newData['type'] : 0;
        $info_id = isset($newData['info_id']) && !empty($newData['info_id']) ? intval($newData['info_id']) : 0;

        if (empty($uid) || empty($info_id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        $user_info = $this->di->user->select("realname")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000062','data'=>array(),'info'=>array());
            return $rs;
        }

        $follow_where['type'] = $type;
        $follow_where['info_id'] = $info_id;
        if ($uid != 1) {
            $follow_where['uid'] = $uid;
        }

        $follow_list = $this->di->public_follow->select("id,type,subject,types,addtime,next_time,file,publisher,updatetime")->where($follow_where)->order("addtime DESC")->limit(10)->fetchRows();
        $follow_list = !empty($follow_list) ? $follow_list : array();
        return $rs = array('code'=>1,'msg'=>'000000','data'=>$follow_list,'info'=>$follow_list);
    }

    // 43 公共跟进记录详情
    public function GetPublicFollowInfo($id) {
        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        $follow_info = $this->redis->get_time('public_follow_info'.$id,'list');
        if (empty($follow_info)) {
            $follow_info = $this->di->public_follow->select("*")->where(array("id"=>$id))->fetchOne();
            $this->redis->set_time('public_follow_info'.$id,$follow_info,6000,'list');
        }
        if (!empty($follow_info)) {
            $rs = array('code'=>1,'msg'=>'000000','data'=>$follow_info,'info'=>$follow_info);
        } else {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
        }
        return $rs;  
    }
    
}