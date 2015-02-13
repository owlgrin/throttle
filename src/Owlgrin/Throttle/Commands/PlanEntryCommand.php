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

		$interactive = $this->option('i');

		if($interactive)
		{
			$plan = $this->addPlanInteractively();
		}
		else
		{
			$plan = json_decode($plan, true);
		}

		$this->representPlanInTable($plan['plan']);
		$this->representFeaturesInTable($plan['plan']);

		$this->addPlanToDatabase($plan);
	}

	protected function addPlanInteractively()
	{
		$plan = [];

		$plan['name'] = $this->ask('What is the name of the plan?');
		$plan['identifier'] = $this->ask('What is the identifier of the plan?');
		$plan['description'] = $this->ask('What description you would like to add?');

		$plan['features'] = [];

		$this->info("Lets add Features Now");

		do
		{
			$plan['features'][] = $this->addFeatures();

		} while($this->confirm('Do you wish to add more features ? [yes|no]'));

		$plan['plan'] = $plan;

		return $plan;
	}

	public function addPlanToDatabase($plan)
	{
		if ($this->confirm('Do you wish to add plan to database ? [yes|no]'))
		{
			$this->plan->add($plan);
			$this->info('Plan Added Successfully');
		}
	}

	protected function addFeatures()
	{
		$feature = [];

		$feature['name'] = $this->ask('Whats tha name Of The feature ?');
		$feature['identifier'] = $this->ask('Whats tha identifier Of The feature ?');
		$feature['aggregator'] = $this->choice('Whats the aggregator Of The feature ?[sum|max]', ['max', 'sum']);

		$feature['tier'] = [];

		$this->info('add tiers in this feature : '. $feature['name']);

		do
		{
			$feature['tier'][] = $this->addTiersInFeature();

		} while($this->confirm('Do you want to add more tiers? [yes|no]'));

		return $feature;
	}

	protected function addTiersInFeature()
	{
		$tiers = [];

		$tiers['rate'] = $this->ask('What would be the rate ?');
		$tiers['per_quantity'] = $this->ask('What would be the per_quantity ?');
		$tiers['limit'] = $this->ask('What would be the limit ?');

		return $tiers;
	}

	protected function representPlanInTable($plan)
	{
		unset($plan['features']);

		$this->info('Representing plan named : '. $plan['name']);

		$this->table(['name', 'identifier', 'description'], [$plan]);
	}

	protected function representFeaturesInTable($plan)
	{
		foreach($plan['features'] as $feature)
		{
			$this->info('Representing feature : "'. $feature['name'] .'" of  plan : "'. $plan['name'] .'"');

			$this->table(['name', 'identifier', 'aggregator'], [ ['name' => $feature['name'] ,'identifier' => $feature['identifier'], 'aggregator' => $feature['aggregator']]]);

			$this->representTiersInTable($feature['tier'], $feature['name']);
		}
	}

	protected function representTiersInTable($tiers, $featureName)
	{
		$this->info('Representing tiers of feature : "'. $featureName .'"');

		$this->table(['rate', 'per_quantity', 'limit'], $tiers);
	}

	protected function getArguments()
	{
		return array(
			array('plan', InputArgument::OPTIONAL, 'Stores a plan and its corresponding features'),
		);
	}

	protected function getOptions()
	{
		return array(
			array('i', null, InputOption::VALUE_NONE, 'If you wants to add plan in interactive way', null)
		);
	}

}