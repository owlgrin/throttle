<?php namespace Owlgrin\Throttle\Usage;

use Illuminate\Database\DatabaseManager as Database;

use Owlgrin\Throttle\Usage\UsageRepo;
use Owlgrin\Throttle\Exceptions;

use Config, Carbon\Carbon, PDOException;

use Owlgrin\Throttle\Feature\FeatureRepo;

class DbUsageRepo implements UsageRepo {

	protected $db;
	protected $featureRepo;

	public function __construct(Database $db, FeatureRepo $featureRepo)
	{
		$this->db = $db;
		$this->featureRepo = $featureRepo;
	}

	public function seedBase($userId, $subscriptions, $date = null)
	{
		$date =  is_null($date) ? Carbon::today()->toDateString() : $date;

		foreach($subscriptions as $subscription)
		{
			if( ! $subscription) continue;

			$features = $this->getFeaturesForUser($subscription['user_id']);

			$usages = $this->prepareUsages($subscription, $features, $date);

			$this->seedPreparedUsages($usages);
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

	private function seedPreparedUsages($usages)
	{
		try
		{
			/*
				insert ignore into _throttle_subscription_feature_usage (subscription_id, feature_id, used_quantity, date)
				values (1, 1, 0, '2014-12-20'), (1, 1, 0, '2014-12-20'), (1, 1, 0, '2014-12-20'), (1, 1, 0, '2014-12-20')
			 */
			$values = [];
			foreach($usages as $usage)
			{
				$values[] = "({$usage['subscription_id']}, {$usage['feature_id']}, {$usage['used_quantity']}, '{$usage['date']}')";
			}

			return $this->db->insert('
					insert ignore into `'.Config::get('throttle::tables.subscription_feature_usage').'`
					(`subscription_id`, `feature_id`, `used_quantity`, `date`)
					values '.implode(',', $values)
				);
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;
		}
	}
}