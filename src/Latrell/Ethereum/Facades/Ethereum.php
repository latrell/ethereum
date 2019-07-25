<?php

namespace Latrell\Ethereum\Facades;

use Illuminate\Support\Facades\Facade;

class Ethereum extends Facade
{

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor()
	{
		return 'ethereum';
	}
}