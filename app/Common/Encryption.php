<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/4/8 0008
 * Time: 09:51
 */
namespace App\Common;
use App\Common\Admin as AdminCommon;
use PhalApi\Model\NotORMModel as NotORM;

/**
 * 客户模块公共处理函数
 */
class Encryption extends NotORM
{
    function keyED($txt, $encrypt_key)
    {
        $encrypt_key = md5($encrypt_key);
        $ctr = 0;
        $tmp = "";
        for ($i = 0; $i < strlen($txt); $i++) {
            if ($ctr == strlen($encrypt_key))
                $ctr = 0;
            $tmp .= substr($txt, $i, 1) ^ substr($encrypt_key, $ctr, 1);
            $ctr++;
        }
        return $tmp;
    }

    function encrypt($txt, $key)
    {
        $encrypt_key = md5(mt_rand(0, 100));
        $ctr = 0;
        $tmp = "";
        for ($i = 0; $i < strlen($txt); $i++) {
            if ($ctr == strlen($encrypt_key))
                $ctr = 0;
            $tmp .= substr($encrypt_key, $ctr, 1) . (substr($txt, $i, 1) ^ substr($encrypt_key, $ctr, 1));
            $ctr++;
        }
        return  $this->keyED($tmp, $key);
    }

    function decrypt($txt, $key)
    {
        $txt = $this->keyED($txt, $key);
        $tmp = "";
        for ($i = 0; $i < strlen($txt); $i++) {
            $md5 = substr($txt, $i, 1);
            $i++;
            $tmp .= (substr($txt, $i, 1) ^ $md5);
        }
        return $tmp;
    }

    function encrypt_url($url, $key)
    {
        return rawurlencode(base64_encode($this->encrypt($url, $key)));
    }

    function decrypt_url($url, $key)
    {
        return $this->decrypt(base64_decode(rawurldecode($url)), $key);
    }

    function geturl($str, $key)
    {
        $str =$this->decrypt_url($str, $key);
        $url_array = explode('&', $str);
        if (is_array($url_array)) {
            foreach ($url_array as $var) {
                $var_array = explode("=", $var);
                $vars[$var_array[0]] = $var_array[1];
            }
        }
        return $vars;
    }
}