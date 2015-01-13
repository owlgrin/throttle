<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Owlgrin\Throttle\Period\ManualSubscriptionPeriod;

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

		$usages = $this->getUsage($userId, $startDate, $endDate);

		$this->info('User With id '.$userId.' has a usages of');

		print_r($usages);
	}

	protected function getUsage($userId, $startDate, $endDate)
	{
		if($startDate == null and $endDate == null)
		{
			return Throttle::user($userId)->getUsage();			
		}
		
		return Throttle::user($userId)->getUsage(new ManualSubscriptionPeriod($startDate, $endDate));			
	}

	protected function getArguments()
	{
		return array(
			array('user', InputArgument::REQUIRED, 'The id of the user whose usage to show')
		);
	}

	protected function getOptions()
	{
		return array(
			array('start_date', null, InputOption::VALUE_OPTIONAL, 'The start date of the usage.', null),
			array('end_date', null, InputOption::VALUE_OPTIONAL, 'The end date of the usage.', null)
		);
	}
}