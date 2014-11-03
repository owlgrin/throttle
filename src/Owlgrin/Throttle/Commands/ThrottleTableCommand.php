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

		return str_replace('plans', Config::get('throttle::tables.plans'), $stub);
		return str_replace('features', Config::get('throttle::tables.features'), $stub);
		return str_replace('plan_feature', Config::get('throttle::tables.plan_feature'), $stub);
		return str_replace('subscriptions', Config::get('throttle::tables.subscriptions'), $stub);
		return str_replace('user_feature_usage', Config::get('throttle::tables.user_feature_usage'), $stub);
		return str_replace('user_feature_limit', Config::get('throttle::tables.user_feature_limit'), $stub);
		return str_replace('packs', Config::get('throttle::tables.packs'), $stub);
		return str_replace('user_packs', Config::get('throttle::tables.user_packs'), $stub);
	}
}