<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Config;

/**
 * Command to generate the required migration
 */
class ThrottleTableCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'throttle:table';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a migration for the throttle database table';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$path = $this->createBaseMigration();

		file_put_contents($path, $this->getMigrationStub());

		$this->info('Migration created successfully!');

		$this->call('dump-autoload');
	}

	/**
	 * Creates the base file for migration o reside into
	 * @return Migration
	 */
	protected function createBaseMigration()
	{
		$name = 'create_throttle_table';

		$path = $this->laravel['path'].'/database/migrations';

		return $this->laravel['migration.creator']->create($name, $path);
	}

	/**
	 * Get the contents of the reminder migration stub.
	 *
	 * @return string
	 */
	protected function getMigrationStub()
	{
		$stub = file_get_contents(__DIR__.'/../../../stubs/migration.stub');

		$stub = str_replace('plans', Config::get('throttle::tables.plans'), $stub);
		$stub = str_replace('features', Config::get('throttle::tables.features'), $stub);
		$stub = str_replace('plan_feature', Config::get('throttle::tables.plan_feature'), $stub);
		$stub = str_replace('subscriptions', Config::get('throttle::tables.subscriptions'), $stub);
		$stub = str_replace('user_feature_usage', Config::get('throttle::tables.user_feature_usage'), $stub);
		$stub = str_replace('user_feature_limit', Config::get('throttle::tables.user_feature_limit'), $stub);
		$stub = str_replace('packs', Config::get('throttle::tables.packs'), $stub);
		$stub = str_replace('user_pack', Config::get('throttle::tables.user_pack'), $stub);
		$stub = str_replace('subscription_period', Config::get('throttle::tables.subscription_period'), $stub);

		return $stub;
	}
}