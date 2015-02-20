<?php namespace Owlgrin\Throttle\Left;

use Carbon\Carbon;

use Illuminate\Database\DatabaseManager as Database;

use Owlgrin\Throttle\Left\LeftRepo;

use Owlgrin\Throttle\Exceptions;

use PDOException, Config;


class DbLeftRepo implements LeftRepo {

	protected $db;

	public function __construct(Database $db)
	{
		$this->db = $db;
	}

	public function leftOnAttempt($subscriptionId, $identifier, $start, $end)
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
					`'.Config::get('throttle::tables.subscription_feature_usage').'` as `ufu`
					inner join `'.Config::get('throttle::tables.features').'` as `f`
					inner join `'.Config::get('throttle::tables.subscription_feature_limit').'` as `ufl`
				on
					`ufl`.`subscription_id` = `ufu`.`subscription_id`
					and `ufu`.`feature_id` = `ufl`.`feature_id`
					and `f`.`id` = `ufu`.`feature_id`
				where
					`ufu`.`date` >= :start_date
					and `ufu`.`date` <= :end_date
					and `f`.`identifier` = :identifier
					and `ufu`.`subscription_id` = :subscriptionId
					and `ufu`.`status` = :usageStatus
					and `ufl`.`status` = :limitStatus
				LIMIT 1
			', [
				':start_date' => $start,
				':end_date' => $end,
				':identifier' => $identifier,
				':subscriptionId' => $subscriptionId,
				':usageStatus' => 'active',
				':limitStatus' => 'active'
			]);

			return $this->left($limit[0]['limit'], $limit[0]['used_quantity']);
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;
		}
	}

	/**
	 * finds the left quantity
	 * @param  [null or integer] $limit
	 * @param  integer $usage
	 * @return left quantity
	 */
	public function left($limit, $usage)
	{

		if(! is_null($limit))
		{
			return $limit - $usage;
		}

		return null;
	}


	public function getLimitOfFeatureSubscribed($subscriptionId, $featureIdentifier)
	{
		try
		{
			return $this->db->table(Config::get('throttle::tables.subscription_feature_limit').' AS ufl')
				->join(Config::get('throttle::tables.features').' AS f', 'f.id', '=', 'ufl.feature_id')
				->where('ufl.subscription_id', $subscriptionId)
				->where('f.identifier', $featureIdentifier)
				->where('ufl.status', 'active')
				->select('ufl.limit')
				->first();
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;
		}
	}

	/**
	 * find the left quantity on refresh
	 * @param  [integer] $subscriptionId
	 * @param  [string] $featureIdentifier
	 * @param  [integer] $usage
	 * @return left quantity on refresh
	 */
	public function leftOnRefresh($subscriptionId, $featureIdentifier, $usage)
	{
		try
		{
			$limit = $this->getLimitOfFeatureSubscribed($subscriptionId, $featureIdentifier);

			return $this->left($limit['limit'], $usage);
		}
		catch(\Exception $e)
		{
			throw new Exceptions\InternalException;
		}
	}
}
