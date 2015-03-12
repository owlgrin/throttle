<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Support\Facades\Event as IlluminateEvent;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

use Throttle;

/**
 * Command to generate the required migration
 */
class SwitchPlanCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'throttle:switch-plan';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Switch user\'s plan';

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
		$planIdentifier = $this->argument('plan');

		$oldPlanBill = Throttle::user($userId)->bill();

		Throttle::user($userId)->switchPlan($planIdentifier);

		IlluminateEvent::fire('throttle.plan.switched', array($userId, $oldPlanBill));

		$this->info('User with ID: '. $userId .' is switched to plan with identifier '.$planIdentifier);
	}

	protected function getArguments()
	{
		return array(
			array('user', InputArgument::REQUIRED, 'The id of the user who wants to subscribe'),
			array('plan', InputArgument::REQUIRED, 'The plan identifier for which user wants to subscribe.')
		);
	}
}