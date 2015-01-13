<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Owlgrin\Throttle\Biller\Biller;
use Owlgrin\Throttle\Subscriber\SubscriberRepo;
use Owlgrin\Throttle\Period\ActiveSubscriptionPeriod;

/**
 * Command to generate the required migration
 */
class UserBillCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'throttle:bill';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Find\'s Bill of the user';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	protected $biller;

	/**
	 * Subscriber Repo.
	 *
	 * @var object
	 */
	protected $subscriptionRepo;

	public function __construct(Biller $biller, SubscriberRepo $subscriptionRepo)
	{
 		parent::__construct();

		$this->biller  = $biller;
		$this->subscriptionRepo  = $subscriptionRepo;
	}

	public function fire()
	{
		$userId = $this->option('user');
		// $startDate = $this->option('start_date');
		// $endDate = $this->option('end_date');

		$subscription = $this->subscriptionRepo->subscription($userId);

		$period = new ActiveSubscriptionPeriod($subscription['id'], true);

		$bill = $this->biller->bill($subscription['id'], $period->start(), $period->end());

		$this->info('User With id '.$userId.' has a bill of');

		$this->displayTables($bill);
		// $this->table([$this->table(['limit', 'rate', 'rate_per_quantity', 'usage', 'amount']), 'feature_name', 'amount', 'usage'], $bill);
	}

	protected function getOptions()
	{
		return array(
			array('user', null, InputOption::VALUE_OPTIONAL, 'The id of the user who wants to subscribe', null),
			array('start_date', null, InputOption::VALUE_OPTIONAL, 'The start date of the bill.', null),
			array('end_date', null, InputOption::VALUE_OPTIONAL, 'The end date of the bill.', null)
		);
	}

	protected function displayTables($bill)
	{
		$lines = [];

		print_r($bill);

		foreach ($bill['lines'] as $line) 
		{
			unset($line['tiers']);
			$lines[] = $line;
		}

		$this->table(['feature_name', 'amount', 'usage'], $lines);

		$total = array(
			array(
				'amount' => $bill['amount']
			)
		);

		$this->table(['total'], $total);
	}
}