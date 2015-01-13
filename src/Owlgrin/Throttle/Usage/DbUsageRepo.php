<?php namespace Owlgrin\Throttle\Usage;

use Owlgrin\Throttle\Usage\UsageRepo;
use Owlgrin\Throttle\Exceptions;
use Config, Carbon\Carbon;

use Owlgrin\Throttle\Subscriber\SubscriberRepo;
use Owlgrin\Throttle\Feature\FeatureRepo;

class DbUsageRepo implements UsageRepo {

	protected $subscriberRepo;
	protected $featureRepo;

	public function __construct(SubscriberRepo $subscriberRepo, FeatureRepo $featureRepo)
	{
		$this->subscriberRepo = $subscriberRepo;
		$this->featureRepo = $featureRepo;
	}

	public function seedBase($userId, $date = null)
	{	
		$date =  is_null($date) ? Carbon::today()->toDateString() : $date;
		
		$subscriptions = $this->getSubscriptions($userId);

		foreach($subscriptions as $subscription)
		{
			if( ! $subscription) continue;

			$features = $this->getFeaturesForUser($subscription['user_id']);

			$usages = $this->prepareUsages($subscription, $features, $date);

			$this->subscriberRepo->seedPreparedUsages($usages);
		}
	}

	private function getSubscriptions($userId)
	{
		// if user explicitly passed, we will return that only
		if( ! is_null($userId))
		{
			return [$this->subscriberRepo->subscription($userId)];
		}

		return $this->subscriberRepo->all();
	}

	private function getFeaturesForUser($userId)
	{
		return $this->featureRepo->allForUser($userId);
	}

	private function prepareUsages($subscription, $features, $date)
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

	private function getUsageForFeature($userId, $featureIdentifier, $date)
	{
		if($seeder = Config::get("throttle::seeders.{$featureIdentifier}"))
		{
			return app($seeder)->getUsageForDate($userId, $date);
		}
		
		return 0;
	}
}