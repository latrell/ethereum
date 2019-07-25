<?php
namespace Latrell\Ethereum\Console;

use Illuminate\Console\Command;

class GenerateCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'ethereum:generate';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = '生成新的钱包地址及私钥。';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function handle()
	{
		$wallet = app('ethereum')->generate();
		$this->info("Address:\t{$wallet->address}\nPrivate key:\t{$wallet->private_key}");
	}
}
