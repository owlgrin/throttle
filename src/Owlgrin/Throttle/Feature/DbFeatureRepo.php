<?php namespace Owlgrin\Throttle\Feature;

use Carbon\Carbon;

use Illuminate\Database\DatabaseManager as Database;
use Owlgrin\Throttle\Exceptions;
use PDOException, Exception, Config;

class DbFeatureRepo implements FeatureRepo {

	protected $db;

	public function __construct(Database $db)
	{
		$this->db = $db;
	}

	//returns limit of the particular feature
	public function featureLimit($planId, $featureId)
	{
		try
		{
			return $this->db->table(Config::get('throttle::tables.plan_feature').' as pf')
				->join(Config::get('throttle::tables.features').' as f', 'f.id', '=', 'pf.feature_id')
				->select('f.name', 'f.identifier', 'pf.tier', 'pf.limit', 'pf.rate', 'pf.per_quantity')
				->where('pf.plan_id', $planId)
				->where('pf.feature_id', $featureId)
				->get();
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;
		}
	}

	public function featureLimitBySubscription($subscriptionId, $featureId)
	{
		try
		{
			return $this->db->table(Config::get('throttle::tables.subscription_feature_limit'))
				->where('subscription_id', $subscriptionId)
				->where('feature_id', $featureId)
				->where('status', 'active')
				->first();
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;
		}
	}

	public function allForUser($userId)
	{
		try
		{
			return $this->db->table(Config::get('throttle::tables.subscriptions').' AS s')
						->join(Config::get('throttle::tables.plan_feature').' AS pf', 's.plan_id', '=', 'pf.plan_id')
						->join(Config::get('throttle::tables.features').' AS f', 'pf.feature_id', '=', 'f.id')
						->select('f.*')
						->groupBy('f.id')
						->get();
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;
		}
	}

	public function getAllFeatures()
	{
		try
		{
			return $this->db->table(Config::get('throttle::tables.features'))
				->get();
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;
		}
	}
}