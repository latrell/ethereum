<?php

namespace Latrell\Ethereum;

class Tool
{
	/**
	 * 大数十六进制转十进制
	 *
	 * @param string $hex
	 *
	 * @return string
	 */
	public static function bchexdec($hex)
	{
		if (strlen($hex) == 1) {
			return hexdec($hex);
		} else {
			$remain = substr($hex, 0, -1);
			$last = substr($hex, -1);
			return bcadd(bcmul(16, Tool::bchexdec($remain)), hexdec($last));
		}
	}

	/**
	 * 大数十进制转十六进制
	 *
	 * @param string $dec
	 *
	 * @return string
	 */
	public static function bcdechex($dec)
	{
		$last = bcmod($dec, 16);
		$remain = bcdiv(bcsub($dec, $last), 16);

		if ($remain == 0) {
			return dechex($last);
		} else {
			return Tool::bcdechex($remain) . dechex($last);
		}
	}
}