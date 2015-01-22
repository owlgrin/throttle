<?php namespace Owlgrin\Throttle\Subscriber;

use Carbon\Carbon;

use Illuminate\Database\DatabaseManager as Database;
use Owlgrin\Throttle\Subscriber\SubscriberRepo;
use Owlgrin\Throttle\Plan\PlanRepo;
use Owlgrin\Throttle\Feature\FeatureRepo;
use Owlgrin\Throttle\Period\PeriodInterface;
use Owlgrin\Throttle\Usage\UsageRepo;
use Owlgrin\Throttle\Period\PeriodRepo;
use Owlgrin\Throttle\Period\CurrentMonthPeriod;

use Owlgrin\Throttle\Exceptions;
use PDOException, Config;
use App;

class DbSubscriberRepo implements SubscriberRepo {

	protected $db;
	protected $planRepo;
	protected $featureRepo;
	protected $usageRepo;
	protected $periodRepo;

	public function __construct(Database $db, PlanRepo $planRepo, FeatureRepo $featureRepo, PeriodRepo $periodRepo, UsageRepo $usageRepo)
	{
		$this->db = $db;
		$this->planRepo = $planRepo;
		$this->featureRepo = $featureRepo;
		$this->usageRepo = $usageRepo;
		$this->periodRepo = $periodRepo;
	}

	public function all()
	{
		try
		{
			return $this->db->table(Config::get('throttle::tables.subscriptions'))
							->where('is_active', true)
							->select('id', 'user_id', 'plan_id', 'subscribed_at')
							->get();
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;
		}
	}

	public function getAllUserIds()
	{
		try
		{
			return $this->db->table(Config::get('throttle::tables.subscriptions'))
							->where('is_active', true)
							->lists('user_id');
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;
		}
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
				//seeding limit of of features
				$this->addInitialLimitForFeatures($subscriptionId, $plan['id']);
				
				//seeding base usages of features
				$this->usageRepo->seedBase($userId, [$this->subscription($userId)]);
				
				//adding subscription period
				$period = App::make(Config::get('throttle::period_class'), ['user' => $userId]);
				$this->periodRepo->store($subscriptionId, $period->start(), $period->end());
			}
					
			//commition the work after processing
			$this->db->commit();
		
