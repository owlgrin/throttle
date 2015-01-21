<?php namespace Owlgrin\Throttle\Plan;

use Illuminate\Database\DatabaseManager as Database;
use Owlgrin\Throttle\Plan\PlanRepo;
use Owlgrin\Throttle\Exceptions;
use PDOException, Config;

class DbPlanRepo implements PlanRepo {

	protected $db;

	public function __construct(Database $db)
	{
		$this->db = $db;
	}

	public function add($plan)
	{
		try
		{
			//starting a transition
			$this->db->beginTransaction();

			$plan = $plan['plan'];
			//add a plan
			$planId = $this->addPlan($plan['name'], $plan['identifier'], $plan['description']);
			
			//for every feature
			//add new feature
			//then add the plan_feature mapping
			foreach($plan['features'] as $feature)
			{
				$featureId = $this->addFeature($feature['name'], $feature['identifier'], array_get($feature, 'aggregator', 'sum'));
				
				foreach($feature['tier'] as $index => $tier)
				{
					$this->addPlanFeature($planId, $featureId, $tier['rate'], $tier['per_quantity'], $index, $tier['limit']);	
				}
			}

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

	private function addPlan($name, $identifier, $description)
	{
		try
		{
			return $this->db->table(Config::get('throttle::tables.plans'))->insertGetId([
				'name' 		  => $name,
				'identifier'  => $identifier,
				'description' => $description
			]);
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;	
		}
	}


	public function addFeature($name, $identifier, $aggregator = 'sum')
	{
		try
		{	
			$featureId = $this->ifFeatureExists($identifier);

			if( ! $featureId)
			{	
				$featureId = $this->db->table(Config::get('throttle::tables.features'))->insertGetId([
					'name' => $name,
					'identifier' => $identifier,
					'aggregator' => $aggregator
				]);
			}

			return (int) $featureId;
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;	
		}
	}

	private function ifFeatureExists($identifier)
	{
		try
		{
			$feature = $this->db->table(Config::get('throttle::tables.features'))
				->where('identifier', $identifier)
				->select('id')
				->first();

			return $feature['id'];
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;	
		}
	}

	private function addPlanFeature($planId, $featureId, $rate, $perQuantity, $tier, $limit)
	{
		try
		{
			$this->db->table(Config::get('throttle::tables.plan_feature'))->insert([
				'plan_id' => $planId,
				'feature_id' => $featureId,
				'rate' => $rate,
				'per_quantity' => $perQuantity,
				'tier' => $tier,
				'limit' => $limit
			]);
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;	
		}
	}

	public function getFeaturesByPlan($planId)
	{
		try
		{
			$features = $this->db->select("SELECT * FROM ".Config::get('throttle::tables.features')." Where id IN (Select distinct(feature_id) from " .Config::get('throttle::tables.plan_feature')." Where `plan_id` = {$planId} )");
		
			foreach($features as $index => $feature) 
			{
				unset($features[$index]);
				$features[$feature['identifier']] = $feature;
			}

			return $features;
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;	
		}
	}

	public function getPlanByIdentifier($identifier)
	{
		try
		{
			return $this->db->table(Config::get('throttle::tables.plans'))
				->where('identifier', $identifier)
				->first();
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;	
		}
	}

	public function getAllPlans()
	{
		try
		{
			return $this->db->table(Config::get('throttle::tables.plans'))
					->get();
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;	
		}	
	}

	public function getFeatureLimitByPlanIdentifier($planIdentifier)
	{
		try
		{
			/**
			 * Accepting planIdentifier
			 * and joining three tables plans, features and plan_feature
			 * and returning features, with their identifier and with their
			 * limit
			 * we have done order by tiers descenting to check if last limit is
			 * null then limit should return null else sum of the limits
			 * Here's the query -> IF(`limit` IS NULL, NULL, SUM(`limit`))
			 */
			return $this->db->select(
				$this->db->raw("SELECT `feature_id` as featureId,
				 `identifier` as identifier, IF(`limit` IS NULL, NULL, 
				 SUM(`limit`)) AS `limit` FROM (SELECT pf.`feature_id`, 
				 pf.`limit`, f.`identifier` FROM ".Config::get('throttle::tables.plan_feature')."
				 AS pf JOIN ".Config::get('throttle::tables.features')." AS f 
				 ON f.id = pf.feature_id JOIN ".Config::get('throttle::tables.plans')." AS p 
				 ON p.id = pf.plan_id WHERE p.`identifier` = :planIdentifier 
				 ORDER BY pf.`tier` DESC) AS `t1` GROUP BY `feature_id`"), 
				['planIdentifier' => $planIdentifier]
			);
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;	
		}
	}

}