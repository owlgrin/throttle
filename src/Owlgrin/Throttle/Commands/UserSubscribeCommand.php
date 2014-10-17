<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Owlgrin\Throttle\Subscriber\SubscriberRepo;
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
	protected $name = 'user:subscribe';

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

	public function __construct(SubscriberRepo $subscription)
	{
 		parent::__construct();
		$this->subscription  = $subscription;
	}

	public function fire()
	{
		$userId = $this->option('user_id');
		$planId = $this->option('plan_id');
		
		$this->subscription->subscribe($userId, $planId);
		
		$this->info('User With id '.$userId.' is subscribed to plan with id '.$planId);
	}

	protected function getOptions()
	{
		return array(
			array('user_id', null, InputOption::VALUE_OPTIONAL, 'The id of the user who wants to subscribe', null),
			array('plan_id', null, InputOption::VALUE_OPTIONAL, 'The plan for which user wants to subscribe.', null),
		);
	}
}