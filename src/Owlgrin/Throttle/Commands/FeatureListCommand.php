<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Owlgrin\Throttle\Plan\PlanRepo;
use Config;

/**
 * Command to generate the required migration
 */
class FeatureListCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'throttle:feature:list';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Displays the list of all features';

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
		$features = $this->plan->getAllFeatures();

		print_r($features);
	}
}