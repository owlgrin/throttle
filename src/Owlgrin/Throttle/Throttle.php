<?php namespace Owlgrin\Throttle;

use Owlgrin\Throttle\Biller\Biller;
use Owlgrin\Throttle\Subscriber\SubscriberRepo as Subscriber;
use Owlgrin\Throttle\Plan\PlanRepo as Plan;
use Owlgrin\Throttle\Exceptions;
use Owlgrin\Throttle\Period\PeriodRepo;
use Owlgrin\Throttle\Period\PeriodInterface;
use Owlgrin\Throttle\Limiter\LimiterInterface;
use Owlgrin\Throttle\Period\CurrentSubscriptionPeriod;
/**
 * The Throttle core
 */
class Throttle {

	protected $biller;
	protected $subscriber;
	protected $plan;
	protected $periodRepo;
	protected $limiter;

	protected $attempts = [];
	protected $user = null;
	protected $subscription = null;

	public function __construct(Biller $biller, Subscriber $subscriber, Plan $plan, PeriodRepo $periodRepo, LimiterInterface $limiter)
	{
		$this->biller = $biller;
		$this->subscriber = $subscriber;
		$this->plan = $plan;
		$this->periodRepo = $periodRepo;
		$this->limiter = $limiter;
	}

	//sets user's details at the time of initialisation
	public function user($user)
	{
		$this->user = $user;

		$this->subscription = $this->subscriber->subscription($this->user);
		if(! $this->subscription)
			throw new Exceptions\NoSubscriptionException;

		$this->features = $this->plan->getFeaturesByPlan($this->subscription['plan_id']);
		$this->period = new CurrentSubscriptionPeriod($this->user);

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

	public function getPeriod()
	{
		return $this->period;
	}

	//subscribes a user to a specific plan
	public function subscribe($user, $planIdentifier)
	{
		$user = is_null($user) ? $this->user : $user;

		 if($this->subscriber->subscription($user))
			throw new Exceptions\SubscriptionExistsException;

		$this->subscriber->subscribe($user, $planIdentifier);

		$this->user($user);
	}

	//unsubscribes a user to a specific plan
	public function unsubscribe($user = null)
	{
		$user = is_null($user) ? $this->user : $user;

		if(! $this->subscriber->subscription($user))
			throw new Exceptions\NoSubscriptionException;

		$this->subscriber->unsubscribe($user);
	}

	public function setPeriod(PeriodInterface $period)
	{
		$this->period = $period;

		return $this;
	}

	public function addPeriod(PeriodInterface $period)
	{
		if(is_null($this->subscription))
			throw new Exceptions\NoSubscriptionException;

		$this->periodRepo->store($this->subscription['id'], $period->start(), $period->end());

		return $this->user($this->user);
	}

	public function attempt($identifier, $count = 1)
	{
		if(is_null(array_get($this->features, $identifier))) return;

		if(is_null($this->subscription))
			throw new Exceptions\NoSubscriptionException;

		$this->limiter->attempt($this->subscription['id'], $identifier, $count, $this->period->start(), $this->period->end());

		$this->attempts[$identifier] = $count;
	}

	public function can($identifier, $quantity = 1)
	{
		if(is_null(array_get($this->features, $identifier))) return true;

		return $this->attempts[$identifier] >= $quantity;
	}

	public function consume($identifier, $quantity = 1)
	{
		if(is_null(array_get($this->attempts, $identifier))) return;

		$this->attempts[$identifier] -= $quantity;
	}

	public function softAttempt($identifier, $count = 1)
	{
		if(is_null(array_get($this->features, $identifier))) return;

		if(is_null($this->subscription))
			throw new Exceptions\NoSubscriptionException;

		$this->attempts[$identifier] = $this->limiter->softAttempt($this->subscription['id'], $identifier, $count, $this->period->start(), $this->period->end());
	}

	public function bill($period = null)
	{
		// if passed period is either null or not an instance of PeriodInterface
		if(is_null($period) or ! ($period instanceof PeriodInterface)) $period = $this->period;

		if(is_null($this->subscription))
			throw new Exceptions\NoSubscriptionException;

		return $this->biller->bill($this->subscription['id'], $period->start(), $period->end());
	}

	public function estimate($usages)
	{
		return $this->biller->estimate($usages);
	}

	//increments usage of a particular identifier
	public function hit($identifier, $quantity = 1)
	{
		if(is_null(array_get($this->features, $identifier))) return;

		if(is_null($this->subscription))
			throw new Exceptions\NoSubscriptionException;

		$this->subscriber->increment($this->subscription['id'], $identifier, $quantity);
	}

	//increments usage of a particular identifier
	public function redeem()
	{
		foreach($this->attempts as $identifier => $quantity)
		{
			if($quantity > 0)
			{
				$this->hit($identifier, -1 * $quantity);
			}
		}
	}

	public function addPlan($plan)
	{
		return $this->plan->add($plan);
	}

	public function getUsage($period = null)
	{
		// if passed period is either null or not an instance of PeriodInterface
		if(is_null($period) or ! ($period instanceof PeriodInterface)) $period = $this->period;

		if(! $this->subscription)
			throw new Exceptions\NoSubscriptionException;

		return $this->subscriber->getUsage($this->subscription['id'], $period->start(), $period->end());
	}

	//switch user's plan
	public function switchPlan($planIdentifier)
	{
		if(! $this->subscription)
			throw new Exceptions\NoSubscriptionException;

		$this->subscriber->switchPlan($this->subscription['id'], $planIdentifier);

		$this->user($this->user);
	}
}