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
				->select('limit', 'rate', 'name', 'per_quantity')
				->where('plan_id', $planId)
				->where('feature_id', $featureId)
				->get();
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