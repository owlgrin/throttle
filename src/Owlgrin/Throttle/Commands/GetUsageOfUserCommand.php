<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Throttle;

/**
 * Command to generate the required migration
 */
class GetUsageOfUserCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'throttle:usage';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Find\'s usage of the user';

	/**
	 * Subscriber Repo.
	 *
	 * @var object
	 */
	protected $subscriptionRepo;

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */

	public function __construct(SubscriberRepo $subscriptionRepo)
	{
 		parent::__construct();

 		$this->subscriptionRepo = $subscriptionRepo;
	}

	public function fire()
	{
		$userId = $this->option('user');

		// $subscription = $this->subscriptionRepo->subscription($userId);

		// $period = new ActiveSubscriptionPeriod($userId);

		$usages = Throttle::user($userId)->getUsage();

		$this->info('User With id '.$userId.' has a usages of');
		print_r($usages);
	}

	protected function getOptions()
	{
		return array(
			array('user', null, InputOption::VALUE_OPTIONAL, 'The id of the user whose usage to show', null)
		);
	}
}