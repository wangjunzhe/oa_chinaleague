<?php
namespace App\Api;
use PhalApi\Api;
use App\Domain\Admin as Domain_Admin;
/**
 * 系统设置接口服务类
 * @author: dogstar <chanzonghuang@gmail.com> 2014-10-04
 */
class Admin extends Api {
    public function getRules() {
        return array(
            'PostModelAdd' => array(
                'model_name' => array('name' => 'model_name', 'type' => 'string', 'require' => true, 'desc' => '模型名称（必填）', 'default' => '客户'),
                'table_name' => array('name' => 'table_name', 'type' => 'string', 'require' => true, 'desc' => '数据表名（必填）', 'default' => 'custom'),
                'table_num' => array('name' => 'table_num', 'type' => 'int','min' => 1, 'max' => 2, 'default' => 1, 'require' => true, 'desc' => '数据表数量（必填）'),
                'beizhu' => array('name' => 'beizhu', 'type' => 'string', 'require' => true, 'desc' => '备注（不必填）', 'default' => '客户模型')
            ),
            'PostDeleteModel' => array(
                'modelid' => array('name' => 'modelid', 'type' => 'int','min' => 1,'require' => true, 'desc' => '模型id（必填）'),
            ),
            'GetModelFieldList' => array(
                'modelid' => array('name' => 'modelid', 'type' => 'int','min' => 1,'require' => true, 'desc' => '模型id（必填）' ),
            ),
            'PostFieldAdd' => array(
                'modelid' => array('name' => 'modelid', 'type' => 'int','min' => 1,'require' => true, 'desc' => '模型id（必填）' ),
                'field' => array('name' => 'field', 'type' => 'string', 'require' => true, 'desc' => '字段名称（必填）', 'default' => ''),
                'name' => array('name' => 'name', 'type' => 'string', 'require' => true, 'desc' => '字段别名（必填）', 'default' => ''),
                'type' => array('name' => 'type', 'type' => 'string', 'require' => true, 'desc' => '字段类型（必填）', 'default' => ''),
                'is_require' => array('name' => 'is_require', 'type' => 'string', 'require' => true, 'desc' => '是否必填（必填）', 'default' => ''),
                'css' => array('name' => 'css', 'type' => 'string', 'require' => true, 'desc' => 'css类名（必填）', 'default' => ''),
                'placeholder' => array('name' => 'placeholder', 'type' => 'string', 'require' => true, 'desc' => '提示（不必填）', 'default' => ''),
                'setting' => array('name' => 'setting', 'type' => 'array', 'require' => true, 'desc' => '字段值（必填）', 'default' => ''),
                'master' => array('name' => 'master', 'type' => 'string', 'require' => true, 'desc' => '是否添加在主表【1是其他为附表】（必填）', 'default' => ''),
            ),
            'GetFieldInfo' => array(
                'fieldid' => array('name' => 'fieldid', 'type' => 'int','min' => 1,'require' => true, 'desc' => '字段id（必填）' ),
            ),
            'PostEditField' => array(
                'fieldid' => array('name' => 'fieldid', 'type' => 'int','min' => 1,'require' => true, 'desc' => '字段id（必填）' ),
                'name' => array('name' => 'name', 'type' => 'string', 'require' => true, 'desc' => '字段别名（必填）', 'default' => ''),
                'is_require' => array('name' => 'is_require', 'type' => 'string', 'require' => true, 'desc' => '是否必填（必填）', 'default' => ''),
                'css' => array('name' => 'css', 'type' => 'string', 'require' => true, 'desc' => 'css类名（必填）', 'default' => ''),
                'placeholder' => array('name' => 'placeholder', 'type' => 'string', 'require' => true, 'desc' => '提示（不必填）', 'default' => ''),
                'setting' => array('name' => 'setting', 'type' => 'array', 'require' => true, 'desc' => '字段值（必填）', 'default' => ''),
            ),
            'PostDeleteField' => array(
                'fieldid' => array('name' => 'fieldid', 'type' => 'int','min' => 1,'require' => true, 'desc' => '字段id（必填）' ),
            ),
        );
    }
    /**
     * 01 添加模型接口
     * @author Wang Junzhe
     * @DateTime 2019-07-25T10:00:38+0800
     * @desc 添加模型数据接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PostModelAdd() {
        $newData = array(
            'model_name' => $this->model_name,
            'table_name' => $this->table_name,
            'table_num' => $this->table_num,
            'beizhu' => $this->beizhu
        );
        $domain = new Domain_Admin();
        $info = $domain->PostModelAdd($newData);
        return $info;
    }

    
    /**
     * 02 模型列表接口
     * @author Wang Junzhe
     * @DateTime 2019-07-25T14:43:00+0800
     * @desc 模型列表数据接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return int data.id 模型id 
     * @return string data.name 模型名称
     * @return string data.master_table 主表名称
     * @return string data.attr_table 附属表
     * 
     * @return string msg 提示信息
     */
    public function GetModelList() {
        $domain = new Domain_Admin();
        $info = $domain->GetModelList();
        return $info;
    }

