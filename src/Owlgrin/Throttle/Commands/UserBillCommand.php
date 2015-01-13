<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Owlgrin\Throttle\Period\ManualSubscriptionPeriod;

use Throttle;

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
	
	public function __construct()
	{
 		parent::__construct();
	}

	public function fire()
	{
		$userId = $this->argument('user');

		$startDate = $this->option('start_date');
		$endDate = $this->option('end_date');

		$bill = $this->getBill($userId, $startDate, $endDate);

		$this->info('User With id '.$userId.' has a bill of');

		print_r($bill);
	}

	protected function getBill($userId, $startDate, $endDate)
	{
		if($startDate == null and $endDate == null)
		{
			return Throttle::user($userId)->bill();
		}

		return Throttle::user($userId)->bill(new ManualSubscriptionPeriod($startDate, $endDate));
	}

	protected function getArguments()
	{
		return array(
			array('user', InputArgument::REQUIRED, 'The id of the user who wants to subscribe')
		);	
	}

	protected function getOptions()
	{
		return array(
			array('start_date', null, InputOption::VALUE_OPTIONAL, 'The start date of the bill.', null),
			array('end_date', null, InputOption::VALUE_OPTIONAL, 'The end date of the bill.', null)
		);
	}
}