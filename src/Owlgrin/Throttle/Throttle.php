<?php namespace Owlgrin\Throttle;

use Owlgrin\Throttle\Biller\Biller;
use Owlgrin\Throttle\Subscriber\SubscriberRepo as Subscriber;
use Owlgrin\Throttle\Plan\PlanRepo as Plan;
use Owlgrin\Throttle\Exceptions;
use Owlgrin\Throttle\Redis\RedisStorage as Redis;
/**
 * The Throttle core
 */
class Throttle {

	protected $biller; 
	protected $subscriber;
	protected $plan;
	protected $redis;

	protected $user = null;
	protected $subscription = null;

	public function __construct(Biller $biller, Subscriber $subscriber, Plan $plan, Redis $redis)
	{
		$this->biller = $biller;
		$this->subscriber = $subscriber;
		$this->plan = $plan;
		$this->redis = $redis;
	}

	//sets users details at the time of initialisation
	public function user($user)
	{
		$this->user = $user;
		$this->subscription = $this->subscriber->subscription($this->user);
		$this->features = $this->plan->getFeaturesByPlan($this->subscription['planId']);
		$this->usages = $this->setUsages();

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

	//this function sets used count
	public function setUsedCount($identifier, $value = 0)
	{
		$this->used[$identifier] = $value;

		return $this;
	}

	//this function returns used count
	public function getUsedCount($identifier)
	{
		return $this->used[$identifier];
	}

	private function setUsages()
	{
		foreach($this->features as $index => $feature) 
		{
			if( ! isset($usages[$feature['identifier']]))
			{
				$this->usages[$feature['identifier']] = 0;
			}
		}	

		return $this->usages;	
	}

	//subscribes a user to a specific plan
	public function subscribe($planIdentifier)
	{
		return $this->subscriber->subscribe($this->user, $planIdentifier);
	}

	public function attempt($identifier, $count = 1)
	{
		$this->subscriber->attempt($this->subscription['subscriptionId'], $identifier, $count);
	}

	public function softAttempt($identifier, $count = 1)
	{
		$this->subscriber->softAttempt($this->subscription['subscriptionId'], $identifier, $count);
	}

	public function can($identifier, $count = 1, $reduce = true)
	{
		$limit = $this->redis->hashGet("throttle:hashes:limit:{$identifier}", $this->user);

		if($limit === false)
		{
			$limit = $limit = $this->subscriber->left($this->subscription['subscriptionId'], $identifier);

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
		return $this->subscriber->increment($this->subscription['subscriptionId'], $identifier, $quantity);
	}
	
	public function plan($plan)
	{
		return $this->plan->add($plan);
	}

	public function unsetLimit($identifier)
	{
		$this->redis->hashUnset("throttle:hashes:limit:{$identifier}", $this->getUser());
	}

	public function exiting()
	{
		$usages = $this->getUsages();
		
		foreach($usages as $entity => $value) 
		{
			if($value > 0 or $value < 0)
			{
				$this->increment($entity, $value);			
			}

			$this->unsetLimit($entity);
		}
	}
}