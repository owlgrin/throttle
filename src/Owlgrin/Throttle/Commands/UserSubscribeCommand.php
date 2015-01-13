<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Throttle;

/**
 * Command to generate the required migration
 */
class UserSubscribeCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'throttle:subscribe';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Subscribes the user to a plan';

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
		$userId = $this->option('user');
		$planIdentifier = $this->option('plan');
	
		Throttle::subscribe($userId, $planIdentifier);

		$this->info('User With id '.$userId.' is subscribed to plan with identifier '.$planIdentifier);
	}

	protected function getOptions()
	{
		return array(
			array('user', null, InputOption::VALUE_REQUIRED, 'The id of the user who wants to subscribe', null),
			array('plan', null, InputOption::VALUE_REQUIRED, 'The plan identifier for which user wants to subscribe.', null),
		);
	}
}