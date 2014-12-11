<?php namespace Owlgrin\Throttle\Feature;

use Carbon\Carbon;

use Illuminate\Database\DatabaseManager as Database;
use Owlgrin\Throttle\Exceptions;
use Exception, Config;

class DbFeatureRepo implements FeatureRepo {

	protected $db;

	public function __construct(Database $db)
	{
		$this->db = $db;
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
}