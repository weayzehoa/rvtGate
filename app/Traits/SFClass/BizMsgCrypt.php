<?php
namespace App\Traits\SFClass;

/**
 * 顺丰国际消息对接加解密
 */

use App\Traits\SFClass\SHA2;
use App\Traits\SFClass\PKCS7Encoder;
use App\Traits\SFClass\ErrorCode;
use App\Traits\SFClass\PrpCrypt;

/**
 * 1.第三方系统发送加密的消息；
 * 2.第三方系统解密收到的消息并对消息合法性进行校验。
 */
class BizMsgCrypt
{
	private $token;
    private $encodingAesKey;
    private $appId;

	/**
	 * 构造函数
	 * @param $token string 提供给开发者的token
	 * @param $encodingAesKey string 提供给开发者的EncodingAESKey
	 * @param $appId string 对接appId
	 */
	public function __construct($token, $encodingAesKey, $appId)
	{
		$this->token = $token;
        if (strlen($encodingAesKey) != 43) {
            return ErrorCode::$IllegalAesKey;
        }
        $this->encodingAesKey = $encodingAesKey;
		$this->appId = $appId;
	}

	/**
	 * 消息加密打包.
	 *
	 * @param $replyMsg string 返回用户的消息，String字符串
	 * @param $timeStamp string 时间戳，可以自己生成，默认取当前时间戳
	 * @param $nonce string 随机串，可以自己生成
	 *
	 * @return array 成功返回数组,失败返回非0状态码
	 */
	public function encryptMsg($replyMsg, $timeStamp, $nonce)
	{
		$pc = new PrpCrypt($this->encodingAesKey);

		$array = $pc->encrypt($replyMsg, $this->appId);
		$ret = $array[0];
		if ($ret != 0) {
			return $ret;
		}

		if ($timeStamp == null) {
			$timeStamp = time();
		}
		$encrypt = $array[1];

		$sha1 = new SHA2;
		$array = $sha1->getSHA2($this->token, $timeStamp, $nonce, $encrypt);
		$ret = $array[0];
		if ($ret != 0) {
			return $ret;
		}
		$signature = $array[1];

        return array(
            "encrypt"=>$encrypt,
            "signature"=>$signature,
            "timeStamp"=>$timeStamp,
            "nonce"=>$nonce
        );
	}


	/**
	 * 检验消息的真实性，并且获取解密后的明文.
	 *
     * @param $timestamp string 时间戳 对应URL参数的timestamp
	 * @param $nonce string 随机串，对应URL参数的nonce
	 * @param $encrypt string 密文，对应POST请求的数据
	 *
	 * @return array 成功0，失败返回对应的错误码
	 */
	public function decryptMsg($timestamp = null, $nonce, $encrypt)
	{

		$pc = new Prpcrypt($this->encodingAesKey);

		if ($timestamp == null) {
			$timestamp = time();
		}

		//验证安全签名
		$sha1 = new SHA2;
		$array = $sha1->getSHA2($this->token, $timestamp, $nonce, $encrypt);
		$ret = $array[0];

		if ($ret != 0) {
			return $ret;
		}

        return $pc->decrypt($encrypt, $this->appId);
	}
}
