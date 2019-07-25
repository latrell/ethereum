<?php

namespace Latrell\Ethereum;

use Illuminate\Support\ServiceProvider;
use Latrell\Ethereum\Console\GenerateCommand;

class EthereumServiceProvider extends ServiceProvider
{
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @deprecated Implement the \Illuminate\Contracts\Support\DeferrableProvider interface instead. Will be removed in Laravel 5.9.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Bootstrap services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([
			__DIR__ . '/../../../config/config.php' => config_path('ethereum.php')
		]);
	}

	/**
	 * Register services.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->mergeConfigFrom(__DIR__ . '/../../../config/config.php', 'ethereum');

		$this->app->singleton('ethereum', function ($app) {
			$api_key = $app->config->get('ethereum.api_key');
			$rpc_url = $app->config->get('ethereum.rpc.url');
			return new Ethereum($api_key, $rpc_url);
		});

		$this->registerCommands();
	}

	/**
	 * Register the lock related console commands.
	 *
	 * @return void
	 */
	public function registerCommands()
	{
		$this->app->singleton('command.ethereum.generate', function () {
			return new GenerateCommand();
		});

		$this->commands('command.ethereum.generate');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [
			'ethereum',
			'command.ethereum.generate',
		];
	}
}
