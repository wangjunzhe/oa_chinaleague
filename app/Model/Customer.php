<?php
namespace App\Model;
use PhalApi\Model\NotORMModel as NotORM;
use App\Model\Common as Common;
use App\Model\Structure as Dostru;
use App\Common\Tree;
use App\Common\Category;
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
    public function GetCustomerList($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $type = isset($newData['type']) && !empty($newData['type']) ? intval($newData['type']) : 0;
        $day_type = isset($newData['day_type']) ? intval($newData['day_type']) : 0;
        $gj_type = isset($newData['gj_type']) ? intval($newData['gj_type']) : 0;
        $yx_type = isset($newData['yx_type']) && !empty($newData['yx_type']) ? $newData['yx_type'] : "";
        $level_type = isset($newData['level_type']) && !empty($newData['level_type']) ? $newData['level_type'] : "";
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
        $admin_common = new AdminCommon();
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

        $where_gx = "  s.from_type = 3 ";
        $where_sh = "  sea_type = 0 ";
        $share_user_where = " 1 ";
        $share_customer = " 1 ";
        $customer_where = $where_sh;// 默认全部/
        $customer_where2 = $where_sh;//私海客户
        $status_where1 = "  (s.from_type  = 1 OR s.from_type  = 2) ";//公海客户
        $status_where3 = $where_gx;//我的共享
        $status_where4 = $where_gx;//共享给我
        $status_where5 = "  sea_type = 0 AND FROM_UNIXTIME( cd.create_time,  '%Y-%m-%d %H:%i:%s' ) BETWEEN '$today_start_date' AND '$today_end_date' ";//今日新增
        $status_where6 = "  (s.from_type  = 1 OR s.from_type  = 2) AND FROM_UNIXTIME( s.addtime,  '%Y-%m-%d %H:%i:%s' ) BETWEEN '$today_start_date' AND '$today_end_date' ";//今日公海
        $status_where7 = $where_gx." AND to_days(FROM_UNIXTIME(s.addtime,'%Y-%m-%d')) = to_days(now()) ";//今日共享
        $status_where8 = $where_gx." AND to_days(FROM_UNIXTIME(s.addtime,'%Y-%m-%d')) = to_days(now())  ";//今日共享给我
        $status_where9 = $where_sh." AND sc.next_follow <> 0 AND TO_DAYS(now()) - TO_DAYS(FROM_UNIXTIME(sc.next_follow,'%Y-%m-%d')) >= 0 ";//今日提醒跟进
        $share_where9 = " sc.next_follow <> 0 AND TO_DAYS(now()) - TO_DAYS(FROM_UNIXTIME(sc.next_follow,'%Y-%m-%d')) >= 0 ";//今日提醒跟进分享表
        $status_where10 = $where_sh." AND TO_DAYS(FROM_UNIXTIME(sc.follw_time,'%Y-%m-%d')) - TO_DAYS(now()) = 0 ";//今日已跟进
        $share_where10 = " TO_DAYS(FROM_UNIXTIME(sc.follw_time,'%Y-%m-%d')) - TO_DAYS(now()) = 0 ";//今日已跟进分享表
        $status_where11 = $where_sh." AND TO_DAYS(now()) - TO_DAYS(FROM_UNIXTIME(sc.next_follow,'%Y-%m-%d')) = 0 ";//今日未跟进
        $share_where11 = "  TO_DAYS(now()) - TO_DAYS(FROM_UNIXTIME(sc.next_follow,'%Y-%m-%d')) = 0 ";//今日未跟进分享表
        $status_where12 = "  sea_type = 0 AND sc.charge_person = concat(sc.creatid,',') ";//我的客户&&未分享
        $status_where13 = $where_gx;//今日共享已跟进/今日共享未跟进

        if ($uid != 1 || $type == 16) {
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
                $user_structure_str = $admin_common->GetMyStructure($user_info['is_leader'],$structure_id,$user_info['parent_id']);
                if ($user_info['is_leader'] == 1 && !empty($user_info['parent_id'])) {
                    // 部门主管&&没有管理其他部门
                    $structure_where = " AND cd.groupid = {$user_structure_str} ";
                    $share_user_where .= " AND s.groupid = {$user_structure_str} ";
                } else {
                    $structure_where = " AND FIND_IN_SET(cd.groupid, '{$user_structure_str}') ";
                    $share_user_where .= "  AND FIND_IN_SET(s.groupid, '{$user_structure_str}') ";
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
        if (!in_array($type,array(15,16)) && $uid != 1) {
            $total_arr = $this->redis->get_time('customer_count_arr_'.$uid,'customer');
            // $total_arr = $this->redis->get_forever('customer_count_arr','customer');
            if ( empty($total_arr) ) {
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
                    $this->redis->set_time('customer_count_arr_'.$uid,$total_data,6000,'customer');
            }
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
                        } else {
                            $senior_where .= " AND {$key} = {$value} ";
                        }
                    } else {
                        if (in_array($key,array("sc.intentionally"))) {
                            $senior_where .= " AND {$key} LIKE '{$value}%' ";
                        } elseif (in_array($key,array("sc.groupid","s.groupid"))) {
                            $senior_where .= " AND FIND_IN_SET({$key}, '{$value}') ";
                        } else {
                            $senior_where .= " AND {$key} LIKE '%{$value}%' ";
                        }
                    }
                }
            }
        }
        // var_dump($senior_where);die;
        // 排序
        if (empty($order_by) || $order_by[1] == 0) {
            $order_str = " sc.is_top DESC,cd.id DESC ";
        } else {
            // $order_key = $order_by[0] == "create_time" ? "cd." : "sc.";
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
                        $where1 = $customer_where2;//今日已跟进
                        $where2 = $share_user_where;//分享今日已跟进
                    } elseif ($type == 11) {
                        # 今日未跟进
                        $where1 = $customer_where2;//今日未跟进
                        $where2 = $share_user_where;//今日分享未跟进
                    } elseif ($type == 15) {
                        $where1 = $customer_where;
                        $where2 = $share_user_where;
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
                    $count_sql = "SELECT count(id) as num FROM ( (SELECT sc.id FROM ".$this->prefix."customer sc LEFT JOIN ".$this->prefix."customer_data cd ON sc.id = cd.cid WHERE ".$keywords_where.$where1.$gj_where.$senior_where.") UNION ALL (SELECT s.id FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."share_customer sc ON s.bid = sc.id LEFT JOIN ".$this->prefix."customer_data cd ON s.cid = cd.cid WHERE ".$keywords_where.$where2.$gj_where.$senior_where." ) ) a";
                    }
                        
                    break;
            }
        }
        // var_dump($sql);die;

        // 客户列表
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
                $customer_list[$key]['charge_person'] = trim($charge_person_str,",");
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
                $username = $this->di->user->where("id",$value['creatid'])->fetchOne("realname");
                $customer_list[$key]['publisher'] = $username;
                
            }
            // $customer_list = \APP\sortArrayByfield($customer_list,"is_top");
        }
        $customer_data['customer_list'] = !empty($customer_list) ? $customer_list : array();
        $rs = array('code'=>1,'msg'=>'000000','data'=>$customer_data,'info'=>$customer_data);
        
        return $rs;
    }

    // 02 删除客户接口
    public function PostDeleteCustomer($type,$uid,$id) {
        $uid = intval($uid);
        $id = intval($id);
        $type = isset($type) ? $type : 0;
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断客户信息是否存在
        $customer_info = $this->di->customer->select("creatid,charge_person")->where(array("id"=>$id,"status"=>1))->fetchOne();
        if (empty($customer_info)) {
            $rs = array('code'=>0,'msg'=>'000050','data'=>array(),'info'=>array());
            return $rs;
        }

        // 非客户创建人不允许删除
        if ($type != 1 && $uid != 1) {
            // 非客户创建人不允许删除
            if ($uid != intval($customer_info['creatid']) && $uid != 1) {
                $rs = array('code'=>0,'msg'=>'000051','data'=>array(),'info'=>array());
                return $rs;
            }

            // 该客户有一个以上负责人，则不允许删除
            $charge_person_arr = explode(",",$customer_info['charge_person']);
            array_pop($charge_person_arr);
            $charge_person_num = count($charge_person_arr);

            if ($charge_person_num > 1) {
                $rs = array('code'=>0,'msg'=>'000052','data'=>array(),'info'=>array());
                return $rs;
            }
        }
        $customer_common = new CustomerCommon();
        $recycle = $this->config->get('common.IS_DELETE');
        if ($recycle) {
            // 客户私海数量减一
            \App\SetLimit($customer_info['creatid'],-1);
            // 删除客户意向
            $edit_info = $this->di->customer->where("id",$id)->delete();
            // 删除客户基本信息
            $this->di->customer_data->where("cid",$id)->delete();
            $bid = $this->di->share_join->where("cid",$id)->fetchPairs("id","beshare_uid");
            foreach ($bid as $key => $value) {
                $this->di->share_customer->where("id",$value)->delete();
                // 客户私海数量减一
                \App\SetLimit($value,-1);
            }
            $this->di->share_join->where("cid",$id)->delete();
            // 删除客户成交审核
            $this->di->customer_order->where("cid",$id)->delete();
            // 删除客户开票信息
            $this->di->customer_invoice->where("cid",$id)->delete();
            // 删除客户退款信息
            $this->di->customer_refund->where("cid",$id)->delete();
            // 删除客户操作日志
            $this->di->customer_log->where("cid",$id)->delete();
            // 删除客户操作日志
            // $this->di->customer_log->where("cid",$id)->delete();
            // 删除客户跟进记录
            $this->di->follw->where("cid",$id)->delete();
            // 增加删除用户删除日志
            $customer_common->CustomerActionLog($uid,$id,7,'删除客户-永久','彻底删除客户');
        } else {
            $customer_common->CustomerActionLog($uid,$id,7,'删除客户-回收','回收客户');
            $edit_info = $this->di->customer->where("id",$id)->update(array("status"=>0));
        }
        if ($edit_info) {
            // 清楚客户缓存
            $this->redis->set_time('customer_lists',array(),60,'customer');
            $this->redis->set_time('customer_count_arr_'.$uid,array(),60,'customer');
            $rs = array('code'=>1,'msg'=>'000005','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'000035','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 03 回归公海
    public function PostReturnSea($uid,$cid_arr) {
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($cid_arr)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $customer_common = new CustomerCommon();

        if (count($cid_arr) > 1) {
            // 批量
            foreach ($cid_arr as $key => $value) {
                // 客户基本信息
                $customer_info = $this->di->customer->select("id,cname,creatid,charge_person")->where(array("id"=>$value,"status"=>1))->fetchOne();
                if (empty($customer_info)) {
                    continue;
                }
                $charge_person_arr = explode(",",trim($customer_info['charge_person'],","));
                array_shift($charge_person_arr);
                if (count($charge_person_arr) == 0 && $customer_info['creatid'] == $uid) {
                    // 满足扔回公海的条件 -- 创建人并且无其他负责人
                    $throw_sea = $this->di->customer->where(array("id"=>$value,"status"=>1))->update(array("sea_type"=>1));
                    if ($throw_sea) {
                        // 扔回公海成功
                        $sea_success .= $customer_info['cname'].',';
                        // 增加扔回公海操作日志
                        $customer_common->CustomerActionLog($uid,$value,1,'扔回公海','回归公海-扔回公海');
                    } else {
                        // 扔回公海失败
                        $sea_error .= $customer_info['cname'].',';
                    }
                    
                } elseif (in_array($uid,$charge_person_arr)) {
                    // 客户负责人(不包含创建人)，不能扔回公海，只能取消自己共享
                    // (2)更改分享表中数据的状态
                    $share_customer_id = $this->di->share_join->where(array("cid"=>$value,"beshare_uid"=>$uid,"creat_id"=>$customer_info['creatid'],"status"=>1))->fetchOne("bid");
                    
                    if ($share_customer_id) {
                        // (1)去掉客户信息中的负责人
                        $charge_person_str = str_replace(",{$uid},",",",$customer_info['charge_person']);
                        $cancel_share = $this->di->customer->where(array("id"=>$value,"status"=>1))->update(array("charge_person"=>$charge_person_str));
                        // $this->di->share_join->where(array("cid"=>$value,"from_type"=>3,"beshare_uid"=>$uid))->update(array("status"=>0,"updatetime"=>time()));
                        // $this->di->share_customer->where(array("id"=>$share_customer_id))->update(array("status"=>0));
                        $this->di->share_join->where(array("cid"=>$value,"bid"=>$share_customer_id,"beshare_uid"=>$uid))->delete();
                        $this->di->share_customer->where(array("id"=>$share_customer_id))->delete();
                        $this->di->follw->where(array("bid"=>$share_customer_id))->update(array("bid"=>0));

                        // 取消共享成功
                        $cancel_success .= $customer_info['cname'].',';
                        // 增加取消共享操作日志
                        $customer_common->CustomerActionLog($uid,$value,5,'取消共享','回归公海-取消共享');
                    } else {
                        // 取消共享失败
                        $cancel_error .= $customer_info['cname'].',';
                    }
                } else {
                    // 无权扔回公海
                    $sea_error .= $customer_info['cname'].',';
                }
            }

            $msg = "";
            if ($sea_success) {
                $msg .= "客户:".trim($sea_success,",")."成功扔回公海；";
            }
            if ($sea_error) {
                $msg .= "客户:".trim($sea_error,",")."扔回公海失败；";
            }
            if ($cancel_success) {
                $msg .= "您已成功取消客户:".trim($cancel_success,",")."的共享；";
            }
            if ($cancel_error) {
                $msg .= "客户:".trim($cancel_error,",")."扔回公海失败，请取消共享！";
            }
        } else {
            $cid = $cid_arr[0];
            // 客户基本信息
            $customer_info = $this->di->customer->select("id,cname,creatid,charge_person")->where(array("id"=>$cid,"status"=>1))->fetchOne();
            $charge_person_arr = explode(",",trim($customer_info['charge_person'],","));
            // $charge_person_arr = array_shift($charge_person_arr);
            array_shift($charge_person_arr);
            if (count($charge_person_arr) == 0 && $customer_info['creatid'] == $uid) {
                // 满足扔回公海的条件
                $throw_sea = $this->di->customer->where(array("id"=>$cid,"status"=>1))->update(array("sea_type"=>1));
                if ($throw_sea) {
                    // 扔回公海成功
                    $msg =  "客户:".$customer_info['cname']."成功扔回公海！";
                    // 增加扔回公海操作日志
                    $customer_common->CustomerActionLog($uid,$cid,1,'扔回公海','回归公海-扔回公海');
                } else {
                    // 扔回公海失败
                    $msg = "客户:".$customer_info['cname']."扔回公海失败！";
                }
                
            } elseif (in_array($uid,$charge_person_arr)) {
                // 客户负责人(不包含创建人)，不能扔回公海，只能取消自己共享
                // (1)去掉客户信息中的负责人
                $charge_person_str = str_replace(",{$uid},",",",$customer_info['charge_person']);
                $cancel_share = $this->di->customer->where(array("id"=>$cid,"status"=>1))->update(array("charge_person"=>$charge_person_str));
                // (2)更改分享表中数据的状态
                $share_customer_id = $this->di->share_join->where(array("cid"=>$cid,"beshare_uid"=>$uid,"creat_id"=>$customer_info['creatid'],"status"=>1))->fetchOne("bid");
                // $this->di->share_join->where(array("cid"=>$cid,"from_type"=>3,"beshare_uid"=>$uid))->update(array("status"=>0,"updatetime"=>time()));
                // $this->di->share_customer->where(array("id"=>$share_customer_id))->update(array("status"=>0));
                $this->di->share_join->where(array("cid"=>$cid,"bid"=>$share_customer_id,"beshare_uid"=>$uid))->delete();
                $this->di->share_customer->where(array("id"=>$share_customer_id))->delete();
                $this->di->follw->where(array("bid"=>$share_customer_id))->update(array("bid"=>0));

                if ($cancel_share) {
                    // 取消共享成功
                    $msg = "客户:".$customer_info['cname']."成功取消共享！";
                    // 增加取消共享操作日志
                    $customer_common->CustomerActionLog($uid,$cid,5,'取消共享','回归公海-取消共享');
                } else {
                    // 取消共享失败
                    $msg = "客户:".$customer_info['cname']."扔回公海失败，请取消共享！";
                }
            } else {
                // 无权扔回公海
                $sea_error .= $customer_info['cname'].',';
            }
            if ($sea_error) {
                $msg = "客户:".trim($sea_error,",")."扔回公海失败；";
            }
        }
        $this->redis->set_time('customer_count_arr_'.$uid,array(),60,'customer');
        if (empty($msg)) {
            $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>1,'msg'=>$msg,'data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 04 公海列表
    public function GetSeaCustomerList($newData) {
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
        $user_info = $this->di->user->select("is_leader,parent_id,status,structure_id")->where("id",$uid)->fetchOne();

        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }
        $structure_id = json_decode($user_info['structure_id']);

        $params[':status'] = 1;
        $params[':sea_type'] = 1;
        $sea_where = " c.status = :status AND c.sea_type = :sea_type ";
        $sea_where1 = $sea_where." AND l.type = 8 AND to_days(FROM_UNIXTIME(l.addtime,'%Y-%m-%d')) = to_days(now()) ";//今日新增公海
        $sea_where2 = $sea_where." AND l.type = 1 AND to_days(FROM_UNIXTIME(l.addtime,'%Y-%m-%d')) = to_days(now()) ";//今日回归公海
        if ($uid != 1) {
        	$child_str = "";
        	// 判断是否是经理
        	if ($user_info['is_leader'] == 2) {
        		// 查询下面子部门
        		$child_structure_arr = $this->di->structure->where(array("pid"=>$structure_id[0]))->limit(5)->fetchPairs("id","name");
        		$child_structure = array_keys($child_structure_arr);
        		$child_str = implode(",",$child_structure);
        	}
        	// 判断是否有其他所管部门
        	if (!empty($user_info['parent_id'])) {
        		$child_str =  empty($child_str) ? "" : ",".$child_str;
        		$child_str .= $user_info['parent_id'].$child_str;
        	}
        	$user_structure_id = array_pop($structure_id);
            // 查看是否部门共享
            $share_structure = $this->di->structure->where(array("id"=>$user_structure_id))->fetchOne("share");
            if (!empty($share_structure)) {
                # 部门共享数据
                $share_structure .= ",".$user_structure_id;
                if (!empty($child_str)) $share_structure .= ",".$child_str;
                $share_structure = trim($share_structure,",");
                $see_where = " AND FIND_IN_SET(cd.groupid, '{$share_structure}') ";
            } else {
                # 普通用户--只允许看本部门的公海数据
                if (!empty($child_str)) {
                	$child_str .= ",".$user_structure_id;
                	$see_where = " AND FIND_IN_SET(cd.groupid, '{$child_str}') ";
                } else {
                	$see_where = " AND cd.groupid = {$user_structure_id} ";
                }
            }
        } else {
            $see_where = "";
        }
        $total_arr = $this->redis->get_time('customer_sea_count','customer');
        if (empty($keywords) || empty($total_arr)) {
            # code...
            # // 计算数量
            //（1）全部
            $gh_count_sql = "SELECT count(c.id) as num FROM ".$this->prefix."customer c LEFT JOIN ".$this->prefix."customer_data cd ON c.id = cd.cid WHERE ".$sea_where.$see_where;
            $customer_gh_num = $this->di->customer->queryAll($gh_count_sql,$params);
            $customer_gh_total = $customer_gh_num[0]['num'];
            // (2)今日新增公海
            $xzgh_count_sql = "SELECT count(cid) as num FROM ( SELECT l.cid FROM ".$this->prefix."customer_log l LEFT JOIN ".$this->prefix."customer c ON l.cid=c.id LEFT JOIN ".$this->prefix."customer_data cd ON c.id = cd.cid WHERE ".$sea_where1.$see_where." GROUP BY l.cid ) temp ";
            $customer_xzgh_num = $this->di->customer_log->queryAll($xzgh_count_sql,$params);
            $customer_xzgh_total = $customer_xzgh_num[0]['num'];
            // (3)今日回归公海
            $hggh_count_sql = "SELECT count(cid) as num FROM ( SELECT l.cid FROM ".$this->prefix."customer_log l LEFT JOIN ".$this->prefix."customer c ON l.cid=c.id LEFT JOIN ".$this->prefix."customer_data cd ON c.id = cd.cid WHERE ".$sea_where2.$see_where." GROUP BY l.cid ) temp ";
            $customer_hggh_num = $this->di->customer_log->queryAll($hggh_count_sql,$params);
            $customer_hggh_total = $customer_hggh_num[0]['num'];

            $total_data['customer_gh_total'] = $customer_gh_total;//全部公海客户
            $total_data['customer_xzgh_total'] = $customer_xzgh_total;//今日新增公海客户
            $total_data['customer_hggh_total'] = $customer_hggh_total;//今日回归公海客户
            $total_arr = $total_data;
            $this->redis->set_time('customer_sea_count',$total_data,6000,'customer');
        }

        // 关键词搜索
        if (!empty($keywords)) {
            $keywords_where = " AND concat(c.cname,cd.cphone,cd.cphonetwo,cd.cphonethree) LIKE '%{$keywords}%' ";
        } else {
            $keywords_where = "";
        }
        // var_dump($keywords_where);
        // 高级搜索
        if (!empty($where_arr)) {
            $table_model = $this->config->get('common.TABLE_FIELD');
            $model_field = $table_model['customer']['data_field'];
            $data_field_arr = array_keys($model_field);
            $data_field_arr = array_merge($data_field_arr,array('zdnum','groupid','formwhere','formwhere2','formwhere3','agent_name','agent_num','project_name','project_num','agent_price','paid','obligations','flag','create_time','ocreatid'));
            foreach ($where_arr as $key => $value) {
                if (is_array($value)) {
                    if ($value[0] == "" || $value[1] == "") {
                        continue;
                    }
                }
                if (!empty($value) || $value == 0) {
                    $key = in_array($key,$data_field_arr) ? 'cd.'.$key : 'c.'.$key;
                    # 其他
                    if (is_array($value) && is_numeric($value[0])) {
                        $see_where .= " AND ( {$key} BETWEEN {$value[0]} AND {$value[1]} ) ";
                    } elseif (is_array($value)) {
                        $see_where .= " AND ( UNIX_TIMESTAMP({$key}) between '{$value[0]}' AND '{$value[1]}' ) ";
                    } elseif (is_numeric($value)) {
                        if ($key == 'c.charge_person') {
                            $see_where .= " AND FIND_IN_SET('{$value}', charge_person) ";
                        } elseif (in_array($key,array("cd.cphone","cd.cphonetwo","cd.cphonethree","cd.wxnum","cd.cemail"))) {
                            $see_where .= " AND {$key} LIKE '{$value}%' ";
                        } else {
                            $see_where .= " AND {$key} = {$value} ";
                        }
                    } else {
                        $see_where .= " AND {$key} LIKE '%{$value}%' ";
                    }
                }
            }
        }

        // 排序
        if (empty($order_by) || $order_by[1] == 0) {
            $order_str = " c.is_top DESC,cd.id DESC ";
        } else {
            $order_key = $order_by[0] == "create_time" ? "cd." : "c.";
            $order_field = $order_key.$order_by[0];
            $order_type = $order_by[1] == 1 ? "DESC" : "ASC";
            $order_str = " {$order_field} {$order_type} ";
        }
        $search_field = "c.id,c.cname,c.intentionally,c.ittnxl,c.ittnzy,c.labelpeer,c.ittnyx,c.ittnxm,c.ittngj,c.budget,c.timeline,c.graduate,c.graduatezy,c.xuel,c.tolink,c.note,c.creatid,c.is_top,c.charge_person,c.follow_up,c.follow_person,c.follw_time,c.next_follow,c.xueshuchengji,c.yuyanchengji,c.hzID,c.sort,cd.cid,cd.zdnum,cd.own,cd.occupation,cd.groupid,cd.sex,cd.age,cd.station,cd.industry,cd.experience,cd.company,cd.scale,cd.character,cd.cphone,cd.cphonetwo,cd.cphonethree,cd.telephone,cd.formwhere,cd.formwhere2,cd.formwhere3,cd.wxnum,cd.cemail,cd.qq,cd.agent_name,cd.create_time";
        switch ($type) {
            case 1:
                # 今日新增公海
                $sql = "SELECT ".$search_field." FROM ".$this->prefix."customer c LEFT JOIN ".$this->prefix."customer_log l ON l.cid=c.id LEFT JOIN ".$this->prefix."customer_data cd ON c.id = cd.cid WHERE ".$sea_where1.$see_where.$keywords_where." GROUP BY l.cid ORDER BY ".$order_str." LIMIT ".$pagenum.",".$pagesize;
                $count_sql = "SELECT count(cid) as num FROM ( SELECT l.cid FROM ".$this->prefix."customer_log l LEFT JOIN ".$this->prefix."customer c ON l.cid=c.id LEFT JOIN ".$this->prefix."customer_data cd ON c.id = cd.cid WHERE ".$sea_where1.$see_where.$keywords_where." GROUP BY l.cid ) temp ";
                break;
            case 2:
                # 今日回归公海
                 $sql = "SELECT ".$search_field." FROM ".$this->prefix."customer c LEFT JOIN ".$this->prefix."customer_log l ON l.cid=c.id LEFT JOIN ".$this->prefix."customer_data cd ON c.id = cd.cid WHERE ".$sea_where2.$see_where.$keywords_where." GROUP BY l.cid ORDER BY ".$order_str." LIMIT ".$pagenum.",".$pagesize;
                $count_sql = "SELECT count(cid) as num FROM ( SELECT l.cid FROM ".$this->prefix."customer_log l LEFT JOIN ".$this->prefix."customer c ON l.cid=c.id LEFT JOIN ".$this->prefix."customer_data cd ON c.id = cd.cid WHERE ".$sea_where2.$see_where.$keywords_where." GROUP BY l.cid ) temp ";
                break;
            default:
                # 全部公海
                $sql = "SELECT ".$search_field." FROM ".$this->prefix."customer c LEFT JOIN ".$this->prefix."customer_data cd ON c.id = cd.cid WHERE ".$sea_where.$see_where.$keywords_where." ORDER BY ".$order_str." LIMIT ".$pagenum.",".$pagesize;
                $count_sql = "SELECT count(c.id) as num FROM ".$this->prefix."customer c LEFT JOIN ".$this->prefix."customer_data cd ON c.id = cd.cid WHERE ".$sea_where.$see_where.$keywords_where;
                break;
        }
        // var_dump($sql);die;

        // 公海客户列表数据
        $customer_gh_list = $this->di->customer->queryAll($sql, $params);
        $admin_common = new AdminCommon();
        $customer_gh_num = $this->di->customer->queryAll($count_sql,$params);
        if (!empty($customer_gh_list)) {
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
                    $username = $this->di->user->where(array("id"=>$v))->fetchOne("realname");
                    $charge_person_str .= $username.",";
                }
                $customer_gh_list[$key]['charge_person'] = trim($charge_person_str,",");
                // 创建人
                $username = $this->di->user->where("id",$value['creatid'])->fetchOne("realname");
                $customer_gh_list[$key]['publisher'] = $username;
            }
        }
        $customer_gh_data['customer_gh_list'] = !empty($customer_gh_list) ? $customer_gh_list : array();
        $customer_gh_data['customer_gh_num'] = !empty($customer_gh_num[0]['num']) ? $customer_gh_num[0]['num'] : 0;
        $customer_gh_data['total_count'] = !empty($total_arr) ? $total_arr : array();

        $rs = array('code'=>1,'msg'=>'000000','data'=>$customer_gh_data,'info'=>$customer_gh_data);
        return $rs;
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
                        $err_msg[] = "客户：".$customer_info['cname']."共享失败，".$share_user_info['realname']."已是该客户负责人！";
                        continue;
                    }
                    // 判断是否超过共享次数
                    if (count($charge_person_arr) >= $max_share_num) {
                        $err_msg[] = "客户：".$customer_info['cname']."共享失败，已超过共享次数；";
                        continue;
                    }

                    // 12-9分享增加被分享人部门id
                    
                    // 被分享人部门
                    $structure_id = json_decode($share_user_info['structure_id']);
                    $user_structure_id = array_pop($structure_id);
                    
                    // 4-3 增加共享数据新逻辑
                    $customer_data = $this->di->customer_data->select("groupid,cphone")->where(array("cid"=>$value))->fetchOne();
                    if (!empty($customer_data['cphone'])) {
                        $field_key = "cphone";
                    } else {
                        $field_key = "wxnum";
                    }
                    // 客户所属部门
                    // 判断是否是共享给同部门
                    if ($user_structure_id == $customer_data['groupid']) {
                        $err_msg[] = "客户：".$customer_info['cname']."共享失败，不允许部门内部人员共享；";
                        continue;
                    }

                    // 不同部门
                    // （1）是否已存在共享过的数据
                    $share_group = $this->di->share_join->where(array("cid"=>$value))->fetchPairs("id","groupid");
                    if (!empty($share_group) && in_array($user_structure_id, $share_group)) {
                        $err_msg[] = "客户：".$customer_info['cname']."共享失败，".$share_user_info['realname']."的部门下已存在分享数据！";
                        continue;
                    }
                    // （2）被分享人部门下已存在该手机号数据
                    $customer_datas = $this->di->customer_data->where(array($field_key=>$customer_data[$field_key],"groupid"=>$user_structure_id))->fetchOne("cid");
                    if (!empty($customer_datas)) {
                        $err_msg[] = "客户：".$customer_info['cname']."共享失败，".$share_user_info['realname']."的部门下已存在相同手机号数据！";
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
                $customer_data = $this->di->customer_data->select("groupid,cphone,wxnum")->where(array("cid"=>$cid))->fetchOne();
                if (!empty($customer_data['cphone'])) {
                    $field_key = "cphone";
                } else {
                    $field_key = "wxnum";
                }
                $share_group = $this->di->share_join->where(array("cid"=>$cid))->fetchPairs("id","groupid");

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

                        // 12-9分享增加被分享人部门id
                        $share_user_info = $this->di->user->select("structure_id,realname")->where(array("id"=>$value,"status"=>1))->fetchOne();
                        $structure_id = json_decode($share_user_info['structure_id']);
                        $user_structure_id = array_pop($structure_id);

                        // 4-3 增加共享数据新逻辑
                        // 判断是否是共享给同部门
                        if ($user_structure_id == $customer_data['groupid']) {
                            $err_msg[] = "客户：".$customer_info['cname']."共享失败，不允许部门内部人员共享；";
                            continue;
                        }

                        // 不同部门
                        // （1）是否已存在共享过的数据
                        if (!empty($share_group) && in_array($user_structure_id, $share_group)) {
                            $err_msg[] = "客户：".$customer_info['cname']."共享失败，".$share_user_info['realname']."的部门下已存在分享数据！";
                            continue;
                        }

                        // （2）被分享人部门下已存在该手机号数据
                        $customer_datas = $this->di->customer_data->where(array($field_key=>$customer_data[$field_key],"groupid"=>$user_structure_id))->fetchOne("cid");
                        if (!empty($customer_datas)) {
                            $err_msg[] = "客户：".$customer_info['cname']."共享失败，".$share_user_info['realname']."的部门下已存在相同手机号数据！";
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
                        $rs = array('code'=>1,'msg'=>'000054','data'=>$err_msg,'info'=>$err_msg);
                    }
                } else {
                    $rs = array('code'=>1,'msg'=>'000055','data'=>array(),'info'=>array());
                }
                break;
        }
        $this->redis->set_time('customer_count_arr_'.$uid,array(),60,'customer');

        return $rs;
    }

    // 06 共享客户人员列表
    public function GetShareCustomerPerson($newData) {
        $type = isset($newData['type'])  ? $newData['type'] : 0;
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $cid = isset($newData['cid']) && !empty($newData['cid']) ? intval($newData['cid']) : 0;

        if (empty($cid) || empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        $customer_info = $this->di->customer->select("id,charge_person,creatid")->where(array("id"=>$cid,"status"=>1))->fetchOne();
        if (empty($customer_info)) {
            $rs = array('code'=>0,'msg'=>'000050','data'=>array(),'info'=>array());
            return $rs;
        }
        
        $charge_person_arr = explode(",",trim($customer_info['charge_person'],","));
        array_shift($charge_person_arr);
        // 判断用户角色
        switch ($type) {
            case 1:
                # 领导
                // 查询领导所管辖部门
                $user_info = $this->di->user->select("structure_id,is_leader,parent_id")->where("id",$uid)->fetchOne();
                $structure_id = json_decode($user_info['structure_id']);
                $admin_common = new AdminCommon;
                $user_structure_str = $admin_common->GetMyStructure($user_info['is_leader'],$structure_id,$user_info['parent_id']);
                $user_structure_arr = explode(",", $user_structure_str);
                // var_dump($user_structure_arr);die;
                $data['role'] = 3;
                break;
            
            default:
                # 其他
                if ($uid == $customer_info['creatid']) {
                    # 创建人
                    $data['role'] = 1;
                } elseif (in_array($uid,$charge_person_arr)) {
                    # 负责人
                    $data['role'] = 2;
                } else {
                    # 无权
                    $data['role'] = 0;
                }
                break;
        }

        $user_list = array();
        if (!empty($charge_person_arr)) {
            foreach ($charge_person_arr as $key => $value) {
                $share_user = $this->di->user->select("realname,structure_id")->where("id",$value)->fetchOne();
                if (!empty($user_structure_arr)) {
                    $share_user_structure = json_decode($share_user['structure_id']);
                    $share_structure_id = array_pop($share_user_structure);
                    if (!in_array($share_structure_id,$user_structure_arr)) {
                        # 非自己部门人员
                        $user_list[$key]['is_click'] = 0;
                    } else {
                        $user_list[$key]['is_click'] = 1;
                    }
                }
                $user_list[$key]['user_name'] = $share_user['realname'];
                $user_list[$key]['uid'] = $value;
            }
            $data['user_list'] = $user_list;
        }

        $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>$data);
        return $rs;
    }

    // 07 取消共享
    public function PostCancleShare($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $cid = isset($newData['cid']) && !empty($newData['cid']) ? $newData['cid'] : 0;

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($cid)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $customer_common = new CustomerCommon();
        // 客户基本信息
        $customer_info = $this->di->customer->select("cname,charge_person,creatid")->where(array("id"=>$cid,"status"=>1))->fetchOne();
        if (empty($customer_info)) {
            $rs = array('code'=>0,'msg'=>'000050','data'=>array(),'info'=>array());
            return $rs;
        }
        $charge_person_arr = explode(",",trim($customer_info['charge_person'],","));
        array_shift($charge_person_arr);
        // 只有负责人能取消共享
        if ($uid == $customer_info['creatid']) {
            $rs = array('code'=>0,'msg'=>'000057','data'=>array(),'info'=>array());
            return $rs;
        }
        
        if (in_array($uid, $charge_person_arr)) {
            // Step 1: 开启事务
            $this->di->beginTransaction('db_master');
            // (1)更改share_join
            
            // $share_bid = $this->di->share_join->where(array("beshare_uid"=>$uid,"cid"=>$cid,"from_type"=>3,"creat_id"=>$customer_info['creatid']))->fetchOne("bid");
            $share_bid = $this->di->share_join->where(array("beshare_uid"=>$uid,"cid"=>$cid,"creat_id"=>$customer_info['creatid']))->fetchOne("bid");
            $share_update = $this->di->share_join->where(array("bid"=>$share_bid))->delete();
            // $share_update = $this->di->share_join->where(array("beshare_uid"=>$uid,"cid"=>$cid,"from_type"=>3,"creat_id"=>$customer_info['creatid']))->update(array("status"=>0,"updatetime"=>time()));
            // (2)share_customer状态
            // $share_customer_update = $this->di->share_customer->where(array("id"=>$share_bid,"status"=>1))->update(array("status"=>0,"next_follow"=>0));
            $share_customer_update = $this->di->share_customer->where(array("id"=>$share_bid))->delete();
            $this->di->follw->where(array("bid"=>$share_bid))->update(array("bid"=>0));
            
            if ($share_update && $share_customer_update) {
                // 提交事务
                $this->di->commit('db_master');
                $charge_person_str = $uid.',';
            } else {
                // 回滚事务
                $this->di->rollback('db_master');
            }
            if ($charge_person_str) {
                // (3)更新客户分享表负责人
                // $customer_update['charge_person'] = str_replace($charge_person_str,"",$customer_info['charge_person']);
                $customer_update['charge_person'] = str_replace(",{$uid},",",",$customer_info['charge_person']);
                $this->di->customer->where("id",$cid)->update($customer_update);
                // (4)增加操作日志
                $note = "取消客户：".$customer_info['cname']."的共享！";
                $customer_common->CustomerActionLog($uid,$cid,5,$note,$note);

                $rs = array('code'=>1,'msg'=>'000058','data'=>array(),'info'=>array());
            } else {
                $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
            }
            $this->redis->set_time('customer_count_arr_'.$uid,array(),60,'customer');
        } else {
            $rs = array('code'=>0,'msg'=>'000057','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 07-1 新取消共享
    public function PostCancleShareNew($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $cid = isset($newData['cid']) && !empty($newData['cid']) ? $newData['cid'] : 0;
        $cancle_uid = isset($newData['cancle_uid']) && !empty($newData['cancle_uid']) ? $newData['cancle_uid'] : array();
        if (empty($uid) || empty($cid) || empty($cancle_uid)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $user_info = $this->di->user->select("realname,structure_id,is_leader,parent_id")->where(array('id'=>$uid))->fetchOne();
        $customer_info = $this->di->customer->select("cname,charge_person,creatid")->where(array("id"=>$cid,"status"=>1))->fetchOne();
        $creatid_str = $customer_info['creatid'].',';
        if (empty($customer_info) || empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000050','data'=>array(),'info'=>array());
            return $rs;
        }
        $customer_common = new CustomerCommon();
        $admin_common = new AdminCommon();
        $charge_person_arr = explode(",",trim($customer_info['charge_person'],","));
        array_shift($charge_person_arr);

        if (in_array($uid,$charge_person_arr) && $uid == $cancle_uid[0]) {
            // Step 1: 开启事务
            $this->di->beginTransaction('db_master');
            # 只能取消自己的共享
            $share_bid = $this->di->share_join->where(array("beshare_uid"=>$uid,"cid"=>$cid,"creat_id"=>$customer_info['creatid']))->fetchOne("bid");
            $share_update = $this->di->share_join->where(array("bid"=>$share_bid))->delete();
            $share_customer_update = $this->di->share_customer->where(array("id"=>$share_bid))->delete();
            $this->di->follw->where(array("bid"=>$share_bid))->update(array("bid"=>0));
            if ($share_update && $share_customer_update) {
                // 提交事务
                $this->di->commit('db_master');
                $charge_person_str = ','.$uid.',';
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
                // 客户私海数量减一
                \App\SetLimit($uid,-1);
                // (5)发送消息，给创建人发送消息
                $msg_title = "您的客户".$customer_info['cname']."被".$user_info['realname']."取消共享";
                $msg_content = "您的客户".$customer_info['cname']."被".$user_info['realname']."取消共享";
                $admin_common->SendMsgNow($uid,$customer_info['creatid'],$msg_title,$msg_content,0,"cancle","customer",$cid);
                $rs = array('code'=>1,'msg'=>'000058','data'=>array(),'info'=>array());
            } else {
                // 回滚事务
                $this->di->rollback('db_master');
                $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
            }
        } elseif ($uid == $customer_info['creatid']) {
            # 创建人 -- 允许取消其他人共享
            foreach ($cancle_uid as $key => $value) {
                if (!in_array($value,$charge_person_arr)) {
                    // 非负责人
                    continue;
                }
                // Step 1: 开启事务
                $this->di->beginTransaction('db_master');
                $share_user_info = $this->di->user->select("realname")->where(array('id'=>$value))->fetchOne();
                $share_bid = $this->di->share_join->where(array("beshare_uid"=>$value,"share_uid"=>$customer_info['creatid'],"cid"=>$cid,"creat_id"=>$customer_info['creatid']))->fetchOne("bid");

                $share_update = $this->di->share_join->where(array("bid"=>$share_bid))->delete();
                $share_customer_update = $this->di->share_customer->where(array("id"=>$share_bid))->delete();
                $this->di->follw->where(array("bid"=>$share_bid))->update(array("bid"=>0));
                if ($share_update && $share_customer_update) {
                    // 提交事务
                    $this->di->commit('db_master');
                    $charge_person_str = ','.$value.',';
                    // (3)更新客户分享表负责人
                    $customer_update['charge_person'] = str_replace($charge_person_str,",",$customer_info['charge_person']);
                    if ($customer_update['charge_person'] == $creatid_str) {
                        // 如果负责人只剩下创建人自己
                        $customer_update['myshare'] = 0;
                    }
                    // 更新customer_info
                    $customer_info['charge_person'] = $customer_update['charge_person'];
                    $this->di->customer->where("id",$cid)->update($customer_update);

                    $cancle_user_str .=  $share_user_info['realname'].',';
                    // 客户私海数量减一
                    \App\SetLimit($value,-1);
                    // (5)发送消息，给负责人发送消息
                    $msg_title = "您负责的客户".$customer_info['cname']."被".$user_info['realname']."取消共享";
                    $msg_content = "您负责的客户".$customer_info['cname']."被".$user_info['realname']."取消共享";
                    $admin_common->SendMsgNow($uid,$value,$msg_title,$msg_content,0,"cancle","customer",$cid);
                    $success_msg[] = "您成功取消客户：".$customer_info['cname']."的负责人".$share_user_info['realname']."的共享！";
                } else {
                    // 回滚事务
                    $this->di->rollback('db_master');
                    $err_msg[] = "您取消客户：".$customer_info['cname']."的负责人".$share_user_info['realname']."的共享，操作失败！";
                    continue;
                }
            }

            if ($success_msg) {
                // (4)增加操作日志
                $note = $user_info['realname']."取消客户：".$customer_info['cname']."负责人".$cancle_user_str."的共享！";
                $customer_common->CustomerActionLog($uid,$cid,5,$note,$note);
                if(!empty($err_msg)) $success_msg = array_merge($success_msg,$err_msg);
                $rs = array('code'=>1,'msg'=>'000058','data'=>$success_msg,'info'=>$success_msg);
            } else {
                $rs = array('code'=>0,'msg'=>'000054','data'=>$err_msg,'info'=>$err_msg);
            }
        } elseif ($user_info['is_leader'] != 0 || $uid == 1) {
            if ($uid != 1) {
                $structure_id = json_decode($user_info['structure_id']);
                $user_structure_str = "";
                $user_structure_str = $admin_common->GetMyStructure($user_info['is_leader'],$structure_id,$user_info['parent_id']);
                $user_structure_arr = explode(",", $user_structure_str);
            }
            
            # 部门领导人 -- 允许取消自己员工
            foreach ($cancle_uid as $key => $value) {
                if ($uid != 1) {
                    # 判断是否为自己部门员工
                    $share_user_info = $this->di->user->select("realname,structure_id")->where(array('id'=>$value))->fetchOne();
                    $share_user_structure = json_decode($share_user_info['structure_id']);
                    $share_structure_id = array_pop($share_user_structure);
                    if (!in_array($share_structure_id,$user_structure_arr)) {
                        # 非自己部门人员
                        $err_msg[] = $share_user_info.'非本部门人员，无法取消该人员共享！';
                        continue;
                    }
                }
                // Step 1: 开启事务
                $this->di->beginTransaction('db_master');
                $share_bid = $this->di->share_join->where(array("beshare_uid"=>$value,"cid"=>$cid,"creat_id"=>$customer_info['creatid']))->fetchOne("bid");
                $share_update = $this->di->share_join->where(array("bid"=>$share_bid))->delete();
                $share_customer_update = $this->di->share_customer->where(array("id"=>$share_bid))->delete();
                $this->di->follw->where(array("bid"=>$share_bid))->update(array("bid"=>0));
                if ($share_update && $share_customer_update) {
                    // 提交事务
                    $this->di->commit('db_master');
                    $charge_person_str = ','.$value.',';
                    // (3)更新客户分享表负责人
                    $customer_update['charge_person'] = str_replace($charge_person_str,",",$customer_info['charge_person']);
                    if ($customer_update['charge_person'] == $creatid_str) {
                        // 如果负责人只剩下创建人自己
                        $customer_update['myshare'] = 0;
                    }
                    // 更新customer_info
                    $customer_info['charge_person'] = $customer_update['charge_person'];
                    $this->di->customer->where("id",$cid)->update($customer_update);

                    $cancle_user_str .=  $share_user_info['realname'].',';
                    // 客户私海数量减一
                    \App\SetLimit($value,-1);
                    // (5)发送消息，给负责人发送消息
                    $msg_title = "您负责的客户".$customer_info['cname']."被".$user_info['realname']."取消共享";
                    $msg_content = "您负责的客户".$customer_info['cname']."被".$user_info['realname']."取消共享";
                    $admin_common->SendMsgNow($uid,$value,$msg_title,$msg_content,0,"cancle","customer",$cid);

                    $success_msg[] = "您成功取消客户：".$customer_info['cname']."的负责人".$share_user_info['realname']."的共享！";
                } else {
                    // 回滚事务
                    $this->di->rollback('db_master');
                    $err_msg[] = "您取消客户：".$customer_info['cname']."的负责人".$share_user_info['realname']."的共享，操作失败！";
                    continue;
                }
            }

            if ($success_msg) {
                // 给客户创建人发送消息
                $msg_title = "您的客户".$customer_info['cname']."被取消共享";
                $msg_content = $user_info['realname']."取消客户：".$customer_info['cname']."负责人：".$cancle_user_str."的共享！";
                $admin_common->SendMsgNow($uid,$customer_info['creatid'],$msg_title,$msg_content,0,"cancle","customer",$cid);

                // (4)增加操作日志
                $note = $user_info['realname']."取消客户：".$customer_info['cname']."负责人".$cancle_user_str."的共享！";
                $customer_common->CustomerActionLog($uid,$cid,5,$note,$note);
                if(!empty($err_msg)) $success_msg = array_merge($success_msg,$err_msg);
                $rs = array('code'=>1,'msg'=>'000058','data'=>$success_msg,'info'=>$success_msg);
            } else {
                $rs = array('code'=>0,'msg'=>'000054','data'=>$err_msg,'info'=>$err_msg);
            }
        } else {
            # 无权操作
            $rs = array('code'=>0,'msg'=>'无权操作','data'=>array(),'info'=>array());
        }
        $this->redis->set_time('customer_count_arr_'.$uid,array(),60,'customer');
        return $rs;
    }

    // 08 分配客户
    public function PostDistribute($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $beshare_uid = isset($newData['beshare_uid']) && !empty($newData['beshare_uid']) ? intval($newData['beshare_uid']) : 0;
        $cid_arr = isset($newData['cid_arr']) && !empty($newData['cid_arr']) ? $newData['cid_arr'] : array();
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($cid_arr) || empty($beshare_uid)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        // $user_info = $this->di->user->select("is_leader,structure_id")->where("id",$uid)->fetchOne();
        $user_info = \App\Getkey($uid,array("is_leader","structure_id"));
        // 判断是否是主管
        if ($uid != 1 && $user_info['is_leader'] == 0) {
            $rs = array('code'=>0,'msg'=>'000059','data'=>array(),'info'=>array());
            return $rs;
        }
        
        $customer_common = new CustomerCommon();
        $data = $customer_common->CustomerDistributeAction($cid_arr,$uid,$beshare_uid,2,1,0);
        if ($data['success_msg']) {
            $msg = '成功分配客户：'.trim($data['success_msg'],",")."！";
            if (!empty($data['err_msg'])) {
                array_unshift($data['err_msg'], $msg);
            } else {
                $data['err_msg'][] = $msg;
            }
            $this->redis->set_time('customer_count_arr_'.$uid,array(),60,'customer');
            $rs = array('code'=>1,'msg'=>$msg,'data'=>$data['err_msg'],'info'=>$data['err_msg']);
        } else {
            $rs = array('code'=>1,'msg'=>'000054','data'=>$data['err_msg'],'info'=>$data['err_msg']);
        }
        return $rs;
    }

    // 09 领取客户
    public function PostReceive($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $type = isset($newData['type']) && !empty($newData['type']) ? intval($newData['type']) : 0;
        $cid_arr = isset($newData['cid_arr']) && !empty($newData['cid_arr']) ? $newData['cid_arr'] : array();
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        if (empty($cid_arr)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        $if_allow = $this->di->admin_config->where(array("title"=>"ALLOW_RECEIVE"))->fetchOne('value');
        if (intval($if_allow) == 0) {
            $rs = array('code'=>0,'msg'=>'000064','data'=>array(),'info'=>array());
            return $rs;
        }
        $customer_common = new CustomerCommon();
        $data = $customer_common->CustomerDistributeAction($cid_arr,$uid,$uid,3,2,$type);
        if ($data['success_msg']) {
            $msg = '成功领取客户：'.trim($data['success_msg'],",")."！";
            if (!empty($data['err_msg'])) {
                array_unshift($data['err_msg'], $msg);
            } else {
                $data['err_msg'][] = $msg;
            }
            $this->redis->set_time('customer_count_arr_'.$uid,array(),60,'customer');
            $rs = array('code'=>1,'msg'=>$msg,'data'=>$data['err_msg'],'info'=>$data['err_msg']);
        } else {
            $rs = array('code'=>1,'msg'=>'000054','data'=>$data['err_msg'],'info'=>$data['err_msg']);
        }
        return $rs;
    }

    // 10 分配/领取客户列表
    public function GetMySeaCustomerList($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $type = isset($newData['type']) ? intval($newData['type']) : 0;
        $keywords = isset($newData['keywords']) ? $newData['keywords'] : '';//搜索关键词(怎么处理？？)
        $pageno = isset($newData['pageno']) ? intval($newData['pageno']) : 1;
        $pagesize = isset($newData['pagesize']) ? intval($newData['pagesize']) : $this->pagesize;
        $pagenum = ($pageno-1)*$pagesize;

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        // 判断用户角色(如果为部门领导则显示整个部门客户,如果为admin显示所有的客户)
        $user_info = $this->di->user->select("is_leader,status,structure_id")->where("id",$uid)->fetchOne();

        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
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

        $sea_where = " s.status = :status AND from_type = {$from_type} AND s.sea_type = 0 AND {$field} = {$uid} ";

        if (!empty($keywords)) {
            # 关键词搜索
            $sea_where .= " AND c.cname LIKE '{$keywords}%' ";
        }
        
        $share_sql = "SELECT s.cid,s.bid,s.creat_id,s.addtime FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."customer c ON s.cid = c.id WHERE ".$sea_where." ORDER BY c.is_top DESC,c.sort DESC,s.addtime DESC LIMIT ".$pagenum.",".$pagesize;
        $customer_list = $this->di->share_join->queryAll($share_sql,$params);

        if (empty($customer_list)) {
            $rs = array('code'=>1,'msg'=>'000002','data'=>array(),'info'=>array());
            return $rs;
        }
        $list = array();
        foreach ($customer_list as $key => $value) {
            if ($value['creat_id'] == $uid) {
                # 为客户创建人
                $table_name = "customer";
                $c_where = "c.id = {$value['cid']}";
            } else {
                # 负责人
                $table_name = "share_customer";
                $c_where = "c.id = {$value['bid']}";
            }
            $sql = "SELECT c.is_top,c.follow_up,c.cname,c.labelpeer,cd.sex,cd.cphone,cd.wxnum,c.charge_person,c.follw_time,c.intentionally,c.ittnxl,c.timeline,c.xuel,c.sort FROM ".$this->prefix.$table_name." c LEFT JOIN ".$this->prefix."customer_data cd ON c.id = cd.cid WHERE ".$c_where;
            $customer_info = $this->di->customer->queryAll($sql,array());
            if (!empty($customer_info[0])) {
                $list[$key] = array_merge($customer_list[$key],$customer_info[0]);
            }
        }
        $share_count_sql = "SELECT count(s.id) as num FROM ".$this->prefix."share_join s LEFT JOIN ".$this->prefix."customer c ON s.cid = c.id WHERE ".$sea_where;
        $count_arr = $this->di->share_join->queryAll($share_count_sql,$params);

        $data['data_list'] = $list;
        $data['data_num'] = $count_arr[0]['num'];
        $rs = array('code'=>1,'msg'=>'000000','data'=>$data,'info'=>$data);
        return $rs;
    }

    // 11 客户成交新增
    public function PostAddCustomerDeal($newData,$field_list,$type,$agent_id) {
        $cid = isset($newData['cid']) && !empty($newData['cid']) ? intval($newData['cid']) : 0;
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $deal_id = isset($newData['deal_id']) && !empty($newData['deal_id']) ? intval($newData['deal_id']) : 0;
        $structure_id = isset($newData['structure_id']) && !empty($newData['structure_id']) ? intval($newData['structure_id']) : 0;
        $general_id = isset($newData['general_id']) && !empty($newData['general_id']) ? intval($newData['general_id']) : 0;

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($cid) || empty($deal_id) || empty($structure_id) || empty($general_id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        $user_info = $this->di->user->select("realname")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000062','data'=>array(),'info'=>array());
            return $rs;
        }

        $customer_deal_info = $this->di->customer_order->where(array("cid"=>$cid,"general_id"=>$general_id))->fetchOne();
        if (!empty($customer_deal_info)) {
            $rs = array('code'=>0,'msg'=>'000063','data'=>array(),'info'=>array());
            return $rs;
        }
        $general_name = $this->di->general_rules->where("id",$general_id)->fetchOne("title");
        $admin_common = new AdminCommon();
        $newData['order_no'] = \App\CreateOrderNo();

        if ($type == 1) {
            # 代理商
            $newData['agent_id'] = $agent_id;
        } elseif ($type == 2) {
            # 项目方
            $newData['project_id'] = $agent_id;
        } else {

        }
        $newData['title'] = $general_name;
        $newData['status'] = 1;
        $newData['publisher'] = $user_info['realname'];
        $newData['addtime'] = time();
        $newData['updatetime'] = time();
        $deal_data = $admin_common->GetFieldListArr($newData,$field_list);
        $deal_id = $this->di->customer_order->insert($deal_data);

        // 更新客户的代理[项目方]费用
        $customer_data_info = $this->di->customer_data->select("agent_name,agent_num,project_name,project_num")->where(array("cid"=>$cid))->fetchOne();
        if ($type == 1) {
            // 查询代理费用
            $agent_money = $this->di->agent_general->where(array("agent_id"=>$agent_id,"general_id"=>$general_id))->fetchOne("agent_money");
            $res_agent = $this->di->customer_data->where(array("cid"=>$cid))->update(array("agent_price"=>$agent_money));
            $agent_info = $this->di->agent->select("flower_name,creatid")->where(array("id"=>$agent_id))->fetchOne();
            // 推送消息
            $msg_title = "您的代理商".$agent_info['flower_name']."有学员已成交！";
            $msg_content = "您的代理商".$agent_info['flower_name']."有学员已成交，请及时处理！";
            $admin_common->SendMsgNow($uid,$agent_info['creatid'],$msg_title,$msg_content,0,"deal","agent",$agent_id);
        }

        if ($type == 2) {
            // 查询项目方代理费用
            $agent_money = $this->di->project_general->where(array("project_id"=>$agent_id,"general_id"=>$general_id))->fetchOne("agent_money");
            $res_agent = $this->di->customer_data->where(array("cid"=>$cid))->update(array("agent_price"=>$agent_money));
            
            $project_side_info = $this->di->project_side->select("flower_name,creatid")->where(array("id"=>$agent_id))->fetchOne();

            // 推送消息
            $msg_title = "您的项目方".$project_side_info['flower_name']."有学员已成交！";
            $msg_content = "您的项目方".$project_side_info['flower_name']."有学员已成交，请及时处理！";
            $admin_common->SendMsgNow($uid,$project_side_info['creatid'],$msg_title,$msg_content,0,"deal","project_side",$agent_id);

        }

        if ($deal_id) {
            $this->redis->flushDB('customer');
            $this->redis->set_time('project_list_'.$uid,'',60,'project');
            $rs = array('code'=>1,'msg'=>'000000','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'000054','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 12 客户成交编辑
    public function PostEditCustomerDeal($id,$newData,$field_list) {
        $cid = isset($newData['cid']) && !empty($newData['cid']) ? intval($newData['cid']) : 0;
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $deal_id = isset($newData['deal_id']) && !empty($newData['deal_id']) ? intval($newData['deal_id']) : 0;
        $structure_id = isset($newData['structure_id']) && !empty($newData['structure_id']) ? intval($newData['structure_id']) : 0;
        $general_id = isset($newData['general_id']) && !empty($newData['general_id']) ? intval($newData['general_id']) : 0;

        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }

        if (empty($id)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }

        $user_info = $this->di->user->select("username")->where("id",$uid)->fetchOne();
        if (empty($user_info)) {
            $rs = array('code'=>0,'msg'=>'000062','data'=>array(),'info'=>array());
            return $rs;
        }

        $customer_deal_info = $this->di->customer_order->where(array("id"=>$id))->fetchOne();
        if (empty($customer_deal_info)) {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
            return $rs;
        }
        $admin_common = new AdminCommon();
        $newData['updatetime'] = time();
        $deal_data = $admin_common->GetFieldListArr($newData,$field_list);

        $this->redis->set_time('deal_info_'.$id,$deal_data,6000,'customer');
        
        $update_info = $this->di->customer_order->where("id",$id)->update($deal_data);
        if ($update_info) {
            $rs = array('code'=>1,'msg'=>'000039','data'=>array(),'info'=>array());
        } else {
            $rs = array('code'=>0,'msg'=>'000040','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 13 成交详情
    public function GetCustomerDealInfo($newData) {
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
        $customer_deal_info = $this->redis->get_time('deal_info_'.$id,'customer');
        if (empty($customer_deal_info)) {
            $customer_deal_info = $this->di->customer_order->select("*")->where(array("id"=>$id,"status"=>1))->fetchOne();
            $customer_deal_info['project_name']=\App\GetFiledInfo('project_side','flower_name',$customer_deal_info['project_id']);
            $customer_deal_info['agent_name']=\App\GetFiledInfo('agent','flower_name',$customer_deal_info['agent_id']);
            $this->redis->set_time('deal_info_'.$id,$customer_deal_info,60,'customer');
        }
        
        if (!empty($customer_deal_info)) {
            $rs = array('code'=>1,'msg'=>'000000','data'=>$customer_deal_info,'info'=>$customer_deal_info);
        } else {
            $rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 14 转移跟进人
    public function PostChangePerson($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $change_uid = isset($newData['change_uid']) && !empty($newData['change_uid']) ? intval($newData['change_uid']) : 0;
        $charge_uid = isset($newData['charge_uid']) && !empty($newData['charge_uid']) ? intval($newData['charge_uid']) : $newData['uid'];
        $cid_arr = isset($newData['cid_arr']) ? $newData['cid_arr'] : array();
        if (empty($change_uid) || empty($cid_arr)) {
            $rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
            return $rs;
        }
        // 当前用户
        $user_info = \App\Getkey($uid,array("realname","structure_id","is_leader","share"));
        if (!empty($user_info['share']) && $user_info['share'] != 'all') {
            $user_share_group = explode(",",$user_info['share']);
        }
        // 转移人
        $change_user_info = \App\Getkey($change_uid,array("realname","structure_id","getlimit","setlimit","share"));
        $change_structure_arr = json_decode($change_user_info['structure_id']);
        $change_structure_id = array_pop($change_structure_arr);
        if (!empty($change_user_info['share']) && $change_user_info['share'] != 'all') {
            $change_share_group = explode(",",trim($change_user_info['share'],","));
        }
        
        // 跟进人
        $charge_user_info = \App\Getkey($charge_uid,array("realname","structure_id","is_leader","share"));
        $charge_structure_arr = json_decode($charge_user_info['structure_id']);
        $charge_structure_id = array_pop($charge_structure_arr);
        if (!empty($charge_user_info['share']) && $charge_user_info['share'] != 'all') {
            $charge_share_group = explode(",",trim($charge_user_info['share'],","));
        }
        
        $customer_common = new CustomerCommon();
        $admin_common = new AdminCommon();
        // 判断转移人的私海数量限制
        if ($change_user_info['setlimit'] != 0 && $change_user_info['getlimit'] >= $change_user_info['setlimit']) {
            // $err_msg[] = "客户转移失败，".$change_user_info['realname']."的私海数量已超过限制！";
            $data_msg['error'][] = $change_user_info['realname']."的私海数据已满".$change_user_info['setlimit']."人，无法移交，转移失败！";
            $rs = array('code'=>1,'msg'=>'000000','data'=>$data_msg,'info'=>$data_msg);
            return $rs;
        }
        
        $getlimit = $change_user_info['getlimit'];
        foreach ($cid_arr as $key => $value) {
            if ($change_user_info['setlimit'] != 0 &&  $getlimit >= $change_user_info['setlimit']) {
                $err_msg[] = $change_user_info['realname']."的私海数据已满".$change_user_info['setlimit']."人，无法转移，转移失败！";
                break;
            }
            if ($uid != 1 && empty($value)) {
                # 非共享数据，不能转移
                $err_msg[] = "客户：".$customer_info['cname']."转移失败！非共享数据，无法转移！";
                continue;
            }
            
            $customer_info = $this->di->customer->select("id,cname,creatid,charge_person")->where(array("id"=>$key))->fetchOne();
            $charge_person_arr = explode(",",trim($customer_info['charge_person'],","));
            array_shift($charge_person_arr);
            if ($uid == $customer_info['creatid'] && $uid != 1) {
                # 客户创建人
                $err_msg[] = "客户：".$customer_info['cname']."转移失败！创建人无法转移客户，请选择移交！";
                continue;
            } elseif (in_array($uid,$charge_person_arr)) {
                # 客户负责人
                // 只能本部门内部转移
                if (!empty($user_share_group) && !in_array($change_structure_id,$user_share_group)) {
                    $err_msg[] = "客户：".$customer_info['cname']."转移失败！只能转移给本部门人员！";
                    continue;
                }
                $share_join_where['cid'] = $key;
                $share_join_where['bid'] = $value;
                $share_bid = $value;
            } elseif ($user_info['is_leader'] != 0) {
                # 领导
                $new_charge_uid = "";
                $charge_userinfo = array();
                $charge_userinfo_group = array();
                $new_charge_uid = $this->di->share_join->select("beshare_uid,bid")->where(array("cid"=>$key,"bid"=>$value))->fetchOne();
                $charge_userinfo = \App\Getkey($new_charge_uid['beshare_uid'],array("realname","structure_id","is_leader","share"));
                if (!empty($charge_userinfo['share']) && $charge_userinfo['share'] != 'all') {
                    $charge_userinfo_group = explode(",",trim($charge_userinfo['share'],","));
                }
                if ($charge_userinfo_group != $change_share_group) {
                    $err_msg[] = "客户：".$customer_info['cname']."转移失败！只能移交给本部门人员！";
                    continue;
                }
                $share_bid = $new_charge_uid['bid'];
                $charge_uid = $new_charge_uid['beshare_uid'];
                $share_join_where['cid'] = $key;
                $share_join_where['bid'] = $share_bid;
                $share_join_where['beshare_uid'] = $new_charge_uid['beshare_uid'];

            } elseif ($uid == 1) {
                # 管理员
                if ($charge_share_group != $change_share_group) {
                    $err_msg[] = "客户：".$customer_info['cname']."转移失败！只能移交给本部门人员！";
                    continue;
                }
                $share_bid = $this->di->share_join->where(array("cid"=>$key,"beshare_uid"=>$charge_uid))->fetchOne();
                $share_join_where['cid'] = $key;
                $share_join_where['beshare_uid'] = $charge_uid;
                $share_join_where['bid'] = $share_bid;
                
            } else {
                # 非创建人和跟进人/非主管/非管理员
                $err_msg[] = "客户：".$customer_info['cname']."转移失败！无权操作！";
                continue;
            }
            // Step 1: 开启事务
            $this->di->beginTransaction('db_master');
            // (1)更改共享数据表
            $share_update_arr = array(
                "beshare_uid" => $change_uid,
                "groupid" => $change_structure_id
            );

            $share_update = $this->di->share_join->where($share_join_where)->update($share_update_arr);
            // (2)更改共享信息表
            $change_update_arr = array(
                "charge_person" => $change_uid.",",
                "follow_up" => 0,
                "follow_person" => $change_uid,
                "follw_time" => time(),
            );
            $share_customer_update = $this->di->share_customer->where(array("id"=>$share_bid))->update($change_update_arr);

            if ($share_update && $share_customer_update) {
                // (3)更改客户表负责人
                $charge_person_str = ','.$charge_uid.',';
                $new_charge_person = ','.$change_uid.',';
                $customer_update['charge_person'] = str_replace($charge_person_str,$new_charge_person,$customer_info['charge_person']);
                $this->di->customer->where(array("id"=>$key))->update($customer_update);

                // (4)增加客户操作日志
                $note = $user_info['realname']."将客户：".$customer_info['cname']."转移给".$change_user_info['realname'];
                $customer_common->CustomerActionLog($charge_uid,$key,15,$note,$note);
               
                // (5)发送消息，给转移人发送消息
                $msg_title = $user_info['realname']."将客户：".$customer_info['cname']."转移给您";
                $msg_content = $user_info['realname']."将客户：".$customer_info['cname']."转移给您";
                $admin_common->SendMsgNow($uid,$change_uid,$msg_title,$msg_content,0,"transfer","customer",$key);
                // 客户私海数量加一
                \App\SetLimit($change_uid,1);
                // 转移人客户私海数量减一
                \App\SetLimit($charge_uid,-1);
                $getlimit++;
                // 提交事务
                $this->di->commit('db_master');
                $success_msg[] = "客户：".$customer_info['cname']."转移给".$change_user_info['realname']."操作成功！";
                continue;
            } else {
                $this->di->rollback('db_master');
                $err_msg[] = "客户：".$customer_info['cname']."转移给".$change_user_info['realname']."操作失败！";
                continue;
            }
        }
        $data_msg = array(
            'success' => $success_msg,
            'error' => $err_msg
        );
        $this->redis->flushDB('customer');
        $rs = array('code'=>1,'msg'=>'000058','data'=>$data_msg,'info'=>$data_msg);
        return $rs;
    }

    // 15 私海数量限制列表
    public function NumberOfLimitlist($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        if (empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000044','data'=>array(),'info'=>array());
            return $rs;
        }
        // 当前用户
        $user_info = \App\Getkey($uid,array("realname","structure_id","parent_id","is_leader","type","type_id"));

        if ($uid != 1 && $user_info['is_leader'] == 0) {
            # 不是领导
            $rs = array('code'=>0,'msg'=>'000084','data'=>array(),'info'=>array());
            return $rs;
        }
        $admin_common = new AdminCommon();
        $tree = new Tree();
        switch ($uid) {
            case 1:
                # 管理员
                $data_list = $this->redis->get_forever('member_number_limit_'.$uid,'user');
                if (empty($data_list)) {
                    $structure = new Category('admin_structure', array('id', 'pid', 'name', 'title'));
                    $data = $structure->getList('structure',"project_num = {$user_info['type_id']}", 0, "id");
                    // 查询所有员工
                    $user_list = $this->di->user->select("id,structure_id,realname,setlimit,new_groupid")->where(array("status"=>1,"type"=>$user_info['type'],"type_id"=>$user_info['type_id']))->fetchAll();
                    foreach ($data as $key => $value) {
                        $member_list = array();
                        foreach ($user_list as $k => $v) {
                            $v['structure_id'] = $v['new_groupid'];
                            if ($v['new_groupid'] == $value['id']) {
                                $member_list[$value['id']][] = $v;
                                unset($user_list[$k]);
                            }
                        }
                        $data[$key]['member_list'] = $member_list[$value['id']];
                    }
                    $data_list = $tree->list_to_tree($data);
                    $this->redis->set_forever('member_number_limit_'.$uid,$data_list,'user');
                }

                break;

            default:
                # 其他
                // 查询下面子部门
                $data_list = $this->redis->get_forever('member_number_limit_'.$uid,'user');
                if (empty($data_list)) {
                    $structure_id = json_decode($user_info['structure_id']);
                    $user_structure_str = "";
                    $user_structure_str = $admin_common->GetMyStructure($user_info['is_leader'],$structure_id,$user_info['parent_id']);
                    $structure = new Category('admin_structure', array('id', 'pid', 'name', 'title'));
                    $data = $structure->getList('structure',"FIND_IN_SET(id,'{$user_structure_str}') AND project_num = {$user_info['type_id']}", 0, "id");
                    // var_dump($data);die;
                    foreach ($data as $key => $value) {
                        // 查询部门下员工
                        $member_where = "status = 1 AND new_groupid = {$value['id']}";
                        $member_list = $this->di->user->select("id,structure_id,realname,setlimit")->where($member_where)->fetchAll();
                        $data[$key]['member_list'] = $member_list;
                    }
                    // var_dump($data);die;
                    $data_list = $tree->list_to_tree($data);
                    $this->redis->set_forever('member_number_limit_'.$uid,$data_list,'user');
                }
                break;
        }
        if ($data_list) {
            $rs = array('code'=>1,'msg'=>'000000','data'=>$data_list,'info'=>$data_list);
        } else {
            $rs = array('code'=>0,'msg'=>'000002','data'=>array(),'info'=>array());
        }
        return $rs;
    }

    // 16 私海数量限制设置
    public function PostNumberLimit($newData) {
        $uid = isset($newData['uid']) && !empty($newData['uid']) ? intval($newData['uid']) : 0;
        $uid_value = isset($newData['uid_value']) && !empty($newData['uid_value']) ? $newData['uid_value'] : array();

        if (empty($uid_value) || empty($uid)) {
            $rs = array('code'=>0,'msg'=>'000002','data'=>array(),'info'=>array());
            return $rs;
        }
        // 当前客户信息
        $user_info =  \App\Getkey($uid,array("realname","is_leader"));
        if ($uid != 1 && $user_info['is_leader'] == 0) {
            $rs = array('code'=>0,'msg'=>'000084','data'=>array(),'info'=>array());
            return $rs;
        }
        $success_user = "";
        $error_user = ""; 

        foreach ($uid_value as $key => $value) {
            if (empty($key)) {
                continue;
            }
            // 当前用户
            $username = \App\Getkey($key,"realname");
            if (empty($username)) {
                continue;
            }
            if ($value != "") {
                $res = $this->di->user->where(array("id"=>$key))->update(array("setlimit"=>$value));
                if ($res) {
                    // 增加操作日志
                    $content = $user_info['realname']."设置用户：".$username."的私海数量限制为：".$value;
                    \App\setlog($uid,2,'私海数量限制','成功',$content,'私海数量限制');
                    $success_user .= $username.",";
                } else {
                    $error_user .= $username.",";
                }
            }
            $this->redis->del($key,'userinfo');
        }

        if ($success_user) {
            $msg[] = "用户:".$success_user."私海数量限制成功！";
            $this->redis->set_forever('member_number_limit_'.$uid,array(),'user');
        }
        if ($error_user) {
            $msg[] = "用户：".$error_user."私海数量限制失败！";
        }
        $this->redis->flushDB('user');
        $this->redis->flushDB('userinfo');
        $this->redis->flushDB('list');

        $rs = array('code'=>1,'msg'=>'000000','data'=>$msg,'info'=>$msg);
        return $rs;
    }

    
}