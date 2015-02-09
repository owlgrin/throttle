<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Owlgrin\Throttle\Subscriber\SubscriberRepo;
use Owlgrin\Throttle\Period\PeriodInterface;
use Owlgrin\Throttle\Period\NextableInterface;
use Owlgrin\Throttle\Period\ManualPeriod;
use Owlgrin\Throttle\Exceptions;

use Throttle;
use Carbon\Carbon, Config, App;

/**
 * Command to generate the required migration
 */
class AddSubscriptionPeriodForUserCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'throttle:add-subscription-period';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Add new subscription period for active users subscription';

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

	public function __construct(SubscriberRepo $subscriptionRepo)
	{
 		parent::__construct();

		$this->subscriptionRepo = $subscriptionRepo;
	}

	public function fire()
	{
		$this->info('Starting at.. ' . date('Y-m-d H:i:s'));

		$users = $this->getUsers();
		
		foreach($users as $index => $user) 
		{	
			Throttle::user($user);

			if($this->isRequiredToUpdatePeriod(Throttle::getPeriod()))
			{
				$this->updateSubscriptionPeriod($user, Throttle::getPeriod());
			}
		}

		$this->info('Ending at.. ' . date('Y-m-d H:i:s'));
	}

	protected function getUsers()
	{
		if( ! is_null($userId = $this->option('user')))
		{
			return [$userId];
		}

		return $this->subscriptionRepo->getAllUserIds();
	}

	protected function updateSubscriptionPeriod($user, NextableInterface $period)
	{
		// get next period
		$next = $period->next();

		// next period should be an instance of PeriodInterface
		if( ! $next instanceof PeriodInterface)
		{
			$this->error('Next period is not an instance of PeriodInterface');
			throw new \Exception('Next period is not an instance of PeriodInterface');
		}

		$this->info('Updating user with ID ' . $user . ' subscription period to ' . $next->start() . ' - ' . $next->end());
		
		try
		{
			Throttle::addPeriod($next);
		}
		catch(Exceptions\InvalidInputException $e)
		{
			$this->info("Period is already added for user " .$user);
		}
	}

	protected function isRequiredToUpdatePeriod(PeriodInterface $period)
	{
		$periodEnd = Carbon::createFromFormat('Y-m-d', $period->end())->endOfDay();
		$today = Carbon::today()->endOfDay();
		
		if($periodEnd->lte($today)) return true;

		return false;		
	}
	
	protected function getOptions()
	{
		return array(
			array('user', null, InputOption::VALUE_OPTIONAL, 'User whose subscription period to be updated', null)
		);
	}
}