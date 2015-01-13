<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Owlgrin\Throttle\Subscriber\SubscriberRepo;
use Throttle;

/**
 * Command to generate the required migration
 */
class IncrementLimitOfUserCommand extends Command {

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
	protected $subscription;

	public function __construct(SubscriberRepo $subscription)
	{
 		parent::__construct();
 		$this->subscription = $subscription;
	}

	public function fire()
	{
		$userId = $this->option('user');
		$feature = $this->option('feature');
		$value = $this->option('value');
	
		$this->subscription->incrementLimit($userId, $feature, $value);

		$this->info('User With id '.$userId.' has incremented the usage with value:'. $value);
	}

	protected function getOptions()
	{
		return array(
			array('user', null, InputOption::VALUE_OPTIONAL, 'The id of the user whose feature\'s limit to change', null),
			array('feature', null, InputOption::VALUE_OPTIONAL, 'The name of feature identifier whose limit to increase', null),
			array('value', null, InputOption::VALUE_OPTIONAL, 'The value of the feature to increase', null)
		);
	}
}