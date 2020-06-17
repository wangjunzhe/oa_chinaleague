<?php
namespace App\Model;
use PhalApi\Model\NotORMModel as NotORM;
use App\Model\Common as Common;
use App\Common\Admin as AdminCommon;
class Admin extends NotORM 
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
		// 加密向量
		$this->iv = \PhalApi\DI()->config->get('common.IV');
		// 页码设置
        $this->pagesize = \PhalApi\DI()->config->get('common.PAGESIZE');
	}

	// 01 添加模型接口
	public function PostModelAdd($newData) {
		// 解密参数
		// $obj = \PhalApi\decrypts($info, $key,$this->iv);
		$model_name = isset($newData['model_name']) ? $newData['model_name'] : "";
		$table_name = isset($newData['table_name']) ? $newData['table_name'] : "";
		$table_num = isset($newData['table_num']) ? $newData['table_num'] : 1;
		$beizhu = isset($newData['beizhu']) ? $newData['beizhu'] : "";
		if (empty($model_name) || empty($table_num)) {
			$rs = array('code'=>0,'msg'=>'000002','data'=>array(),'info'=>array());
			return $rs;
		}

		$table = $this->cache->get('table');
		// 判断表是否存在
		if (empty($table)) {
			$sql = "SHOW TABLES";
			$tables = $this->di->model->queryAll($sql,array());
			for ($i=0; $i < count($tables); $i++) { 
				$table[] = $tables[$i]['Tables_in_league'];
			}
			$this->cache->set('table',$table,600);
		}
		$m_path = $this->config->get('common.M_PATH');
		//先创建表，然后执行下面的操作
		$basic_tablename = $this->prefix.$table_name;
		$table_model_field = $this->prefix.'model_field';
		if($table_num == 2) {
			// 附表
			$att_tablename = $this->prefix.$table_name."_data";
			$attr_table = $table_name."_data";
			//选择了创建2个表
			if(in_array($basic_tablename, $table) || in_array($att_tablename, $table)) {
				$rs = array('code'=>0,'msg'=>'000001','data'=>array(),'info'=>array());
				return $rs;
			}
			$sqldata = file_get_contents($m_path.'db2.sql');
		} else {
			$att_tablename = "";
			//创建独立单表
			if(in_array($basic_tablename, $table)) {
				$rs = array('code'=>0,'msg'=>'000001','data'=>array(),'info'=>array());
				return $rs;
			}
			$sqldata = file_get_contents($m_path.'db1.sql');
		}
		$sqldata = str_replace('$basic_tablename', $basic_tablename, $sqldata);
		$sqldata = str_replace('$att_tablename', $att_tablename, $sqldata);
		$sqldata = str_replace('$table_model_field', $table_model_field, $sqldata);
		
		$admin_common = new AdminCommon();
		$sql_result = $admin_common->sql_execute($sqldata);
		if ($sql_result) {
			$formdata = array();
			array_push($table,$basic_tablename);
			if ($table_num == 2) {
				array_push($table,$att_tablename);
				$formdata['attr_table'] = $table_name."_data";
			}
			// 更新数据表缓存
			$this->cache->set('table',$table,600);
			$formdata['name'] = $model_name;
			$formdata['master_table'] = $table_name;
			$formdata['remark'] = $beizhu;
			$modelid = $this->di->model->insert($formdata);
			
			$rs = array('code'=>1,'msg'=>'000000','data'=>array(),'info'=>array());
		} else {
			$rs = array('code'=>0,'msg'=>'999999','data'=>array(),'info'=>array());
		}
		
		return $rs;
	}

	// 02 模型列表接口
    public function GetModelList() {
    	$model_list = $this->di->model->select("modelid id,name,master_table,attr_table")->where("status",1)->limit(20)->fetchAll();
    	if (!empty($model_list)) {
    		$rs = array('code'=>1,'msg'=>'000000','data'=>$model_list,'info'=>$model_list);
    	} else {
			$rs = array('code'=>1,'msg'=>'000002','data'=>array(),'info'=>array());
    	}
    	return $rs;
    }

    // 03 删除模型接口
    public function PostDeleteModel($modelid) {
    	// 解密参数
		// $obj = \PhalApi\decrypts($info, $key,$this->iv);
		$modelid = isset($modelid) ? intval($modelid) : 0;

		if (empty($modelid)) {
			$rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
			return $rs;
		}

		$model_info = $this->di->model->select("master_table,attr_table")->where("modelid",$modelid)->fetchOne();
		if (empty($model_info)) {
			$rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
			return $rs;
		}

		$this->di->model->where(array('modelid'=>$modelid))->delete();
		$this->di->model_field->where(array('modelid'=>$modelid))->delete();

        if($model_info['master_table']) {
        	$this->di->model->executeSql("DROP TABLE ".$this->prefix.$model_info['master_table']);
        }
        if($model_info['attr_table']) {
            $this->di->model->executeSql("DROP TABLE ".$this->prefix.$model_info['attr_table']);
        }
        // 清除表缓存
        $this->cache->delete("table");
		$rs = array('code'=>1,'msg'=>'000005','data'=>array(),'info'=>array());
		return $rs;
    }
	
	// 04 模型字段列表接口
	public function GetModelFieldList($modelid) {
		$modelid = isset($modelid) ? intval($modelid) : 0;
		if (empty($modelid)) {
			$rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
			return $rs;
		}
		$field_list = $this->di->model_field->select("id,name,field,type,master_field master,css,sort,`is_require`,placeholder,setting")->where("modelid",$modelid)->order("sort DESC,id ASC")->fetchAll();
		if (empty($field_list)) {
			$rs = array('code'=>1,'msg'=>'000002','data'=>array(),'info'=>array());
		} else {
		    if($modelid==2){
		        $new=new Structure();
		        foreach ($field_list as $k=>$v){
		            if($v['field']=='shujusuoshubumen'){
		                $field_list[$k]['setting']=$new->GetLoveFiled(\PhalApi\DI()->session->uid,'shujusuoshubumen','2');
                    }
                }
            }
			$rs = array('code'=>1,'msg'=>'000000','data'=>$field_list,'info'=>$field_list);
		}
		return $rs;
	}

	// 05 添加字段接口
	public function PostFieldAdd($newData) {
		$modelid = isset($newData['modelid']) ? intval($newData['modelid']) : 0;
		$field = isset($newData['field']) ? $newData['field'] : "";
		$name = isset($newData['name']) ? $newData['name'] : "";
		$type = isset($newData['type']) ? $newData['type'] : "input";
		$is_require = isset($newData['is_require']) ? $newData['is_require'] : 0;
		$css = isset($newData['css']) ? $newData['css'] : "";
		$placeholder = isset($newData['placeholder']) ? $newData['placeholder'] : "";
		$setting = isset($newData['setting']) ? $newData['setting'] : "";
		$master_field = isset($newData['master_field']) && !empty($newData['master_field']) ? $newData['master_field'] : 1;

		if (empty($modelid)) {
			$rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
			return $rs;
		}
		if (empty($field) || empty($name)) {
			$rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
			return $rs;
		}
		//检查是否存在该字段
		$field_info = $this->di->model_field->where(array('modelid'=>$modelid,'field'=>$field))->fetchOne("name");

		if (!empty($field_info)) {
			$rs = array('code'=>0,'msg'=>'000006','data'=>array(),'info'=>array());
			return $rs;
		}
		$model_info = $this->di->model->select("master_table,attr_table")->where("modelid",$modelid)->fetchOne();
		$db_table = $master_field == 1 ? $this->prefix.$model_info['master_table'] : $this->prefix.$model_info['attr_table'];
		switch ($type) {
			case 'input':
				# 文本框
				$sql = "ALTER TABLE `$db_table` ADD `$field` CHAR( 50 ) NOT NULL default '' COMMENT '$name'";
				break;
			case 'radio':
				# 单选
				$sql = "ALTER TABLE `$db_table` ADD `$field` CHAR( 10 ) NOT NULL default '' COMMENT '$name'";
				break;
			case 'checkbox':
				# 多选
				$sql = "ALTER TABLE `$db_table` ADD `$field` varchar( 255 ) NOT NULL default '' COMMENT '$name'";
				break;
			case 'select':
				# 下拉
				$sql = "ALTER TABLE `$db_table` ADD `$field` varchar( 20 ) NOT NULL default '' COMMENT '$name'";
				break;
			case 'date':
				# 日期
				$sql = "ALTER TABLE `$db_table` ADD `$field` DATE NULL COMMENT '$name'";
				break;
			default:
				# code...
				break;
		}
		// Step 1: 开启事务
        $this->di->beginTransaction('db_master');
		$fieldid = $this->di->$db_table->query($sql,array());
		$newData['setting'] = json_encode($newData['setting'],JSON_UNESCAPED_UNICODE);
		// 字段添加成功
		$model_field_id = $this->di->model_field->insert($newData);

		if ($modelid == 2) {
			// 分享客户信息表
			$sql = str_replace("crm_customer","crm_share_customer",$sql);
			$this->di->share_customer->query($sql,array());
		}
		
		if ($fieldid && $model_field_id) {
            // 提交事务
            $this->di->commit('db_master');
            $rs = array('code'=>1,'msg'=>'000008','data'=>array(),'info'=>array());
        } else {
            // 回滚事务
            $this->di->rollback('db_master');
            $rs = array('code'=>0,'msg'=>'000007','data'=>array(),'info'=>array());
        }
		
		return $rs;
	}

	// 06 字段详情接口
	public function GetFieldInfo($fieldid) {
		$fieldid = isset($fieldid) ? intval($fieldid) : 0;
		if (empty($fieldid)) {
			$rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
			return $rs;
		}
		$field_info = $this->di->model_field->select("id,field,name,type,`is_require`,css,placeholder,setting,master_field")->where("id",$fieldid)->fetchOne();
		if (!empty($field_info)) {
			$rs = array('code'=>1,'msg'=>'000000','data'=>$field_info,'info'=>$field_info);
		} else {
			$rs = array('code'=>1,'msg'=>'000004','data'=>array(),'info'=>array());
		}
		return $rs;
	}

	// 07 字段修改接口
	public function PostEditField($newData) {
		$fieldid = isset($newData['fieldid']) ? intval($newData['fieldid']) : 0;
		$name = isset($newData['name']) ? $newData['name'] : "";
		$is_require = isset($newData['is_require'])  ? $newData['is_require'] : 0;
		$css = isset($newData['css']) ? $newData['css'] : "";
		$placeholder = isset($newData['placeholder']) ? $newData['placeholder'] : "";
		$setting = isset($newData['setting']) ? json_encode($newData['setting'],JSON_UNESCAPED_UNICODE) : "";
		if (empty($fieldid)) {
			$rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
			return $rs;
		}
		$field_info = $this->di->model_field->where("id",$fieldid)->fetchOne("name");
		if (empty($field_info)) {
			$rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
			return $rs;
		}
		$edit_info = $this->di->model_field->where("id",$fieldid)->update(array("name"=>$name,"is_require"=>$is_require,"css"=>$css,"placeholder"=>$placeholder,"setting"=>$setting));
		if ($edit_info) {
			$rs = array('code'=>1,'msg'=>'000009','data'=>array(),'info'=>array());
		} else {
			$rs = array('code'=>0,'msg'=>'000010','data'=>array(),'info'=>array());
		}
		return $rs;
	}

	// 08 字段删除接口
	public function PostDeleteField($fieldid) {
		$fieldid = isset($fieldid) ? intval($fieldid) : 0;
		if (empty($fieldid)) {
			$rs = array('code'=>0,'msg'=>'000003','data'=>array(),'info'=>array());
			return $rs;
		}
		$field_info = $this->di->model_field->select("modelid,master_field,field")->where("id",$fieldid)->fetchOne();
		if (empty($field_info)) {
			$rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
			return $rs;
		}
		$model_info = $this->di->model->select("master_table,attr_table")->where("modelid",$field_info['modelid'])->fetchOne();
		if (empty($model_info)) {
			$rs = array('code'=>0,'msg'=>'000004','data'=>array(),'info'=>array());
			return $rs;
		}
		// Step 1: 开启事务
        $this->di->beginTransaction('db_master');
        $field = $field_info['field'];
        $tablename = $field_info['master_field'] == 1 ? $model_info['master_table'] : $model_info['attr_table'];
        $tablename = $this->prefix.$tablename;
		$res1 = $this->di->model_field->where("id",$fieldid)->delete();
		$res2 = $this->di->model->query("ALTER TABLE `$tablename` DROP `$field`",array());

		if ($field_info['modelid'] == 2) {
			// 分享客户信息表
			$sql = str_replace("crm_customer","crm_share_customer",$sql);
			$this->di->model->query("ALTER TABLE `crm_share_customer` DROP `$field`",array());
		}

		if ($res1 && $res2) {
            // 提交事务
            $this->di->commit('db_master');
            $rs = array('code'=>1,'msg'=>'000011','data'=>array(),'info'=>array());
        } else {
            // 回滚事务
            $this->di->rollback('db_master');
            $rs = array('code'=>0,'msg'=>'000012','data'=>array(),'info'=>array());
        }
		return $rs;
	}
}