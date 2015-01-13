<?php namespace Owlgrin\Throttle\Usage;

use Owlgrin\Throttle\Usage\UsageRepo;
use Owlgrin\Throttle\Exceptions;
use Config, Carbon\Carbon;

use Owlgrin\Throttle\Feature\FeatureRepo;

class DbUsageRepo implements UsageRepo {

	protected $featureRepo;

	public function __construct(FeatureRepo $featureRepo)
	{
		$this->featureRepo = $featureRepo;
	}

	public function getBaseUsages($userId, $subscriptions, $date = null)
	{	
		$date =  is_null($date) ? Carbon::today()->toDateString() : $date;
		
		foreach($subscriptions as $subscription)
		{
			if( ! $subscription) continue;

			$features = $this->getFeaturesForUser($subscription['user_id']);

			return $this->prepareUsages($subscription, $features, $date);
		}
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