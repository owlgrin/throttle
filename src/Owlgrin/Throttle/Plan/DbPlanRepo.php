<?php namespace Owlgrin\Throttle\Plan;

use Illuminate\Database\DatabaseManager as Database;
use Owlgrin\Throttle\Plan\PlanRepo;
use Owlgrin\Throttle\Exceptions;
use Exception;

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
		catch(\Exception $e)
		{
			//rollback if failed
			$this->db->rollback();

			throw new Exceptions\InvalidInputException;
		}
	}

	private function addPlan($name, $identifier, $description)
	{
		try
		{
			$planId = $this->db->table('plans')->insertGetId([
				'name' 		  => $name,
				'identifier'  => $identifier,
				'description' => $description
			]);

			return $planId;
		}
		catch(Exception $e)
		{
			throw new Exceptions\InvalidInputException;
		}
	}


	private function addFeature($name, $identifier)
	{
		$featureId = $this->db->table('features')->insertGetId([
			'name' => $name,
			'identifier' => $identifier
		]);

		return $featureId;
	}

	private function addPlanFeature($planId, $featureId, $rate, $perQuantity, $tier, $limit)
	{
		$this->db->table('plan_feature')->insert([
			'plan_id' => $planId,
			'feature_id' => $featureId,
			'rate' => $rate,
			'per_quantity' => $perQuantity,
			'tier' => $tier,
			'limit' => $limit
		]);
	}
}