    /**
     * 03 删除模型接口
     * @author Wang Junzhe
     * @DateTime 2019-07-25T14:58:17+0800
     * @desc 删除模型数据接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     * 
     * @return string msg 提示信息
     */
    public function PostDeleteModel() {
        $domain = new Domain_Admin();
        $info = $domain->PostDeleteModel($this->modelid);
        return $info;
    }

    /**
     * 04 模型字段列表接口
     * @author Wang Junzhe
     * @DateTime 2019-07-26T13:47:16+0800
     * @desc 模型字段列表数据接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return int data.id 字段id 
     * @return string data.name 字段别名
     * @return string data.field 字段名
     * @return string data.type 字段类型
     * @return string data.master 所在表[1主表其他为附表]
     * @return string data.css 位置
     * @return string data.is_require 是否为必填【0不必填1必填】
     * @return string data.placeholder 字段提示
     * @return string data.setting 
     * @return int data.sort 排序
     * 
     * @return string msg 提示信息
     */
    public function GetModelFieldList() {
        $domain = new Domain_Admin();
        $info = $domain->GetModelFieldList($this->modelid);
        return $info;
    }

    /**
     * 05 添加字段接口
     * @author Wang Junzhe
     * @DateTime 2019-07-26T15:12:35+0800
     * @desc 添加字段数据接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostFieldAdd() {
        $newData = array(
            'modelid' => $this->modelid,
            'field' => $this->field,
            'name' => $this->name,
            'type' => $this->type,
            'is_require' => $this->is_require,
            'css' => $this->css,
            'placeholder' => $this->placeholder,
            'setting' => $this->setting,
            'master_field' => $this->master
        );
        $domain = new Domain_Admin();
        $info = $domain->PostFieldAdd($newData);
        return $info;
    }

    /**
     * 06 字段详情接口
     * @author Wang Junzhe
     * @DateTime 2019-07-29T10:12:42+0800
     * @desc 字段详情数据接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string data.id 字段ID
     * @return string data.field 字段
     * @return string data.name 字段别名
     * @return string data.type 字段类型
     * @return string data.is_require 是否必填【1必填】
     * @return string data.css 字段类型css类名
     * @return string data.placeholder 字段提示
     * @return string data.setting 字段值（需要进行unserialize()）
     * @return string data.master_field 是否为主表字段
     *
     * @return string msg 提示信息
     */
    public function GetFieldInfo() {
        $domain = new Domain_Admin();
        $info = $domain->GetFieldInfo($this->fieldid);
        return $info;
    }

    /**
     * 07 字段修改接口
     * @author Wang Junzhe
     * @DateTime 2019-07-29T14:34:39+0800
     * @desc 修改字段数据接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostEditField() {
        $newData = array(
            'fieldid' => $this->fieldid,
            'name' => $this->name,
            'is_require' => $this->is_require,
            'css' => $this->css,
            'placeholder' => $this->placeholder,
            'setting' => $this->setting
        );
        $domain = new Domain_Admin();
        $info = $domain->PostEditField($newData);
        return $info;
    }

    /**
     * 08 字段删除接口
     * @author Wang Junzhe
     * @DateTime 2019-07-29T15:41:48+0800
     * @desc 删除字段数据接口（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostDeleteField() {
        $domain = new Domain_Admin();
        $info = $domain->PostDeleteField($this->fieldid);
        return $info;
    }

    


}
