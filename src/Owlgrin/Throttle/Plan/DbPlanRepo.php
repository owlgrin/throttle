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
				$featureId = $this->addFeature($feature['name'], $feature['identifier']);
				
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

			throw new Exceptions\InternalException("Something went wrong with database");
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
			throw new Exceptions\InternalException("Something went wrong with database");	
		}
	}


	public function addFeature($name, $identifier)
	{
		try
		{	
			$featureId = $this->ifFeatureExists($identifier);

			if( ! $featureId)
			{	
				$featureId = $this->db->table(Config::get('throttle::tables.features'))->insertGetId([
					'name' => $name,
					'identifier' => $identifier
				]);
			}

			return (int) $featureId;
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException("Something went wrong with database");	
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
			throw new Exceptions\InternalException("Something went wrong with database");	
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
			throw new Exceptions\InternalException("Something went wrong with database");	
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
			throw new Exceptions\InternalException("Something went wrong with database");	
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
			throw new Exceptions\InternalException("Something went wrong with database");	
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
			throw new Exceptions\InternalException("Something went wrong with database");	
		}	
	}
}