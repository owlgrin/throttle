<?php namespace Owlgrin\Throttle\Limiter;

use Carbon\Carbon;

use Illuminate\Database\DatabaseManager as Database;
use Owlgrin\Throttle\Limiter\LimiterInterface;
use Owlgrin\Throttle\Subscriber\SubscriberRepo;
use Owlgrin\Throttle\Exceptions;
use PDOException, Config;

class Limiter implements LimiterInterface {

	protected $db;
	protected $subscriberRepo;

	public function __construct(Database $db, SubscriberRepo $subscriberRepo)
	{
		$this->db = $db;
		$this->subscriberRepo = $subscriberRepo;
	}


	public function attempt($subscriptionId, $identifier, $count = 1, $start, $end)
	{
		try
		{		
			//starting a transition
			$this->db->beginTransaction();

			$limit = $this->subscriberRepo->left($subscriptionId, $identifier, $start, $end);

			if( ! $this->isHardLimitAllowed($limit, $count))
			{
				throw new Exceptions\LimitExceededException;
			}

			$this->subscriberRepo->increment($subscriptionId, $identifier, $count);

			//commition the work after processing
			$this->db->commit();
		}
		catch(Exceptions\LimitExceededException $e)
		{
			throw new Exceptions\LimitExceededException;
		}
		catch(PDOException $e)
		{
			//rollback if failed
			$this->db->rollback();

			throw new Exceptions\MySqlExceptrion("Something went wrong with database");	
		}
	}

	public function softAttempt($subscriptionId, $identifier, $count = 1, $start, $end)
	{
		try
		{
			//starting a transition
			$this->db->beginTransaction();

			$limit = $this->subscriberRepo->left($subscriptionId, $identifier, $start, $end);

			$incrementCount = $this->isSoftLimitAllowed($limit, $count);

			$this->subscriberRepo->increment($subscriptionId, $identifier, $incrementCount);	

			//commition the work after processing
			$this->db->commit();	

			return $incrementCount;
		}
		catch(PDOException $e)
		{
			//rollback if failed
			$this->db->rollback();

			throw new Exceptions\MySqlExceptrion("Something went wrong with database");	
		}
	}

	private function isSoftLimitAllowed($limitLeft, $countRequested)
	{
		if(! $this->checkIfNull($limitLeft) )
		{
			return $limitLeft >= $countRequested ? $countRequested : $limitLeft;
		}
		
		return $countRequested;
	}

	private function isHardLimitAllowed($limitLeft, $countRequested)
	{
		if(! $this->checkIfNull($limitLeft) )
			return $limitLeft >= $countRequested;
		
		return true;
	}

	private function checkIfNull($limit)
	{
		return is_null($limit);
	}
}