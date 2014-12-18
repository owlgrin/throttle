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
		catch(Exception $e)
		{
			throw new Exceptions\InternalExceptrion("Something went wrong");	
		}
		catch(PDOException $e)
		{
			throw new Exceptions\MySqlExceptrion("Something went wrong with database");	
		}
	}

	public function getAllFeatures()
	{
		return $this->db->table(Config::get('throttle::tables.features'))
			->get();
	}
}