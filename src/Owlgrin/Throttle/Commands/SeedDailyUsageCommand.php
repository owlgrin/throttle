<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

use Owlgrin\Throttle\Subscriber\SubscriberRepo;
/**
 * Command to generate the required migration
 */
class SeedDailyUsageCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'throttle:seed:daily:usage';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Seed Daily Usage';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	protected $subscriber;

	public function __construct(SubscriberRepo $subscriber)
	{
 		parent::__construct();

 		$this->subscriber = $subscriber;
	}

	public function fire()
	{
		$userId = $this->argument('user');

		$subscription = $this->subscriber->subscription($userId);

		$this->subscriber->addInitialUsageForFeatures($subscription['id'], $subscription['plan_id']);

		$this->info('User with subscription id '. $subscription['id'] .' has been has been seed with planID '. $subscription['plan_id']);
	}
	

	protected function getArguments()
	{
		return array(
			array('user', InputArgument::REQUIRED, 'The user\'s id. whose usage you want to seed')
		);
	}
}