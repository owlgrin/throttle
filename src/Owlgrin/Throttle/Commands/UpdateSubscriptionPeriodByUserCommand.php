<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Owlgrin\Throttle\Period\ManualPeriod;
use Throttle;

/**
 * Command to generate the required migration
 */
class UpdateSubscriptionPeriodByUserCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'throttle:update-subscription-period-by-user';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Update the subscription period of particular active user subscription';

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
		$this->info('Starting at.. ' . date('Y-m-d H:i:s'));

		$user = $this->option('user');
		$start = $this->option('start');
		$end = $this->option('end');	

		$this->info('Updating user with ID ' . $user . ' subscription period to ' . $start . ' - ' . $end);
		
		Throttle::user($user)->addPeriod(new ManualPeriod($start, $end));

		$this->info('Ending at.. ' . date('Y-m-d H:i:s'));
	}

	protected function getOptions()
	{
		return array(
			array('user', null, InputOption::VALUE_OPTIONAL, 'User whose subscription period to be updated', null),
			array('start', null, InputOption::VALUE_OPTIONAL, 'Start of period (YYYY-MM-DD format).', null),
			array('end', null, InputOption::VALUE_OPTIONAL, 'End of period (YYYY-MM-DD format).', null)
		);
	}
}