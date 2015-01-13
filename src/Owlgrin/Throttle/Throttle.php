<?php namespace Owlgrin\Throttle;

use Owlgrin\Throttle\Biller\Biller;
use Owlgrin\Throttle\Subscriber\SubscriberRepo as Subscriber;
use Owlgrin\Throttle\Plan\PlanRepo as Plan;
use Owlgrin\Throttle\Exceptions;
use Owlgrin\Throttle\Period\PeriodRepo;
use Owlgrin\Throttle\Period\PeriodInterface;
use Owlgrin\Throttle\Limiter\LimiterInterface;
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

	//sets users details at the time of initialisation
	public function user($user)
	{
		$this->user = $user;
		$this->subscription = $this->subscriber->subscription($this->user);
		$this->features = $this->plan->getFeaturesByPlan($this->subscription['plan_id']);
		$this->period = $this->periodRepo->getPeriodBySubscription($this->subscription['id']);

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
	public function subscribe($planIdentifier, $user = null)
	{
		$user = is_null($user) ? $this->user : $user;

		 if($this->subscriber->subscription($user))
			throw new Exceptions\InternalException('Subscription already exists');

		$subscriptionId = $this->subscriber->subscribe($user, $planIdentifier);
		 
		$this->user($user);

		return $subscriptionId;
	}

	//unsubscribes a user to a specific plan
	public function unsubscribe($user = null)
	{
		$user = is_null($user) ? $this->user : $user;

		if(! $this->subscriber->subscription($user))
			throw new Exceptions\SubscriptionException('No Subscription exists');

		$this->subscriber->unsubscribe($user);
	}
	
	public function setPeriod(PeriodInterface $period)
	{
		$this->period = ['starts_at' => $period->start(), 'ends_at' => $period->end()];

		return $this;
	}

	public function addPeriod(PeriodInterface $period, $subscriptionId = null)
	{
		$subscriptionId = is_null($subscriptionId) ? $this->subscription['id'] : $subscriptionId;

		if(is_null($this->subscription))
			throw new Exceptions\SubscriptionException('No Subscription exists');
	
		$this->periodRepo->unsetPeriod($subscriptionId);

		$this->periodRepo->store($subscriptionId, $period->start(), $period->end());

		return $this->user($this->user);
	}

	public function attempt($identifier, $count = 1)
	{
		if(is_null($this->subscription))
			throw new Exceptions\SubscriptionException('No Subscription exists');

		$this->limiter->attempt($this->subscription['id'], $identifier, $count, $this->period['starts_at'], $this->period['ends_at']);

		$this->attempts[$identifier] = $count;
	}

	public function can($identifier, $quantity = 0)
	{
		return $this->attempts[$identifier] > $quantity;
	}

	public function consume($identifier, $quantity = 1)
	{
		$this->attempts[$identifier] -= $quantity;
	}

	public function softAttempt($identifier, $count = 1)
	{
		if(is_null($this->subscription))
			throw new Exceptions\SubscriptionException('No Subscription exists');

		$this->attempts[$identifier] = $this->limiter->softAttempt($this->subscription['id'], $identifier, $count, $this->period['starts_at'], $this->period['ends_at']);
	}

	public function bill()
	{
		if(is_null($this->subscription))
			throw new Exceptions\SubscriptionException('No Subscription exists');

		return $this->biller->bill($this->subscription['id'], $this->period['starts_at'], $this->period['ends_at']);
	}

	public function estimate($usages)
	{
		return $this->biller->estimate($usages);
	}

	//increments usage of a particular identifier
	public function hit($identifier, $quantity = 1)
	{
		if(is_null($this->subscription))
			throw new Exceptions\SubscriptionException('No Subscription exists');

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

	public function getUsage($user = null, PeriodInterface $period)
	{
		$user = is_null($user) ? $this->user : $user;

		if(! $this->subscriber->subscription($user))
			throw new Exceptions\SubscriptionException('No Subscription exists');

		return $this->subscriber->getUsage("3", $period->start(), $period->end());
	}
}