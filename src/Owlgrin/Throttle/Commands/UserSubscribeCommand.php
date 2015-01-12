<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Owlgrin\Throttle\Subscriber\SubscriberRepo;
use Owlgrin\Throttle\Period\PeriodRepo;
use Owlgrin\Throttle\Period\CurrentSubscriptionPeriod;

use Config;

/**
 * Command to generate the required migration
 */
class UserSubscribeCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'throttle:subscribe';
	protected $periodRepo;	

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Subscribes the user to a plan';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	protected $subscription;

	public function __construct(SubscriberRepo $subscription, PeriodRepo $periodRepo, CurrentSubscriptionPeriod $period)
	{
 		parent::__construct();
		$this->subscription  = $subscription;
		$this->periodRepo = $periodRepo;
		$this->period = $period;
	}

	public function fire()
	{
		$userId = $this->option('user');
		$planIdentifier = $this->option('plan');

		$subscriptionId = $this->subscription->subscribe($userId, $planIdentifier);

		$this->periodRepo->store($subscriptionId, $this->period->start(), $this->period->end());
		
		$this->info('User With id '.$userId.' is subscribed to plan with identifier '.$planIdentifier);
	}

	protected function getOptions()
	{
		return array(
			array('user', null, InputOption::VALUE_OPTIONAL, 'The id of the user who wants to subscribe', null),
			array('plan', null, InputOption::VALUE_OPTIONAL, 'The plan identifier for which user wants to subscribe.', null),
		);
	}
}