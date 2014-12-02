<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Owlgrin\Throttle\Subscriber\SubscriberRepo;
use Owlgrin\Throttle\Period\ThrottlePeriod;
use Owlgrin\Throttle\Pack\PackRepo;
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
	protected $pack;
	protected $period;

	public function __construct(SubscriberRepo $subscriber, ThrottlePeriod $period, PackRepo $pack)
	{
 		parent::__construct();

 		$this->subscriber = $subscriber;
 		$this->period = $period;
 		$this->pack = $pack;
	}

	public function fire()
	{
		$userId = $this->option('user_id');

		$subscription = $this->subscriber->subscription($userId);
		
			// if($this->period->isNewPeriod())
			// {
				$this->pack->seedPackForNewPeriod($subscription['subscription_id'], new ThrottlePeriod );
			// }

			$this->subscriber->addInitialUsageForFeatures($subscription['subscription_id'], $subscription['plan_id']);

			$this->info('User With subscription id '. $subscription['subscription_id'] .' has been has been seed with planID '. $subscription['plan_id']);
	
	}
	
	protected function getOptions()
	{
		return array(
			array('user_id', null, InputOption::VALUE_OPTIONAL, 'The user\'s id.', null),
		);
	}
}