<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Owlgrin\Throttle\Subscriber\SubscriberRepo;
use Throttle;
use Carbon\Carbon, Config, App;

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
			if($this->isRequiredToUpdatePeriod($user))
			{
				$this->period = App::make(Config::get('throttle::period_class'), ['user' => $user]);			
				
				$this->info('Updating user with ID ' . $user . ' subscription period to ' . $this->period->start(true) . ' - ' . $this->period->end(true));

				Throttle::addPeriod($this->period);
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

	protected function isRequiredToUpdatePeriod($user)
	{		
		$periodEnd = Carbon::createFromFormat('Y-m-d', Throttle::user($user)->getPeriod()->end())->endOfDay();
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