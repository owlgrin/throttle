<?php namespace Owlgrin\Throttle;

use Owlgrin\Throttle\Biller\Biller;
use Owlgrin\Throttle\Subscriber\SubscriberRepo as Subscriber;
use Owlgrin\Throttle\Plan\PlanRepo as Plan;
use Owlgrin\Throttle\Exceptions;
use Owlgrin\Throttle\Redis\RedisStorage as Redis;
use Owlgrin\Throttle\Pack\PackRepo;
use Owlgrin\Throttle\Period\PeriodRepo;
/**
 * The Throttle core
 */
class Throttle {

	protected $biller; 
	protected $subscriber;
	protected $plan;
	protected $redis;
	protected $pack;
	protected $periodRepo;	

	protected $user = null;
	protected $subscription = null;

	public function __construct(Biller $biller, Subscriber $subscriber, Plan $plan, Redis $redis, PackRepo $pack, PeriodRepo $periodRepo)
	{
		$this->biller = $biller;
		$this->subscriber = $subscriber;
		$this->plan = $plan;
		$this->redis = $redis;
		$this->pack = $pack;
		$this->periodRepo = $periodRepo;
	}

	//sets users details at the time of initialisation
	public function user($user)
	{
		$this->user = $user;
		$this->subscription = $this->subscriber->subscription($this->user);
		$this->features = $this->plan->getFeaturesByPlan($this->subscription['plan_id']);
		$this->usages = $this->setUsages();
		$this->period = $this->periodRepo->getPeriodBySubscription($this->subscription['subscription_id']);

		return $this;
	}

	//returns user's details
	public function getUser()
	{
		return $this->user;
	}

	//returns user's subscription
	public function getSubscription()
	{
		return $this->subscription;
	}

	public function getFeatures()
	{
		return $this->features;
	}

	public function getUsages()
	{
		return $this->usages;
	}

	public function incrementUsages($identifier, $count = 1)
	{
		$this->usages[$identifier] += $count;
	}

	private function setUsages()
	{
		$initializeUsage = [];

		foreach($this->features as $index => $feature) 
		{
			if( ! isset($usages[$feature['identifier']]))
			{
				$initializeUsage[$feature['identifier']] = 0;
			}
		}	

		return $initializeUsage;	
	}

	//subscribes a user to a specific plan
	public function subscribe($planIdentifier, $user = null)
	{
		$user = is_null($user) ? $this->user : $user;

		$this->subscriber->subscribe($user, $planIdentifier);

		$this->user($user);
	}

	//unsubscribes a user to a specific plan
	public function unsubscribe($planIdentifier)
	{
		return $this->subscriber->unsubscribe($this->user);
	}
	
	public function usage()
	{
		return $this->subscriber->getUserUsage($this->subscription['subscription_id'], $this->period['starts_at'], $this->period['ends_at']);
	}

	public function period($startDate, $endDate)
	{
		$this->periodRepo->store($this->subscription['subscription_id'], $startDate, $endDate);

		return $this->user($this->user);
	}

	public function updatePeriod($startDate, $endDate)
	{
		$this->periodRepo->unsetPeriod($this->subscription['subscription_id']);

		$this->period($startDate, $endDate);
	}

	public function can($identifier, $count = 1, $reduce = true)
	{
		$limit = $this->redis->hashGet("throttle:hashes:limit:{$identifier}", $this->user);
	
		if($limit === false)
		{
	 		$limit = $this->subscriber->left($this->subscription['subscription_id'], $identifier, $this->period['starts_at'], $this->period['ends_at']);

			if(! is_null($limit)) 
			{
				$limit = $limit - $this->usages[$identifier];
			}
		
			$this->redis->hashSet("throttle:hashes:limit:{$identifier}", $this->user, $limit);
		}
		
		if($limit === "" or is_null($limit)) 
		{
			return true;
		}

		else if($limit >= $count)
		{
			if($reduce === true)
			{
				$this->redis->hashIncrement("throttle:hashes:limit:{$identifier}", $this->user, -($count));
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	public function bill()
	{
		return $this->biller->bill($this->user, $this->period['starts_at'], $this->period['ends_at']);
	}

	public function estimate($usages)
	{
		return $this->biller->estimate($usages);
	}

	//increments usage of a particular identifier
	public function increment($identifier, $quantity = 1)
	{
		return $this->subscriber->increment($this->subscription['subscription_id'], $identifier, $quantity);
	}
	
	public function plan($plan)
	{
		return $this->plan->add($plan);
	}

	public function unsetLimit($identifier)
	{
		$this->redis->hashUnset("throttle:hashes:limit:{$identifier}", $this->getUser());
	}

	public function flush()
	{
		$usages = $this->getUsages();
	
		foreach($usages as $entity => $value) 
		{
			if($value !== 0)
			{
				$this->increment($entity, $value);			
			}

			$this->unsetLimit($entity);
		}
	}

	public function addPack($packId, $units)
	{
		$this->pack->addPackForUser($packId, $this->subscription['subscription_id'], $units);
	}

	public function removePack($packId, $units)
	{
		$this->pack->removePacksForUser($packId, $this->subscription['subscription_id'], $units);
	}
}