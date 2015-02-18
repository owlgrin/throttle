<?php namespace Owlgrin\Throttle\Limiter;

use Carbon\Carbon;

use Illuminate\Database\DatabaseManager as Database;
use Owlgrin\Throttle\Limiter\LimiterInterface;
use Owlgrin\Throttle\Subscriber\SubscriberRepo;
use Owlgrin\Throttle\Usage\UsageRepo;
use Owlgrin\Throttle\Exceptions;
use PDOException, Config;

class Limiter implements LimiterInterface {

	protected $db;
	protected $subscriberRepo;
	protected $usageRepo;

	public function __construct(Database $db, SubscriberRepo $subscriberRepo, UsageRepo $usageRepo)
	{
		$this->db = $db;
		$this->subscriberRepo = $subscriberRepo;
		$this->usageRepo = $usageRepo;
	}


	public function attempt($subscriptionId, $identifier, $count = 1, $start, $end, $increment = true)
	{
		try
		{
			//starting a transition
			$this->db->beginTransaction();

			$limit = $this->subscriberRepo->left($subscriptionId, $identifier, $start, $end);

			if( ! $this->hasAvailableQuota($limit, $count))
			{
				throw new Exceptions\LimitExceededException('throttle::responses.message.limit_excedeed', ['attributes' => $identifier]);
			}

			if($increment)
			{
				$this->subscriberRepo->increment($subscriptionId, $identifier, $count);
			}

			//commition the work after processing
			$this->db->commit();
		}
		catch(PDOException $e)
		{
			//rollback if failed
			$this->db->rollback();

			throw new Exceptions\InternalException;
		}
	}

	public function softAttempt($subscriptionId, $identifier, $count = 1, $start, $end)
	{
		try
		{
			//starting a transition
			$this->db->beginTransaction();

			$limit = $this->subscriberRepo->left($subscriptionId, $identifier, $start, $end);

			$availableQuota = $this->getAvailableQuota($limit, $count);

			$this->subscriberRepo->increment($subscriptionId, $identifier, $availableQuota);

			//commition the work after processing
			$this->db->commit();

			return $availableQuota;
		}
		catch(PDOException $e)
		{
			//rollback if failed
			$this->db->rollback();

			throw new Exceptions\InternalException;
		}
	}

	private function getAvailableQuota($limitLeft, $countRequested)
	{
		if( ! is_null($limitLeft))
		{
			return $limitLeft >= $countRequested ? $countRequested : $limitLeft;
		}

		return $countRequested;
	}

	private function hasAvailableQuota($limitLeft, $countRequested)
	{
		if( ! is_null($limitLeft))
			return $limitLeft >= $countRequested;

		return true;
	}

	public function refreshOnAttempt($userId, $subscriptionId, $identifier, $count = 1, $start, $end)
	{
		try
		{
			//starting a transition
			$this->db->beginTransaction();

			$this->attempt($subscriptionId, $identifier, $count, $start, $end, $increment = false);

			//find base usage of the identifier
			$refresh = $this->usageRepo->getUsageForFeature($userId, $identifier, $date = null);

			//then update usage
			$this->subscriberRepo->refreshUsage($subscriptionId, $identifier, $refresh + $count);

			//commition the work after processing
			$this->db->commit();
		}
		catch(PDOException $e)
		{
			//rollback if failed
			$this->db->rollback();

			throw new Exceptions\InternalException;
		}
	}
}