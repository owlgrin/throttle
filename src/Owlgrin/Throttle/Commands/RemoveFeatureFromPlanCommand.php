<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Owlgrin\Throttle\Plan\PlanRepo;
use Owlgrin\Throttle\Subscriber\SubscriberRepo;

/**
 * Command to generate the required migration
 */
class RemoveFeatureFromPlanCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'throttle:plan-remove-feature';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'This command removes features from the plan';

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
		$featureIds = [];

		$planIdentifier = $this->argument('plan');

		$plan = $this->planRepo->getPlanByIdentifier($planIdentifier);

		$tiers = $this->planRepo->getTiersByPlanIdentifier($planIdentifier);

		$this->represntPlanInTable($plan);

		$this->representTiersInTable($tiers);

		while($this->confirm('Do you wish to remove features from the plan ? [yes|no]'))
		{
			$featureIdentifier = $this->ask('Which identifier you want to remove?');

			$featureIds[] = $this->planRepo->removeFeatureFromPlan($plan['id'], $featureIdentifier);
		}

		$subscribers = $this->planRepo->findSubscribersByPlanId($plan['id']);

		foreach ($featureIds as $featureId)
		{
			$this->updateSubscribersForUpdatedPlan($subscribers, $featureId);
		}
	}

	protected function updateSubscribersForUpdatedPlan($subscribers, $featureId)
	{
		foreach ($subscribers as $subscriber)
		{
			$this->subscriberRepo->removeUsagesOfSubscription($subscriber['id'], $featureId);
			$this->subscriberRepo->removeLimitsOfSubscription($subscriber['id'], $featureId);
		}
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