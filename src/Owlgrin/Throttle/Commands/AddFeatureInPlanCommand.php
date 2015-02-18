<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Owlgrin\Throttle\Plan\PlanRepo;
use Owlgrin\Throttle\Subscriber\SubscriberRepo;
use Owlgrin\Throttle\Usage\UsageRepo;
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
	protected $subscriberRepo;
	protected $usageRepo;

	public function __construct(PlanRepo $planRepo, SubscriberRepo $subscriberRepo, UsageRepo $usageRepo)
	{
 		parent::__construct();
 		$this->planRepo = $planRepo;
 		$this->subscriberRepo = $subscriberRepo;
 		$this->usageRepo = $usageRepo;
	}

	public function fire()
	{
		$newFeatures = [];

		$planIdentifier = $this->argument('plan');

		$plan = $this->planRepo->getPlanByIdentifier($planIdentifier);

		$tiers = $this->planRepo->getTiersByPlanIdentifier($planIdentifier);

		$this->represntPlanInTable($plan);

		$this->representPlanFeaturesInTable($tiers);

		$oldFeatures = $this->planRepo->getFeaturesByPlanIdentifier($planIdentifier);
		$oldFeatures = array_pick($oldFeatures, 'identifier');

		do
		{
			$newFeatures[] = $this->addFeatures($oldFeatures, $plan['id']);

		} while($this->confirm('Do you wish to add more features ? [yes|no]'));

		$this->representFeaturesInTable($newFeatures);

		if( count($newFeatures) > 0)
		{
			$this->updateSubscribersForUpdatedPlan($plan['id'], $newFeatures);
		}
	}

	protected function updateSubscribersForUpdatedPlan($planId, $features)
	{
		$subscribers = $this->subscriberRepo->findSubscribersByPlanId($planId);

		foreach ($subscribers as $subscriber)
		{
			foreach ($features as $feature)
			{
				$this->subscriberRepo->addInitialLimitForNewFeature($subscriber['id'], $planId, $feature['id']);
				$this->usageRepo->addInitialUsagesForFeature($subscriber, $feature['id'], $feature);
			}
		}
	}


	protected function addFeatures($oldFeatures, $planId)
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

		$feature['id'] = $this->planRepo->addFeature($feature['name'], $feature['identifier'], array_get($feature, 'aggregator', 'sum'));

		foreach($feature['tier'] as $index => $tier)
		{
			$this->planRepo->addPlanFeature($planId, $feature['id'], $tier['rate'], $tier['per_quantity'], $index, $tier['limit']);
		}

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


	protected function represntPlanInTable($plan)
	{
		$this->info('Representing plan of identifier : "'. $plan['identifier'] .'"');

		$this->table(['id', 'name', 'identifier', 'description'], [$plan]);
	}

	protected function representPlanFeaturesInTable($tiers)
	{
		$this->info('Representing plan Features');

		$this->table([ 'plan_id', 'feature_id', 'rate', 'per_quantity', 'tier', 'limit', 'identifier' ], $tiers);
	}


	protected function getArguments()
	{
		return array(
			array('plan', InputArgument::REQUIRED, 'The identifier of the plan whose feature you want to add')
		);
	}

}