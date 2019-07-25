<?php

namespace Latrell\Ethereum;

use Curl\Curl;
use kornrunner\Keccak;
use Latrell\Ethereum\Exception as EthereumException;
use Sop\CryptoEncoding\PEM;
use Sop\CryptoTypes\Asymmetric\EC\ECPrivateKey;

class Ethereum
{
	protected $api_key, $rpc_url;

	public function __construct($api_key, $rpc_url)
	{
		$this->api_key = $api_key;
		$this->rpc_url = $rpc_url;
	}

	/**
	 * 生成一个新的钱包地址及私钥
	 *
	 * @return Wallet
	 * @throws \Latrell\Ethereum\Exception
	 */
	public function generate()
	{
		$config = [
			'private_key_type' => OPENSSL_KEYTYPE_EC,
			'curve_name' => 'secp256k1'
		];
		$res = openssl_pkey_new($config);
		if (! $res) {
			throw new EthereumException('ERROR: Fail to generate private key. -> ' . openssl_error_string());
		}
		// 生成私钥
		openssl_pkey_export($res, $priv_key);
		// 获取公钥
		$key_detail = openssl_pkey_get_details($res);
		$priv_pem = PEM::fromString($priv_key);
		// 转换为椭圆曲线私钥格式
		$ec_priv_key = ECPrivateKey::fromPEM($priv_pem);
		// 然后将其转换为ASN1结构
		$ec_priv_seq = $ec_priv_key->toASN1();
		// HEX中的私钥和公钥
		$priv_key_hex = bin2hex($ec_priv_seq->at(1)->asOctetString()->string());
		$pub_key_hex = bin2hex($ec_priv_seq->at(3)->asTagged()->asExplicit()->asBitString()->string());
		// 从公钥导出以太坊地址
		// 每个EC公钥始终以0x04开头，
		// 我们需要删除前导0x04才能正确hash它
		$pub_key_hex_2 = substr($pub_key_hex, 2);
		// Hash
		$hash = Keccak::hash(hex2bin($pub_key_hex_2), 256);
		// 以太坊地址长度为20个字节。 （40个十六进制字符长）
		// 我们只需要最后20个字节作为以太坊地址
		$wallet_address = '0x' . substr($hash, -40);
		$wallet_private_key = '0x' . $priv_key_hex;
		// 返回钱包地址及私钥
		$wallet = new Wallet;
		$wallet->address = $wallet_address;
		$wallet->private_key = $wallet_private_key;
		return $wallet;
	}

	/**
	 * 取得地址余额
	 *
	 * @param string $address 要查询的地址
	 *
	 * @return number 余额
	 * @throws \Latrell\Ethereum\Exception
	 */
	public function getBalance($address)
	{
		$amount = $this->request('balance', [
			'address' => $address,
			'tag' => 'latest'
		]);
		return $this->toEth($amount);
	}

	/**
	 * 转换到十进制ETH单位
	 *
	 * @param string $value 十进制或十六进制数值
	 *
	 * @return float 十进制浮点数
	 */
	public function toEth($value)
	{
		if (preg_match('/^0x/', $value)) {
			$value = $this->toDec($value);
		}
		return bcdiv($value, '1000000000000000000', 18);
	}

	/**
	 * 转换到十进制Wei单位
	 *
	 * @param float $value
	 *
	 * @return string 十进制大数
	 */
	public function toWei($value)
	{
		return bcmul((string) $value, '1000000000000000000');
	}

	/**
	 * 转换十六进制到十进制文本。
	 *
	 * @param string $value 十六进制文本
	 *
	 * @return string 十进制文本
	 */
	protected function toDec($value)
	{
		return Tool::bchexdec($value);
	}

	/**
	 * 转换十进制到十六进制文本。
	 *
	 * @param string $value 十进制文本
	 *
	 * @return string 十六进制文本
	 */
	protected function toHex($value)
	{
		return '0x' . Tool::bcdechex($value);
	}

	/**
	 * 向节点服务器发送请求
	 *
	 * @throws \Latrell\Ethereum\Exception
	 */
	protected function request($method, $params = [])
	{
		// 不同的操作请求不同的节点。
		$module = '';
		if (! preg_match('/^eth_/', $method)) {
			$module = 'account';
		} elseif (in_array($method, [
			'eth_gasPrice',
			'eth_getTransactionCount',
			'eth_sendRawTransaction',
		])) {
			$module = 'proxy';
		}

		$curl = new Curl();
		$curl->setOpt(CURLOPT_TIMEOUT, 10);
		$curl->setHeader('Content-Type', 'application/json');
		if ($module) {
			$curl->get('https://api.etherscan.io/api', array_merge([
				'module' => $module,
				'action' => $method,
				'apikey' => $this->api_key,
			], $params));
		} else {
			$curl->post($this->rpc_url, json_encode([
				'jsonrpc' => '2.0',
				'method' => $method,
				'params' => array_values($params),
				'id' => 1
			]));
		}
		$curl->close();
		if ($curl->error) {
			throw new EthereumException($curl->error_message, $curl->error_code);
		}
		$json = json_decode($curl->response);
		if (isset($json->error)) {
			throw new EthereumException($json->error->message, $json->error->code);
		}
		if (! isset($json->result)) {
			throw new EthereumException('Unexpected data structure: ' . $curl->response);
		}
		return $json->result;
	}
}
