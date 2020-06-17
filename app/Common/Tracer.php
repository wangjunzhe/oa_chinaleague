<?php
namespace App\Common;
//编辑客户
class Tracer extends \PhalApi\Helper\Tracer {

    public function sql($statement) {
        parent::sql($statement);

        // TODO：进行更多操作
    }

}