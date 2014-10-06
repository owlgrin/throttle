<?php namespace Owlgrin\Throttle\Subscriber;

use Carbon\Carbon;

use Illuminate\Database\DatabaseManager as Database;
use Owlgrin\Throttle\Subscriber\SubscriberRepo;
use Owlgrin\Throttle\Exceptions;

class DbSubscriberRepo implements SubscriberRepo {

	protected $db;

	public function __construct(Database $db)
	{
		$this->db = $db;
	}

	public function subscribe($userId, $planId)
	{
		//user is subscribed in subscriptions and id is returned
		$subscriptionId = $this->db->table('subscriptions')->insertGetId([
				'user_id' 		=> $userId,
				'plan_id' 		=> $planId,
				'subscribed_at' => $this->db->raw('now()'),
				'created_at' 	=> $this->db->raw('now()'),
				'updated_at' 	=> $this->db->raw('now()')
		]);

		if($subscriptionId)
		{
			//find limit of the features
			$this->addInitialUsageForFeatures($subscriptionId, $planId);
			$this->addInitialLimitForFeatures($subscriptionId, $planId);
		}
	}

	private function addInitialUsageForFeatures($subscriptionId, $planId)
	{
		//INSERT into user_feature_usage(`subscription_id`, `feature_id`, `used_quantity`, `date`)
		//we are insering into user_feature table's specific columns 
		//SELECT $subscriptionId, `feature_id`, 0, now() 
		//by selecting a subscriptioniD, many features as per plan , initial usage is 0
		//date is Today
		//from `plan_feature` where `plan_id` = $planId
		//from plan_features tables  
		//GROUP BY `feature_id`
		//feature _id is grouped
		$usage = $this->db->insert("INSERT into user_feature_usage(`subscription_id`, `feature_id`, `used_quantity`, `date`) SELECT $subscriptionId, `feature_id`, 0, now() from `plan_feature` where `plan_id` = $planId GROUP BY `feature_id`");
		return;
	}

	private function addInitialLimitForFeatures($subscriptionId, $planId)
	{
		//INSERT into user_feature_limit(`subscription_id`, `feature_id`, `limit`) 
		//SELECT $subscriptionId, `feature_id` as featureId,
		//here we are selecting a common subscription id
		//distinct feature id
		//and limit 
		//IF(`limit` IS NULL, NULL, SUM(`limit`)) AS `limit` 
		//if limit is null then return null
		//else return sum(limit)
		//FROM (SELECT `feature_id`, `limit` FROM `plan_feature` WHERE `plan_id` = $planId ORDER BY `tier` DESC) AS `t1`\
		//we are selecting feature_id and limit in desending order
		//so as if the null is present in the limit it will come at the top
		//IF() condition in sql checks only top values
		// GROUP BY `feature_id`
		// grouping feature_ids
		$limit = $this->db->insert("INSERT into user_feature_limit(`subscription_id`, `feature_id`, `limit`) SELECT $subscriptionId, `feature_id` as featureId, IF(`limit` IS NULL, NULL, SUM(`limit`)) AS `limit` FROM (SELECT `feature_id`, `limit` FROM `plan_feature` WHERE `plan_id` = $planId ORDER BY `tier` DESC) AS `t1` GROUP BY `feature_id`");
		return;
	}

	public function incrementUsage($subscriptionId, $featureId, $incrementCount = 1)
	{
		try
		{
			if(! $this->checkFeatureLimit($subscriptionId, $featureId, $incrementCount))
			{
				throw new Exceptions\LimitExceededException;
			}			

			$today = Carbon::today()->toDateString();
			$update = $this->db->table('user_feature_usage')
				->where('subscription_id', $subscriptionId)
				->where('feature_id', $featureId)
				->where('date', $today)
				->increment('used_quantity', $incrementCount);

			if($update == 0)
			{
				$this->addUsageForFeatures($usage->subscription_id, $usage->feature_id, $incrementCount);		
			}
		}
		catch(Exceptions\LimitExceededException $e)
		{
			throw new Exceptions\LimitExceededException;
		}
		catch(\Exception $e)
		{
			throw new Exceptions\InternalException;
		}
	}

	private function checkFeatureLimit($subscriptionId, $featureId, $incrementCount)
	{
		$limit = $this->db->table('user_feature_usage AS ufu')
			->join('user_feature_limit as ufl', function($join)
			{
				$join->on('ufu.subscription_id', '=', 'ufl.subscription_id');
				$join->on('ufu.feature_id', '=', 'ufl.feature_id');
			})
			->where('ufu.subscription_id', $subscriptionId)
			->where('ufu.feature_id', $featureId)
			->select($this->db->raw('sum( ufu.used_quantity ) AS used, ufl.limit AS fLimit'))
			->first();
	
		if(! is_null($limit->fLimit))
		{
			return $limit->fLimit > $limit->used;
		}

		return true;
	}
	
	private function addUsageForFeatures($subscriptionId, $featureId, $usedQuantity)
	{
		$this->db->table('user_feature_usage')->insert(
		[
			'subscription_id' 	=> $subscriptionId,
			'feature_id'    	=> $featureId,
			'used_quantity' 	=> $usedQuantity,
			'date'    			=> $this->db->raw('now()')
		]);			
	}

	public function setLimit($subscriptionId, $featureId, $limit)
	{
		$this->db->table('user_feature_limit')
			->where('subscription_id', $subscriptionId)
			->where('feature_id', $featureId)
			->update(['limit' => $limit]);
	}

	public function userDetails($userId, $startDate, $endDate)
	{
		//if end date is then then end date id today
		$endDate = is_null($endDate) ? Carbon::today()->toDateTimeString(): $endDate;

		return $this->db->table('user_feature_usage AS u')
			->join('subscriptions as s','s.id', '=', 'u.subscription_id')
			->where('u.date', '>=', $startDate)
			->where('u.date', '<=', $endDate)
			->where('s.user_id', '=', $userId)
			->select('plan_id', 'feature_id', 'used_quantity', 'date')
			->get();
	}

	public function featureLimit($planId, $featureId)
	{
		return $this->db->table('plan_feature as pfm')
			->join('features as f', 'f.id', '=', 'pfm.feature_id')
			->select('limit', 'rate', 'name')
			->where('plan_id', $planId)
			->where('feature_id', $featureId)
			->get();
	}
}
