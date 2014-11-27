<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Owlgrin\Throttle\Plan\PlanRepo;
use Config;

/**
 * Command to generate the required migration
 */
class PlanListCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'throttle:plan:list';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Displays the list of all plans';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	protected $plan;

	public function __construct(PlanRepo $plan)
	{
 		parent::__construct();
		$this->plan = $plan;
	}

	public function fire()
	{
		$plans = $this->plan->getAllPlans();

		print_r($plans);
	}
}