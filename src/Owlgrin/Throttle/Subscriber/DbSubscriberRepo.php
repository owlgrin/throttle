<?php namespace Owlgrin\Throttle\Subscriber;

use Carbon\Carbon;

use Illuminate\Database\DatabaseManager as Database;
use Owlgrin\Throttle\Subscriber\SubscriberRepo;
use Owlgrin\Throttle\Plan\PlanRepo;
use Owlgrin\Throttle\Period\PeriodInterface;
use Owlgrin\Throttle\Exceptions;
use Exception, Config;

class DbSubscriberRepo implements SubscriberRepo {

	protected $db;
	protected $planRepo;

	public function __construct(Database $db, PlanRepo $planRepo)
	{
		$this->db = $db;
		$this->planRepo = $planRepo;
	}

	public function subscribe($userId, $planIdentifier)
	{	
		try
		{
			//starting a transition
			$this->db->beginTransaction();

			//unsubscribing to previous plan.
			$this->db->table(Config::get('throttle::tables.subscriptions'))
				->where('user_id', $userId)
				->where('is_active', '1')
				->update(['is_active' => '0']);

			//getting previous plan
			$plan = $this->planRepo->getPlanByIdentifier($planIdentifier);

			//user is subscribed in subscriptions and id is returned
			$subscriptionId = $this->db->table(Config::get('throttle::tables.subscriptions'))->insertGetId([
					'user_id' 		=> $userId,
					'plan_id' 		=> $plan['id'],
					'is_active'		=> '1',
					'subscribed_at' => $this->db->raw('now()'),
			]);

			if($subscriptionId)
			{
				//find limit of the features
				$this->addInitialUsageForFeatures($subscriptionId, $plan['id']);
				$this->addInitialLimitForFeatures($subscriptionId, $plan['id']);
			}

			//commition the work after processing
			$this->db->commit();
		}
		catch(\Exception $e)
		{
			//rollback if failed
			$this->db->rollback();
			dd($e->getMessage());
			throw new Exceptions\InvalidInputException;
		}

	}