			return $subscriptionId;
		}
		catch(PDOException $e)
		{
			//rollback if failed
			$this->db->rollback();

			throw new Exceptions\InternalException;	
		}
	}

	//unsubscribe the user
	public function unsubscribe($userId)
	{
		try
		{
			//starting a transition
			$this->db->beginTransaction();

			$this->db->table(Config::get('throttle::tables.subscriptions'))
				->where('user_id', $userId)
				->where('is_active', '1')
				->update(['is_active' => '0']);
			
			$this->periodRepo->unsetPeriodOfUser($userId);

			//commition the work after processing
			$this->db->commit();
		}
		catch(PDOException $e)
		{
			//rollback if failed
			$this->db->rollback();

			throw new Exceptions\InternalException;	
		}
	}

	public function addInitialUsageForFeatures($subscriptionId, $planId)
	{
		try
		{
			/*
				INSERT into user_feature_usage(`subscription_id`, `feature_id`, `used_quantity`, `date`)
					SELECT $subscriptionId, `feature_id`, 0, now()
					FROM `plan_feature` where `plan_id` = $planId
					GROUP BY `feature_id`
			*/
			return $this->db->insert( $this->db->raw("INSERT into ".Config::get('throttle::tables.user_feature_usage').
				"(`subscription_id`, `feature_id`,`used_quantity`, `date`) SELECT :subscriptionId, `feature_id`, 0, now() 
				from ".Config::get('throttle::tables.plan_feature')." where `plan_id` = :planId GROUP BY `feature_id`"), 
				[ 'subscriptionId' => $subscriptionId, 'planId' => $planId ]);
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;	
		}
	}

	private function addInitialLimitForFeatures($subscriptionId, $planId)
	{
		try
		{
			/*
				INSERT into user_feature_limit(`subscription_id`, `feature_id`, `limit`) 
					SELECT $subscriptionId, `feature_id` as featureId,
					IF(`limit` IS NULL, NULL, SUM(`limit`)) AS `limit` -- if limit is null, return null, else sum of limit of all tiers --
						FROM (SELECT `feature_id`, `limit` FROM `plan_feature` WHERE `plan_id` = $planId ORDER BY `tier` DESC) AS `t1`
						 -- we are selecting feature_id and limit in desending order
						 -- so, if the null is present in the limit it will come at the top
						 -- and IF() condition in sql checks only top values
						GROUP BY `feature_id`
			*/
			return $this->db->insert( $this->db->raw("INSERT into ".Config::get('throttle::tables.user_feature_limit').
				"(`subscription_id`, `feature_id`, `limit`) SELECT :subscriptionId, `feature_id` as featureId, 
				IF(`limit` IS NULL, NULL, SUM(`limit`)) AS `limit` FROM (SELECT `feature_id`, `limit` FROM 
				".Config::get('throttle::tables.plan_feature')." WHERE `plan_id` = :planId ORDER BY `tier` DESC) AS 
				`t1` GROUP BY `feature_id`"), ['subscriptionId' => $subscriptionId, 'planId' => $planId]);
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;	
		}
	}

	//manually increment limit of a subscription
	public function incrementLimit($subscriptionId, $featureIdentifier, $value)
	{
		try
		{
			$this->db->table(Config::get('throttle::tables.user_feature_limit').' AS ufl')
				->join(Config::get('throttle::tables.features').' AS f', 'f.id', '=', 'ufl.feature_id')
				->join(Config::get('throttle::tables.subscriptions').' AS s', 's.id', '=', 'ufl.subscription_id')
				->where('s.id', $subscriptionId)
				->where('f.identifier', $featureIdentifier)
				->increment('limit', $value);
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;	
		}
	}

	//returns usage of the subscription
	public function getUsage($subscriptionId, $startDate, $endDate)
	{
		try
		{
			//if end date is then then end date id today
			$endDate = is_null($endDate) ? Carbon::today()->toDateTimeString(): $endDate;

			return $this->db->select('
					select 
						`s`.`plan_id`, `ufu`.`feature_id`,
						case `f`.`aggregator`
							when \'max\' then max(`ufu`.`used_quantity`)
							when \'sum\' then sum(`ufu`.`used_quantity`)
						end as `used_quantity`
					from 
						`'.Config::get('throttle::tables.user_feature_usage').'` as `ufu` 
						inner join `'.Config::get('throttle::tables.subscriptions').'` as `s`
						inner join `'.Config::get('throttle::tables.features').'` as `f`
					on
						`s`.`id` = `ufu`.`subscription_id`
						and `f`.`id` = `ufu`.`feature_id`
					where
						`ufu`.`date` >= :start_date
						and `ufu`.`date` <= :end_date
						and `s`.`id` = :subscription_id
					group by `f`.`id`
				', [
					':start_date' => $startDate,
					':end_date' => $endDate,
					':subscription_id' => $subscriptionId
				]);
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;
		}
	}

	//returns subscription of a user
	public function subscription($userId)
	{	
		try
		{
			return $this->db->table(Config::get('throttle::tables.subscriptions'))
				->where('user_id', $userId)
				->where('is_active', '1')
				->select('id', 'user_id', 'plan_id', 'subscribed_at')
				->first();
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;
		}
	}

	//increments usage of a feature by identifier
	public function increment($subscriptionId, $identifier, $count = 1)
	{
		try
		{
			$today = Carbon::today()->toDateString();

			$update = $this->db->table(Config::get('throttle::tables.user_feature_usage').' AS ufu')
				->join(Config::get('throttle::tables.features').' AS f', 'ufu.feature_id', '=', 'f.id')
				->where('ufu.subscription_id', $subscriptionId)
				->where('f.identifier', $identifier)
				->where('ufu.date', $today)
				->increment('ufu.used_quantity', $count);

			//count should not be equal to zero 
			//we dont want to create entry of those feature whose count is zero
			if($update == 0 and $count != 0)
			{
				$this->addUsageByFeatureIdentifier($subscriptionId, $identifier, $count);
			}
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;	
		}
	}

	//add usage of a feature identifier
	private function addUsageByFeatureIdentifier($subscriptionId, $identifier, $usedQuantity)
	{
		try
		{
			$this->db->table(Config::get('throttle::tables.user_feature_usage'))->insert(
				[
					'subscription_id' 	=> $subscriptionId,
					'feature_id'    	=> $this->db->raw("(select id from ". Config::get('throttle::tables.features') ." where identifier = '$identifier')"),
					'used_quantity' 	=> $usedQuantity,
					'date'    			=> $this->db->raw('now()')
				]
			);
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;	
		}
	}

	//returns limit of a feature left

	public function left($subscriptionId, $identifier, $start, $end)
	{	
		try
		{
			$limit = $this->db->select('
				select 
					`ufl`.`limit`,
					case `f`.`aggregator`
						when \'max\' then max(`ufu`.`used_quantity`)
						when \'sum\' then sum(`ufu`.`used_quantity`)
					end as `used_quantity`
				from
					`'.Config::get('throttle::tables.user_feature_usage').'` as `ufu` 
					inner join `'.Config::get('throttle::tables.features').'` as `f`
					inner join `'.Config::get('throttle::tables.user_feature_limit').'` as `ufl`
				on
					`ufl`.`subscription_id` = `ufu`.`subscription_id`
					and `ufu`.`feature_id` = `ufl`.`feature_id`
					and `f`.`id` = `ufu`.`feature_id`
				where
					`ufu`.`date` >= :start_date
					and `ufu`.`date` <= :end_date
					and `f`.`identifier` = :identifier
					and `ufu`.`subscription_id` = :subscriptionId
				LIMIT 1
			', [
				':start_date' => $start,
				':end_date' => $end,
				':identifier' => $identifier,
				':subscriptionId' => $subscriptionId
			]);


			if(! is_null($limit[0]['limit']))
			{
				return $limit[0]['limit'] - $limit[0]['used_quantity'];
			}

			return null;
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;	
		}
	}

	public function canReduceLimit($subscriptionId, $featureId, $limit)
	{
		try
		{
			$feature = $this->db->select("SELECT * FROM ".Config::get('throttle::tables.user_feature_limit')." WHERE `subscription_id` = ".$subscriptionId." AND feature_id = ".$featureId." AND `limit` >= ((SELECT `used_quantity` FROM ".Config::get('throttle::tables.user_feature_usage')." WHERE `subscription_id` = ".$subscriptionId." AND `feature_id` = ".$featureId.") + ".$limit.")");
		
			if($feature)
			{
				return true;
			}

			return false;
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;
		}
	}
}
