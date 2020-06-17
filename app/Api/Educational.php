<?php
namespace App\Api;
use PhalApi\Api;
use App\Domain\Educational as Domain_Educational;
use App\Common\Yesapiphpsdk;

/**
 * 教务系统接口
 * @author: 小明 <2669750716@qq.com> 2019-09-010
 */
class Educational extends Api {
    public function getRules() {
        return array(
            'PostEduAdd' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'group_id' => array('name' => 'group_id','type' => 'string','require' => true,'desc' => '部门id（必填）只需要最后一个id','default' => '' ),
                'group_name' => array('name' => 'group_name','type' => 'string','require' => true,'desc' => '部门name（必填）部门1,部门2','default' => '' ),
                'uname' => array('name' => 'uname','type' => 'string','require' => true,'desc' => '教务人员姓名加id例如:admin,11','default' => 'admin,11' ),
                'sex' => array('name' => 'sex','type' => 'string','require' => true,'desc' => '性别','default' => '男' ),
                'phone' => array('name' => 'phone','type' => 'int','require' => true,'desc' => '联系电话','default' => '' ),
                'class_id' => array('name' => 'class_id','type' => 'string','require' => false,'desc' => '管理班级（不必填）例如:班级id1,班级id2,','default' => '' ),
                'note' => array('name' => 'web_site','type' => 'string','require' => false,'desc' => '附件（不必填）','default' => '' ),
                'beizhu' => array('name' => 'beizhu','type' => 'string','require' => false,'desc' => '备注（必填）','default' => '' ),
                'status' => array('name' => 'status','type' => 'int','require' => false,'desc' => '状态（必填）','default' => '1' ),
                'field_list' => array('name' => 'field_list','type' => 'array','require' => true,'desc' => '灵活字段(必填)职称','default' => 'array=>("tec_title"=>"教务老师")' ),
            ),
            'PostEduEdit' => array(
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '教务列表id（必填）','default' => '' ),
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'group_id' => array('name' => 'group_id','type' => 'string','require' => true,'desc' => '部门id（必填）只需要最后一个id','default' => '' ),
                'group_name' => array('name' => 'group_name','type' => 'string','require' => true,'desc' => '部门name（必填）部门1,部门2','default' => '' ),
                'uname' => array('name' => 'uname','type' => 'string','require' => true,'desc' => '教务人员姓名加id例如:admin,11','default' => 'admin,11' ),
                'sex' => array('name' => 'sex','type' => 'string','require' => true,'desc' => '性别','default' => '男' ),
                'phone' => array('name' => 'phone','type' => 'int','require' => true,'desc' => '联系电话','default' => '' ),
                'class_id' => array('name' => 'class_id','type' => 'string','require' => false,'desc' => '管理班级（不必填）例如:班级id1,班级id2,','default' => '' ),
                'note' => array('name' => 'note','type' => 'string','require' => false,'desc' => '网址（不必填）','default' => '' ),
                'status' => array('name' => 'status','type' => 'int','require' => false,'desc' => '状态（必填）','default' => '' ),
                'beizhu' => array('name' => 'beizhu','type' => 'string','require' => true,'desc' => '联系人（必填）','default' => '' ),
                'field_list' => array('name' => 'field_list','type' => 'array','require' => true,'desc' => '灵活字段(必填)职称','default' => 'array=>("tec_title"=>"教务老师")' ),

            ),
            'GetEduList' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'type' => array('name' => 'type','type' => 'int','require' => false,'desc' => '类型1有效2无效','default' => '1' ),
                'keywords' => array('name' => 'keywords', 'type' => 'string','require' => false, 'desc' => '关键词搜索（不必填）' ),
                'pageno' => array('name' => 'pageno', 'type' => 'int','min' => 1,'require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','min' => 1,'require' => false, 'desc' => '条数限制（不必填）' ),
            ),
            'PostEduStudentList' => array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'type' => array('name' => 'type','type' => 'int','require' => false,'desc' => '类型1总客户2今日新增3本周新增4本月新增','default' => '1' ),
                'keywords' => array('name' => 'keywords', 'type' => 'string','require' => false, 'desc' => '关键词搜索（不必填）' ),
                'pageno' => array('name' => 'pageno', 'type' => 'int','min' => 1,'require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','min' => 1,'require' => false, 'desc' => '条数限制（不必填）' ),
            ),
            'PostEduDele'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '当前列表id（必填）','default' => '' ),
            ),
            'PostEduTeacherSalaryDel'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '当前列表id（必填）','default' => '' ),
            ),
            'PostEduClassDele'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '当前列表id（必填）','default' => '' ),
            ),
            'GetEduInfo'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '当前教务id（必填）','default' => '' ),
                'type' => array('name' => 'type','type' => 'int','require' => true,'desc' => '1教务详情2上课记录详情3学员资料详情4老师课酬详情（必填）','default' => '' ),
            ),
            'PostEduClassAdd'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'xm' => array('name' => 'xm','type' => 'string','require' => true,'min' => 1,'desc' => '所属项目名称（必填）','default' => '' ),
                'xm_id' => array('name' => 'xm_id','type' => 'int','require' => true,'min' => 1,'desc' => '所属项目id（必填）','default' => '' ),
                'zhuti' => array('name' => 'zhuti','type' => 'string','require' => false,'desc' => '课程主题（非必填）','default' => '' ),
                'concerns' => array('name' => 'concerns','type' => 'string','require' => false,'desc' => '关注问题（非必填）','default' => '' ),
                'solutions' => array('name' => 'solutions','type' => 'string','require' => false,'desc' => '解决方案（非必填）','default' => '' ),
                'edu_uid' => array('name' => 'edu_uid','type' => 'int','require' => true,'desc' => '上课老师id（必填）','default' => '' ),
                'edu_uname' => array('name' => 'edu_uname','type' => 'string','require' => true,'desc' => '上课老师name（必填）','default' => '' ),
                'class_id' => array('name' => 'class_id','type' => 'string','require' => true,'desc' => '上课班级id（必填）','default' => '' ),
                'class_name' => array('name' => 'class_name','type' => 'string','require' => true,'desc' => '上课老班级name（必填）','default' => '' ),
                'class_guimo' => array('name' => 'class_guimo','type' => 'string','require' => false,'desc' => '上课规模（非必填）','default' => '' ),
                'class_date' => array('name' => 'class_date','type' => 'int','require' => false,'desc' => '上课时间（非必填）','default' => '' ),
                'class_adress' => array('name' => 'class_adress','type' => 'int','require' => false,'desc' => '上课地址（非必填）','default' => '' ),
                'class_hours' => array('name' => 'class_hours','type' => 'int','require' => false,'desc' => '上课时长（非必填）','default' => '' ),
                'fankui' => array('name' => 'fankui','type' => 'string','require' => false,'desc' => '学员反馈（非必填）','default' => '' ),
                'status' => array('name' => 'status','type' => 'int','require' => false,'desc' => '付款状态（非必填）','default' => '' ),
                'note' => array('name' => 'note','type' => 'string','require' => false,'desc' => '附件（非必填）','default' => '' ),
                'beizhu' => array('name' => 'beizhu','type' => 'string','require' => false,'desc' => '备注（非必填）','default' => '' ),
                'create_name' => array('name' => 'create_name','type' => 'string','require' => true,'desc' => '创建人名称（必填）','default' => '' ),
            ),
            'PostEduClassEdit'=>array(
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '当前上课信息记录id（必填）','default' => '' ),
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'xm' => array('name' => 'xm','type' => 'string','require' => true,'min' => 1,'desc' => '所属项目名称（必填）','default' => '' ),
                'xm_id' => array('name' => 'xm_id','type' => 'int','require' => true,'min' => 1,'desc' => '所属项目id（必填）','default' => '' ),
                'zhuti' => array('name' => 'zhuti','type' => 'string','require' => false,'desc' => '课程主题（非必填）','default' => '' ),
                'concerns' => array('name' => 'concerns','type' => 'string','require' => false,'desc' => '关注问题（非必填）','default' => '' ),
                'solutions' => array('name' => 'solutions','type' => 'string','require' => false,'desc' => '解决方案（非必填）','default' => '' ),
                'edu_uid' => array('name' => 'edu_uid','type' => 'int','require' => true,'desc' => '上课老师id（必填）','default' => '' ),
                'edu_uname' => array('name' => 'edu_uname','type' => 'string','require' => true,'desc' => '上课老师name（必填）','default' => '' ),
                'class_id' => array('name' => 'class_id','type' => 'string','require' => true,'desc' => '上课班级id（必填）','default' => '' ),
                'class_name' => array('name' => 'class_name','type' => 'string','require' => true,'desc' => '上课老班级name（必填）','default' => '' ),
                'class_guimo' => array('name' => 'class_guimo','type' => 'string','require' => false,'desc' => '上课规模（非必填）','default' => '' ),
                'class_date' => array('name' => 'class_date','type' => 'int','require' => false,'desc' => '上课时间（非必填）','default' => '' ),
                'class_adress' => array('name' => 'class_adress','type' => 'int','require' => false,'desc' => '上课地址（非必填）','default' => '' ),
                'class_hours' => array('name' => 'class_hours','type' => 'int','require' => false,'desc' => '上课时长（非必填）','default' => '' ),
                'fankui' => array('name' => 'fankui','type' => 'string','require' => false,'desc' => '学员反馈（非必填）','default' => '' ),
                'status' => array('name' => 'status','type' => 'int','require' => false,'desc' => '付款状态（非必填）','default' => '' ),
                'note' => array('name' => 'note','type' => 'string','require' => false,'desc' => '附件（非必填）','default' => '' ),
                'beizhu' => array('name' => 'beizhu','type' => 'string','require' => false,'desc' => '备注（非必填）','default' => '' ),
                'updateman' => array('name' => 'updateman','type' => 'string','require' => true,'desc' => '更新人名称（必填）','default' => '' ),
            ),
            'PostEduClassList'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'tid' => array('name' => 'tid','type' => 'int','require' => false,'desc' => '当前教务老师id（非必填）','default' => '0' ),
                'type' => array('name' => 'type','type' => 'int','require' => false,'desc' => '类型1全部2今日3本周','default' => '1' ),
                'keywords' => array('name' => 'keywords', 'type' => 'string','require' => false, 'desc' => '关键词搜索（不必填）' ),
                'pageno' => array('name' => 'pageno', 'type' => 'int','min' => 1,'require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','min' => 1,'require' => false, 'desc' => '条数限制（不必填）' ),
            ),
            'PostEduStudent'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'cname' => array('name' => 'cname','type' => 'string','require' => true,'min' => 1,'desc' => '学员名称（必填）','default' => '' ),
                'cid' => array('name' => 'cid','type' => 'int','require' => true,'min' => 1,'desc' => '学员id（必填）','default' => '' ),
                'order_num' => array('name' => 'order_num','type' => 'string','require' => true,'desc' => '成交编号（必填）','default' => '' ),
                'cphone' => array('name' => 'cphone','type' => 'string','require' => false,'desc' => '学员电话（非必填）','default' => '' ),
                'card_type' => array('name' => 'card_type','type' => 'string','require' => false,'desc' => '证件类型（非必填）','default' => '' ),
                'cart_note' => array('name' => 'cart_note','type' => 'string','require' => false,'desc' => '证件上传（必填）','default' => '' ),
                'card_num' => array('name' => 'card_num','type' => 'string','require' => false,'desc' => '证件编号（必填）','default' => '' ),
                'sos_name' => array('name' => 'sos_name','type' => 'string','require' => false,'desc' => '紧急联系人姓名（非必填）','default' => '' ),
                'guanxi' => array('name' => 'guanxi','type' => 'string','require' => false,'desc' => '关系（非必填）','default' => '' ),
                'sos_phone' => array('name' => 'sos_phone','type' => 'string','require' => false,'desc' => '紧急联系人电话（非必填）','default' => '' ),
                'xmid' => array('name' => 'xmid','type' => 'int','require' => false,'desc' => '项目id（非必填）','default' => '' ),
                'xm_name' => array('name' => 'xm_name','type' => 'string','require' => false,'desc' => '项目名称（非必填）','default' => '' ),
                'yuanxiao' => array('name' => 'yuanxiao','type' => 'string','require' => false,'desc' => '院校（非必填）','default' => '' ),
                'xueli' => array('name' => 'xueli','type' => 'string','require' => false,'desc' => '学历（非必填）','default' => '' ),
                'zhuanye' => array('name' => 'zhuanye','type' => 'string','require' => false,'desc' => '专业（非必填）','default' => '' ),
                'class_id' => array('name' => 'class_id','type' => 'int','require' => false,'desc' => '班级id（非必填）','default' => '' ),
                'class_name' => array('name' => 'class_name','type' => 'string','require' => false,'desc' => '班级名称（非必填）','default' => '' ),
                'class_teacher' => array('name' => 'class_teacher','type' => 'string','require' => true,'desc' => '班级老师id，隔开（必填）','default' => '' ),
                'class_teachername' => array('name' => 'class_teachername','type' => 'string','require' => true,'desc' => '班级老师姓名，隔开（必填）','default' => '' ),
                'jiaowu_teacher' => array('name' => 'jiaowu_teacher','type' => 'int','require' => true,'desc' => '教务老师id（必填）','default' => '' ),
                'jiaowu_teachername' => array('name' => 'jiaowu_teachername','type' => 'string','require' => true,'desc' => '教务老师姓名（必填）','default' => '' ),
                'ziliao' => array('name' => 'ziliao','type' => 'string','require' => false,'desc' => '资料（非必填）','default' => '' ),
                'student_id' => array('name' => 'student_id','type' => 'string','require' => false,'desc' => '学生证编号（非必填）','default' => '' ),
                'z_xm' => array('name' => 'z_xm','type' => 'string','require' => false,'desc' => '转介绍项目（非必填）','default' => '' ),
                'beizhu' => array('name' => 'beizhu','type' => 'string','require' => false,'desc' => '备注（非必填）','default' => '' ),
                'note' => array('name' => 'note','type' => 'string','require' => false,'desc' => '附件（非必填）','default' => '' ),
                'creat_name' => array('name' => 'creat_name','type' => 'string','require' => true,'desc' => '操作人姓名（必填）','default' => '' ),
            ),
            'PostEduStudentEdit'=>array(
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '当前列表id（必填）','default' => '' ),
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'cname' => array('name' => 'cname','type' => 'string','require' => true,'min' => 1,'desc' => '学员名称（必填）','default' => '' ),
                'cid' => array('name' => 'cid','type' => 'int','require' => true,'min' => 1,'desc' => '学员id（必填）','default' => '' ),
                'order_num' => array('name' => 'zhuti','type' => 'string','require' => true,'desc' => '成交编号（必填）','default' => '' ),
                'cphone' => array('name' => 'cphone','type' => 'string','require' => false,'desc' => '学员电话（非必填）','default' => '' ),
                'card_type' => array('name' => 'card_type','type' => 'string','require' => false,'desc' => '证件类型（非必填）','default' => '' ),
                'cart_note' => array('name' => 'cart_note','type' => 'string','require' => false,'desc' => '证件上传（必填）','default' => '' ),
                'card_num' => array('name' => 'card_num','type' => 'string','require' => false,'desc' => '证件编号（必填）','default' => '' ),
                'sos_name' => array('name' => 'sos_name','type' => 'string','require' => false,'desc' => '紧急联系人姓名（非必填）','default' => '' ),
                'guanxi' => array('name' => 'guanxi','type' => 'string','require' => false,'desc' => '关系（非必填）','default' => '' ),
                'sos_phone' => array('name' => 'sos_phone','type' => 'string','require' => false,'desc' => '紧急联系人电话（非必填）','default' => '' ),
                'xmid' => array('name' => 'xmid','type' => 'int','require' => false,'desc' => '项目id（非必填）','default' => '' ),
                'xm_name' => array('name' => 'xm_name','type' => 'string','require' => false,'desc' => '项目名称（非必填）','default' => '' ),
                'yuanxiao' => array('name' => 'yuanxiao','type' => 'string','require' => false,'desc' => '院校（非必填）','default' => '' ),
                'xueli' => array('name' => 'xueli','type' => 'string','require' => false,'desc' => '学历（非必填）','default' => '' ),
                'zhuanye' => array('name' => 'zhuanye','type' => 'string','require' => false,'desc' => '专业（非必填）','default' => '' ),
                'class_id' => array('name' => 'class_id','type' => 'int','require' => false,'desc' => '班级id（非必填）','default' => '' ),
                'class_name' => array('name' => 'class_name','type' => 'string','require' => false,'desc' => '班级名称（非必填）','default' => '' ),
                'class_teacher' => array('name' => 'class_teacher','type' => 'string','require' => true,'desc' => '班级老师id，隔开（必填）','default' => '' ),
                'class_teachername' => array('name' => 'class_teachername','type' => 'string','require' => true,'desc' => '班级老师姓名，隔开（必填）','default' => '' ),
                'jiaowu_teacher' => array('name' => 'jiaowu_teacher','type' => 'int','require' => true,'desc' => '教务老师id（必填）','default' => '' ),
                'jiaowu_teachername' => array('name' => 'jiaowu_teachername','type' => 'string','require' => true,'desc' => '教务老师姓名（必填）','default' => '' ),
                'ziliao' => array('name' => 'ziliao','type' => 'string','require' => false,'desc' => '资料（非必填）','default' => '' ),
                'student_id' => array('name' => 'student_id','type' => 'string','require' => false,'desc' => '学生证编号（非必填）','default' => '' ),
                'z_xm' => array('name' => 'z_xm','type' => 'string','require' => false,'desc' => '转介绍项目（非必填）','default' => '' ),
                'beizhu' => array('name' => 'beizhu','type' => 'string','require' => false,'desc' => '备注（非必填）','default' => '' ),
                'note' => array('name' => 'note','type' => 'string','require' => false,'desc' => '附件（非必填）','default' => '' ),
                'updatename' => array('name' => 'updatename','type' => 'string','require' => false,'desc' => '编辑人姓名（非必填）','default' => '' ),
            ),
            'PostEduStudentDele'=>array(
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '当前列表id（必填）','default' => '' ),
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
            ),
            'PostEduTeacherSalaryAdd'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'group_id' => array('name' => 'group_id','type' => 'int','require' => true,'min' => 1,'desc' => '部门id（必填）','default' => '' ),
                'edu_uid' => array('name' => 'edu_uid','type' => 'string','require' => true,'min' => 1,'desc' => '教师id（必填）','default' => '' ),
                'group_name' => array('name' => 'group_name','type' => 'string','require' => true,'min' => 1,'desc' => '部门名称（必填）','default' => '' ),
                'class_num' => array('name' => 'class_num','type' => 'string','require' => true,'min' => 1,'desc' => '上课编号（必填）','default' => '' ),
                'pay_time' => array('name' => 'pay_time','type' => 'string','require' => false,'desc' => '成交时间（非必填）默认今天','default' => '' ),
                'pay_note' => array('name' => 'pay_note','type' => 'string','require' => false,'desc' => '款项说明（非必填）','default' => '' ),
                'askforman_id' => array('name' => 'askforman_id','type' => 'string','require' => false,'desc' => '申请人（必填）','default' => '' ),
                'askforman_name' => array('name' => 'askforman_name','type' => 'string','require' => false,'desc' => '申请人姓名（必填）','default' => '' ),
                'pay_type' => array('name' => 'pay_type','type' => 'string','require' => false,'desc' => '付款方式（非必填）','default' => '' ),
                'pay_money' => array('name' => 'pay_money','type' => 'string','require' => false,'desc' => '薪酬金额（非必填）','default' => '' ),
                'beizhu' => array('name' => 'beizhu','type' => 'string','require' => false,'desc' => '备注（非必填）','default' => '' ),
                'note' => array('name' => 'note','type' => 'string','require' => false,'desc' => '附件（非必填）','default' => '' ),
                'open_bank' => array('name' => 'open_bank','type' => 'string','require' => false,'desc' => '开户银行（非必填）','default' => '' ),
                'open_bank_num' => array('name' => 'open_bank_num','type' => 'string','require' => false,'desc' => '开户银行账号（非必填）','default' => '' ),
                'create_name' => array('name' => 'create_name','type' => 'string','require' => true,'min' => 1,'desc' => '操作人姓名（必填）','default' => '' ),
            ),
            'PostEduTeacherSalaryEdit'=>array(
                'id' => array('name' => 'id','type' => 'int','require' => true,'min' => 1,'desc' => '当前列表id（必填）','default' => '' ),
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'group_id' => array('name' => 'group_id','type' => 'int','require' => true,'min' => 1,'desc' => '部门id（必填）','default' => '' ),
                'edu_uid' => array('name' => 'edu_uid','type' => 'string','require' => true,'min' => 1,'desc' => '教师id（必填）','default' => '' ),
                'group_name' => array('name' => 'group_name','type' => 'string','require' => true,'min' => 1,'desc' => '部门名称（必填）','default' => '' ),
                'class_num' => array('name' => 'class_num','type' => 'string','require' => true,'min' => 1,'desc' => '上课编号（必填）','default' => '' ),
                'pay_time' => array('name' => 'pay_time','type' => 'string','require' => false,'desc' => '成交时间（非必填）默认今天','default' => '' ),
                'pay_note' => array('name' => 'pay_note','type' => 'string','require' => false,'desc' => '款项说明（非必填）','default' => '' ),
                'askforman_id' => array('name' => 'askforman_id','type' => 'string','require' => false,'desc' => '申请人（必填）','default' => '' ),
                'askforman_name' => array('name' => 'askforman_name','type' => 'string','require' => false,'desc' => '申请人姓名（必填）','default' => '' ),
                'pay_type' => array('name' => 'pay_type','type' => 'string','require' => false,'desc' => '付款方式（非必填）','default' => '' ),
                'pay_money' => array('name' => 'pay_money','type' => 'string','require' => false,'desc' => '薪酬金额（非必填）','default' => '' ),
                'beizhu' => array('name' => 'beizhu','type' => 'string','require' => false,'desc' => '备注（非必填）','default' => '' ),
                'note' => array('name' => 'note','type' => 'string','require' => false,'desc' => '附件（非必填）','default' => '' ),
                'open_bank' => array('name' => 'open_bank','type' => 'string','require' => false,'desc' => '开户银行（非必填）','default' => '' ),
                'open_bank_num' => array('name' => 'open_bank_num','type' => 'string','require' => false,'desc' => '开户银行账号（非必填）','default' => '' ),
                'create_name' => array('name' => 'create_name','type' => 'string','require' => true,'min' => 1,'desc' => '操作人姓名（必填）','default' => '' ),
            ),
            'PostEduTeacherSalaryList'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'tid' => array('name' => 'tid','type' => 'int','require' => false,'desc' => '教务详情id（必填）','default' => '' ),
                'type' => array('name' => 'type','type' => 'int','require' => false,'desc' => '类型1总课酬2本日3本周4本月','default' => '1' ),
                'keywords' => array('name' => 'keywords', 'type' => 'string','require' => false, 'desc' => '关键词搜索（不必填）' ),
                'pageno' => array('name' => 'pageno', 'type' => 'int','min' => 1,'require' => false, 'desc' => '当前页码（不必填）' ),
                'pagesize' => array('name' => 'pagesize', 'type' => 'int','min' => 1,'require' => false, 'desc' => '条数限制（不必填）' ),
            ),
            "GetAllStatisticsList"=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'type' => array('name' => 'type','type' => 'int','require' => false,'desc' => '类型1客户2代理商3项目方','default' => '1' ),
                'date_sl' => array('name' => 'date_sl', 'type' => 'string','require' => false, 'desc' => '数量类型1本周2本月3本年', 'default'=>'1' ),
                'date_yj' => array('name' => 'date_yj', 'type' => 'string','require' => false,'desc' => '业绩类型0今天1本周2本月3本年4全部', 'default'=>'1' ),
            ),
            "GetCusFollowCensus"=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'type' => array('name' => 'type','type' => 'int','require' => false,'desc' => '类型1客户2跟进','default' => '1' ),
                'users' => array('name' => 'users','type' => 'array','require' => false,'desc' => '用户(组)','default' => '' ),
                'date_len' => array('name' => 'date_len', 'type' => 'int','require' => false, 'desc' => '数量类型1本周2本月3本年', 'default'=>'1' ),
                'date_section' => array('name' => 'date_section', 'type' => 'array','require' => false,'desc' => '区间类型开始时间first=>123,结束时间last=>123(时间戳)', 'default'=>'' ),
            ),
            'GetClassNum'=>array(
                'uid' => array('name' => 'uid','type' => 'int','require' => true,'min' => 1,'desc' => '当前用户id（必填）','default' => '' ),
                'id' => array('name' => 'id','type' => 'int','require' => true,'desc' => '教师id','default' => '1' ),

            ),
            'GetPhoneadress'=>array(
                'phone'=> array('name'=>'phone','type'=>'string','require'=>false,'desc'=>'' ),
            ),
        );
    }

    /**
     *  获取手机号
     * @author Dai Ming
     * @DateTime 11:50 2019/10/28
     * @desc    （未完成）（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function GetPhoneadress()
    {

        $adress=new Yesapiphpsdk();
        $data=$adress->request('App.Common_Phone.QueryLocation',array('phone'=>$this->phone));
        $newData=@$data['data']['info']['province'].$data['data']['info']['city'];
        $rs = array('code'=>1,'msg'=>'000000','data'=>$newData,'info'=>$newData);
        return $rs;
    }

    /**
     * 获取首页数据统计
     * @author Dai Ming
     * @DateTime 14:58 2019/10/16
     * @desc  获取首页数据统计（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function GetAllStatisticsList()
    {

        $domain = new Domain_Educational();
        $info = $domain->GetAllStatisticsList($this->uid,$this->type,$this->date_sl,$this->date_yj);
        return $info;
    }
    /**
     * 客户级别统计
     * @author Dai Ming
     * @DateTime 14:58 2020/03/20
     * @desc  客户级别统计（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function  GetCusFollowCensus()
    {
        $domain = new Domain_Educational();
        $info = $domain->GetCusFollowCensus($this->uid,$this->type,$this->users,$this->date_len,$this->date_section);
        return $info;
    }
    /**
     * 新建教务-新增
     * @desc 新建教务-新增接口（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostEduAdd() {
        $newData = array(
            'uid' =>$this->uid,
            'group_id' =>$this->group_id,
            'group_name' =>$this->group_name,
            'uname' =>$this->uname,
            'sex' =>$this->sex,
            'phone' =>$this->phone,
            'status'=>$this->status,
            'class_id' =>$this->class_id,
            'note'=>$this->note,
            'beizhu'=>$this->beizhu,
        );
        $domain = new Domain_Educational();
        $info = $domain->PostEduAdd($newData,$this->field_list);
        return $info;
    }

    /**
     * 教务编辑
     * @author Dai Ming
     * @DateTime 16:31 2019/9/10 0010
     * @desc 教务编辑  （待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function  PostEduEdit(){
        $newData = array(
            'uid' =>$this->uid,
            'group_id' =>$this->group_id,
            'group_name' =>$this->group_name,
            'uname' =>$this->uname,
            'sex' =>$this->sex,
            'phone' =>$this->phone,
            'status'=>$this->status,
            'class_id' =>$this->class_id,
            'note'=>$this->note,
            'beizhu'=>$this->beizhu,
        );
        $domain = new Domain_Educational();
        $info = $domain->PostEduEdit($this->id,$newData,$this->field_list);
        return $info;
    }

    /**
     * 教务列表(带查询)
     * @author Dai Ming
     * @DateTime 09:36 2019/10/10 0010
     * @desc  教务列表（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function GetEduList()
    {
        $domain = new Domain_Educational();
        $newData=array(
            'uid'=>$this->uid,
            'keywords'=>$this->keywords,
            'type'=>$this->type,
            'pageno'=>$this->pageno,
            'pagesize'=>$this->pagesize,
        );
        $info = $domain->GetEduList($newData);
        return $info;

    }

    /**
     * 教务/上课信息/学员信息/教师报酬 详情
     * @author Dai Ming
     * @DateTime 10:17 2019/10/11 0011
     * @desc  教务/上课信息/学员信息/教师报酬（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function GetEduInfo()
    {
        $domain = new Domain_Educational();
        $info = $domain->GetEduInfo($this->uid,$this->id,$this->type);
        return $info;
    }

    /**
     * 上课信息添加
     * @author Dai Ming
     * @DateTime 15:13 2019/10/11 0011
     * @desc 上课信息添加（待对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostEduClassAdd()
    {
        $Ticket=new  Domain_Educational();
        $info=$Ticket->PostEduClassAdd(\PhalApi\DI()->request->getAll());
        return $info;
    }


    /**
     * 上课信息编辑
     * @author Dai Ming
     * @DateTime 16:05 2019/10/11
     * @desc  上课信息编辑（未对接）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostEduClassEdit()
    {
        $Ticket=new  Domain_Educational();
        $info=$Ticket->PostEduClassEdit(\PhalApi\DI()->request->getAll());
        return $info;
    }

    /**
     *  上课信息删除
     * @author Dai Ming
     * @DateTime 10:44 2019/10/14
     * @desc  上课信息删除（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostEduClassDele()
    {
        $Ticket=new  Domain_Educational();
        $info=$Ticket->PostEduClassDele($this->uid,$this->id);
        return $info;
    }

    /**
     * 上课信息列表（带查询）
     * @author Dai Ming
     * @DateTime 10:54 2019/10/14
     * @desc  上课信息列表 （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostEduClassList()
    {
        $domain = new Domain_Educational();
        $newData=array(
            'uid'=>$this->uid,
            'tid'=>$this->tid,
            'keywords'=>$this->keywords,
            'type'=>$this->type,
            'pageno'=>$this->pageno,
            'pagesize'=>$this->pagesize,
        );
        $info = $domain->PostEduClassList($newData);
        return $info;
    }

    /**
     * 新建上课学员资料
     * @author Dai Ming
     * @DateTime 9:37 2019/10/12
     * @desc   新建上课学员资料（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostEduStudent()
    {
        $Ticket=new  Domain_Educational();
        $info=$Ticket->PostEduStudent(\PhalApi\DI()->request->getAll());
        return $info;
    }

    /**
     * 编辑上课学员资料
     * @author Dai Ming
     * @DateTime 9:53 2019/10/12
     * @desc    编辑上课学员资料（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostEduStudentEdit()
    {
        $Ticket=new  Domain_Educational();
        $info=$Ticket->PostEduStudentEdit(\PhalApi\DI()->request->getAll());
        return $info;

    }

    /**
     * 学员资料列表
     * @author Dai Ming
     * @DateTime 14:51 2019/10/14
     * @desc    （未完成）（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostEduStudentList()
    {
        $domain = new Domain_Educational();
        $newData=array(
            'uid'=>$this->uid,
            'keywords'=>$this->keywords,
            'type'=>$this->type,
            'pageno'=>$this->pageno,
            'pagesize'=>$this->pagesize,
        );
        $info = $domain->PostEduStudentList($newData);
        return $info;

    }


    /**
     * 教师薪资记录新增
     * @author Dai Ming
     * @DateTime 14:34 2019/10/12 0012
     * @desc 教师薪资记录新增（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostEduTeacherSalaryAdd()
    {
        $Ticket=new  Domain_Educational();
        $info=$Ticket->PostEduTeacherSalaryAdd(\PhalApi\DI()->request->getAll());
        return $info;
    }

    /**
     *  教师薪资记录编辑
     * @author Dai Ming
     * @DateTime 9:13 2019/10/15
     * @desc    （未完成）（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostEduTeacherSalaryEdit()
    {
        $Ticket=new  Domain_Educational();
        $info=$Ticket->PostEduTeacherSalaryEdit(\PhalApi\DI()->request->getAll());
        return $info;

    }


    /**
     *  教师薪资记录删除
     * @author Dai Ming
     * @DateTime 10:14 2019/10/16
     * @desc   教师薪资记录删除 （已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostEduTeacherSalaryDel()
    {
        $Ticket=new  Domain_Educational();
        $info=$Ticket->PostEduTeacherSalaryDel($this->uid,$this->id);
        return $info;
    }


    /**
     * 删除教务
     * @author Dai Ming
     * @DateTime 9:20 2019/10/14
     * @desc  删除教务（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostEduDele()
    {
        $Ticket=new  Domain_Educational();
        $info=$Ticket->PostEduDele($this->uid,$this->id);
        return $info;
    }

    /**
     * 教师薪资记录列表
     * @author Dai Ming
     * @DateTime 10:14 2019/10/16
     * @desc 教师薪资记录列表（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostEduTeacherSalaryList(){
        $domain = new Domain_Educational();
        $newData=array(
            'uid'=>$this->uid,
            'tid'=>$this->tid,
            'keywords'=>$this->keywords,
            'type'=>$this->type,
            'pageno'=>$this->pageno,
            'pagesize'=>$this->pagesize,
        );
        $info = $domain->PostEduTeacherSalaryList($newData);
        return $info;
    }

    /**
     * 上课编号列表
     * @author Dai Ming
     * @DateTime 11:21 2019/10/17
     * @desc   上课编号列表（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function GetClassNum()
    {
        $domain = new Domain_Educational();

        $info = $domain->GetClassNum($this->uid,$this->id);
        return $info;
    }

    /**
     * 删除学员资料
     * @author Dai Ming
     * @DateTime 15:28 2019/10/21 0021
     * @desc   删除学员资料（已完成）
     * @return int ret 返回接口状态码，其中：200成功，400非法请求，500服务器错误
     * @return int code 操作码，1表示成功，其他都表示失败
     * @return array data 接口请求成功以后返回的数据
     *
     * @return string msg 提示信息
     */
    public function PostEduStudentDele()
    {
        $domain = new Domain_Educational();

        $info = $domain->PostEduStudentDele($this->uid,$this->id);
        return $info;
    }


}
