<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
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
		$userId = $this->option('user');
		$feature = $this->option('feature');
		$value = $this->option('value');
		
		if(is_null($userId))
		{
			$this->error("Should add userid as option");
		}		

		if(is_null($feature))
		{
			$this->error("Should add feature name as option");
		}

		if(is_null($value))
		{
			$this->error("Should add value as option");
		}

		if(! is_null($userId) and ! is_null($feature) and ! is_null($value))
		{
			$subscription = $this->subscriptionRepo->subscription($userId);

			$this->subscriptionRepo->incrementLimit($subscription['id'], $feature, $value);

			$this->info('User With id '.$userId.' has incremented the usage with value:'. $value);
		}
	}

	protected function getOptions()
	{
		return array(
			array('user', null, InputOption::VALUE_REQUIRED, 'The id of the user whose feature\'s limit to change', null),
			array('feature', null, InputOption::VALUE_REQUIRED, 'The name of feature identifier whose limit to increase', null),
			array('value', null, InputOption::VALUE_REQUIRED, 'The value of the feature to increase', null)
		);
	}
}