<?php namespace Owlgrin\Throttle\Period;

use Illuminate\Database\DatabaseManager as Database;
use Owlgrin\Throttle\Period\PeriodRepo;
use Owlgrin\Throttle\Exceptions;

use Config, PDOException;

class DbPeriodRepo implements PeriodRepo {

	public function __construct(Database $db)
	{
		$this->db = $db;
	}

	public function store($subscriptionId, $startDate, $endDate)
	{
		try
		{
			return $this->db->table(Config::get('throttle::tables.subscription_period'))->insertGetId([
				'subscription_id' => $subscriptionId,
				'starts_at' => $startDate,
				'ends_at' => $endDate,
				'status' => 1
			]);
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException("Something went wrong with database");	
		}
	}

	public function getPeriodBySubscription($subscriptionId)
	{
		try
		{
			return $this->db->table(Config::get('throttle::tables.subscription_period'))
				->where('subscription_id', $subscriptionId)
				->where('status', 1)
				->first();
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException("Something went wrong with database");	
		}
	}

	public function unsetPeriod($subscriptionId)
	{
		try
		{
			$this->db->table(Config::get('throttle::tables.subscription_period'))
				->where('subscription_id', $subscriptionId)
				->where('status', 1)
				->update(['status' => 0]);
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException("Something went wrong with database");	
		}
	}

	public function unsetPeriodOfUser($userId)
	{
		try
		{
			$this->db->table(Config::get('throttle::tables.subscription_period').' AS sp')
				->join(Config::get('throttle::tables.subscriptions').' AS s', 's.id', '=', 'sp.subscription_id')
				->where('s.user_id', $userId)
				->where('sp.status', 1)
				->update(['sp.status' => 0]);
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException("Something went wrong with database");	
		}
	}

	public function getPeriodByUser($userId)
	{
		try
		{
			return $this->db->table(Config::get('throttle::tables.subscription_period').' AS sp')
				->join(Config::get('throttle::tables.subscriptions').' AS s', 's.id', '=', 'sp.subscription_id')
				->where('s.user_id', $userId)
				->where('sp.status', 1)
				->first();
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException("Something went wrong with database");	
		}
	}

}