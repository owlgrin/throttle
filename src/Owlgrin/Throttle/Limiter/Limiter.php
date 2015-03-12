<?php namespace Owlgrin\Throttle\Limiter;

use Carbon\Carbon;

use Illuminate\Database\DatabaseManager as Database;
use Owlgrin\Throttle\Limiter\LimiterInterface;
use Owlgrin\Throttle\Subscriber\SubscriberRepo;
use Owlgrin\Throttle\Left\LeftRepo;
use Owlgrin\Throttle\Usage\UsageRepo;
use Owlgrin\Throttle\Exceptions;
use PDOException, Config;

class Limiter implements LimiterInterface {

	protected $db;
	protected $subscriberRepo;
	protected $usageRepo;
	protected $leftRepo;

	public function __construct(Database $db, SubscriberRepo $subscriberRepo, UsageRepo $usageRepo, LeftRepo $leftRepo)
	{
		$this->db = $db;
		$this->subscriberRepo = $subscriberRepo;
		$this->usageRepo = $usageRepo;
		$this->leftRepo = $leftRepo;
	}


	public function attempt($subscriptionId, $identifier, $count = 1, $start, $end)
	{
		try
		{
			//starting a transition
			$this->db->beginTransaction();

			$left = $this->leftRepo->leftOnAttempt($subscriptionId, $identifier, $start, $end);

			if( ! $this->hasAvailableQuota($left, $count))
			{
				throw new Exceptions\LimitExceededException('throttle::responses.message.limit_excedeed', ['attributes' => $identifier]);
			}

			$this->subscriberRepo->increment($subscriptionId, $identifier, $count);

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

			$left = $this->leftRepo->leftOnAttempt($subscriptionId, $identifier, $start, $end);

			$availableQuota = $this->getAvailableQuota($left, $count);

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

	public function refreshWithAttempt($userId, $subscriptionId, $identifier, $count = 1, $start, $end)
	{
		try
		{
			//starting a transition
			$this->db->beginTransaction();

			//find base usage of the identifier
			$refreshUsage = $this->usageRepo->getUsageForFeature($userId, $identifier, $date = null);

			//the left usage
			$left = $this->leftRepo->leftOnRefresh($subscriptionId, $identifier, $refreshUsage);

			if( ! $this->hasAvailableQuota($left, $count))
			{
				throw new Exceptions\LimitExceededException('throttle::responses.message.limit_excedeed', ['attributes' => $identifier]);
			}

			//then update usage
			$this->subscriberRepo->refreshUsage($subscriptionId, $identifier, $refreshUsage + $count);

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