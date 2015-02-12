<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Owlgrin\Throttle\Plan\PlanRepo;
use Owlgrin\Throttle\Subscriber\SubscriberRepo;

/**
 * Command to generate the required migration
 */
class UpdatePlanCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'throttle:plan-update';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'This command updates plan';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	protected $planRepo;

	public function __construct(PlanRepo $planRepo, SubscriberRepo $subscriberRepo)
	{
 		parent::__construct();
 		$this->planRepo = $planRepo;
 		$this->subscriberRepo = $subscriberRepo;
	}

	public function fire()
	{
		$planIdentifier = $this->argument('plan');

		$plan = $this->planRepo->getPlanByIdentifier($planIdentifier);

		$tiers = $this->planRepo->getTiersByPlanIdentifier($planIdentifier);

		$this->represntPlanInTable($plan);

		// if($this->confirm('Do you wish to update plan to database ? [yes|no]'))
		// {
		// 	$plan = $this->updatePlan($plan);

		// 	$this->planRepo->updatePlan($plan);
		// }

		$this->representTiersInTable($tiers);

		while($this->confirm('Do you wish to update features of the plan ? [yes|no]'))
		{
			$featureIdentifier = $this->ask('Which identifier you want to update?');

			$featureTiers = $this->planRepo->getFeatureTiersByPlanIdentifier($planIdentifier, $featureIdentifier);

			$this->representTiersInTable($featureTiers);

			$this->updateTiersOfFeature($plan['id'], $featureIdentifier);
		}

		$this->updateSubscribersForUpdatedPlan($plan);
	}

	protected function updateSubscribersForUpdatedPlan($plan)
	{
		$subscribers = $this->planRepo->findSubscribersByPlanId($plan['id']);

		foreach ($subscribers as $subscriber)
		{
			$this->subscriberRepo->updateInitialLimitForFeatures($subscriber['id'], $plan['id']);
		}
	}

	protected function updatePlan($plan)
	{
		if ($this->confirm('Do you wish to update name of the plan ? [yes|no]'))
		{
			$plan['name'] = $this->ask('What is the name of the plan?');
		}

		if ($this->confirm('Do you wish to update identifier of the plan ? [yes|no]'))
		{
			$plan['identifier'] = $this->ask('What is the identifier of the plan?');
		}

		if ($this->confirm('Do you wish to update description of the plan ? [yes|no]'))
		{
			$plan['description'] = $this->ask('What description you would like to add?');
		}

		return $plan;
	}

	protected function updateTiersOfFeature($planId, $featureIdentifier)
	{
		$tiers = [];

		do
		{
			$tiers[] = $this->addTiersInFeature();

		} while($this->confirm('Do you want to add more tiers? [yes|no]'));

		//Finally updting plan by feature
		$this->planRepo->updateFeatureTiersOfPlan($planId, $featureIdentifier, $tiers);
	}


	protected function addTiersInFeature()
	{
		$tiers = [];

		$tiers['rate'] = $this->ask('What would be the rate ?');
		$tiers['per_quantity'] = $this->ask('What would be the per_quantity ?');
		$tiers['limit'] = $this->ask('What would be the limit ?');

		return $tiers;
	}


	protected function represntPlanInTable($plan)
	{
		$this->info('Representing plan of identifier : "'. $plan['identifier'] .'"');

		$this->table(['id', 'name', 'identifier', 'description'], [$plan]);
	}

	protected function representTiersInTable($tiers)
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