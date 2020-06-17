<?php
namespace App\Model;
use PhalApi\Model\NotORMModel as NotORM;
use App\Model\Common as Common;
use App\Common\Admin as AdminCommon;
use App\Common\Customer as CustomerCommon;
class Customer extends NotORM 
{
    protected $di;
    protected $iv;
    protected $prefix;
    protected $cache;
    protected $redis;
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

    // 01 客户列表接口
    // 01 客户列表接口
    public function GetCustomerList($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $type = isset($newData['type']) && !empty($newData['type']) ? intval($newData['type']) : 0;
        $day_type = isset($newData['day_type']) ? intval($newData['day_type']) : 0;
        $yx_type = isset($newData['yx_type']) && !empty($newData['yx_type']) ? $newData['yx_type'] : "";
        $level_type = isset($newData['level_type']) && !empty($newData['level_type']) ? $newData['level_type'] : "";
        $keywords = isset($newData['keywords']) ? $newData['keywords'] : '';//搜索关键词
        $where_arr = isset($newData['where_arr']) && !empty($newData['where_arr']) ? $newData['where_arr'] : array();
        $pageno = isset($newData['pageno']) ? intval($newData['pageno']) : 1;
        $pagesize = isset($newData['pagesize']) ? intval($newData['pagesize']) : $this->pagesize;
        $pagenum = ($pageno-1)*$pagesize;

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断用户角色(如果为部门领导则显示整个部门客户,如果为admin显示所有的客户)
        $user_info = $this->di->user->select("is_leader,status,structure_id,parent_id")->where("id",$uid)->fetchOne();
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
        
        $params[':status'] = 1;
        // 关键词
        if (!empty($keywords)) {
            $keywords_where = " concat(sc.cname,cd.cphone,cd.cphonetwo,cd.cphonethree) LIKE '%{$keywords}%' AND ";
        } else {
            $keywords_where = "";
        }
        // 意向类型
        if (!empty($yx_type)) {
            $keywords_where .= " sc.intentionally = '{$yx_type}' AND ";
        }
        
        $today_start_date         = date('Y-m-d 00:00:00',time()); //今天
        $today_end_date           = date('Y-m-d 23:59:59',time());

        $where_gx = " s.status = :status AND s.from_type = 3 ";
        $where_sh = " sc.status = :status AND sea_type = 0 ";
        $share_user_where = " sc.status = :status ";
        $share_customer = " sc.status = :status";
        $customer_where = $where_sh;// 默认全部/
        $customer_where2 = $where_sh;//私海客户
        $status_where1 = " s.status = :status AND (s.from_type  = 1 OR s.from_type  = 2) ";//公海客户
        $status_where3 = $where_gx;//我的共享
        $status_where4 = $where_gx;//共享给我
        $status_where5 = " sc.status = :status AND sea_type = 0 AND FROM_UNIXTIME( cd.create_time,  '%Y-%m-%d %H:%i:%s' ) BETWEEN '$today_start_date' AND '$today_end_date' ";//今日新增
        $status_where6 = " s.status = :status AND (s.from_type  = 1 OR s.from_type  = 2) AND FROM_UNIXTIME( s.addtime,  '%Y-%m-%d %H:%i:%s' ) BETWEEN '$today_start_date' AND '$today_end_date' ";//今日公海
        $status_where7 = $where_gx." AND to_days(FROM_UNIXTIME(s.addtime,'%Y-%m-%d')) = to_days(now()) ";//今日共享
        $status_where8 = $where_gx." AND to_days(FROM_UNIXTIME(s.addtime,'%Y-%m-%d')) = to_days(now())  ";//今日共享给我
        $status_where9 = $where_sh." AND sc.next_follow <> 0 AND TO_DAYS(now()) - TO_DAYS(FROM_UNIXTIME(sc.next_follow,'%Y-%m-%d')) >= 0 ";//今日提醒跟进
        $share_where9 = " sc.status = :status AND sc.next_follow <> 0 AND TO_DAYS(now()) - TO_DAYS(FROM_UNIXTIME(sc.next_follow,'%Y-%m-%d')) >= 0 ";//今日提醒跟进分享表
        $status_where10 = $where_sh." AND TO_DAYS(FROM_UNIXTIME(sc.follw_time,'%Y-%m-%d')) - TO_DAYS(now()) = 0 ";//今日已跟进
        $share_where10 = " sc.status = :status AND TO_DAYS(FROM_UNIXTIME(sc.follw_time,'%Y-%m-%d')) - TO_DAYS(now()) = 0 ";//今日已跟进分享表
        $status_where11 = $where_sh." AND TO_DAYS(now()) - TO_DAYS(FROM_UNIXTIME(sc.next_follow,'%Y-%m-%d')) = 0 ";//今日未跟进
        $share_where11 = " sc.status = :status AND TO_DAYS(now()) - TO_DAYS(FROM_UNIXTIME(sc.next_follow,'%Y-%m-%d')) = 0 ";//今日未跟进分享表
        $status_where12 = " sc.status = :status AND sc.myshare = 1 ";//我的客户&&未分享
        $status_where13 = $where_gx;//今日共享已跟进/今日共享未跟进

        if ($uid != 1) {
            // 非超级管理员
            if ( in_array($user_info['is_leader'],array(1,2,3)) && $type == 15) {
                // 查询部门id
                $structure_id = json_decode($user_info['structure_id']);
                /**if ($user_info['is_leader'] == 1) {
                    # 部门主管&&看下级
                    $user_structure_id = array_pop($structure_id);
                    $structure_where = " AND cd.groupid = {$user_structure_id} ";
                    $share_user_where .= " AND cd.groupid = {$user_structure_id} ";
                } else {
                    # 部门经理
                    // 查询所有子部门的id
                    $structure_arr = $this->di->structure->where(array("pid"=>$structure_id[0]))->fetchPairs("id","name");
                    $user_structure_arr = array_keys($structure_arr);
                    $user_structure_str = implode(",",$user_structure_arr);
                    $structure_where = " AND FIND_IN_SET(cd.groupid, '{$user_structure_str}') ";
                    $share_user_where .= " AND FIND_IN_SET(cd.groupid, '{$user_structure_str}') ";
                }**/
                // 12-9查看下级更改为查看本部门客户已经共享给本部分的数据
                $user_structure_str = "";
                switch ($user_info['is_leader']) {
                    case 1:
                        # 部门主管&&看下级
                        $user_structure_id = array_pop($structure_id);
                        $user_structure_str .= $user_structure_id;
                        break;
                    case 2:
                        # 部门经理
                        $structure_arr = $this->di->structure->where(array("pid"=>$structure_id[0]))->fetchPairs("id","name");
                        if (!empty($structure_arr)) {
                            $user_structure_arr = array_keys($structure_arr);
                            $user_structure_str .= implode(",",$user_structure_arr);
                        } else {
                            $user_structure_str = $structure_id[0];
                        }
                        break;
                    default:
                        # code...
                        break;
                }
                if (!empty($user_info['parent_id'])) {
                    $user_structure_str .= ",".$user_info['parent_id'];
                    $user_structure_str = trim($user_structure_str,",");
                }

                if ($user_info['is_leader'] == 1 && empty($user_info['parent_id'])) {
                    // 部门主管&&没有管理其他部门
                    $user_structure_id
                    $structure_where = " AND cd.groupid = {$user_structure_id} ";
                    $share_user_where .= " AND s.groupid = {$user_structure_id} ";
                } else {
                    $structure_where = " AND FIND_IN_SET(cd.groupid, '{$user_structure_str}') ";
                    $share_user_where .= " AND FIND_IN_SET(s.groupid, '{$user_structure_str}') ";
                }

                $customer_where .= $structure_where;
                $share_customer .= $share_user_where;
                $customer_share_where .= $structure_where;
                $status_where1 .= $structure_where;
                $status_where3 .= $structure_where;
                $status_where4 .= $structure_where;
                $status_where5 .= $structure_where;
                $status_where6 .= $structure_where;
                $status_where7 .= $structure_where;
                $status_where8 .= $structure_where;
                $status_where9 .= $structure_where;
                $share_where9 .= $structure_where;
                $status_where10 .= $structure_where;
                $share_where10 .= $structure_where;
                $status_where11 .= $structure_where;
                $share_where11 .= $structure_where;
                $status_where12 .= $structure_where;
                $status_where13 .= $structure_where;
            } else {
                // 普通用户 -- 只查看自己的客户
                $share_user_where .= " AND FIND_IN_SET('{$uid}', charge_person) ";
                $customer_where .= " AND creatid = '{$uid}' ";// 私人客户
                $share_customer .= " AND creatid <> '{$uid}' AND beshare_uid = '{$uid}' ";// 其他客户
                $customer_where2 .= "AND creatid = '{$uid}' ";///私海客户
                $status_where1 .= " AND s.beshare_uid = '{$uid}' AND sc.creatid <> '{$uid}' ";//公海客户
                $status_where3 .= " AND (s.creat_id = '{$uid}' OR s.share_uid = '{$uid}') ";//我的共享
                $status_where4 .= " AND beshare_uid = '{$uid}' ";//共享给我
                $status_where5 .= " AND creatid = '{$uid}' ";//今日新增
                $status_where6 .= " AND s.beshare_uid = '{$uid}' AND sc.creatid <> '{$uid}' ";//今日公海
                $status_where7 .= " AND (s.creat_id = '{$uid}' OR s.share_uid = '{$uid}') ";//今日共享
                $status_where8 .= " AND beshare_uid = '{$uid}' ";//今日共享给我
                $status_where9 .= " AND creatid = '{$uid}'";//今日提醒跟进
                $share_where9 .= " AND creatid != '{$uid}' AND FIND_IN_SET('{$uid}', charge_person) ";//今日已跟进分享表
                $status_where10 .= " AND creatid = '{$uid}'";//今日提醒跟进
                $share_where10 .= " AND ".$share_user_where;//今日已跟进分享表
                $status_where11 .= " AND creatid = '{$uid}'";//今日未跟进
                $share_where11 .= " AND ".$share_user_where;//今日未跟进分享表
                $status_where12 .= " AND creatid = '{$uid}' ";//我的客户&&未分享
                $status_where13 .= " AND s.creat_id = '{$uid}' ";//今日共享已跟进/未跟进
            }
        }
        // 读取缓存
        if ($type != 15 && $uid != 1) {
            $total_arr = $this->redis->get_time('customer_count_arr','customer');
            // $total_arr = $this->redis->get_forever('customer_count_arr','customer');
            if ( empty($total_arr) || empty($keywords) ) {
                    // 计算数量
                    //（1）公海总数
                    $gh_count_sql = "SELECT count(s.id) as num FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id WHERE ".$status_where1;
                    $customer_gh_num = $this->di->share_join->queryAll($gh_count_sql,$params);
                    $customer_gh_total = $customer_gh_num[0]['num'];
                    // (2)私海总数
                    $sh_count_sql = "SELECT count(id) as num FROM ".$this->prefix."customer sc WHERE ".$customer_where2;
                    $customer_sh_num = $this->di->customer->queryAll($sh_count_sql,$params);
                    $customer_sh_total = $customer_sh_num[0]['num'];
                    // (3)我的共享
                    $gx_count_sql = "SELECT count(cid) as num FROM ( SELECT cid FROM ".$this->prefix."share_join s WHERE ".$status_where3." GROUP BY cid ) temp ";
                    $customer_gx_num = $this->di->share_join->queryAll($gx_count_sql,$params);
                    $customer_gx_total = $customer_gx_num[0]['num'];
                    // (4)共享给我
                    $gxgw_count_sql = "SELECT count(id) as num FROM ".$this->prefix."share_join s WHERE ".$status_where4;
                    $customer_gxgw_num = $this->di->share_join->queryAll($gxgw_count_sql,$params);
                    $customer_gxgw_total = $customer_gxgw_num[0]['num'];
                    // (5)今日新增
                    $jrxz_count_sql = "SELECT count(sc.id) as num FROM ".$this->prefix."customer sc LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid WHERE ".$status_where5;
                    $customer_jrxz_num = $this->di->customer->queryAll($jrxz_count_sql,$params);
                    $customer_jrxz_total = $customer_jrxz_num[0]['num'];
                    // (6)今日公海
                    $jrgh_count_sql = "SELECT count(s.id) as num FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$status_where6;
                    $customer_jrgh_num = $this->di->share_join->queryAll($jrgh_count_sql,$params);
                    $customer_jrgh_total = $customer_jrgh_num[0]['num'];
                    // (7)今日共享
                    $jrgx_count_sql = "SELECT count(cid) as num FROM ( SELECT cid FROM ".$this->prefix."share_join s WHERE ".$status_where7." GROUP BY cid ) temp ";
                    $customer_jrgx_num = $this->di->share_join->queryAll($jrgx_count_sql,$params);
                    $customer_jrgx_total = $customer_jrgx_num[0]['num'];
                    // (8)今日共享给我
                    $jrgxgw_count_sql = "SELECT count(id) as num FROM ".$this->prefix."share_join s WHERE ".$status_where8;
                    $customer_jrgxgw_num = $this->di->share_join->queryAll($jrgxgw_count_sql,$params);
                    $customer_jrgxgw_total = $customer_jrgxgw_num[0]['num'];
                    // (9)今日提醒跟进
                    $jrtxgj_count_sql = "SELECT count(id) as num FROM ( (SELECT sc.id FROM ".$this->prefix."customer sc WHERE ".$status_where9.") UNION ALL (SELECT sc.id FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id WHERE ".$share_where9." ) ) a";
                    $customer_jrtxgj_num = $this->di->customer->queryAll($jrtxgj_count_sql,$params);
                    $customer_jrtxgj_total = $customer_jrtxgj_num[0]['num'];
                    // (10)今日已跟进
                    $jrygj_count_sql = "SELECT count(id) as num FROM ( (SELECT sc.id FROM ".$this->prefix."customer sc WHERE ".$status_where10.") UNION ALL (SELECT sc.id FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id WHERE ".$share_where10." ) ) a";
                    $customer_jrygj_num = $this->di->customer->queryAll($jrygj_count_sql,$params);
                    $customer_jrygj_total = $customer_jrygj_num[0]['num'];
                    // (11)今日未跟进
                    $jrwgj_count_sql = "SELECT count(id) as num FROM ( (SELECT sc.id FROM ".$this->prefix."customer sc WHERE ".$status_where11.") UNION ALL (SELECT sc.id FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id WHERE ".$share_where11." ) ) a";
                    $customer_jrwgj_num = $this->di->customer->queryAll($jrwgj_count_sql,$params);
                    $customer_jrwgj_total = $customer_jrwgj_num[0]['num'];
                    // (12)我的客户&&未共享
                    $wgx_count_sql = "SELECT count(sc.id) as num FROM ".$this->prefix."customer sc WHERE ".$status_where12;
                    $customer_wgx_num = $this->di->share_join->queryAll($wgx_count_sql,$params);
                    $customer_wgx_total = $customer_wgx_num[0]['num'];
                    // (13)今日共享已跟进
                    $jrgxygj_count_sql = "SELECT count(id) as num FROM ( SELECT s.id FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id WHERE ".$status_where13." AND TO_DAYS(FROM_UNIXTIME(sc.follw_time,'%Y-%m-%d')) - TO_DAYS(now()) = 0 ) temp ";
                    $customer_jrgxygj_num = $this->di->share_join->queryAll($jrgxygj_count_sql,$params);
                    $customer_jrgxygj_total = $customer_jrgxygj_num[0]['num'];
                    // (14)今日共享未跟进
                    $jrgxwgj_count_sql = "SELECT count(id) as num FROM ( SELECT s.id FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id WHERE ".$status_where13." AND TO_DAYS(now()) - TO_DAYS(FROM_UNIXTIME(sc.next_follow,'%Y-%m-%d')) = 0 ) temp ";
                    $customer_jrgxwgj_num = $this->di->share_join->queryAll($jrgxwgj_count_sql,$params);
                    $customer_jrgxwgj_total = $customer_jrgxwgj_num[0]['num'];
                    // (0)客户总数
                    // $customer_count_sql = "SELECT count(id) as num FROM ( (SELECT sc.* FROM ".$this->prefix."customer sc WHERE ".$customer_where.") ) a";
                    $customer_count_sql = "SELECT count(id) as num FROM ( (SELECT sc.id FROM ".$this->prefix."customer sc LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid WHERE ".$customer_where.") UNION ALL (SELECT sc.id FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id WHERE ".$share_customer." ) ) a";
                    $customer_count_num = $this->di->customer->queryAll($customer_count_sql,$params);
                    $customer_total_num = $customer_count_num[0]['num'];

                    $total_data['total'] = $customer_total_num;
                    $total_data['gh_total'] = $customer_gh_total;//公海
                    $total_data['sh_total'] = $customer_sh_total;//私海
                    $total_data['gx_total'] = $customer_gx_total;//我的共享
                    $total_data['gxgw_total'] = $customer_gxgw_total;//共享给我
                    $total_data['jrxz_total'] = $customer_jrxz_total;//今日新增
                    $total_data['jrgh_total'] = $customer_jrgh_total;//今日公海
                    $total_data['jrgx_total'] = $customer_jrgx_total;//今日共享
                    $total_data['jrgxgw_total'] = $customer_jrgxgw_total;//今日共享给我
                    $total_data['jrtxgj_total'] = $customer_jrtxgj_total;//今日提醒跟进
                    $total_data['jrygj_total'] = $customer_jrygj_total;//今日已跟进
                    $total_data['jrwgj_total'] = $customer_jrwgj_total;//今日未跟进
                    $total_data['wgx_total'] = $customer_wgx_total;//我的客户&&未共享
                    $total_data['jrgxygj_total'] = $customer_jrgxygj_total;//今日共享已跟进
                    $total_data['jrgxwgj_total'] = $customer_jrgxwgj_total;//今日共享未跟进
                    $total_arr = $total_data;
                    $this->redis->set_time('customer_count_arr',$total_data,6000,'customer');
            }
        }
        switch ($day_type) {
            case 1:
                // 1天
                $mark_day = 1;
                break;
            case 2:
                // 3天内
                $mark_day = 3;
                break;
            case 3:
                // 7天内
                $mark_day = 7;
                break;
            case 4:
                // 30天
                $mark_day = 30;
                break;
            case 5:
                // 60天
                $mark_day = 60;
                break;
            case 6:
                # 90天
                $mark_day = 90;
                break;
            default:
                // 全部
                $mark_day = 0;
                break;
        }

        if ($type == 13 || $type == 10) {
            // 已跟进
            $mark = "<=";
            $field = "sc.follw_time";
        }

        if ($type == 14 || $type == 11) {
            // 未跟进
            $mark = ">=";
            $field = "sc.next_follow";
        }
        if($mark_day){
            $end_date         = date('Y-m-d 23:59:59',time()); 
            $gj_where .= " AND DATEDIFF('{$end_date}', FROM_UNIXTIME( sc.follw_time,  '%Y-%m-%d %H:%i:%s' ) ) {$mark} {$mark_day} ";
        } else {
            $gj_where .= " AND TO_DAYS(FROM_UNIXTIME(".$field.",'%Y-%m-%d')) - TO_DAYS(now()) = 0 ";
        }
        // 高级搜索
        if (!empty($where_arr)) {
            $table_model = $this->config->get('common.TABLE_FIELD');
            $model_field = $table_model['customer']['data_field'];
            $data_field_arr = array_keys($model_field);
            $data_field_arr = array_merge($data_field_arr,array('zdnum','groupid','formwhere','formwhere2','formwhere3','agent_name','agent_num','project_name','project_num','agent_price','paid','obligations','flag','create_time','ocreatid'));
            $senior_where = "";
            foreach ($where_arr as $key => $value) {
                if (is_array($value)) {
                    if ($value[0] == "" || $value[1] == "") {
                        continue;
                    }
                }
                if (!empty($value) || $value == 0) {
                    $key = in_array($key,$data_field_arr) ? 'cd.'.$key : 'sc.'.$key;
                    # 其他
                    if (is_array($value) && is_numeric($value[0])) {
                        $senior_where .= " AND ( {$key} BETWEEN {$value[0]} AND {$value[1]} ) ";
                    } elseif (is_array($value)) {
                        $senior_where .= " AND ( UNIX_TIMESTAMP({$key}) between '{$value[0]}' AND '{$value[0]}' ) ";
                    } elseif (is_numeric($value)) {
                        if ($key == 'charge_person') {
                            $senior_where .= " AND FIND_IN_SET('{$value}', charge_person) ";
                        }elseif (in_array($key,array("cd.cphone","cd.cphonetwo","cd.cphonethree"))) {
                            $senior_where .= " AND {$key} LIKE '{$value}%' ";
                        } else {
                            $senior_where .= " AND {$key} = {$value} ";
                        }
                    } else {
                        $senior_where .= " AND {$key} LIKE '{$value}%' ";
                    }
                }
            }
        }
        $customer_field = "sc.id,sc.cname,sc.labelpeer,sc.intentionally,sc.ittnxl,sc.ittnzy,sc.ittnyx,sc.ittnxm,sc.ittngj,sc.budget,sc.timeline,sc.graduate,sc.graduatezy,sc.xuel,sc.tolink,sc.note,sc.creatid,sc.is_top,sc.charge_person,sc.follow_up,sc.follow_person,sc.follw_time,sc.next_follow,sc.xueshuchengji,sc.yuyanchengji,sc.hzID,sc.sort,'' as bid,cd.cid,cd.zdnum,cd.own,cd.occupation,cd.groupid,cd.sex,cd.age,cd.station,cd.industry,cd.experience,cd.company,cd.scale,cd.character,cd.agent_name,cd.cphone,cd.cphonetwo,cd.cphonethree,cd.telephone,cd.formwhere,cd.formwhere2,cd.formwhere3,cd.wxnum,cd.cemail,cd.qq,cd.create_time,cd.ocreatid";
        $customer_data_field = "sc.id,sc.cname,sc.labelpeer,sc.intentionally,sc.ittnxl,sc.ittnzy,sc.ittnyx,sc.ittnxm,sc.ittngj,sc.budget,sc.timeline,sc.graduate,sc.graduatezy,sc.xuel,sc.tolink,sc.note,sc.creatid,sc.is_top,sc.charge_person,sc.follow_up,sc.follow_person,sc.follw_time,sc.next_follow,sc.xueshuchengji,sc.yuyanchengji,sc.hzID,sc.sort,s.id as bid,cd.cid,cd.zdnum,cd.own,cd.occupation,cd.groupid,cd.sex,cd.age,cd.station,cd.industry,cd.experience,cd.company,cd.scale,cd.character,cd.agent_name,cd.cphone,cd.cphonetwo,cd.cphonethree,cd.telephone,cd.formwhere,cd.formwhere2,cd.formwhere3,cd.wxnum,cd.cemail,cd.qq,cd.create_time,cd.ocreatid";
        
        if ($uid == 1) {
            # 超级管理员
            $sql = "SELECT ".$customer_field." FROM ".$this->prefix."customer sc LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid WHERE ".$keywords_where." sc.status = :status AND sea_type = 0 ".$senior_where." ORDER BY sc.is_top DESC,cd.id DESC LIMIT ".$pagenum.",".$pagesize;

            $count_sql = "SELECT count(sc.id) as num FROM ".$this->prefix."customer sc LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid WHERE ".$keywords_where." sc.status = :status  AND sea_type = 0 ".$senior_where;
        } else {
            switch ($type) {
                case 1:
                    # 公海客户(分配或者领取的--不包含自己创建的客户)
                    $sql = "SELECT ".$customer_data_field." FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where1.$senior_where." ORDER BY sc.is_top DESC,cd.id DESC LIMIT ".$pagenum.",".$pagesize;
                    $count_sql = "SELECT count(s.id) as num FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where1.$senior_where;
                    break;
                case 2:
                    # 我的客户(自己创建的客户)
                    $sql = "SELECT ".$customer_field." FROM ".$this->prefix."customer sc LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid WHERE ".$keywords_where.$customer_where2.$senior_where." ORDER BY sc.is_top DESC,cd.id DESC LIMIT ".$pagenum.",".$pagesize;
                    $count_sql = "SELECT count(sc.id) as num FROM ".$this->prefix."customer sc LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid WHERE ".$keywords_where.$customer_where2.$senior_where;
                    
                    break;
                case 3:
                    # 我共享的客户
                    $sql = "SELECT ".$customer_data_field." FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where3.$senior_where." ORDER BY sc.is_top DESC,cd.id DESC LIMIT ".$pagenum.",".$pagesize;
                    $count_sql = "SELECT count(cid) as num FROM ( SELECT s.cid FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where3.$senior_where." ) temp ";

                    break;
                case 4:
                    # 共享给我的
                    $sql = "SELECT ".$customer_data_field." FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where4.$senior_where." ORDER BY sc.is_top DESC,cd.id DESC LIMIT ".$pagenum.",".$pagesize;
                    $count_sql = "SELECT count(s.id) as num FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where4.$senior_where;
                    
                    break;
                case 5:
                    # 今日新增客户
                    $sql = "SELECT ".$customer_field." FROM ".$this->prefix."customer sc LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid WHERE ".$keywords_where.$status_where5.$senior_where." ORDER BY sc.is_top DESC,cd.id DESC LIMIT ".$pagenum.",".$pagesize;
                    $count_sql = "SELECT count(sc.id) as num FROM ".$this->prefix."customer sc LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid WHERE ".$keywords_where.$status_where5.$senior_where;

                    break;
                case 6:
                    # 今日公海客户
                    $sql = "SELECT ".$customer_data_field." FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where6.$senior_where." ORDER BY sc.is_top DESC,cd.id DESC LIMIT ".$pagenum.",".$pagesize;
                    $count_sql = "SELECT count(s.id) as num FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.beshare_uid = cd.cid WHERE ".$keywords_where.$status_where6.$senior_where;
                    break;
                case 7:
                    # 今日我共享的客户
                    $sql = "SELECT ".$customer_data_field." FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where7.$senior_where." ORDER BY sc.is_top DESC,cd.id DESC LIMIT ".$pagenum.",".$pagesize;
                    $count_sql = "SELECT count(cid) as num FROM ( SELECT s.cid FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where7.$senior_where." ) temp ";

                    break;
                case 8:
                    # 今日共享给我的客户
                    $sql = "SELECT ".$customer_data_field." FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where8.$senior_where." ORDER BY sc.is_top DESC,cd.id DESC LIMIT ".$pagenum.",".$pagesize;
                    $count_sql = "SELECT count(s.id) as num FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where8.$senior_where;

                    break;
                case 12:
                    # 我的客户&&未分享的
                    $sql = "SELECT ".$customer_field." FROM ".$this->prefix."customer sc LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid WHERE ".$keywords_where.$status_where12.$senior_where." ORDER BY sc.is_top DESC,cd.id DESC LIMIT ".$pagenum.",".$pagesize;
                    $count_sql = "SELECT count(sc.id) as num FROM ".$this->prefix."customer sc LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid WHERE ".$keywords_where.$status_where12.$senior_where;

                    break;
                case 13:
                    # 今日共享已跟进
                    $sql = "SELECT ".$customer_data_field." FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where13.$senior_where.$gj_where." ORDER BY sc.is_top DESC,cd.id DESC LIMIT ".$pagenum.",".$pagesize;
                    $count_sql = "SELECT count(id) as num FROM ( SELECT s.id FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where13.$senior_where.$gj_where." ) temp ";
                    break;
                case 14:
                    # 今日共享未跟进
                    $sql = "SELECT ".$customer_data_field." FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where13.$senior_where.$gj_where." ORDER BY sc.is_top DESC,cd.id DESC LIMIT ".$pagenum.",".$pagesize;
                    $count_sql = "SELECT count(id) as num FROM ( SELECT s.id FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where13.$senior_where.$gj_where." ) temp ";
                    break;
                default:
                    # 默认(全部)
                    // 客户列表：(1)自己创建的客户并且为私海的(2)共享给我的【分享给我的，分配给我的，自己从公海领取的】
                    if ($type == 9) {
                        # 今日提醒跟进
                        $where1 = $status_where9;//今日提醒跟进
                        $where2 = $share_where9;//分享今日提醒跟进
                    } elseif ($type == 10) {
                        # 今日已跟进
                        $where1 = $customer_where2.$gj_where;//今日已跟进
                        $where2 = $share_user_where.$gj_where;//分享今日已跟进
                    } elseif ($type == 11) {
                        # 今日未跟进
                        $where1 = $customer_where2.$gj_where;//今日未跟进
                        $where2 = $share_user_where.$gj_where;//今日分享未跟进
                    } elseif ($type == 15) {
                        $where1 = $customer_where;
                        $where2 = $share_user_where;
                    } else {
                        $where1 = $customer_where;
                        $where2 = $share_customer;
                    }
                    $sql = "SELECT
                                a.*
                            FROM
                                (
                                    (
                                        SELECT
                                            ".$customer_field."
                                        FROM
                                            ".$this->prefix."customer sc
                                        LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid
                                        WHERE
                                            ".$keywords_where.$where1.$senior_where."
                                    )
                                    UNION ALL
                                    (
                                        SELECT
                                            ".$customer_data_field."
                                        FROM
                                            ".$this->prefix."share_join s 
                                        LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id
                                        LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid
                                        WHERE
                                            ".$keywords_where.$where2.$senior_where."
                                    )
                                ) AS a
                            ORDER BY
                                a.is_top DESC,
                                a.id DESC
                            LIMIT ".$pagenum.",".$pagesize;
                        $count_sql = "SELECT count(id) as num FROM ( (SELECT sc.* FROM ".$this->prefix."customer sc LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid WHERE ".$keywords_where.$where1.$senior_where.") UNION ALL (SELECT sc.* FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$where2.$senior_where." ) ) a";
                        // var_dump($sql);die;
                    break;
            }
        }
        // var_dump($sql);
        // var_dump($count_sql);die;

        // 客户列表
        $customer_list = $this->di->customer->queryAll($sql, $params);
        $customer_num = $this->di->customer->queryAll($count_sql,$params);
        
        $customer_data['customer_num'] = !empty($customer_num[0]['num']) ? $customer_num[0]['num'] : 0;
        $customer_data['total_count'] = !empty($total_arr) ? $total_arr : array();
        if (!empty($customer_list)) {
            $admin_common = new AdminCommon();
            foreach ($customer_list as $key => $value) {
                // 组织部门
                $customer_list[$key]['group'] = $admin_common->GetStructNameById($value['groupid']);
                # 原始创建人
                if (!empty($value['ocreatid'])) {
                    $customer_list[$key]['old_publisher'] = $this->di->user->where(array("id"=>$value['ocreatid']))->fetchOne("realname");
                } else {
                    $customer_list[$key]['old_publisher'] = "";
                }
                $charge_person_str = "";
                // 负责人处理
                $charge_person_arr = explode(",",trim($value['charge_person'],","));
                foreach ($charge_person_arr as $k => $v) {
                    $username = $this->di->user->where(array("id"=>$v))->fetchOne("realname");
                    $charge_person_str .= $username.",";
                }
                $customer_list[$key]['charge_person'] = $charge_person_str;
                // 查看下级员工隐藏手机号 12-6加
                if ($uid != 1 && $type == 15 && !in_array($uid,$charge_person_arr)) {
                    // 隐藏手机号
                    $customer_list[$key]['cphone'] = !empty($value['cphone']) ? \App\string_substr($value['cphone'],3,8) : "";
                    $customer_list[$key]['cphonetwo'] = !empty($value['cphonetwo']) ? \App\string_substr($value['cphonetwo'],3,8) : "";
                    $customer_list[$key]['cphonethree'] = !empty($value['cphonethree']) ? \App\string_substr($value['cphonethree'],3,8) : "";
                    // 隐藏座机
                    $customer_list[$key]['telephone'] = !empty($value['telephone']) ? \App\string_substr($value['telephone'],0,3) : "";
                    // 隐藏微信和邮箱
                    $customer_list[$key]['wxnum'] = !empty($value['wxnum']) ? \App\string_substr($value['wxnum'],0,3) : "";
                    $customer_list[$key]['cemail'] = !empty($value['cemail']) ? \App\string_substr($value['cemail'],0,3) : "";
                }
                // 创建人
                $username = $this->di->user->where("id",$value['creatid'])->fetchOne("realname");
                $customer_list[$key]['publisher'] = $username;
                // 重新计算跟进次数
                if ($value['creatid'] != $uid && $uid != 1) {
                    # 负责人查询复制表的跟进次数
                    $bid = $this->di->share_join->where(array("beshare_uid"=>$uid,"cid"=>$value['cid'],"creat_id"=>$value['creatid'],"status"=>1))->fetchOne("bid");
                    // if (empty($bid)) {
                    //     // 领取
                    //     $bid = $this->di->share_join->where(array("from_type"=>2,"beshare_uid"=>$uid,"share_uid"=>$uid,"cid"=>$value['cid'],"creat_id"=>$value['creatid'],"status"=>1))->fetchOne("bid");
                    // }
                    // if (empty($bid)) {
                    //     // 分配
                    //     $bid = $this->di->share_join->where(array("from_type"=>1,"beshare_uid"=>$uid,"cid"=>$value['cid'],"creat_id"=>$value['creatid'],"status"=>1))->fetchOne("bid");
                    // }
                    // $customer_list[$key]['bid'] = $bid;

                    $share_info = $this->di->share_customer->select("follow_up,next_follow,follw_time,is_top")->where(array("id"=>$bid))->fetchOne();
                    $customer_list[$key]['follow_up'] = $share_info['follow_up'];
                    // 下次跟进时间
                    $customer_list[$key]['next_follow'] = $share_info['next_follow'];
                    // 最后跟进时间
                    $customer_list[$key]['follw_time'] = $share_info['follw_time'];
                    // 是否置顶
                    $customer_list[$key]['is_top'] = $share_info['is_top'];
                }
            }
            // $customer_list = \APP\sortArrayByfield($customer_list,"is_top");
        }
        $customer_data['customer_list'] = !empty($customer_list) ? $customer_list : array();
        $rs = array('code'=>1,'msg'=>'000000','data'=>$customer_data,'info'=>$customer_data);
        
        return $rs;
    }

    public function PostExportModelExcel($tablename) {
        $table_field = $this->config->get('common.TABLE_FIELD');
        $field_list = $table_field[$tablename];
        $model_id = $field_list['model_id'];

        // 查询灵活字段以及值
        $model_field_arr = $this->di->model_field->select("field,name,type,is_require,setting")->where(array("modelid"=>$model_id,"master_field"=>1))->order("id DESC")->limit(15)->fetchRows();
        if (!empty($model_field_arr)) {
            foreach ($model_field_arr as $key => $value) {
                $field_list['filed'][$value['field']]['name'] = $value['name'];
                $field_list['filed'][$value['field']]['type'] = $value['type'];
                $field_list['filed'][$value['field']]['is_require'] = $value['is_require'];
                $field_list['filed'][$value['field']]['setting'] = $value['setting'];
            }
        }
        
        $customer_field_list = $field_list['filed'];
        //客户模块
        if ($tablename == 'customer') {
            $customer_field_list = array_merge($field_list['filed'], $field_list['data_field']);
        }
         //引入phpexecl服务启动文件
        $objPHPExcel =new NOP();
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
        $excelModel = new Excel();
        //设置属性
        $objProps = $objPHPExcel->getProperties();
        $objProps->setCreator("ZKLM");
        $objProps->setLastModifiedBy("ZKLM");
        $objProps->setTitle("ZKLM");
        $objProps->setSubject("ZKLM data");
        $objProps->setDescription("ZKLM data");
        $objProps->setKeywords("ZKLM data");
        $objProps->setCategory("ZKLM");
        $objPHPExcel->setActiveSheetIndex(0);
        $objActSheet = $objPHPExcel->getActiveSheet();
        $objActSheet->setTitle('智库联盟'.$field_list['model_name'].'导入模板'.date('Y-m-d',time()));

        //填充边框
        $styleArray = [
            'borders'=>[
                'outline'=>[
                    'style'=>\PHPExcel_Style_Border::BORDER_THICK, //设置边框
                    'color' => ['argb' => '#F0F8FF'], //设置颜色
                ],
            ],
        ];

        $row = 1000;
        $k = 0;
        foreach ($customer_field_list as $field) {
            $objActSheet->getColumnDimension($excelModel->stringFromColumnIndex($k))->setWidth(20); //设置单元格宽度
            if ($field['type'] == 'address') {
                for ($a=0; $a<=2; $a++){
                    $address = array('所在省','所在市','所在县');//如果是所在省的话
                    $objActSheet->getStyle($excelModel->stringFromColumnIndex($k).'2')->applyFromArray($styleArray);//填充样式
                    $objActSheet->setCellValue($excelModel->stringFromColumnIndex($k).'2', $address[$a]);
                    $k++;
                }
            } else {
                if ($field['type'] == 'select' || $field['type'] == 'checkbox' || $field['type'] == 'radio') {
                    if (!empty($field['setting']) && !is_array($field['setting'])) {
                       $setting = json_decode($field['setting'],true) ? : [];
                    } else {
                        $setting = $field['setting'];
                    }
                    $select_value = implode(',',$setting);
                    if ($select_value) {
                        for ($c=3; $c<=10; $c++) {
                            //数据有效性 start
                            $objValidation = $objActSheet->getCell($excelModel->stringFromColumnIndex($k).$c)->getDataValidation(); //这一句为要设置数据有效性的单元格
                            $objValidation -> setType(\PHPExcel_Cell_DataValidation::TYPE_LIST)
                                -> setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION)
                                -> setAllowBlank(false)
                                -> setShowInputMessage(true)
                                -> setShowErrorMessage(true)
                                -> setShowDropDown(true)
                                -> setErrorTitle('输入的值有误')
                                -> setError('您输入的值不在下拉框列表内.')
                                -> setPromptTitle('--请选择--')
                                -> setFormula1("'".'"'.$select_value.'"'."'");
                            //数据有效性  end
                        }
                    }
                }
                if ($field['type'] == 'sql') {
                    if (array_key_exists('redis_key', $field)) {
                        $setting_arr = $this->redis->get_time($field['redis_key'],$field['redis_table']);
                    }
                    if (empty($setting_arr)) {
                        $table_name = $field['from_table'];
                        if ($field['from_table'] == 'agent') {
                            $sql = "SELECT id,CONCAT_WS('_',number,flower_name) as title FROM ".$this->prefix.$table_name." WHERE status = 1 LIMIT 50 ";
                        } else {
                            $sql = "SELECT id,CONCAT_WS('_',id,title) as title FROM ".$this->prefix.$table_name." WHERE status = 1 LIMIT 50 ";
                        }
                        $setting_arr = $this->di->$table_name->queryAll($sql);
                    }
                    $setting = array_column($setting_arr, "title");
                    $select_value = implode(',',$setting);
                    $str_len = strlen($select_value);
                    if($str_len >= 255){
                        $str_list_arr = explode(',', $select_value);
                        if($str_list_arr) {
                            foreach($str_list_arr as $i =>$d){
                                $c = "P".($i+1);
                                $objActSheet->setCellValue($c,$d); 
                            } 
                        }
                        $endcell = $c;
                        $objActSheet->getColumnDimension('P')->setVisible(false); 
                    }
                    // $select_value = "1_人工智能与智能产业化,2_技术经济及管理,2_技术经济及管理,2_技术经济及管理,2_技术经济及管理,2_技术经济及管理,2_技术经济及管理,2_技术经济及管理,2_技术经济及管理,2_技术经济及管理,2_技术经济及管理,2_技术经济及管理,2_技术经济及管理,2_技术经济及管理,2_技术经济及管理,2_技术经济及管理,2_技术经济及管理,2_技术经济及管理,2_技术经济及管理,2_技术经济及管理,2_技术经济及管理,2_技术经济及管理,2_技术经济及管理,2_技术经济及管理,2_技术经济及管理,2_技术经济及管理,";
                    for ($c=3; $c<=10; $c++) {
                        //数据有效性 start
                        $objValidation = $objActSheet->getCell($excelModel->stringFromColumnIndex($k).$c)->getDataValidation(); //这一句为要设置数据有效性的单元格
                        $objValidation -> setType(\PHPExcel_Cell_DataValidation::TYPE_LIST)
                            -> setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION)
                            -> setAllowBlank(false)
                            -> setShowInputMessage(true)
                            -> setShowErrorMessage(true)
                            -> setShowDropDown(true)
                            -> setErrorTitle('输入的值有误')
                            -> setError('您输入的值不在下拉框列表内.')
                            -> setPromptTitle('--请选择--');
                        if($str_len<255) {
                            $objValidation->setFormula1('"' . $select_value . '"'); 
                        } else {
                            $objValidation->setFormula1("sheet1!P1:{$endcell}"); 
                        }
                            // -> setFormula1("'".'"'.$select_value.'"'."'");
                        //数据有效性  end
                    }
                }
                $field['name'] = \App\sign_required($field['is_require'], $field['name']);
                iconv("UTF-8","GBK",$field['name']);
                $objActSheet->getStyle($excelModel->stringFromColumnIndex($k).'2')->applyFromArray($styleArray);//填充样式
                $objActSheet->setCellValue($excelModel->stringFromColumnIndex($k).'2', $field['name']);
                $k++;
            }
        }
        $max_customer_column = $excelModel->stringFromColumnIndex($k-1);
        $mark_customer = $excelModel->stringFromColumnIndex($k);

        $objActSheet->mergeCells('A1:'.$max_customer_column.'1');
        $objActSheet->getStyle('A1:'.$mark_customer.'1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER); //水平居中
        $objActSheet->getStyle('A1:'.$mark_customer.'1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER); //垂直居中
        $objActSheet->getRowDimension(1)->setRowHeight(28); //设置行高
        $objActSheet->getStyle('A1')->getFont()->getColor()->setARGB('FFFF0000');
        $objActSheet->getStyle('A1')->getAlignment()->setWrapText(true);
        //给单元格填充背景色
        $objActSheet->getStyle('A1:'.$max_customer_column.'1')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('#ff9900');
        $content = $field_list['model_name'].'信息（*代表必填项）';
        $objActSheet->setCellValue('A1', $content);
        $objActSheet->getStyle('A1:'.$max_customer_column.'1')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
        $objActSheet->getStyle('A1')->getBorders()->getRight()->getColor()->setARGB('#000000');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        ob_end_clean();
        header("Content-Type: application/vnd.ms-excel;");
        header("Content-Disposition:attachment;filename=".$field_list['model_name']."信息导入模板".date('Y-m-d',time()).".xls");
        header("Pragma:no-cache");
        header("Expires:0");
        $objWriter->save('php://output');
        exit;
    }

    // 05 共享客户
    public function PostShareCustomer($newData) {
        $type = isset($newData['type'])  ? $newData['type'] : 0;
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $cid_arr = isset($newData['cid_arr']) && !empty($newData['cid_arr']) ? $newData['cid_arr'] : array();
        $share_uid = isset($newData['share_uid']) && !empty($newData['share_uid']) ? $newData['share_uid'] : array();
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($share_uid) || empty($cid_arr)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $customer_common = new CustomerCommon();
        $admin_common = new AdminCommon();
        $max_share_num = $this->config->get('common.MAX_SHARE_NUM');//最大分享数
        switch ($type) {
            case 1:
                # 多对1
                $share_uid = $share_uid[0];
                $user_info = $this->di->user->select("realname,structure_id")->where(array("id"=>$uid,"status"=>1))->fetchOne();
                $share_user_info = $this->di->user->select("realname,structure_id")->where(array("id"=>$share_uid,"status"=>1))->fetchOne();

                
                foreach ($cid_arr as $key => $value) {
                    $customer_info = $this->di->customer->select("*")->where(array("id"=>$value,"status"=>1))->fetchOne();
                    if (empty($customer_info)) {
                        $err_msg[] = "客户：".$customer_info['cname']."信息不存在";
                        continue;
                    }
                    // 判断是否是客户创建人
                    if ($customer_info['creatid'] == $share_uid) {
                        $err_msg[] = "客户：".$customer_info['cname']."共享失败，无法共享给创建人；";
                        continue;
                    }
                    // 判断是否已经共享过了
                    $charge_person_arr = explode(",",trim($customer_info['charge_person'],","));
                    array_shift($charge_person_arr);
                    if (in_array($share_uid, $charge_person_arr)) {
                        $err_msg[] = "客户：".$customer_info['cname']."共享失败，".$user_info['username']."已是该客户负责人！";
                        continue;
                    }
                    // 判断是否超过共享次数
                    if (count($charge_person_arr) >= $max_share_num) {
                        $err_msg[] = "客户：".$customer_info['cname']."共享失败，已超过共享次数；";
                        continue;
                    }

                    // (2)插入share_customer
                    $share_customer_data = $customer_info;
                    unset($share_customer_data['id']);
                    $share_customer_data['myshare'] = 0;
                    $share_customer_data['getshare'] = 1;
                    $share_customer_data['is_top'] = 0;
                    $share_customer_data['charge_person'] = $share_uid.',';
                    $share_customer_data['follow_up'] = 0;
                    $share_customer_data['next_follow'] = 0;//11-26要求共享去掉下次跟进
                    $share_customer_data['follw_time'] = time();//11-29改为分享的日期 

                    $share_bid = $this->di->share_customer->insert($share_customer_data);
                    $share_bid = $this->di->share_customer->insert_id();
                    // 12-9分享增加被分享人部门id
                    $structure_id = json_decode($share_user_info['structure_id']);
                    $user_structure_id = array_pop($structure_id);
                    // (3)插入share_join
                    $share_data = array(
                        'cid' => $value,
                        'share_uid' => $uid,
                        'beshare_uid' => $share_uid,
                        'groupid' => $user_structure_id,
                        'from_type' => 3,//分享
                        'status' => 1,
                        'addtime' => time(),
                        'updatetime' => time(),
                        'bid' => $share_bid,
                        'creat_id' => $customer_info['creatid'],
                    );
                    $share_id = $this->di->share_join->insert($share_data);
                    $share_id = $this->di->share_join->insert_id();

                    if ($share_id) {
                        $charge_person_str = $share_uid.",";
                        if ($customer_info['creatid'] == $uid) {
                            # 当前共享人为客户的创建人
                            $table_name = "customer";
                            $update_id = $value;
                            $gx_msg = $user_info['realname']."共享客户:".$customer_info['cname']."给:".$share_user_info['realname'];
                            $set = "`myshare` = 1,`charge_person`=CONCAT(charge_person,'{$charge_person_str}')";
                        } else {
                            # 二次共享
                            $table_name = "share_customer";
                            $update_id = $share_bid;
                            $gx_msg = $user_info['realname']."共享客户:".$customer_info['cname']."给:".$share_user_info['realname'];
                            $set = "`myshare` = 1";
                            $customer_update_sql = "UPDATE ".$this->prefix."customer SET `charge_person`=CONCAT(charge_person,'{$charge_person_str}') WHERE id = ".$value;
                            $this->di->customer->executeSql($customer_update_sql);
                        }
                        $update_sql = "UPDATE ".$this->prefix.$table_name." SET ".$set." WHERE id = ".$update_id;

                        $this->di->customer->executeSql($update_sql);
                        // (5)增加操作日志
                        $customer_common->CustomerActionLog($uid,$value,4,'共享客户',$gx_msg);
                        // 推送即时消息
                        $msg_title = $user_info['realname'].'共享给您一个客户：'.$customer_info['cname'].'！';
                        $msg_content = $user_info['realname']."共享给您一个客户：".$customer_info['cname'].",请及时处理！";

                        $admin_common->SendMsgNow($uid,$share_uid,$msg_title,$msg_content,0,"share","customer",$value);
                    }
                }

                if (!empty($err_msg)) {
                    $rs = array('code'=>1,'msg'=>'000083','data'=>$err_msg,'info'=>$err_msg);
                } else {
                    $rs = array('code'=>1,'msg'=>'000056','data'=>array(),'info'=>array());
                }
                break;
            
            default:
                # 1对多
                $cid = $cid_arr[0];
                $username = $this->di->user->where(array("id"=>$uid,"status"=>1))->fetchOne("realname");

                // 客户基本信息
                $customer_info = $this->di->customer->select("*")->where(array("id"=>$cid,"status"=>1))->fetchOne();
                if (empty($customer_info)) {
                    $rs = array('code'=>0,'msg'=>'000050','data'=>array(),'info'=>array());
                    return $rs;
                }

                $charge_person_arr = explode(",",trim($customer_info['charge_person'],","));
                array_shift($charge_person_arr);
                // 判断主表负责人个数【除自己外是否小于5则允许共享】
                if (count($charge_person_arr) < $max_share_num) {
                    foreach ($share_uid as $key => $value) {
                        // 判断是否是客户创建人
                        if ($customer_info['creatid'] == $value) {
                            $err_msg[] = "客户：".$customer_info['cname']."共享失败，无法共享给创建人；";
                            continue;
                        }

                        // 判断是否已经共享过了
                        if (in_array($value, $charge_person_arr)) {
                            $err_msg[] = "客户：".$customer_info['cname']."共享失败，无法共享给该客户负责人！";
                            continue;
                        }

                        // 判断是否超过共享次数
                        if (count($charge_person_arr) >= $max_share_num) {
                            $err_msg[] = "客户：".$customer_info['cname']."共享失败，已超过共享次数；";
                            continue;
                        }

                        // Step 1: 开启事务
                        $this->di->beginTransaction('db_master');
                        // (1)判断之前有取消共享的 
                        $share_bid = $this->di->share_join->where(array("cid"=>$cid,"share_uid"=>$uid,"beshare_uid"=>$value,"from_type"=>3,"creat_id"=>$customer_info['creatid']))->fetchOne("bid");
                        if ($share_bid) {
                            // (2)更改share_join
                            $share_update = $this->di->share_join->where(array("cid"=>$cid,"share_uid"=>$uid,"beshare_uid"=>$value,"from_type"=>3,"creat_id"=>$customer_info['creatid']))->update(array("status"=>1,"updatetime"=>time()));
                            // (3)share_customer状态
                            $share_customer_update = $this->di->share_customer->where(array("id"=>$share_bid))->update(array("status"=>1,"next_follow"=>0));
                        } else {
                            // (2)插入share_customer
                            $share_customer_data = $customer_info;
                            unset($share_customer_data['id']);
                            $share_customer_data['myshare'] = 0;
                            $share_customer_data['getshare'] = 1;
                            $share_customer_data['is_top'] = 0;
                            $share_customer_data['charge_person'] = $value.',';
                            $share_customer_data['follow_up'] = 0;
                            $share_customer_data['next_follow'] = 0;//11-26要求共享去掉下次跟进
                            $share_customer_data['follw_time'] = time();//11-29改为分享的日期 

                            $share_bid = $this->di->share_customer->insert($share_customer_data);
                            $share_bid = $this->di->share_customer->insert_id();
                            // 12-9分享增加被分享人部门id
                            $new_structure_id = $this->di->user->where(array("id"=>$value,"status"=>1))->fetchOne("structure_id");
                            $structure_id = json_decode($new_structure_id);
                            $user_structure_id = array_pop($structure_id);
                            // (3)插入share_join
                            $share_data = array(
                                'cid' => $cid,
                                'share_uid' => $uid,
                                'beshare_uid' => $value,
                                'groupid' => $user_structure_id,
                                'from_type' => 3,//分享
                                'status' => 1,
                                'addtime' => time(),
                                'updatetime' => time(),
                                'bid' => $share_bid,
                                'creat_id' => $customer_info['creatid'],
                            );
                            $share_id = $this->di->share_join->insert($share_data);
                            $share_id = $this->di->share_join->insert_id();

                        }
                        // (4)更新主表负责人
                        if ( ($share_update && $share_customer_update) || ($share_bid && $share_id) ) {
                            $charge_person_str .= $value.',';
                            // 推送消息
                            $msg_title = $username.'共享给您一个客户：'.$customer_info['cname'].'！';
                            $msg_content = $username."共享给您一个客户：".$customer_info['cname'].",请及时处理！";

                            $admin_common->SendMsgNow($uid,$value,$msg_title,$msg_content,0,"share","customer",$cid);
                            $max_share_num -= 1;
                            // 提交事务
                            $this->di->commit('db_master');
                        } else {
                            // 回滚事务
                            $this->di->rollback('db_master');
                            continue;
                        }
                    }

                    if ($charge_person_str) {
                        if ($customer_info['creatid'] == $uid) {
                            # 当前共享人为客户的创建人
                            $table_name = "customer";
                            $update_id = $cid;
                            $gx_msg = '共享客户给'.\App\GetFiledInfo('user','realname',$share_uid);
                            $set = "`myshare` = 1,`charge_person`=CONCAT(charge_person,'{$charge_person_str}')";
                        } else {
                            # 二次共享
                            $table_name = "share_customer";
                            $update_id = $share_bid;
                            $gx_msg = '共享客户给'.\App\GetFiledInfo('user','realname',$share_uid);
                            $set = "`myshare` = 1";
                            $customer_update_sql = "UPDATE ".$this->prefix."customer SET `charge_person`=CONCAT(charge_person,'{$charge_person_str}') WHERE id = ".$cid;
                            $this->di->customer->executeSql($customer_update_sql);
                        }
                        $update_sql = "UPDATE ".$this->prefix.$table_name." SET ".$set." WHERE id = ".$update_id;
                        $this->di->customer->executeSql($update_sql);
                        // (5)增加操作日志
                        $customer_common->CustomerActionLog($uid,$cid,4,'共享客户',$gx_msg);

                        $rs = array('code'=>1,'msg'=>'000056','data'=>$err_msg,'info'=>$err_msg);
                    } else {
                        $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
                    }
                } else {
                    $rs = array('code'=>1,'msg'=>'000055','data'=>array(),'info'=>array());
                }
                break;
        }
        $this->redis->set_time('customer_count_arr_'.$uid,array(),60,'customer');

        return $rs;
    }

    // 客户转移
    public function PostChangePerson($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $change_uid = isset($newData['change_uid']) && !empty($newData['change_uid']) ? intval($newData['change_uid']) : 0;
        $charge_uid = isset($newData['charge_uid']) && !empty($newData['charge_uid']) ? intval($newData['charge_uid']) : 0;

        $bid_arr = isset($newData['bid_arr']) ? $newData['bid_arr'] : array();
        $cid_arr = isset($newData['cid_arr']) ? $newData['cid_arr'] : array();
        if (empty($change_uid) || empty($cid_arr)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        // 当前用户
        $user_info = \App\Getkey($uid,array("realname","structure_id","is_leader"));
        $structure_id = json_decode($user_info['structure_id']);
        $user_structure_id = array_pop($structure_id);
        // 转移人
        $change_user_info = \App\Getkey($change_uid,array("realname","structure_id","is_leader"));
        $change_structure_arr = json_decode($change_user_info['structure_id']);
        $change_structure_id = array_pop($change_structure_arr);

        // 跟进人
        if ($uid == 1) {
            $charge_user_info = \App\Getkey($charge_uid,array("realname","structure_id","is_leader"));
            $charge_structure_arr = json_decode($charge_user_info['structure_id']);
            $charge_structure_id = array_pop($charge_structure_arr);
        }

        $customer_common = new CustomerCommon();
        $admin_common = new AdminCommon();

        foreach ($cid_arr as $key => $value) {
            // Step 1: 开启事务
            $this->di->beginTransaction('db_master');
            $customer_info = $this->di->customer->select("id,cname,creatid,charge_person")->where(array("id"=>$value))->fetchOne();
            $charge_person_arr = explode(",",trim($customer_info['charge_person'],","));
            array_shift($charge_person_arr);
            if (empty($bid_arr[$key])) {
                # 非共享数据，不能转移
                $err_msg[] = "客户：".$customer_info['cname']."转移失败！非共享数据，无法移交！";
                continue;
            }
            // 共享信息
            $share_info = $this->di->share_join->select("groupid,beshare_uid")->where(array("cid"=>$value,"bid"=>$bid_arr[$key]))->fetchOne();
            if ($uid == $customer_info['creatid']) {
                # 客户创建人
                $err_msg[] = "客户：".$customer_info['cname']."转移失败！创建人无法转移客户，请选择移交！";
                continue;
            } elseif (in_array($uid,$charge_person_arr)) {
                # 客户负责人
                // 只能本部门内部转移
                if ($user_structure_id != $change_structure_id) {
                    $err_msg[] = "客户：".$customer_info['cname']."转移失败！只能移交给本部门人员！";
                    continue;
                }
                $beshare_uid = $uid;

            } elseif ($user_info['is_leader'] != 0 || $uid == 1) {
                # 管理员或者领导
                if ($share_info['groupid'] != $change_structure_id) {
                    $err_msg[] = "客户：".$customer_info['cname']."转移失败！只能移交给本部门人员！";
                    continue;
                }
                $beshare_uid = $share_info['beshare_uid'];
            } else {
                # 非创建人和跟进人/非主管/非管理员
                $err_msg[] = "客户：".$customer_info['cname']."转移失败！无权操作！";
                continue;
            }

            // (1)更改共享数据表
            $share_update_arr = array(
                "beshare_uid" => $change_uid,
                "groupid" => $change_structure_id
            );
            $share_update = $this->di->share_join->where(array("cid"=>$value,"bid"=>$bid_arr[$key]))->update($share_update_arr);
            // (2)更改共享信息表
            $change_update_arr = array(
                "charge_person" => $change_uid.",",
                "follow_up" => 0,
                "follow_person" => "",
                "follw_time" => time(),
            );
            $share_customer_update = $this->di->share_customer->where(array("id"=>$bid_arr[$key]))->update($change_update_arr);
            if ($share_update && $share_customer_update) {
                // 提交事务
                $this->di->commit('db_master');
                // (3)更改客户表负责人
                $charge_person_str = ','.$beshare_uid.',';
                $new_charge_person = ','.$change_uid.',';
                $customer_update['charge_person'] = str_replace($charge_person_str,$new_charge_person,$customer_info['charge_person']);
                $this->di->customer->where(array("id"=>$value))->update($customer_update);

                // (4)增加客户操作日志
                $note = $user_info['realname']."将客户：".$customer_info['cname']."转移给".$change_user_info['realname'];
                $customer_common->CustomerActionLog($beshare_uid,$value,15,$note,$note);
               
                // (5)发送消息，给转移人发送消息
                $msg_title = $user_info['realname']."将客户：".$customer_info['cname']."转移给您";
                $msg_content = $user_info['realname']."将客户：".$customer_info['cname']."转移给您";
                $admin_common->SendMsgNow($uid,$change_uid,$msg_title,$msg_content,0,"transfer","customer",$value);

                $success_msg[] = "客户：".$customer_info['cname']."转移给".$change_user_info['realname']."操作成功！";
            } else {
                $this->di->rollback('db_master');
                $err_msg[] = "客户：".$customer_info['cname']."转移给".$change_user_info['realname']."操作失败！";
                continue;
            }
        }
        if ($success_msg) {
            if(!empty($err_msg)) $success_msg = array_merge($success_msg,$err_msg);
            $rs = array('code'=>1,'msg'=>'000058','data'=>$success_msg,'info'=>$success_msg);
        } else {
            $rs = array('code'=>1,'msg'=>'000054','data'=>$err_msg,'info'=>$err_msg);
        }
        return $rs;
    }

    // 分配领取逻辑处理
    public function CustomerDistributeAction($cid_arr,$uid,$beshare_uid,$type,$from_type) {
        if (empty($cid_arr)) {
            return false;
        } else {
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
            $username = \App\Getkey($uid,"username");
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
                    if (empty($value)) {
                        $err_msg[] = "客户".$customer_info['cname'].$type_name."失败，参数有误！";
                        continue;
                    }
                    $share_join = $this->di->share_join->select("sea_type,bid")->where(array("cid"=>$key,"bid"=>$value))->fetchOne();
                    if (empty($share_join)) {
                        $err_msg[] = "客户".$customer_info['cname'].$type_name."失败，数据有误！";
                        continue;
                    }
                    // 判断客户是否是公海客户
                    if ($share_join['sea_type'] == 0) {
                        $err_msg[] = "客户".$customer_info['cname'].$type_name."失败，客户类型为私海！";
                        continue;
                    }
                    // 客户信息
                    // $customer_info = $this->di->customer->select("*")->where(array("id"=>$key))->fetchOne();
                    if ($beshare_usernamme['setlimit'] != 0 && $beshare_limit >= $beshare_usernamme['setlimit']) {
                        // $err_msg[] = "客户".$customer_info['cname'].$type_name."失败，".$beshare_usernamme['realname']."的私海数量已超过限制！";
                        if ($type == 2) {
                            $err_msg[] = $beshare_usernamme['realname']."私海数据已满".$beshare_usernamme['setlimit']."人，无法分配，分配失败！";
                        } else {
                            $err_msg[] = "你的私海数据已满".$beshare_usernamme['setlimit']."人，无法领取，领取失败！";
                        }
                        continue;
                    }

                    // 如果客户的创建人为自己
                    // Step 1: 开启事务
                    $this->di->beginTransaction('db_master');
                    // (1)插入share_customer
                    $share_customer_data = $customer_info;
                    unset($share_customer_data['id']);

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
                        'creat_id' => $customer_info['creatid'],
                        'sea_type' => 0,
                    );
                    $share_id = $this->di->share_join->insert($share_data);
                    
                    if ($bid && $share_id) {
                        // 提交事务
                        $this->di->commit('db_master');
                        // (3) 更新客户负责人以及客户状态(判断是否为客户创建人)
                        $customer_update_data['sea_type'] = 0;
                        if ($beshare_uid != $customer_info['creatid']) {
                            # 非创建人
                            $customer_update_data['charge_person'] = $customer_info['creatid'].','.$beshare_uid.',';
                        }
                        $this->di->customer->where(array("id"=>$key))->update($customer_update_data);
                        // 用户客户私海数量加一
                        // 客户私海数量加一
                        \App\SetLimit($beshare_uid,1);
                        // (4) 增加操作日志
                        $this->CustomerActionLog($uid,$key,$type,$type_msg,'公海'.$type_msg);
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
        $data['success_msg'] = $success_msg;
        $data['err_msg'] = $err_msg;
        return $data;
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
    public function CustomerDistributeAction($cid_arr,$uid,$beshare_uid,$type,$from_type) {
        if (empty($cid_arr)) {
            return false;
        } else {
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
            $username = \App\Getkey($uid,"username");
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
                    if (empty($value)) {
                        $err_msg[] = "客户".$customer_info['cname'].$type_name."失败，参数有误！";
                        continue;
                    }
                    $share_join = $this->di->share_join->select("id,sea_type,bid,beshare_uid")->where(array("cid"=>$key,"bid"=>$value))->fetchOne();
                    $customer_info = $this->di->customer->select("cname,charge_person,creatid")->where(array("id"=>$key))->fetchOne();
                    if (empty($share_join) || empty($customer_info)) {
                        $err_msg[] = "客户".$customer_info['cname'].$type_name."失败，数据有误！";
                        continue;
                    }
                    // 判断客户是否是公海客户
                    if ($share_join['sea_type'] == 0) {
                        $err_msg[] = "客户".$customer_info['cname'].$type_name."失败，客户类型为私海！";
                        continue;
                    }
                    // 客户信息
                    // $customer_info = $this->di->customer->select("*")->where(array("id"=>$key))->fetchOne();
                    if ($beshare_usernamme['setlimit'] != 0 && $beshare_limit >= $beshare_usernamme['setlimit']) {
                        // $err_msg[] = "客户".$customer_info['cname'].$type_name."失败，".$beshare_usernamme['realname']."的私海数量已超过限制！";
                        if ($type == 2) {
                            $err_msg[] = $beshare_usernamme['realname']."私海数据已满".$beshare_usernamme['setlimit']."人，无法分配，分配失败！";
                        } else {
                            $err_msg[] = "你的私海数据已满".$beshare_usernamme['setlimit']."人，无法领取，领取失败！";
                        }
                        continue;
                    }

                    // 如果客户的创建人为自己
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
                        $charge_person_str = ','.$share_join['beshare_uid'].',';
                        $new_charge_person = ','.$beshare_uid.',';
                        $customer_update['charge_person'] = str_replace($charge_person_str,$new_charge_person,$customer_info['charge_person']);
                        $this->di->customer->where(array("id"=>$key))->update($customer_update);
                        // 客户私海数量加一
                        \App\SetLimit($beshare_uid,1);
                        // (4) 增加操作日志
                        $this->CustomerActionLog($uid,$key,$type,$type_msg,'公海'.$type_msg);
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
        \PhalApi\DI()->redis->flushDB('customer');
        $data['success_msg'] = $success_msg;
        $data['err_msg'] = $err_msg;
        return $data;
    }

    // 04 [领取/分配]客户列表
    public function GetReceiveCustomerListOld($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $type = isset($newData['type']) ? intval($newData['type']) : 0;
        $keywords = isset($newData['keywords']) ? $newData['keywords'] : '';
        $order_by = isset($newData['order_by']) && !empty($newData['order_by']) ? $newData['order_by'] : array();
        $where_arr = isset($newData['where_arr']) && !empty($newData['where_arr']) ? $newData['where_arr'] : array();
        $pageno = isset($newData['pageno']) ? intval($newData['pageno']) : 1;
        $pagesize = isset($newData['pagesize']) ? intval($newData['pagesize']) : $this->pagesize;
        $pagenum = ($pageno-1)*$pagesize;

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断用户角色(如果为部门领导则显示整个部门客户,如果为admin显示所有的客户)
        $user_info = \App\Getkey($uid,array("is_leader","status","structure_id","parent_id"));
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }

        // 关键词搜索
        if (!empty($keywords)) {
            $keywords_where = " AND IF(s.creat_id = {$uid},CONCAT(c.cname,cd.cphone) LIKE '%{$keywords}%',CONCAT(sc.cname,cd.cphone) LIKE '%{$keywords}%') ";
        } else {
            $keywords_where = "";
        }

        $structure_id = json_decode($user_info['structure_id']);
        $user_structure_id = array_pop($structure_id);
        $params[':status'] = 1;
        if ($type == 1) {
            # 领取
            $from_type = 2;
            $field = "beshare_uid";
        } elseif ($type == 2) {
            # 我分配的
            $from_type = 1;
            $field = "share_uid";
        } else {
            # 分配给我
            $from_type = 1;
            $field = "beshare_uid";
        }

        // 高级搜索
        if (!empty($where_arr)) {
            $senior_where = "";
            foreach ($where_arr as $key => $value) {
                if (is_array($value)) {
                    if ($value[0] == "" || $value[1] == "") {
                        continue;
                    }
                }
                if (!empty($value) || $value == 0) {
                    $key = 'addtime' ? 's.'.$key : 'c.'.$key;
                    # 其他
                    if (is_array($value) && is_numeric($value[0])) {
                        $senior_where .= " AND ( {$key} BETWEEN {$value[0]} AND {$value[1]} ) ";
                    } else {
                        $senior_where .= " AND {$key} LIKE '%{$value}%' ";
                    }
                }
            }
        }

        // 排序
        if (empty($order_by) || $order_by[1] == 0) {
            $order_str = " s.addtime DESC ";
        } else {
            $order_field = $order_by[0];
            $order_type = $order_by[1] == 1 ? "DESC" : "ASC";
            $order_str = " {$order_field} {$order_type} ";
        }
        $sea_where = " s.status = :status AND s.sea_type = 0 AND from_type = {$from_type}  AND {$field} = {$uid} ";
        // if ($uid != 1) {
        //     $sea_where .= " AND {$field} = {$uid} ";
        // }
        $share_sql = "SELECT s.cid,s.bid,s.creat_id,s.addtime,IF(s.creat_id = {$uid},c.cname,sc.cname) as cname,IF(s.creat_id = {$uid},c.intentionally,sc.intentionally) as intentionally,IF(s.creat_id = {$uid},c.ittnxl,sc.ittnxl) as ittnxl,cd.sex,cd.cphone FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."customer c ON c.id = s.cid AND s.creat_id = {$uid}  LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id AND s.creat_id <> {$uid} LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$sea_where.$senior_where.$keywords_where." ORDER BY ".$order_str." LIMIT ".$pagenum.",".$pagesize;
        $customer_list = $this->di->share_join->queryAll($share_sql,$params);

        if (empty($customer_list)) {
            $rs = array('code'=>1,'msg'=>'000002','data'=>array(),'info'=>array());
            return $rs;
        }
        $share_count_sql = "SELECT count(s.id) as num FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."customer c ON c.id = s.cid AND s.creat_id = {$uid}  LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id AND s.creat_id <> {$uid} LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$sea_where.$senior_where.$keywords_where;

        $count_arr = $this->di->share_join->queryAll($share_count_sql,$params);
        $data['data_list'] = $customer_list;
        $data['data_num'] = $count_arr[0]['num'];
        $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>$data);
        return $rs;
    }
    
}