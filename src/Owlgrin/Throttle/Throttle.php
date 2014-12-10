<?php namespace Owlgrin\Throttle;

use Carbon\Carbon;

use Owlgrin\Throttle\Biller\Biller;
use Owlgrin\Throttle\Subscriber\SubscriberRepo as Subscriber;
use Owlgrin\Throttle\Plan\PlanRepo as Plan;
use Owlgrin\Throttle\Exceptions;
use Owlgrin\Throttle\Redis\RedisStorage as Redis;
use Owlgrin\Throttle\Period\PeriodInterface;
use Owlgrin\Throttle\Pack\PackRepo;
use Owlgrin\Throttle\Period\ThrottlePeriod;
/**
 * The Throttle core
 */
class Throttle {

	protected $biller; 
	protected $subscriber;
	protected $plan;
	protected $redis;
	protected $pack;

	protected $user = null;
	protected $subscription = null;

	public function __construct(Biller $biller, Subscriber $subscriber, Plan $plan, Redis $redis, PackRepo $pack)
	{
		$this->biller = $biller;
		$this->subscriber = $subscriber;
		$this->plan = $plan;
		$this->redis = $redis;
		$this->pack = $pack;
	}

	//sets users details at the time of initialisation
	public function user($user)
	{
		$this->user = $user;
		$this->subscription = $this->subscriber->subscription($this->user);
		$this->features = $this->plan->getFeaturesByPlan($this->subscription['plan_id']);
		$this->usages = $this->setUsages();
		// $this->limits = $this->setLimit($this->subscription['subscription_id']);

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

	// public function getLimits()
	// {
	// 	return $this->limits;
	// }


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

	// private function setLimit($subscriptionId)
	// {
	// 	$initializeLimit = [];

	// 	$limits = $this->subscriber->getLimit($subscriptionId);

	// 	foreach ($limits as $index => $limit) 
	// 	{
	// 		$initializeLimit[$limit['identifier']] = $limit['limit'];
	// 	}

	// 	return $initializeLimit;
	// }

	//subscribes a user to a specific plan
	public function subscribe($planIdentifier)
	{
		return $this->subscriber->subscribe($this->user, $planIdentifier);
	}

	//unsubscribes a user to a specific plan
	public function unsubscribe($planIdentifier)
	{
		return $this->subscriber->unsubscribe($this->user);
	}
	
	public function usage(PeriodInterface $period)
	{
		return $this->subscriber->getUserUsage($this->subscription['subscription_id'], $period);
	}

	public function can($identifier, $count = 1, $reduce = true, PeriodInterface $period)
	{
		$limit = $this->redis->hashGet("throttle:hashes:limit:{$identifier}", $this->user);

		if($limit === false)
		{
	 		$limit = $this->subscriber->left($this->subscription['subscription_id'], $identifier, $period->start(), $period->end());

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

	public function bill($startDate, $endDate)
	{
		return $this->biller->bill($this->user, $startDate, $endDate);
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

	public function addPack($packId, $units, PeriodInterface $period)
	{
		$this->pack->addPackForUser($packId, $this->subscription['subscription_id'], $units, $period);
	}

	public function removePack($packId, $units, PeriodInterface $period)
	{
		$this->pack->removePacksForUser($packId, $this->subscription['subscription_id'], $units, $period);
	}
}