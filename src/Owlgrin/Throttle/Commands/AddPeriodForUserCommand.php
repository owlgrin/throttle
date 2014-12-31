<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Owlgrin\Throttle\Period\PeriodRepo;
use Config;

/**
 * Command to generate the required migration
 */
class AddPeriodForUserCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'throttle:add:user:period';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Add Period for user';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	protected $period;

	public function __construct(PeriodRepo $period)
	{
 		parent::__construct();
		$this->period = $period;
	}

	public function fire()
	{
		$subscriptionId = $this->option('subscription_id');
		$startDate = $this->option('start_date');
		$endDate = $this->option('end_date');

		$this->period->store($subscriptionId, $startDate, $endDate);
		$this->info('Period added for subscription id '.$subscriptionId.' has been added from '.$startDate. 'to' .$endDate);
	}
	
	protected function getOptions()
	{
		return array(
			array('subscription_id', null, InputOption::VALUE_OPTIONAL, 'The user\'s subscription id.', null),
			array('start_date', null, InputOption::VALUE_OPTIONAL, 'The start date of the period.', null),
			array('end_date', null, InputOption::VALUE_OPTIONAL, 'The end date of the period.', null),
		);
	}
}