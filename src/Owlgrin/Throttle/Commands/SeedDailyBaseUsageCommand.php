<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Owlgrin\Throttle\Subscriber\SubscriberRepo;
use Owlgrin\Throttle\Feature\FeatureRepo;
use Carbon\Carbon;
use App, Config;

/**
 * Command to generate the required migration
 */
class SeedDailyBaseUsageCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'throttle:seed-base-usage';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Seed Daily Base Usage';

	/**
	 * The subscription repo
	 *
	 * @var Owlgrin\Throttle\Subscriber\SubscriberRepo
	 */
	protected $subscriptionRepo;

	/**
	 * The feature repo
	 *
	 * @var Owlgrin\Throttle\Feature\FeatureRepo
	 */
	protected $featureRepo;

	public function __construct(SubscriberRepo $subscriptionRepo, FeatureRepo $featureRepo)
	{
 		parent::__construct();
 		$this->subscriptionRepo = $subscriptionRepo;
 		$this->featureRepo = $featureRepo;
	}

	public function fire()
	{
		$date = $this->option('date');

		$subscriptions = $this->getSubscriptions();

		foreach($subscriptions as $subscription)
		{
			if( ! $subscription) continue;

			$features = $this->getFeaturesForUser($subscription['user_id']);

			$usages = $this->prepareUsages($subscription, $features, $date);

			$this->subscriptionRepo->seedPreparedUsages($usages);
		}
	}

	protected function getSubscriptions()
	{
		// if user explicitly passed, we will return that only
		if( ! is_null($userId = $this->option('user')))
		{
			return [$this->subscriptionRepo->subscription($userId)];
		}

		return $this->subscriptionRepo->all();
	}

	protected function getFeaturesForUser($userId)
	{
		return $this->featureRepo->allForUser($userId);
	}

	protected function prepareUsages($subscription, $features, $date)
	{
		$usages = [];

		foreach($features as $feature)
		{
			$usages[] = [
				'subscription_id' => $subscription['id'],
				'feature_id' => $feature['id'],
				'date' => $date,
				'used_quantity' => $this->getUsageForFeature($subscription['user_id'], $feature['identifier'], $date)
			];
		}

		return $usages;
	}

	protected function getUsageForFeature($userId, $featureIdentifier, $date)
	{
		if($seeder = Config::get("throttle::seeders.{$featureIdentifier}"))
		{
			return app($seeder)->getUsageForDate($userId, $date);
		}
		
		return 0;
	}
	
	protected function getOptions()
	{
		return array(
			array('date', null, InputOption::VALUE_OPTIONAL, 'The date (YYYY-MM-DD) for which the usages are to be seeded.', Carbon::tomorrow()->toDateString()),
			array('user', null, InputOption::VALUE_OPTIONAL, 'The user for whom we need to seed the usage.', null),
		);
	}
}