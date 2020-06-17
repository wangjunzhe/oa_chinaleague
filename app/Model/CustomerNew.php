<?php
namespace App\Model;
use PhalApi\Model\NotORMModel as NotORM;
use App\Model\Common as Common;
use App\Common\Admin as AdminCommon;
use App\Common\Customer as CustomerCommon;
class CustomerNew extends NotORM 
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

    // 01 客户列表接口
    public function GetMyCustomerList($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $type = isset($newData['type']) && !empty($newData['type']) ? intval($newData['type']) : 0;
        $day_type = isset($newData['day_type']) ? intval($newData['day_type']) : 0;
        $gj_type = isset($newData['gj_type']) ? intval($newData['gj_type']) : 0;
        $yx_type = isset($newData['yx_type']) && !empty($newData['yx_type']) ? $newData['yx_type'] : "";
        $keywords = isset($newData['keywords']) ? $newData['keywords'] : '';//搜索关键词
        $where_arr = isset($newData['where_arr']) && !empty($newData['where_arr']) ? $newData['where_arr'] : array();
        $order_by = isset($newData['order_by']) && !empty($newData['order_by']) ? $newData['order_by'] : array();
        
        $pageno = isset($newData['pageno']) ? intval($newData['pageno']) : 1;
        $pagesize = isset($newData['pagesize']) ? intval($newData['pagesize']) : $this->pagesize;
        $pagenum = ($pageno-1)*$pagesize;

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        // 获取当前人员信息
        $user_info = \App\Getkey($uid,array("is_leader","status","structure_id","parent_id"));
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
        // 当前人员部门id
        $structure_id = json_decode($user_info['structure_id']);
        $user_structure_id = array_pop($structure_id);

        $admin_common = new AdminCommon();
        $params[':status'] = 1;
        $keywords_where = "";
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
        // 公私海类型
        $share_sea_type = " s.sea_type = 0 AND ";
        $sea_type_where = " sea_type = 0 AND ";
        

        $where_gx = $share_sea_type." s.from_type = 3 ";
        $where_sh = $sea_type_where;
        $share_user_where = $share_sea_type;
        $share_customer = $share_sea_type;
        $customer_where = $where_sh;// 默认全部/
        $customer_where2 = $where_sh;//私海客户
        $status_where1 = $share_sea_type." (s.from_type  = 1 OR s.from_type  = 2) ";//公海客户
        $status_where3 = $where_gx;//我的共享
        $status_where4 = $where_gx;//共享给我
        $status_where5 = $sea_type_where." FROM_UNIXTIME( cd.create_time,  '%Y-%m-%d %H:%i:%s' ) BETWEEN '$today_start_date' AND '$today_end_date' ";//今日新增
        $status_where6 = $share_sea_type." (s.from_type  = 1 OR s.from_type  = 2) AND FROM_UNIXTIME( s.addtime,  '%Y-%m-%d %H:%i:%s' ) BETWEEN '$today_start_date' AND '$today_end_date' ";//今日公海
        $status_where7 = $where_gx." AND to_days(FROM_UNIXTIME(s.addtime,'%Y-%m-%d')) = to_days(now()) ";//今日共享
        $status_where8 = $where_gx." AND to_days(FROM_UNIXTIME(s.addtime,'%Y-%m-%d')) = to_days(now())  ";//今日共享给我
        $status_where9 = $where_sh." sc.next_follow <> 0 AND TO_DAYS(now()) - TO_DAYS(FROM_UNIXTIME(sc.next_follow,'%Y-%m-%d')) >= 0 ";//今日提醒跟进
        $share_where9 = $share_sea_type." sc.next_follow <> 0 AND TO_DAYS(now()) - TO_DAYS(FROM_UNIXTIME(sc.next_follow,'%Y-%m-%d')) >= 0 ";//今日提醒跟进分享表
        $status_where10 = $where_sh." TO_DAYS(FROM_UNIXTIME(sc.follw_time,'%Y-%m-%d')) - TO_DAYS(now()) = 0 ";//今日已跟进
        $share_where10 = $share_sea_type." TO_DAYS(FROM_UNIXTIME(sc.follw_time,'%Y-%m-%d')) - TO_DAYS(now()) = 0 ";//今日已跟进分享表
        $status_where11 = $where_sh." TO_DAYS(now()) - TO_DAYS(FROM_UNIXTIME(sc.next_follow,'%Y-%m-%d')) = 0 ";//今日未跟进
        $share_where11 = $share_sea_type." TO_DAYS(now()) - TO_DAYS(FROM_UNIXTIME(sc.next_follow,'%Y-%m-%d')) = 0 ";//今日未跟进分享表
        $status_where12 = $sea_type_where." sc.charge_person = concat(sc.creatid,',') ";//我的客户&&未分享
        $status_where13 = $where_gx;//今日共享已跟进/今日共享未跟进

        if ($uid != 1 || $type == 16) {
            // 非超级管理员
            if ( in_array($user_info['is_leader'],array(1,2,3)) && $type == 15) {
                
                // 12-9查看下级更改为查看本部门客户以及共享给本部分的数据
                $user_structure_str = $admin_common->GetMyStructure($user_info['is_leader'],json_decode($user_info['structure_id']),$user_info['parent_id']);
                if ($user_info['is_leader'] == 1 && empty($user_info['parent_id'])) {
                    // 部门主管&&没有管理其他部门
                    $structure_where = " cd.groupid = {$user_structure_str} ";
                    $share_user_where .= " s.groupid = {$user_structure_str} ";
                } else {
                    $structure_where = " FIND_IN_SET(cd.groupid, '{$user_structure_str}') ";
                    $share_user_where .= " FIND_IN_SET(s.groupid, '{$user_structure_str}') ";
                }
                // var_dump()
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
                // 部门类型
                $group_type_where = " AND cd.groupid = {$user_structure_id} ";
                // 普通用户 -- 只查看自己的客户
                $share_user_where .= " AND sc.charge_person = CONCAT(s.beshare_uid,',') ";
                $customer_where .= " creatid = '{$uid}' ".$group_type_where;// 私人客户
                $share_customer .= " creatid <> '{$uid}' AND beshare_uid = '{$uid}' ";// 其他客户
                $customer_where2 .= " creatid = '{$uid}' ".$group_type_where;///私海客户
                $status_where1 .= " AND s.beshare_uid = '{$uid}' AND sc.creatid <> '{$uid}' ";//公海客户
                $status_where3 .= " AND s.share_uid = '{$uid}' AND s.from_type = 3 ";//我的共享
                $status_where4 .= " AND beshare_uid = '{$uid}' ";//共享给我
                $status_where5 .= " AND creatid = '{$uid}' ".$group_type_where;//今日新增
                $status_where6 .= " AND s.beshare_uid = '{$uid}' AND sc.creatid <> '{$uid}' ";//今日公海
                $status_where7 .= " AND s.share_uid = '{$uid}' AND s.from_type = 3 ";//今日共享
                $status_where8 .= " AND beshare_uid = '{$uid}' ";//今日共享给我
                $status_where9 .= " AND creatid = '{$uid}'";//今日提醒跟进
                $share_where9 .= " AND creatid != '{$uid}' AND sc.charge_person = CONCAT('{$uid}',',')  ";//今日已跟进分享表
                $status_where10 .= " AND creatid = '{$uid}'";//今日提醒跟进
                $share_where10 .= " AND s.beshare_uid = '{$uid}' AND sc.creatid <> '{$uid}' ";//今日已跟进分享表
                $status_where11 .= " AND creatid = '{$uid}'".$group_type_where;//今日未跟进
                $share_where11 .= " AND sc.charge_person = CONCAT('{$uid}',',') ";//今日未跟进分享表
                $status_where12 .= " AND creatid = '{$uid}' ".$group_type_where;//我的客户&&未分享
                $status_where13 .= " AND s.creat_id = '{$uid}' ";//今日共享已跟进/未跟进
            }
        }
        // var_dump($status_where11);
        // var_dump($share_where11);

        // 读取缓存
        if (!in_array($type,array(15,16)) && $uid != 1) {
            $total_arr = $this->redis->get_time('customer_count_arr_'.$uid,'customer');
            if ( empty($total_arr) ) {
                    // 计算数量
                    //（1）公海总数
                    $gh_count_sql = "SELECT count(s.id) as num FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id WHERE ".$status_where1;
                    $customer_gh_num = $this->di->share_join->queryAll($gh_count_sql,$params);
                    $customer_gh_total = $customer_gh_num[0]['num'];
                    // (2)私海总数
                    $sh_count_sql = "SELECT count(sc.id) as num FROM ".$this->prefix."customer sc LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid WHERE ".$customer_where2;
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
                    $jrtxgj_count_sql = "SELECT count(id) as num FROM ( (SELECT sc.id FROM ".$this->prefix."customer sc LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid WHERE ".$status_where9.") UNION ALL (SELECT sc.id FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id WHERE ".$share_where9." ) ) a";
                    $customer_jrtxgj_num = $this->di->customer->queryAll($jrtxgj_count_sql,$params);
                    $customer_jrtxgj_total = $customer_jrtxgj_num[0]['num'];
                    // (10)今日已跟进
                    $jrygj_count_sql = "SELECT count(id) as num FROM ( (SELECT sc.id FROM ".$this->prefix."customer sc LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid WHERE ".$status_where10.") UNION ALL (SELECT sc.id FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id WHERE ".$share_where10." ) ) a";
                    $customer_jrygj_num = $this->di->customer->queryAll($jrygj_count_sql,$params);
                    $customer_jrygj_total = $customer_jrygj_num[0]['num'];
                    // (11)今日未跟进
                    $jrwgj_count_sql = "SELECT count(id) as num FROM ( (SELECT sc.id FROM ".$this->prefix."customer sc LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid WHERE ".$status_where11.") UNION ALL (SELECT sc.id FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id WHERE ".$share_where11." ) ) a";
                    $customer_jrwgj_num = $this->di->customer->queryAll($jrwgj_count_sql,$params);
                    $customer_jrwgj_total = $customer_jrwgj_num[0]['num'];
                    // (12)我的客户&&未共享
                    $wgx_count_sql = "SELECT count(sc.id) as num FROM ".$this->prefix."customer sc LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid WHERE ".$status_where12;
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
                    $this->redis->set_time('customer_count_arr_'.$uid,$total_data,6000,'customer');
            }
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
                    if($key == 'sc.share_time') $key = 's.addtime';
                    if ($key == "sc.share_group") $key = "s.groupid";
                    # 其他
                    if (is_array($value) && is_numeric($value[0])) {
                        $senior_where .= " AND ( {$key} BETWEEN {$value[0]} AND {$value[1]} ) ";
                    } elseif (is_array($value)) {
                        $senior_where .= " AND ( UNIX_TIMESTAMP({$key}) between '{$value[0]}' AND '{$value[1]}' ) ";
                    } elseif (is_numeric($value)) {
                        if ($key == 'sc.charge_person') {
                            $senior_where .= " AND FIND_IN_SET('{$value}', charge_person) ";
                        }elseif (in_array($key,array("cd.cphone","cd.cphonetwo","cd.cphonethree","cd.wxnum","cd.cemail"))) {
                            $senior_where .= " AND {$key} LIKE '{$value}%' ";
                        }elseif ($key == 'cd.sex') {
                            $senior_where .= " AND {$key} = '{$value}' ";
                        } else {
                            $senior_where .= " AND {$key} = {$value} ";
                        }
                    } else {
                        if (in_array($key,array("sc.intentionally","sc.xuel","sc.ittnxl"))) {
                            $senior_where .= " AND {$key} = '{$value}' ";
                        } elseif (in_array($key,array("cd.groupid","s.groupid"))) {
                            $senior_where .= " AND FIND_IN_SET({$key}, '{$value}') ";
                        }  else {
                            $senior_where .= " AND {$key} LIKE '%{$value}%' ";
                        }
                    }
                }
            }
        }
        // 排序
        if (empty($order_by) || $order_by[1] == 0) {
            $order_str = " sc.is_top DESC,cd.id DESC ";
        } else {
            if (in_array($order_by[0],array("create_time","cphone"))) {
                $order_key = "cd.";
            } else {
                $order_key = "sc.";
            }
            
            $order_field = $order_key.$order_by[0];
            if($order_field == "sc.share_time") $order_field = "s.addtime";
            $order_type = $order_by[1] == 1 ? "DESC" : "ASC";
            $order_str = " {$order_field} {$order_type} ";
        }

        if ($day_type) {
            $mark_day_arr = array(0,1,3,7,30,60,90);
            $mark_day = $mark_day_arr[$day_type];
        }

        if ($type == 13 || $type == 10 || $gj_type == 1) {
            // 已跟进
            $mark = "<=";
            $field = "sc.follw_time";
        }

        if ($type == 14 || $type == 11 || $gj_type == 2) {
            // 未跟进
            $mark = ">=";
            $field = "sc.next_follow";
        }
        $genjin_type_arr = array(10,11,13,14);
        
        if( !empty($day_type) && !empty($gj_type) ){
            $end_date         = date('Y-m-d 23:59:59',time()); 
            $gj_where = " AND DATEDIFF('{$end_date}', FROM_UNIXTIME( sc.follw_time,  '%Y-%m-%d %H:%i:%s' ) ) {$mark} {$mark_day} ";
        } elseif (in_array($type,$genjin_type_arr)) {
            $gj_where = " AND TO_DAYS(FROM_UNIXTIME(".$field.",'%Y-%m-%d')) - TO_DAYS(now()) = 0 ";
        } else {
            $gj_where = " ";
        }

        $field = "sc.id,sc.cname,sc.labelpeer,sc.intentionally,sc.ittnxl,sc.ittnzy,sc.ittnyx,sc.ittnxm,sc.ittngj,sc.budget,sc.timeline,sc.graduate,sc.graduatezy,sc.xuel,sc.tolink,sc.note,sc.creatid,sc.is_top,sc.charge_person,sc.follow_up,sc.follow_person,sc.follw_time,sc.next_follow,sc.xueshuchengji,sc.yuyanchengji,sc.hzID,sc.sort,cd.cid,cd.zdnum,cd.own,cd.occupation,cd.groupid,cd.sex,cd.age,cd.station,cd.industry,cd.experience,cd.company,cd.scale,cd.character,cd.agent_name,cd.cphone,cd.cphonetwo,cd.cphonethree,cd.telephone,cd.formwhere,cd.formwhere2,cd.formwhere3,cd.wxnum,cd.cemail,cd.qq,cd.create_time,cd.ocreatid";
        $customer_field = $field.",'' as bid";
        $customer_data_field = $field.",s.bid";
        if ($uid == 1 || $type == 16) {
            # 超级管理员
            $sql = "SELECT ".$customer_field." FROM ".$this->prefix."customer sc LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid WHERE ".$keywords_where." sc.status = :status AND sea_type = 0 ".$senior_where.$gj_where." ORDER BY ".$order_str." LIMIT ".$pagenum.",".$pagesize;

            $count_sql = "SELECT count(sc.id) as num FROM ".$this->prefix."customer sc LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid WHERE ".$keywords_where." sc.status = :status AND sea_type = 0 ".$senior_where.$gj_where;

        } else {
            switch ($type) {
                case 1:
                    # 公海客户(分配或者领取的--不包含自己创建的客户)
                    $sql = "SELECT ".$customer_data_field." FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where1.$gj_where.$senior_where." ORDER BY ".$order_str." LIMIT ".$pagenum.",".$pagesize;
                    $count_sql = "SELECT count(s.id) as num FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where1.$gj_where.$senior_where;
                    break;
                case 2:
                    # 我的客户(自己创建的客户)
                    $sql = "SELECT ".$customer_field." FROM ".$this->prefix."customer sc LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid WHERE ".$keywords_where.$customer_where2.$gj_where.$senior_where." ORDER BY ".$order_str." LIMIT ".$pagenum.",".$pagesize;
                    $count_sql = "SELECT count(sc.id) as num FROM ".$this->prefix."customer sc LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid WHERE ".$keywords_where.$customer_where2.$gj_where.$senior_where;
                    
                    break;
                case 3:
                    # 我共享的客户
                    $sql = "SELECT ".$customer_data_field.",s.addtime as share_time,s.groupid as share_group FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where3.$gj_where.$senior_where." ORDER BY ".$order_str." LIMIT ".$pagenum.",".$pagesize;
                    $count_sql = "SELECT count(cid) as num FROM ( SELECT s.cid FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where3.$gj_where.$senior_where." ) temp ";

                    break;
                case 4:
                    # 共享给我的
                    $sql = "SELECT ".$customer_data_field.",s.addtime as share_time,s.groupid as share_group FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where4.$gj_where.$senior_where." ORDER BY ".$order_str." LIMIT ".$pagenum.",".$pagesize;
                    $count_sql = "SELECT count(s.id) as num FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where4.$gj_where.$senior_where;
                    
                    break;
                case 5:
                    # 今日新增客户
                    $sql = "SELECT ".$customer_field." FROM ".$this->prefix."customer sc LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid WHERE ".$keywords_where.$status_where5.$gj_where.$senior_where." ORDER BY ".$order_str." LIMIT ".$pagenum.",".$pagesize;
                    $count_sql = "SELECT count(sc.id) as num FROM ".$this->prefix."customer sc LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid WHERE ".$keywords_where.$status_where5.$gj_where.$senior_where;
                    break;
                case 6:
                    # 今日公海客户
                    $sql = "SELECT ".$customer_data_field." FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where6.$gj_where.$senior_where." ORDER BY ".$order_str." LIMIT ".$pagenum.",".$pagesize;
                    $count_sql = "SELECT count(s.id) as num FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.beshare_uid = cd.cid WHERE ".$keywords_where.$status_where6.$gj_where.$senior_where;
                    break;
                case 7:
                    # 今日我共享的客户
                    $sql = "SELECT ".$customer_data_field.",s.addtime as share_time,s.groupid as share_group FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where7.$gj_where.$senior_where." ORDER BY ".$order_str." LIMIT ".$pagenum.",".$pagesize;
                    $count_sql = "SELECT count(cid) as num FROM ( SELECT s.cid FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where7.$gj_where.$senior_where." ) temp ";

                    break;
                case 8:
                    # 今日共享给我的客户
                    $sql = "SELECT ".$customer_data_field.",s.addtime as share_time,s.groupid as share_group FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where8.$gj_where.$senior_where." ORDER BY ".$order_str." LIMIT ".$pagenum.",".$pagesize;
                    $count_sql = "SELECT count(s.id) as num FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where8.$gj_where.$senior_where;
                    // var_dump($sql);die;
                    break;
                case 12:
                    # 我的客户&&未分享的
                    $sql = "SELECT ".$customer_field." FROM ".$this->prefix."customer sc LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid WHERE ".$keywords_where.$status_where12.$gj_where.$senior_where." ORDER BY ".$order_str." LIMIT ".$pagenum.",".$pagesize;
                    $count_sql = "SELECT count(sc.id) as num FROM ".$this->prefix."customer sc LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid WHERE ".$keywords_where.$status_where12.$gj_where.$senior_where;

                    break;
                case 13:
                    # 今日共享已跟进
                    $sql = "SELECT ".$customer_data_field.",s.addtime as share_time,s.groupid as share_group FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where13.$senior_where.$gj_where." ORDER BY ".$order_str." LIMIT ".$pagenum.",".$pagesize;
                    $count_sql = "SELECT count(id) as num FROM ( SELECT s.id FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where13.$senior_where.$gj_where." ) temp ";
                    break;
                case 14:
                    # 今日共享未跟进
                    $sql = "SELECT ".$customer_data_field.",s.addtime as share_time,s.groupid as share_group FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$status_where13.$senior_where.$gj_where." AND TO_DAYS(FROM_UNIXTIME(".$field.",'%Y-%m-%d')) - TO_DAYS(now()) = 0 ORDER BY ".$order_str." LIMIT ".$pagenum.",".$pagesize;
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
                        $where1 = $status_where10;//今日已跟进
                        $where2 = $share_where10;//分享今日已跟进
                    } elseif ($type == 11) {
                        # 今日未跟进
                        $where1 = $status_where11;//今日未跟进
                        $where2 = $share_where11;//今日分享未跟进
                        // var_dump($where1);
                        // var_dump($where2);

                    } elseif ($type == 15) {
                        $where1 = $customer_where;
                        $where2 = $share_user_where." AND s.beshare_uid <> s.creat_id ";
                        $customer_field .= ",0 as share_time,'' as share_group ";
                        $customer_data_field .= ",s.addtime as share_time,s.groupid as share_group ";
                        if ((strpos($senior_where,'s.addtime') !== false || strpos($order_str,'s.addtime') !== false) || strpos($senior_where,'s.groupid') !== false) {
                            $types = true;
                        }
                    } else {
                        $where1 = $customer_where;
                        $where2 = $share_customer;
                    }
                    $order_str = str_replace("cd.", "a.", $order_str);
                    $order_str = str_replace("sc.", "a.", $order_str);

                    if ($type == 15 &&  $types) {
                        $order_str = str_replace("s.addtime", "a.share_time", $order_str);
                        $sql = "SELECT
                                a.*
                            FROM
                                (
                                    (
                                        SELECT
                                            ".$customer_data_field."
                                        FROM
                                            ".$this->prefix."share_join s 
                                        LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id
                                        LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid
                                        WHERE
                                            ".$keywords_where.$where2.$gj_where.$senior_where."
                                    )
                                ) AS a
                            ORDER BY
                                ".$order_str."
                            LIMIT ".$pagenum.",".$pagesize;
                        $count_sql = "SELECT count(id) as num FROM ( (SELECT sc.* FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$where2.$gj_where.$senior_where." ) ) a";
                    } else {
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
                                            ".$keywords_where.$where1.$gj_where.$senior_where."
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
                                            ".$keywords_where.$where2.$gj_where.$senior_where."
                                    )
                                ) AS a
                            ORDER BY
                                ".$order_str."
                            LIMIT ".$pagenum.",".$pagesize;
                    $count_sql = "SELECT count(id) as num FROM ( (SELECT sc.* FROM ".$this->prefix."customer sc LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid WHERE ".$keywords_where.$where1.$gj_where.$senior_where.") UNION ALL (SELECT sc.* FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$where2.$gj_where.$senior_where." ) ) a";
                    }
                        
                    break;
            }
        }
        // var_dump($sql);die;

        $customer_list = $this->di->customer->queryAll($sql, $params);
        $customer_num = $this->di->customer->queryAll($count_sql,$params);
        
        $customer_data['customer_num'] = !empty($customer_num[0]['num']) ? $customer_num[0]['num'] : 0;
        $customer_data['total_count'] = !empty($total_arr) ? $total_arr : array();
        if($uid==1 || $type == 16){
            $customer_data['total_count']=['total'=>$customer_data['customer_num']];
        }
        if (!empty($customer_list)) {
            
            foreach ($customer_list as $key => $value) {
                // 组织部门
                $customer_list[$key]['group'] = $admin_common->GetStructNameById($value['groupid']);
                // 分享部门
                if (!empty($value['share_group'])) {
                    $customer_list[$key]['share_group'] = $admin_common->GetStructNameById($value['share_group']);
                }
                # 原始创建人
                if (!empty($value['ocreatid'])) {
                    $customer_list[$key]['old_publisher'] = \App\Getkey($value['ocreatid'],"realname");
                } else {
                    $customer_list[$key]['old_publisher'] = "";
                }
                $charge_person_str = "";
                // 负责人处理
                $charge_person_arr = explode(",",trim($value['charge_person'],","));
                
                if (!empty($charge_person_arr)) {
                    // if (count($charge_person_arr) > 1) {
                    //     array_shift($charge_person_arr);
                    // }
                    foreach ($charge_person_arr as $k => $v) {
                        // 查询share_join 表中的客户公私海类型
                        $sea_type = $this->di->share_join->select("sea_type,groupid")->where(array("cid"=>$value['cid'],"beshare_uid"=>$v))->fetchOne();
                        if ($sea_type['sea_type'] == 1) {
                            $share_group_name = $admin_common->GetStructNameById($sea_type['groupid']);
                            $username = $share_group_name."公海";
                        } else {
                            $username = \App\Getkey($v,"realname");
                        }
                        $charge_person_str .= $username.",";
                    }
                    $customer_list[$key]['charge_person'] = trim($charge_person_str,",");
                } else {
                    $customer_list[$key]['charge_person'] = "";
                }
                
                // 查看下级员工隐藏手机号 12-6加
                if ($uid != 1 && in_array($type,array(15,16)) && !in_array($uid,$charge_person_arr)) {
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
                $customer_list[$key]['publisher'] = \App\Getkey($value['creatid'],"realname");
                
            }
        }
        $customer_data['customer_list'] = !empty($customer_list) ? $customer_list : array();
        $rs = array('code'=>1,'msg'=>'000000','data'=>$customer_data,'info'=>$customer_data);
        return $rs;
    }

    // 02 回归公海
    public function PostReturnToSea($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $cid_arr = isset($newData['cid_arr']) && !empty($newData['cid_arr']) ? $newData['cid_arr'] : array();
        $cancle_uid = isset($newData['cancle_uid']) && !empty($newData['cancle_uid']) ? intval($newData['cancle_uid']) : 0;
        if (empty($uid) || empty($cid_arr)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断用户角色
        $user_info = \App\Getkey($uid,array("realname","is_leader","structure_id"));
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }
        
        if ($uid == 1 && !empty($cancle_uid)) {
            $beshare_uid = $cancle_uid;
        } else {
            $beshare_uid = $uid;
        }
        $beshare_info = \App\Getkey($beshare_uid,array("realname","structure_id"));
        $structure_id = json_decode($beshare_usernamme['structure_id']);
        $user_structure_id = array_pop($structure_id);
        $customer_common = new CustomerCommon();
        $admin_common = new AdminCommon();
        
        foreach ($cid_arr as $key => $value) {
            if (empty($key)) {
                $err_msg[] = "回归公海失败，客户参数有误！";
                continue;
            }
            // 客户基本信息
            $customer_info = $this->di->customer->select("id,cname,creatid,charge_person")->where(array("id"=>$key))->fetchOne();
            if (empty($customer_info)) {
                $err_msg[] = "客户：".$customer_info['cname']."回归公海失败，客户信息不存在！";
                continue;
            }
            $charge_person_arr = explode(",",trim($customer_info['charge_person'],","));
            array_shift($charge_person_arr);
            
            if (empty($value)) {
                # 创建人数据回归公海的
                if (in_array($uid,$charge_person_arr)) {
                    # 当前用户是客户负责人
                    $err_msg[] = "客户：".$customer_info['cname']."回归公海失败，跟进人无权将创建数据回归公海！";
                    continue;
                } elseif ($uid == $customer_info['creatid'] || $uid == 1 || $user_info['is_leader'] != 0) {
                    # 创建人自己/管理员/部门负责人回归公海
                    // 更改客户的公私海类型
                    $throw_sea = $this->di->customer->where(array("id"=>$key))->update(array("sea_type"=>1));
                    // 判断之前有没有自己领取或者分配的
                    $share_info = $this->di->share_join->where(array("cid"=>$key,"beshare_uid"=>$customer_info['creatid']))->delete();
                    if ($throw_sea) {
                        // 客户私海数量减一
                        \App\SetLimit($customer_info['creatid'],-1);
                        // 扔回公海成功
                        $success_msg[] = "客户：".$customer_info['cname']."回归公海操作成功！";
                        $create_realname = \App\Getkey($customer_info['creatid'],"realname");
                        // 增加扔回公海操作日志
                        $note = $user_info['realname']."将客户：".$customer_info['cname']."创建人：".$create_realname."的数据扔回公海！";
                        $customer_common->CustomerActionLog($uid,$key,1,'扔回公海',$note);
                        // 给客户创建人发送消息
                        $msg_title = "您创建的客户".$customer_info['cname']."被".$user_info['realname']."回归公海！";
                        $msg_content = "您创建的客户".$customer_info['cname']."被".$user_info['realname']."回归公海！";
                        $admin_common->SendMsgNow($uid,$customer_info['creatid'],$msg_title,$msg_content,0,"return","customer",$customer_info['id']);
                    } else {
                        // 扔回公海失败
                        $err_msg[] = "客户：".$customer_info['cname']."回归公海操作失败！";
                        continue;
                    }
                } else {
                    # 无权操作
                    $err_msg[] = "客户：".$customer_info['cname']."回归公海失败，无权操作！";
                    continue;
                }
            } else {
                # 共享数据回归公海
                if ($uid == $customer_info['creatid']) {
                    # 客户创建人
                    $err_msg[] = "客户：".$customer_info['cname']."回归公海失败，已共享给其他人的数据无法回归公海！";
                    continue;
                } elseif (in_array($uid,$charge_person_arr) || $user_info['is_leader'] != 0) {
                    # 客户负责人
                    $share_where = array();
                    $share_where['cid'] = $key;
                    $share_where['bid'] = $value;
                    if ($user_info['is_leader'] != 0) {
                        $share_info = $this->di->share_join->select("beshare_uid")->where($share_where)->fetchOne();
                        $beshare_uid  = "";
                        $beshare_info  = array();
                        # 部门主管查看下级
                        $beshare_uid = $share_info['beshare_uid'];
                        $beshare_info = \App\Getkey($beshare_uid,array("realname"));
                        $share_where['beshare_uid'] = $beshare_uid;
                    } else {
                        $share_where['beshare_uid'] = $beshare_uid;
                        $share_info = $this->di->share_join->select("beshare_uid")->where($share_where)->fetchOne();
                    }
                    if ($share_info) {
                        $throw_sea = $this->di->share_join->where($share_where)->update(array("sea_type"=>1));
                        if ($throw_sea) {
                            // 客户私海数量减一
                            \App\SetLimit($beshare_uid,-1);
                            // 扔回公海成功
                            $success_msg[] = "客户：".$customer_info['cname']."回归公海操作成功！";
                            // 增加扔回公海操作日志
                            $note = $user_info['realname']."将客户：".$customer_info['cname']."的负责人：".$beshare_info['realname']."的数据扔回公海！";
                            $customer_common->CustomerActionLog($uid,$key,17,'扔回公海',$note);
                            if ($user_info['is_leader'] != 0 || $uid == 1) {
                                // 给扔回公海的负责人发送消息
                                $msg_title = "您跟进的客户".$customer_info['cname']."被".$user_info['realname']."回归公海！";
                                $msg_content = "您跟进的客户".$customer_info['cname']."被".$user_info['realname']."回归公海！";
                                $admin_common->SendMsgNow($uid,$beshare_uid,$msg_title,$msg_content,0,"return","customer",0);
                            }
                        } else {
                            // 扔回公海失败
                            $err_msg[] = "客户：".$customer_info['cname']."共享数据回归公海操作失败！";
                            continue;
                        }
                    } else {
                        $err_msg[] = "客户：".$customer_info['cname']."回归公海失败，共享信息数据有误！";
                        continue;
                    }
                } else {
                    # 无权操作
                    $err_msg[] = "客户：".$customer_info['cname']."回归公海失败，无权操作！";
                    continue;
                }
            }
        }
        $this->redis->flushDB('customer');
        $data_msg = array(
            'success' => $success_msg,
            'error' => $err_msg
        );
        return $rs = array('code'=>1,'msg'=>'000000','data'=>$data_msg,'info'=>$data_msg); 
    }

    // 03 公海客户列表
    public function GetMySeaCustomerLists($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $type = isset($newData['type']) && !empty($newData['type']) ? intval($newData['type']) : 0;
        $keywords = isset($newData['keywords']) ? $newData['keywords'] : '';//搜索关键词
        $where_arr = isset($newData['where_arr']) && !empty($newData['where_arr']) ? $newData['where_arr'] : array();
        $order_by = isset($newData['order_by']) && !empty($newData['order_by']) ? $newData['order_by'] : array();

        $pageno = isset($newData['pageno']) ? intval($newData['pageno']) : 1;
        $pagesize = isset($newData['pagesize']) ? intval($newData['pagesize']) : $this->pagesize;
        $pagenum = ($pageno-1)*$pagesize;
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断用户角色(如果为部门领导则显示整个部门客户,如果为admin显示所有的客户)
        $user_info =  \App\Getkey($uid,array("is_leader","status","structure_id","parent_id"));

        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }
        $admin_common = new AdminCommon();
        $structure_id = json_decode($user_info['structure_id']);
        $params[':status'] = 1;
        $params[':sea_type'] = 1;
        $params[':share_sea_type'] = 1;

        $sea_where = " sc.status = :status AND sc.sea_type = :sea_type ";
        $share_sea_where = " s.sea_type = :share_sea_type ";

        $sea_where1 = $sea_where." AND l.type = 8 AND to_days(FROM_UNIXTIME(l.addtime,'%Y-%m-%d')) = to_days(now()) ";//今日新增公海
        $share_sea_where1 = $share_sea_where." AND l.type = 16 AND to_days(FROM_UNIXTIME(l.addtime,'%Y-%m-%d')) = to_days(now()) ";//共享今日新增公海

        $sea_where2 = $sea_where." AND l.type = 1 AND to_days(FROM_UNIXTIME(l.addtime,'%Y-%m-%d')) = to_days(now()) ";//今日回归公海
        $share_sea_where2 = $share_sea_where." AND l.type = 17 AND to_days(FROM_UNIXTIME(l.addtime,'%Y-%m-%d')) = to_days(now()) ";//今日回归公海

        $user_structure_id = array_pop($structure_id);
        $sea_group = $this->di->structure->where(array("id"=>$user_structure_id))->fetchOne("share");
        if ($uid == 1 || $sea_group == 'all') {
            # 查看全部
            $sea_where .= "";
            $share_sea_where .= "";
        } else {
            if (empty($sea_group) && $user_info['is_leader'] == 0) {
                $sea_group = $structure_id.",";
            }
            $sea_group_old = trim($sea_group,",");
            if ( (empty($sea_group) || $sea_group_old == $user_structure_id )  && $user_info['is_leader'] != 0) {
                // 12-9查看下级更改为查看本部门客户以及共享给本部分的数据
                $user_structure_str = $admin_common->GetMyStructure($user_info['is_leader'],json_decode($user_info['structure_id']),$user_info['parent_id']);
                $sea_group = $user_structure_str;
                // $sea_group = implode(",",json_decode($user_info['structure_id']));
            }
            # 非系统管理员
            $customer_data_join = " LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid ";
            $group_where = " AND FIND_IN_SET(cd.groupid, '{$sea_group}') ";
            $group_share_where = " AND FIND_IN_SET(s.groupid, '{$sea_group}') ";

            $sea_where .= $group_where;
            $share_sea_where .= $group_share_where;

            $sea_where1 .= $group_where;
            $share_sea_where1 .= $group_share_where;

            $sea_where2 .= $group_where;
            $share_sea_where2 .= $group_share_where;

        }
        

        $total_arr = $this->redis->get_time('customer_sea_count_'.$uid,'customer');
        if (empty($total_arr)) {
            # // 计算数量
            //（1）全部
            $gh_count_sql = "SELECT count(id) as num FROM ( (SELECT sc.id FROM ".$this->prefix."customer sc ".$customer_data_join." WHERE ".$sea_where.") UNION ALL (SELECT s.cid as id FROM ".$this->prefix."share_join s WHERE ".$share_sea_where." ) ) a";
            $customer_gh_num = $this->di->customer->queryAll($gh_count_sql,$params);
            $customer_gh_total = $customer_gh_num[0]['num'];
            // (2)今日新增公海
            $xzgh_count_sql = "SELECT count(DISTINCT id) as num FROM ( (SELECT sc.id FROM ".$this->prefix."customer sc ".$customer_data_join." LEFT JOIN ".$this->prefix."customer_log l ON sc.id = l.cid WHERE ".$sea_where1.") ) a";
            $customer_xzgh_num = $this->di->customer_log->queryAll($xzgh_count_sql,$params);
            $customer_xzgh_total = $customer_xzgh_num[0]['num'];
            // (3)今日回归公海
            $hggh_count_sql = "SELECT count(DISTINCT id) as num FROM ( (SELECT sc.id FROM ".$this->prefix."customer sc ".$customer_data_join." LEFT JOIN ".$this->prefix."customer_log l ON sc.id = l.cid WHERE ".$sea_where2.") UNION ALL (SELECT s.cid as id FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."customer_log l ON s.cid = l.cid  WHERE ".$share_sea_where2." ) ) a";
            $customer_hggh_num = $this->di->customer_log->queryAll($hggh_count_sql,$params);
            $customer_hggh_total = $customer_hggh_num[0]['num'];

            $total_data['customer_gh_total'] = $customer_gh_total;//全部公海客户
            $total_data['customer_xzgh_total'] = $customer_xzgh_total;//今日新增公海客户
            $total_data['customer_hggh_total'] = $customer_hggh_total;//今日回归公海客户
            $total_arr = $total_data;
            $this->redis->set_time('customer_sea_count_'.$uid,$total_data,6000,'customer');
        }

        // 关键词搜索
        if (!empty($keywords)) {
            $keywords_where = " AND concat(sc.cname,cd.cphone,cd.cphonetwo,cd.cphonethree) LIKE '%{$keywords}%' ";
        } else {
            $keywords_where = "";
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
                        $senior_where .= " AND ( UNIX_TIMESTAMP({$key}) between '{$value[0]}' AND '{$value[1]}' ) ";
                    } elseif (is_numeric($value)) {
                        if ($key == 'sc.charge_person') {
                            $senior_where .= " AND FIND_IN_SET('{$value}', charge_person) ";
                        } elseif (in_array($key,array("cd.cphone","cd.cphonetwo","cd.cphonethree","cd.wxnum","cd.cemail"))) {
                            $senior_where .= " AND {$key} LIKE '{$value}%' ";
                        } elseif ($key == 'cd.sex') {
                            $senior_where .= " AND {$key} = '{$value}' ";
                        } else {
                            $senior_where .= " AND {$key} = {$value} ";
                        }
                    } else {
                        if (in_array($key,array("sc.intentionally","sc.xuel","sc.ittnxl"))) {
                            $senior_where .= " AND {$key} = '{$value}' ";
                        } elseif (in_array($key,array("cd.groupid","s.groupid"))) {
                            $senior_where .= " AND FIND_IN_SET({$key}, '{$value}') ";
                        }  else {
                            $senior_where .= " AND {$key} LIKE '%{$value}%' ";
                        }
                    }
                }
            }
            if ((strpos($senior_where,'cd.groupid') !== false)) {
                $senior_where2 = str_replace("cd.groupid", "s.groupid", $senior_where);
            } else {
                $senior_where2 = $senior_where;
            }
        }
        // 排序
        if (empty($order_by) || $order_by[1] == 0) {
            $order_str = " a.is_top DESC,a.id DESC ";
        } else {
            $order_field = $order_by[0];
            $order_type = $order_by[1] == 1 ? "DESC" : "ASC";
            $order_str = " {$order_field} {$order_type} ";
        }

        $field = "sc.id,sc.cname,sc.labelpeer,sc.intentionally,sc.ittnxl,sc.ittnzy,sc.ittnyx,sc.ittnxm,sc.ittngj,sc.budget,sc.timeline,sc.graduate,sc.graduatezy,sc.xuel,sc.tolink,sc.note,sc.creatid,sc.is_top,sc.charge_person,sc.follow_up,sc.follow_person,sc.follw_time,sc.next_follow,sc.xueshuchengji,sc.yuyanchengji,sc.hzID,sc.sort,cd.cid,cd.zdnum,cd.own,cd.occupation,cd.sex,cd.age,cd.station,cd.industry,cd.experience,cd.company,cd.scale,cd.character,cd.agent_name,cd.cphone,cd.cphonetwo,cd.cphonethree,cd.telephone,cd.formwhere,cd.formwhere2,cd.formwhere3,cd.wxnum,cd.cemail,cd.qq,cd.create_time,cd.ocreatid";
        $customer_field = $field.",cd.groupid,'' as bid";
        $share_customer_field = $field.",s.groupid,s.bid";
        switch ($type) {
            case 1:
                # 今日新增公海
                $where1 = $sea_where1;
                $where2 = $share_sea_where1;
                $log_join = " LEFT JOIN ".$this->prefix."customer_log l ON sc.id = l.cid ";
                $share_log_join = " LEFT JOIN ".$this->prefix."customer_log l ON s.cid = l.cid ";
                $count = "count(DISTINCT id)";
                break;
            case 2:
                # 今日回归公海
                $where1 = $sea_where2;
                $where2 = $share_sea_where2;
                $log_join = " LEFT JOIN ".$this->prefix."customer_log l ON sc.id = l.cid ";
                $share_log_join = " LEFT JOIN ".$this->prefix."customer_log l ON s.cid = l.cid ";
                $count = "count(DISTINCT id)";
                break;
            default:
                # 全部公海
                $where1 = $sea_where;
                $where2 = $share_sea_where;
                $log_join = " ";
                $count = "count(id)";
                break;
        }
        
        $sql = "SELECT
                    DISTINCT a.*
                FROM
                    (
                        (
                            SELECT
                                ".$customer_field."
                            FROM
                                ".$this->prefix."customer sc
                            LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid
                            ".$log_join."
                            WHERE
                                ".$where1.$senior_where.$keywords_where."
                        )
                        UNION ALL
                        (
                            SELECT
                                ".$share_customer_field."
                            FROM
                                ".$this->prefix."share_join s 
                            LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id
                            ".$share_log_join."
                            LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid
                            WHERE
                                ".$where2.$senior_where2.$keywords_where."
                        )
                    ) AS a
                ORDER BY
                    ".$order_str."
                LIMIT ".$pagenum.",".$pagesize;
        // var_dump($sql);die;
        $customer_gh_list = $this->di->customer->queryAll($sql, $params);
        if (!empty($customer_gh_list)) {
            $count_sql = "SELECT {$count} as num FROM ( (SELECT sc.id FROM ".$this->prefix."customer sc ".$log_join." LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid WHERE ".$where1.$senior_where.$keywords_where.") UNION ALL (SELECT s.cid as id FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id ".$share_log_join." LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$where2.$senior_where2.$keywords_where." ) ) a";
            $customer_gh_num = $this->di->customer->queryAll($count_sql,$params);
            $admin_common = new AdminCommon();
            foreach ($customer_gh_list as $key => $value) {
                // 隐藏手机号
                $customer_gh_list[$key]['cphone'] = !empty($value['cphone']) ? \App\string_substr($value['cphone'],3,8) : "";
                $customer_gh_list[$key]['cphonetwo'] = !empty($value['cphonetwo']) ? \App\string_substr($value['cphonetwo'],3,8) : "";
                $customer_gh_list[$key]['cphonethree'] = !empty($value['cphonethree']) ? \App\string_substr($value['cphonethree'],3,8) : "";
                // 隐藏座机
                $customer_gh_list[$key]['telephone'] = !empty($value['telephone']) ? \App\string_substr($value['telephone'],0,3) : "";
                // 隐藏微信和邮箱
                $customer_gh_list[$key]['wxnum'] = !empty($value['wxnum']) ? \App\string_substr($value['wxnum'],0,3) : "";
                $customer_gh_list[$key]['cemail'] = !empty($value['cemail']) ? \App\string_substr($value['cemail'],0,3) : "";

                $customer_gh_list[$key]['group'] = $admin_common->GetStructNameById($value['groupid']);
                $charge_person_str = "";
                // 负责人处理
                $charge_person_arr = explode(",",trim($value['charge_person'],","));
                foreach ($charge_person_arr as $k => $v) {
                    $username = \App\Getkey($v,"realname");
                    $charge_person_str .= $username.",";
                }
                $customer_gh_list[$key]['charge_person'] = trim($charge_person_str,",");
                // 创建人
                $customer_gh_list[$key]['publisher'] = \App\Getkey($value['creatid'],"realname");
                # 原始创建人
                if (!empty($value['ocreatid'])) {
                    $customer_list[$key]['old_publisher'] = \App\Getkey($value['ocreatid'],"realname");
                } else {
                    $customer_list[$key]['old_publisher'] = "";
                }
            }
        }
        $customer_gh_data['customer_gh_list'] = !empty($customer_gh_list) ? $customer_gh_list : array();
        $customer_gh_data['customer_gh_num'] = !empty($customer_gh_num[0]['num']) ? $customer_gh_num[0]['num'] : 0;
        $customer_gh_data['total_count'] = !empty($total_arr) ? $total_arr : array();
        $rs = array('code'=>1,'msg'=>'000000','data'=>$customer_gh_data,'info'=>$customer_gh_data);
        // echo "<pre>";
        // print_r($rs);
        // exit;
        return $rs;
    }
    
    // 04 [领取/分配]客户列表
    public function GetReceiveCustomerList($newData) {
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

    // 05 报备客户
    public function PostShareMyCustomer($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $type = isset($newData['type']) ? intval($newData['type']) : 0;
        $is_repeat = isset($newData['is_repeat']) ? intval($newData['is_repeat']) : 0;
        $is_leader = isset($newData['is_leader']) ? intval($newData['is_leader']) : 0;
        $cid_arr = isset($newData['cid_arr']) && !empty($newData['cid_arr']) ? $newData['cid_arr'] : array();
        $beshare_uid = isset($newData['beshare_uid']) && !empty($newData['beshare_uid']) ? $newData['beshare_uid'] : array();
        // $beshare_uid = isset($newData['share_uid']) && !empty($newData['share_uid']) ? $newData['share_uid'] : array();

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断用户角色(如果为部门领导则显示整个部门客户,如果为admin显示所有的客户)
        $user_info = \App\Getkey($uid,array("is_leader","structure_id","parent_id","capacity","share"));
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }
        if (!empty($user_info['share']) && $user_info['share'] != 'all') {
            $user_share_group = explode(",",$user_info['share']);
        } else {
            $structure_arr = json_decode($user_info['structure_id']);
            $structure_id = array_pop($structure_arr);
            $user_share_group[] = $structure_id;
        }
        $customer_common = new CustomerCommon();
        $admin_common = new AdminCommon();
        switch ($type) {
            case 1:
                # 多个客户共享给一个人
                $beshare_uid = $beshare_uid[0];
                $beshare_user_info = \App\Getkey($beshare_uid,array("realname","structure_id","share","capacity","setlimit","getlimit"));
                $beshare_structure_arr = json_decode($beshare_user_info['structure_id']);
                $beshare_structure_id = array_pop($beshare_structure_arr);

                // 判断被分享人与分析人是否是一个部门
                if($user_share_group && in_array($beshare_structure_id,$user_share_group)){ 
                    $data_msg['error'][] = "客户共享失败，不允许部门内部人员共享！";
                    $rs = array('code'=>1,'msg'=>'000000','data'=>$data_msg,'info'=>$data_msg);
                    return $rs;
                }
                // 判断被分享人私海数量[setlimit为0不限制数量限制]
                if ($beshare_user_info['setlimit'] != 0 && $beshare_user_info['getlimit'] >= $beshare_user_info['setlimit']) {
                    // $err_msg[] = "客户共享失败，".$beshare_user_info['realname']."的私海数量已超过限制！";
                    $data_msg['error'][] = $beshare_user_info['realname']."的私海数量已满".$beshare_user_info['setlimit']."人，无法共享给".$beshare_user_info['realname']."，共享失败！";
                    // 给被分享人发送消息提示
                    $msg_title = "刚刚".$user_info['realname']."共享客户给你，你的私海数据已满".$beshare_user_info['setlimit']."人，其他人无法共享给你，共享失败！";
                    $msg_content = "刚刚".$user_info['realname']."共享客户给你，你的私海数据已满".$beshare_user_info['setlimit']."人，其他人无法共享给你，共享失败！";
                    $admin_common->SendMsgNow($uid,$beshare_uid,$msg_title,$msg_content,0,"share","customer",0);

                    $rs = array('code'=>1,'msg'=>'000000','data'=>$data_msg,'info'=>$data_msg);
                    return $rs;
                }

                // 报备规则
                $err_str = $customer_common->GetReportRule($user_info['capacity'],$beshare_user_info['capacity']);
                if (!empty($err_str)) {
                    $data_msg['error'][] = "客户共享失败，".$err_str;
                    $rs = array('code'=>1,'msg'=>'000000','data'=>$data_msg,'info'=>$data_msg);
                    return $rs;
                }
                $getlimit = $beshare_user_info['getlimit'];
                foreach ($cid_arr as $key => $value) {

                    if ($beshare_user_info['setlimit'] != 0 &&  $getlimit >= $beshare_user_info['setlimit']) {
                        $err_msg[] = "客户共享失败，".$beshare_user_info['realname']."的私海数量已超过限制！";
                        continue;
                    }

                    // 判断客户是否已经报备两次
                    $share_num = $this->di->share_join->where("cid = {$value} AND from_type = 3 AND to_days(FROM_UNIXTIME(addtime,'%Y-%m-%d')) = to_days(now())")->count("id");
                    if ($share_num >= 2) {
                        $err_msg[] = "客户：".$customer_info['cname']."共享给".$beshare_user_info['realname']."失败，每天最多可报备2次!";
                        continue;
                    }
                    // 客户信息
                    $customer_info = $this->di->customer->select("*")->where(array("id"=>$value,"status"=>1))->fetchOne();
                    $charge_person_arr = explode(",",trim($customer_info['charge_person'],","));
                    array_shift($charge_person_arr);
                    if (in_array($beshare_uid, $charge_person_arr)) {
                        $err_msg[] = "客户：".$customer_info['cname']."共享失败，".$beshare_user_info['realname']."已是该客户负责人！";
                        continue;
                    }
                    $customer_data = $this->di->customer_data->select("wxnum,cphone,cphonetwo,cphonethree,telephone")->where(array("cid"=>$value))->fetchOne();
                    // 判断手机号是否存在于被分享人部门 同部门不允许手机号重复[如果被共享人部门有该手机号则不允许共享]
                    $data_arr = $customer_common->GetStructByPhone($customer_data,$beshare_user_info['share']);
                    if (!empty($data_arr['sh_arr']) || !empty($data_arr['gx_arr'])) {
                        # 部门下存在该手机号
                        if (!empty($data_arr['gx_arr'])) {
                            # 该部门下共享数据存在此手机号
                            if (count($data_arr['gx_arr']) > 1) {
                                $err_msg[] = "客户：".$customer_info['cname']."共享给".$beshare_user_info['realname']."失败，数据重复无法共享！";
                                continue;
                            }
                            if ($data_arr['gx_arr'][0]['sea_type'] == 1) {
                                # 在公海-启用
                                $share_arr = $customer_common->DoSeaOpenCustomer($customer_info,$data_arr['gx_arr'][0]['cid'],$data_arr['gx_arr'][0]['bid'],$uid,$beshare_uid,$beshare_structure_id);
                                if ($share_arr['err_person']) {
                                    $err_msg[] = "客户：".$customer_info['cname']."共享给".$beshare_user_info['realname']."失败!";
                                    continue;
                                }
                                if ($share_arr['success_person']) {
                                    $charge_person_str .= $value.",";
                                    $success_msg[] = "客户：".$customer_info['cname']."共享给".$beshare_user_info['realname']."成功!";
                                    $getlimit++;
                                    $share_num++;
                                }
                            } else {
                                # 在私海-不能共享
                                $err_msg[] = "客户：".$customer_info['cname']."共享给".$beshare_user_info['realname']."失败，本部门下已存在此共享数据！";
                                continue;
                            }
                        }

                        if (!empty($data_arr['sh_arr'])) {
                            # 该部门下创建有此手机号
                            if (count($data_arr['sh_arr']) > 1) {
                                $err_msg[] = "客户：".$customer_info['cname']."共享给".$beshare_user_info['realname']."失败，本部门下已存在该手机号！";
                                continue;
                            }
                            if ($data_arr['sh_arr'][0]['sea_type'] == 1) {
                                # 在公海-启用【创建人更改为新的创建人，保留原来的原始创建人】
                                // $err_msg[] = "客户：".$customer_info['cname']."共享给".$beshare_user_info['realname']."失败，本部门下公海下已存在此共享数据！";
                                // continue;
                                $share_arr = $customer_common->DoSeaOpenCustomer($customer_info,$data_arr['sh_arr'][0]['cid'],0,$uid,$beshare_uid,$beshare_structure_id);
                                if ($share_arr['err_person']) {
                                    $err_msg[] = "客户：".$customer_info['cname']."共享给".$beshare_user_info['realname']."失败!";
                                    continue;
                                }
                                if ($share_arr['success_person']) {
                                    $charge_person_str .= $value.",";
                                    // $success_msg[] = "客户：".$customer_info['cname']."共享给".$beshare_user_info['realname']."成功!";
                                    $success_msg[] = "客户：".$customer_info['cname']."共享给".$beshare_user_info['realname']."失败，本部门下已存在此共享数据！说明：系统自动把客户从公海启用到了【".$beshare_user_info['realname']."】的私海。";
                                    $getlimit++;
                                    $share_num++;
                                }
                            } else {
                                # 在私海-不能共享
                                $err_msg[] = "客户：".$customer_info['cname']."共享给".$beshare_user_info['realname']."失败，本部门下已存在此共享数据！";
                                continue;
                            }
                        }
                        
                    } else {
                        # 部门下没有该手机号，允许共享
                        $share_arr = $customer_common->DoShareCustomer($customer_info,$value,$uid,$beshare_uid,$beshare_structure_id,$is_repeat);
                        if ($share_arr['err_person']) {
                            $err_msg[] = "客户：".$customer_info['cname']."共享给".$beshare_user_info['realname']."失败!";
                            continue;
                        }
                        if ($share_arr['success_person']) {
                            $getlimit++;
                            $share_num++;
                            $charge_person_str .= $value.",";
                            $success_msg[] = "客户：".$customer_info['cname']."共享给".$beshare_user_info['realname']."成功!";
                        }
                    }
                }
                break;
            
            default:
                # 一个客户共享给多个人
                $cid = $cid_arr[0];
                
                // 判断客户是否今天已经报备两次
                $share_num = $this->di->share_join->where("cid = {$cid} AND from_type = 3 AND to_days(FROM_UNIXTIME(addtime,'%Y-%m-%d')) = to_days(now())")->count("id");
                if ($share_num >= 2) {
                    $rs = array('code'=>0,'msg'=>'000501','data'=>array(),'info'=>array());
                    return $rs;
                }
                // 客户信息
                $customer_info = $this->di->customer->select("*")->where(array("id"=>$cid,"status"=>1))->fetchOne();
                    
                if (empty($customer_info)) {
                    $rs = array('code'=>0,'msg'=>'000050','data'=>array(),'info'=>array());
                    return $rs;
                }
                if ($customer_info['sea_type'] == 1) {
                    $rs = array('code'=>0,'msg'=>'000505','data'=>array(),'info'=>array());
                    return $rs;
                }
                $charge_person_arr = explode(",",trim($customer_info['charge_person'],","));
                array_shift($charge_person_arr);
                $customer_data = $this->di->customer_data->select("wxnum,cphone,cphonetwo,cphonethree,telephone")->where(array("cid"=>$customer_info['id']))->fetchOne();
                foreach ($beshare_uid as $key => $value) {
                    // 判断是否超过共享次数
                    if ($share_num >= 2) {
                        $err_msg[] = "客户：".$customer_info['cname']."共享失败，每天最多可报备2次！";
                        continue;
                    }

                    $beshare_user_info = \App\Getkey($value,array("realname","structure_id","share","capacity","setlimit","getlimit"));
                    $beshare_structure_arr = json_decode($beshare_user_info['structure_id']);
                    $beshare_structure_id = array_pop($beshare_structure_arr);

                    // $find_beshare_structure = $beshare_structure_id.',';
                    if (in_array($value, $charge_person_arr)) {
                        $err_msg[] = "客户：".$customer_info['cname']."共享失败，".$beshare_user_info['realname']."已是该客户负责人！";
                        continue;
                    }
                    // 不允许部门内部[同一个公海算是同一部门]共享
                    if($user_share_group && in_array($beshare_structure_id,$user_share_group)){ 
                        $err_msg[] = "客户：".$customer_info['cname']."共享给".$beshare_user_info['realname']."失败，不允许部门内部人员共享；";
                        continue;
                    }
                    // 判断被分享人私海数量[setlimit为0不限制数量限制]
                    if ($beshare_user_info['setlimit'] != 0 && $beshare_user_info['getlimit'] >= $beshare_user_info['setlimit']) {
                        // $err_msg[] = "客户：".$customer_info['cname']."共享给".$beshare_user_info['realname']."失败，".$beshare_user_info['realname']."的私海数量超过限制；";
                        $err_msg[] = $beshare_user_info['realname']."的私海数量已满".$beshare_user_info['setlimit']."人，无法共享给".$beshare_user_info['realname']."，共享失败！";
                        // 给被分享人发送消息提示
                        $msg_title = "刚刚".$user_info['realname']."共享客户给你，你的私海数据已满".$beshare_user_info['setlimit']."人，其他人无法共享给你，共享失败！";
                        $msg_content = "刚刚".$user_info['realname']."共享客户给你，你的私海数据已满".$beshare_user_info['setlimit']."人，其他人无法共享给你，共享失败！";
                        $admin_common->SendMsgNow($uid,$value,$msg_title,$msg_content,0,"share","customer",0);
                        continue;
                    }
                    // 报备规则
                    $err_str = $customer_common->GetReportRule($user_info['capacity'],$beshare_user_info['capacity']);
                    if (!empty($err_str)) {
                        $err_msg[] = "客户：".$customer_info['cname']."共享给".$beshare_user_info['realname']."失败，".$err_str;
                        continue;
                    }
                    // 判断手机号是否存在于被分享人部门 同部门不允许手机号重复[如果被共享人部门有该手机号则不允许共享]
                    $data_arr = $customer_common->GetStructByPhone($customer_data,$beshare_user_info['share']);
                    if (!empty($data_arr['sh_arr']) || !empty($data_arr['gx_arr'])) {
                        # 部门下存在该手机号
                        if (!empty($data_arr['gx_arr'])) {
                            # 该部门下共享数据存在此手机号
                            if (count($data_arr['gx_arr']) > 1) {
                                $err_msg[] = "客户：".$customer_info['cname']."共享给".$beshare_user_info['realname']."失败，数据重复无法共享！";
                                continue;
                            }
                            if ($data_arr['gx_arr'][0]['sea_type'] == 1) {
                                # 在公海-启用
                                $share_arr = $customer_common->DoSeaOpenCustomer($customer_info,$data_arr['gx_arr'][0]['cid'],$data_arr['gx_arr'][0]['bid'],$uid,$value,$beshare_structure_id);
                                if ($share_arr['err_person']) {
                                    $err_msg[] = "客户：".$customer_info['cname']."共享给".$beshare_user_info['realname']."失败!";
                                    continue;
                                }
                                if ($share_arr['success_person']) {
                                    $charge_person_str .= $value.",";
                                    $success_msg[] = "客户：".$customer_info['cname']."共享给".$beshare_user_info['realname']."成功!";
                                    $getlimit++;
                                    $share_num++;
                                }
                            } else {
                                # 在私海-不能共享
                                $err_msg[] = "客户：".$customer_info['cname']."共享给".$beshare_user_info['realname']."失败，本部门下已存在此共享数据！";
                                continue;
                            }
                        }

                        if (!empty($data_arr['sh_arr'])) {
                            # 该部门下创建有此手机号
                            if (count($data_arr['sh_arr']) > 1) {
                                $err_msg[] = "客户：".$customer_info['cname']."共享给".$beshare_user_info['realname']."失败，本部门下已存在改手机号！";
                                continue;
                            }
                            if ($data_arr['sh_arr'][0]['sea_type'] == 1) {
                                # 在公海-不能共享
                                $share_arr = $customer_common->DoSeaOpenCustomer($customer_info,$data_arr['sh_arr'][0]['cid'],0,$uid,$value,$beshare_structure_id);
                                if ($share_arr['err_person']) {
                                    $err_msg[] = "客户：".$customer_info['cname']."共享给".$beshare_user_info['realname']."失败!";
                                    continue;
                                }
                                if ($share_arr['success_person']) {
                                    $charge_person_str .= $value.",";
                                    // $success_msg[] = "客户：".$customer_info['cname']."共享给".$beshare_user_info['realname']."成功!";
                                    $success_msg[] = "客户：".$customer_info['cname']."共享给".$beshare_user_info['realname']."失败，本部门下已存在此共享数据！说明：系统自动把客户从公海启用到了【".$beshare_user_info['realname']."】的私海。";
                                    $getlimit++;
                                    $share_num++;
                                }
                            } else {
                                # 在私海-不能共享
                                $err_msg[] = "客户：".$customer_info['cname']."共享给".$beshare_user_info['realname']."失败，本部门下已存在此共享数据！";
                                continue;
                            }
                        }

                    } else {
                        # 部门下没有该手机号，允许共享
                        $share_arr = $customer_common->DoShareCustomer($customer_info,$cid,$uid,$value,$beshare_structure_id,$is_repeat);
                        if ($share_arr['err_person']) {
                            $err_msg[] = "客户：".$customer_info['cname']."共享给".$beshare_user_info['realname']."失败!";
                            continue;
                        }
                        if ($share_arr['success_person']) {
                            $charge_person_str .= $value.",";
                            $success_msg[] = "客户：".$customer_info['cname']."共享给".$beshare_user_info['realname']."成功!";
                            $getlimit++;
                            $share_num++;
                        }
                    }
                }
                break;
        }
        $data_msg = array(
            'success' => $success_msg,
            'error' => $err_msg
        );
        $this->redis->flushDB('customer');
        return $rs = array('code'=>1,'msg'=>'000000','data'=>$data_msg,'info'=>$data_msg);
    }



}