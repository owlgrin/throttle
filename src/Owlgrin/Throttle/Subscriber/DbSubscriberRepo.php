<?php namespace Owlgrin\Throttle\Subscriber;

use Carbon\Carbon;

use Illuminate\Database\DatabaseManager as Database;
use Owlgrin\Throttle\Subscriber\SubscriberRepo;
use Owlgrin\Throttle\Plan\PlanRepo;
use Owlgrin\Throttle\Feature\FeatureRepo;
use Owlgrin\Throttle\Period\PeriodInterface;
use Owlgrin\Throttle\Exceptions;
use PDOException, Config;

class DbSubscriberRepo implements SubscriberRepo {

	protected $db;
	protected $planRepo;
	protected $featureRepo;

	public function __construct(Database $db, PlanRepo $planRepo, FeatureRepo $featureRepo)
	{
		$this->db = $db;
		$this->planRepo = $planRepo;
		$this->featureRepo = $featureRepo;
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
				//find limit of the features
				$this->addInitialUsageForFeatures($subscriptionId, $plan['id']);
				$this->addInitialLimitForFeatures($subscriptionId, $plan['id']);
			}

					
			//commition the work after processing
			$this->db->commit();
		
			return $subscriptionId;
		}
		catch(PDOException $e)
		{
			//rollback if failed
			$this->db->rollback();

			throw new Exceptions\InternalException("Something went wrong with database");	
		}
	}

	//unsubscribe the user
	public function unsubscribe($userId)
	{
		try
		{
			$this->db->table(Config::get('throttle::tables.subscriptions'))
				->where('user_id', $userId)
				->where('is_active', '1')
				->update(['is_active' => '0']);
		}
		catch(PDOException $e)
		{
			//rollback if failed
			$this->db->rollback();

			throw new Exceptions\InternalException("Something went wrong with database");	
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
			throw new Exceptions\InternalException("Something went wrong with database");	
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
			throw new Exceptions\InternalException("Something went wrong with database");	
		}
	}

	//manually increment limit of a subscription
	public function incrementLimit($subscriptionId, $featureId, $value)
	{
		try
		{
			$this->db->table(Config::get('throttle::tables.user_feature_limit'))
				->where('subscription_id', $subscriptionId)
				->where('feature_id', $featureId)
				->increment('limit', $value);
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException("Something went wrong with database");	
		}
	}

	public function seedPreparedUsages($usages)
	{
		try
		{
			/*
				insert ignore into _throttle_user_feature_usage (subscription_id, feature_id, used_quantity, date)
				values (1, 1, 0, '2014-12-20'), (1, 1, 0, '2014-12-20'), (1, 1, 0, '2014-12-20'), (1, 1, 0, '2014-12-20')
			 */
			$values = [];
			foreach($usages as $usage)
			{
				$values[] = "({$usage['subscription_id']}, {$usage['feature_id']}, {$usage['used_quantity']}, '{$usage['date']}')";
			}

			return $this->db->insert('
					insert ignore into `'.Config::get('throttle::tables.user_feature_usage').'`
					(`subscription_id`, `feature_id`, `used_quantity`, `date`)
					values '.implode(',', $values)
				);
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;
		}
	}

	//returns usage of the user
	public function getUsage($userId, $startDate, $endDate)
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
						and `s`.`user_id` = :user_id
					group by `f`.`id`
				', [
					':start_date' => $startDate,
					':end_date' => $endDate,
					':user_id' => $userId
				]);
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException("Something went wrong with database");
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
			throw new Exceptions\SubscriptionException('No Subscription exists');
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
			throw new Exceptions\InternalException("Something went wrong with database");	
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
			throw new Exceptions\InternalException("Something went wrong with database");	
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
					inner join `'.Config::get('throttle::tables.subscriptions').'` as `s`
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
			throw new Exceptions\InternalException("Something went wrong with database");	
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
			throw new Exceptions\InternalException("Something went wrong with database");	
		}
	}
}