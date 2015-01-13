<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Owlgrin\Throttle\Subscriber\SubscriberRepo;
use Owlgrin\Throttle\Usage\UsageRepo;
use Carbon\Carbon;
use Throttle;

/**
 * Command to generate the required migration
 */
class SeedDailyBaseUsageCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'throttle:seed-base-usage';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Seed Daily Base Usage';

	/**
	 * Usage Repo.
	 *
	 * Owlgrin\Throttle\Subscriber\SubscriberRepo;
	 * 
	 * @var object
	 * 
	 */
	protected $subscriptionRepo;

	/**
	 * Usage Repo.
	 *
	 * Owlgrin\Throttle\Usage\UsageRepo;
	 * 
	 * @var object
	 * 
	 */
	protected $usageRepo;

	public function __construct(UsageRepo $usageRepo, SubscriberRepo $subscriptionRepo)
	{
 		parent::__construct();

 		$this->usageRepo = $usageRepo;
 		$this->subscriptionRepo = $subscriptionRepo;
	}

	public function fire()
	{
		$this->info('Starting at.. ' . date('Y-m-d H:i:s'));
		
		$date = $this->option('date');
		$userId = $this->option('user');

		$subscriptions = $this->getSubscriptions($userId);

		$usages = $this->usageRepo->getBaseUsages($userId, $subscriptions, $date);

		$this->subscriptionRepo->seedPreparedUsages($usages);
		
		$this->info('Starting at.. ' . date('Y-m-d H:i:s'));
	}

	protected function getSubscriptions($userId)
	{
		// if user explicitly passed, we will return that only
		if( ! is_null($userId))
		{
			return [$this->subscriptionRepo->subscription($userId)];
		}

		return $this->subscriptionRepo->all();
	}

	protected function getOptions()
	{
		return array(
			array('date', null, InputOption::VALUE_OPTIONAL, 'The date (YYYY-MM-DD) for which the usages are to be seeded.', Carbon::tomorrow()->toDateString()),
			array('user', null, InputOption::VALUE_OPTIONAL, 'The user for whom we need to seed the usage.', null),
		);
	}
}