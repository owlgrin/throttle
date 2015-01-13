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
			//starting a transition
			$this->db->beginTransaction();

			$this->unsetPeriod($subscriptionId);

			return $this->db->table(Config::get('throttle::tables.subscription_period'))->insertGetId([
				'subscription_id' => $subscriptionId,
				'starts_at' => $startDate,
				'ends_at' => $endDate,
				'is_active' => 1
			]);

			$this->db->commit();
		}
		catch(PDOException $e)
		{
			$this->db->rollback();
			
			throw new Exceptions\InternalException("Something went wrong with database");	
		}
	}

	public function getPeriodBySubscription($subscriptionId)
	{
		try
		{
			return $this->db->table(Config::get('throttle::tables.subscription_period'))
				->where('subscription_id', $subscriptionId)
				->where('is_active', 1)
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
				->where('is_active', 1)
				->update(array('is_active' => 0));
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
				->where('sp.is_active', 1)
				->update(['sp.is_active' => 0]);
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
				->where('sp.is_active', 1)
				->first();
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException("Something went wrong with database");	
		}
	}

}