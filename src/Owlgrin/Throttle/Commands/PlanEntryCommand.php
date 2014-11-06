<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Owlgrin\Throttle\Plan\PlanRepo;
use Config;

/**
 * Command to generate the required migration
 */
class PlanEntryCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'throttle:plan';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Enters a plan in the database';

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
		$plan = $this->argument('plan');

		$this->plan->add(json_decode($plan, true));

		$this->info('Plan Added Successfully');
	}
	protected function getArguments()
	{
		return array(
			array('plan', InputArgument::REQUIRED, 'Stores a plan and its corresponding features'),
		);
	}
}