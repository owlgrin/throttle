<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Owlgrin\Throttle\Plan\PlanRepo;

/**
 * Command to generate the required migration
 */
class AddFeatureInPlanCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'throttle:add-feature-in-plan';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'This command adds a feature in existing plan';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	protected $planRepo;

	public function __construct(PlanRepo $planRepo)
	{
 		parent::__construct();
 		$this->planRepo = $planRepo;
	}

	public function fire()
	{
		$planIdentifier = $this->argument('plan');

		$oldFeatures = $this->planRepo->getFeaturesByPlanIdentifier($planIdentifier);
		$oldFeatures = array_pick($features, 'identifier');

		do
		{
			$newFeatures[] = $this->addFeatures($oldFeatures);

		} while($this->confirm('Do you wish to add more features ? [yes|no]'));

		$this->representFeaturesInTable($newFeatures);

		$this->addPlanToDatabase($planIdentifier, $newFeatures);
	}

	public function addPlanToDatabase($planIdentifier, $features)
	{
		if ($this->confirm('Do you wish to add features to the plan ? [yes|no]'))
		{
			$this->planRepo->addFeaturesInPlan($planIdentifier, $features);
			$this->info('Features Added Successfully');
		}
	}

	protected function addFeatures($oldFeatures)
	{
		$feature['name'] = $this->ask('Whats tha name Of The feature ?');

		while(in_array($feature['identifier'] = $this->ask('Whats tha identifier Of The feature ?'), $oldFeatures))
		{
			$this->info('Same Feature Identifier Already Exists');
		}

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

	protected function representFeaturesInTable($features)
	{
		foreach($features as $feature)
		{
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
			array('plan', InputArgument::REQUIRED, 'The identifier of the plan whose feature you want to add')
		);
	}

}