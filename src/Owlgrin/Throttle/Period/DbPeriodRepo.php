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

			if( ! $this->isValidPeriodForSubscription($subscriptionId, $startDate, $endDate))
				throw new Exceptions\InvalidInputException('Invalid period(' . $startDate . '-' . $endDate . ') for Subscription ID: ' . $subscriptionId);

			$this->unsetPeriod($subscriptionId);

			$periodId = $this->db->table(Config::get('throttle::tables.subscription_period'))->insertGetId([
				'subscription_id' => $subscriptionId,
				'starts_at' => $startDate,
				'ends_at' => $endDate,
				'is_active' => 1
			]);

			$this->db->commit();

			return $periodId;
		}
		catch(PDOException $e)
		{
			$this->db->rollback();
			
			throw new Exceptions\InternalException;	
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
			throw new Exceptions\InternalException;	
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
			throw new Exceptions\InternalException;	
		}
	}

	public function getActivePeriodBySubscription($subscriptionId)
	{
		try
		{
			$period = $this->db->table(Config::get('throttle::tables.subscription_period'). ' AS sp')
				->where('sp.subscription_id', $subscriptionId)
				->where('sp.is_active', 1)
				->select('sp.starts_at', 'sp.ends_at')
				->first();

			return $period;
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;	
		}
	}

	public function getActivePeriodByUser($userId)
	{
		try
		{
			$period = $this->db->table(Config::get('throttle::tables.subscription_period').' AS sp')
				->join(Config::get('throttle::tables.subscriptions').' AS s', 's.id', '=', 'sp.subscription_id')
				->where('s.user_id', $userId)
				->where('sp.is_active', 1)
				->select('sp.starts_at', 'sp.ends_at')
				->first();
			
			return $period;
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;	
		}
	}

	public function getCurrentPeriodBySubscription($subscriptionId, $date = null)
	{
		try
		{
			$query = $this->db->table(Config::get('throttle::tables.subscription_period'). ' AS sp')
				->where('sp.subscription_id', $subscriptionId)
				->select('sp.starts_at', 'sp.ends_at');

			if( ! is_null($date))
			{
				$query->where('sp.starts_at', '<=', $date);
				$query->where('sp.ends_at', '>=', $date);
			}
			else
			{
				$query->where('sp.starts_at', '<=', $this->db->raw('current_date()'));
				$query->where('sp.ends_at', '>=', $this->db->raw('current_date()'));
			}

			return $query->first();
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;	
		}
	}

	public function getCurrentPeriodByUser($userId, $date = null)
	{
		try
		{
			$query = $this->db->table(Config::get('throttle::tables.subscription_period').' AS sp')
				->join(Config::get('throttle::tables.subscriptions').' AS s', 's.id', '=', 'sp.subscription_id')
				->where('s.user_id', $userId)
				->select('sp.starts_at', 'sp.ends_at');

			if( ! is_null($date))
			{
				$query->where('sp.starts_at', '<=', $date);
				$query->where('sp.ends_at', '>=', $date);
			}
			else
			{
				$query->where('sp.starts_at', '<=', $this->db->raw('current_date()'));
				$query->where('sp.ends_at', '>=', $this->db->raw('current_date()'));
			}

			return $query->first();
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;	
		}
	}

	private function isValidPeriodForSubscription($subscriptionId, $startDate, $endDate)
	{
		try
		{
			$periods = $this->db->table(Config::get('throttle::tables.subscription_period').' AS sp')
					->where('sp.subscription_id', $subscriptionId)
					->where(function($query) use ($startDate, $endDate)
		            {
						$query->whereBetween('sp.ends_at', array($startDate, $endDate))
							  ->orWhereBetween('sp.starts_at', array($startDate, $endDate));
		            })->get();

			return count($periods) == 0; // valid only if there's no overlapping periods		
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException;	
		}
	}
}