	public function addInitialUsageForFeatures($subscriptionId, $planId)
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
		return $this->db->insert("INSERT into ".Config::get('throttle::tables.user_feature_usage')."(`subscription_id`, `feature_id`, `used_quantity`, `date`) SELECT $subscriptionId, `feature_id`, 0, now() from ".Config::get('throttle::tables.plan_feature')." where `plan_id` = $planId GROUP BY `feature_id`");
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
		return $this->db->insert("INSERT into ".Config::get('throttle::tables.user_feature_limit')."(`subscription_id`, `feature_id`, `limit`) SELECT $subscriptionId, `feature_id` as featureId, IF(`limit` IS NULL, NULL, SUM(`limit`)) AS `limit` FROM (SELECT `feature_id`, `limit` FROM ".Config::get('throttle::tables.plan_feature')." WHERE `plan_id` = $planId ORDER BY `tier` DESC) AS `t1` GROUP BY `feature_id`");
	}

	//increments usage of a feature by featureid
	public function incrementUsage($subscriptionId, $featureId, $incrementCount = 1)
	{
		try
		{
			if(! $this->checkFeatureLimit($subscriptionId, $featureId, $incrementCount))
			{
				throw new Exceptions\LimitExceededException;
			}			

			$today = Carbon::today()->toDateString();
			$update = $this->db->table(Config::get('throttle::tables.user_feature_usage'))
				->where('subscription_id', $subscriptionId)
				->where('feature_id', $featureId)
				->where('date', $today)
				->increment('used_quantity', $incrementCount);

			if($update == 0)
			{
				$this->addUsageByFeatureId($subscriptionId, $featureId, $incrementCount);		
			}
		}
		catch(Exceptions\LimitExceededException $e)
		{
	        throw new Exceptions\InternalException('exceptions.repo.unknown');
		}
		catch(\Exception $e)
		{
			throw new Exceptions\InternalException;
		}
	}

	//check whether a user can access a feature or not by featureId
	public function checkFeatureLimit($subscriptionId, $featureId, $incrementCount)
	{
		$limit = $this->db->table(Config::get('throttle::tables.user_feature_usage').' AS ufu')
			->join(Config::get('throttle::tables.user_feature_limit').' as ufl', function($join)
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
			return $limit->fLimit > $limit->used+$incrementCount;
		}

		return true;
	}
	
	//manually adds usage of a subsription 
	private function addUsageByFeatureId($subscriptionId, $featureId, $usedQuantity)
	{
		$this->db->table(Config::get('throttle::tables.user_feature_usage'))->insert(
		[
			'subscription_id' 	=> $subscriptionId,
			'feature_id'    	=> $featureId,
			'used_quantity' 	=> $usedQuantity,
			'date'    			=> $this->db->raw('now()')
		]);			
	}

	//manually setts limit of a subscription
	public function setLimit($subscriptionId, $featureId, $limit)
	{
		$this->db->table(Config::get('throttle::tables.user_feature_limit'))
			->where('subscription_id', $subscriptionId)
			->where('feature_id', $featureId)
			->update(['limit' => $limit]);
	}

	//manually increment limit of a subscription
	public function incrementLimit($subscriptionId, $featureId, $value)
	{
		$this->db->table(Config::get('throttle::tables.user_feature_limit'))
			->where('subscription_id', $subscriptionId)
			->where('feature_id', $featureId)
			->increment('limit', $value);
	}

	//returns usage of the user
	public function getUsage($userId, $startDate, $endDate)
	{
		//if end date is then then end date id today
		$endDate = is_null($endDate) ? Carbon::today()->toDateTimeString(): $endDate;

		return $this->db->table(Config::get('throttle::tables.user_feature_usage').' as u')
			->join(Config::get('throttle::tables.subscriptions').' as s','s.id', '=', 'u.subscription_id')
			->where('u.date', '>=', $startDate)
			->where('u.date', '<=', $endDate)
			->where('s.user_id', '=', $userId)
			->select(\DB::raw('plan_id, feature_id, SUM(used_quantity) as used_quantity'))
			->groupBy('feature_id')
			->get();
	}

	//returns usage of the user
	public function getLimit($subscriptionId)
	{
		return $this->db->table(Config::get('throttle::tables.user_feature_limit').' as tfl')
			->join(Config::get('throttle::tables.features').' as f','f.id', '=', 'tfl.feature_id')
			->select(\DB::raw('f.identifier as identifier, tfl.limit'))
			->get();
	}


	//returns limit of the particular feature
	public function featureLimit($planId, $featureId)
	{
		return $this->db->table(Config::get('throttle::tables.plan_feature').' as pf')
			->join(Config::get('throttle::tables.features').' as f', 'f.id', '=', 'pf.feature_id')
			->select('limit', 'rate', 'name', 'per_quantity')
			->where('plan_id', $planId)
			->where('feature_id', $featureId)
			->get();
	}

	//returns subscription of a user
	public function subscription($userId)
	{
		$user = $this->db->table(Config::get('throttle::tables.subscriptions'))
			->where('user_id', $userId)
			->where('is_active', '1')
			->select('id AS subscription_id', 'plan_id', 'subscribed_at')
			->first();

		return $user;
	}

	//check whether a user can access a feature or not by identifier
	public function can($subscriptionId, $identifier, $incrementCount)
	{
		$limit = $this->db->table(Config::get('throttle::tables.user_feature_usage').' AS ufu')
			->join(Config::get('throttle::tables.user_feature_limit').' as ufl', function($join)
			{
				$join->on('ufu.subscription_id', '=', 'ufl.subscription_id');
				$join->on('ufu.feature_id', '=', 'ufl.feature_id');
			})
			->join(Config::get('throttle::tables.features').' as f', function($join)
			{
				$join->on('f.id', '=', 'ufu.feature_id');
			})
			->where('ufu.subscription_id', $subscriptionId)
			->where('f.identifier', $identifier)
			->select($this->db->raw('sum( ufu.used_quantity ) AS used, ufl.limit AS fLimit'))
			->first();

		if(! is_null($limit['fLimit']))
		{
			return $limit['fLimit'] >= $limit['used']+$incrementCount;
		}

		return true;
	}

	//increments usage of a feature by identifier
	public function increment($subscriptionId, $identifier, $count = 1)
	{
		try
		{
			if(! $this->can($subscriptionId, $identifier, $count))
			{
				throw new Exceptions\LimitExceededException;
			}			

			$today = Carbon::today()->toDateString();

			$update = $this->db->table(Config::get('throttle::tables.user_feature_usage').' AS ufu')
				->join(Config::get('throttle::tables.features').' AS f', 'ufu.feature_id', '=', 'f.id')
				->where('ufu.subscription_id', $subscriptionId)
				->where('f.identifier', $identifier)
				->where('ufu.date', $today)
				->increment('ufu.used_quantity', $count);

			if($update == 0)
			{
				$this->addUsageByFeatureIdentifier($subscriptionId, $identifier, $count);
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

	//add usage of a feature identifier
	private function addUsageByFeatureIdentifier($subscriptionId, $identifier, $usedQuantity)
	{
		$this->db->table(Config::get('throttle::tables.user_feature_usage'))->insert(
		[
			'subscription_id' 	=> $subscriptionId,
			'feature_id'    	=> $this->db->raw("(select id from ". Config::get('throttle::tables.features') ." where identifier = '$identifier')"),
			'used_quantity' 	=> $usedQuantity,
			'date'    			=> $this->db->raw('now()')
		]);
	}

	//returns limit of a feature left
	public function left($subscriptionId, $identifier, $start, $end)
	{
		// $limit = $this->getLimitByIdentifier($subscriptionId, $identifier);
		// $usages = $this->findLeftUsages($subscriptionId, $identifier, $start, $end);

		// if(! is_null($limit['limit']))
		// {
		// 	return $limit['limit'] - $usages['used'];
		// }

		// return null;

		
		$limit = $this->db->table(Config::get('throttle::tables.user_feature_usage').' AS ufu')
			->join(Config::get('throttle::tables.user_feature_limit').' as ufl', function($join)
			{
				$join->on('ufu.subscription_id', '=', 'ufl.subscription_id');
				$join->on('ufu.feature_id', '=', 'ufl.feature_id');
			})
			->join(Config::get('throttle::tables.features').' as f', function($join)
			{
				$join->on('f.id', '=', 'ufu.feature_id');
			})
			->where('ufu.subscription_id', $subscriptionId)
			->where('f.identifier', $identifier)
			->whereBetween('date', [$start, $end])
			->select($this->db->raw('ifnull(sum( ufu.used_quantity ), 0) AS used, ufl.limit AS fLimit'))
			->first();

		if(! is_null($limit['fLimit']))
		{
			return $limit['fLimit'] - $limit['used'];
		}

		return null;
	}

	public function findLeftUsages($subscriptionId, $identifier, $startDate, $endDate)
	{
		return $this->db->table(Config::get('throttle::tables.user_feature_usage').' AS ufu')
			->join(Config::get('throttle::tables.features').' as f', function($join)
			{
				$join->on('f.id', '=', 'ufu.feature_id');
			})
			->where('ufu.subscription_id', $subscriptionId)
			->where('f.identifier', $identifier)
			->whereBetween('date', [$startDate, $endDate])
			->select($this->db->raw('ifnull(sum( ufu.used_quantity ), 0) AS used'))
			->first();
	}

	//returns usage of the user
	public function getLimitByIdentifier($subscriptionId, $identifier)
	{
		return $this->db->table(Config::get('throttle::tables.user_feature_limit').' as tfl')
			->join(Config::get('throttle::tables.features').' as f','f.id', '=', 'tfl.feature_id')
			->where('f.identifier', $identifier) 
			->select('tfl.limit')
			->first();
	}


	public function canReduceLimit($subscriptionId, $featureId, $limit)
	{
		$feature = $this->db->select("SELECT * FROM ".Config::get('throttle::tables.user_feature_limit')." WHERE `subscription_id` = ".$subscriptionId." AND feature_id = ".$featureId." AND `limit` >= ((SELECT `used_quantity` FROM ".Config::get('throttle::tables.user_feature_usage')." WHERE `subscription_id` = ".$subscriptionId." AND `feature_id` = ".$featureId.") + ".$limit.")");
	
		if($feature)
		{
			return true;
		}

		return false;
	}

	public function getUserFeaturesLimit($subscriptionId)
	{
		return $this->db->table(Config::get('throttle::tables.user_feature_limit') .' as ufl')
			->join(Config::get('throttle::tables.features'). ' as f', 'ufl.feature_id', '=', 'f.id')
			->where('subscription_id', $subscriptionId)
			->select('f.id as feature_id', 'f.name as feature_name', 'ufl.limit as feature_limit')
			->get();
	}

	public function getUserUsage($subscriptionId, PeriodInterface $period)
	{		 
		return $this->db->table(Config::get('throttle::tables.user_feature_limit') .' as ufl')
			->leftJoin(Config::get('throttle::tables.features'). ' as f', 'ufl.feature_id', '=', 'f.id')
			->leftJoin(Config::get('throttle::tables.user_feature_usage'). ' as ufu', 'ufl.feature_id', '=', 'ufu.feature_id')
			->where('ufl.subscription_id', $subscriptionId)
			->whereBetween('ufu.date', [$period->start(), $period->end()])
			->select(\DB::raw('f.id as feature_id, f.identifier as feature_identifier, f.name as feature_name, ufl.limit as feature_limit, SUM(ufu.used_quantity) as feature_usage'))
			->groupBy('ufu.feature_id')
			->get();
	}
}