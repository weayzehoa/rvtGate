<?php
namespace App\Traits\SFClass;

use Exception;
use App\Traits\SFClass\ErrorCode;

/**
 * sha256 class
 *
 * 消息签名方法.
 */
class SHA2
{
	/**
	 * 用sha256算法生成安全签名
	 * @param string $token 票据
	 * @param string $timestamp 时间戳
	 * @param string $nonce 随机字符串
	 * @param string $encrypt_msg 密文消息
	 */
	public function getSHA2($token, $timestamp, $nonce, $encrypt_msg)
	{
		try {
			$array = array($encrypt_msg, $token, $timestamp, $nonce);
			sort($array, SORT_STRING);
			$str = implode($array);
			return array(ErrorCode::$OK, hash('sha256', $str));
		} catch (Exception $e) {
			return array(ErrorCode::$ComputeSignatureError, null);
		}
	}
}
