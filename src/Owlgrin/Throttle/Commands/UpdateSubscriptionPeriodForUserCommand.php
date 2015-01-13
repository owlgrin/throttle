<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Owlgrin\Throttle\Subscriber\SubscriberRepo;
use Owlgrin\Throttle\Period\CurrentMonthPeriod;
use Throttle;
use Carbon\Carbon;

/**
 * Command to generate the required migration
 */
class UpdateSubscriptionPeriodForUserCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'throttle:update-subscription-period';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Update the subscription period of active users subscription';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */

	/**
	 * The subscription repo
	 *
	 * @var Owlgrin\Throttle\Subscriber\SubscriberRepo
	 */
	protected $subscriptionRepo;

	/**
	 * The current month period
	 *
	 * @var Owlgrin\Throttle\Period\CurrentMonthPeriod
	 */
	protected $currentMonthPeriod;

	public function __construct(SubscriberRepo $subscriptionRepo, CurrentMonthPeriod $currentMonthPeriod)
	{
 		parent::__construct();

		$this->subscriptionRepo = $subscriptionRepo;
		$this->currentMonthPeriod = $currentMonthPeriod;
	}

	public function fire()
	{
		$this->info('Starting at.. ' . date('Y-m-d H:i:s'));

		$users = $this->subscriptionRepo->getAllUserIds();
		
		foreach($users as $index => $user) 
		{
			Throttle::user($user);

			$period = Throttle::getPeriod();

			if( ! is_null($period))
			{
				$periodEnd = Carbon::createFromFormat('Y-m-d', $period['ends_at'])->endOfDay();
				$today = Carbon::today()->endOfDay();
				
				if($periodEnd->lte($today))
				{
					$this->info('Updating user with ID ' . $user . ' subscription period to ' . $this->currentMonthPeriod->start(true) . ' - ' . $this->currentMonthPeriod->end(true));

					Throttle::addPeriod($this->currentMonthPeriod);
				}
			}
		}

		$this->info('Ending at.. ' . date('Y-m-d H:i:s'));
	}
	
	protected function getOptions()
	{
		return array(
		// 	array('user', null, InputOption::VALUE_OPTIONAL, 'User whose subscription period to be updated', null),
		// 	array('start_date', null, InputOption::VALUE_OPTIONAL, 'Start of period (Y-m-d format).', null),
		// 	array('end_date', null, InputOption::VALUE_OPTIONAL, 'End of period (Y-m-d format).', null)
		);
	}
}