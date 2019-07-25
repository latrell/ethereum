<?php
return [

	/**
	 * @link https://etherscan.io/myapikey
	 */
	'api_key' => env('ETHEREUM_API_KEY', 'YourApiKeyToken'),

	'rpc' => [
		'url' => env('ETHEREUM_RPC_URL', 'http://localhost:8545'),
	]
];