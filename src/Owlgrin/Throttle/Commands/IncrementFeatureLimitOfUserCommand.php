<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

use Owlgrin\Throttle\Subscriber\SubscriberRepo;
use Throttle;

/**
 * Command to generate the required migration
 */
class IncrementFeatureLimitOfUserCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'throttle:limit:increment';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Increment\'s limit of the user';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	protected $subscriptionRepo;

	public function __construct(SubscriberRepo $subscriptionRepo)
	{
 		parent::__construct();

 		$this->subscriptionRepo = $subscriptionRepo;
	}

	public function fire()
	{
		$userId = $this->argument('user');
		$feature = $this->argument('feature');
		$value = $this->argument('value');

		$subscription = $this->subscriptionRepo->subscription($userId);

		$this->subscriptionRepo->incrementLimit($subscription['id'], $feature, $value);

		$this->info('User With id '.$userId.' has incremented the usage with value:'. $value);
	}

	protected function getArguments()
	{
		return array(
			array('user', InputArgument::REQUIRED, 'The id of the user whose feature\'s limit to change'),
			array('feature', InputArgument::REQUIRED, 'The name of feature identifier whose limit to increase'),
			array('value', InputArgument::REQUIRED, 'The value of the feature to increase')
		);
	}
}