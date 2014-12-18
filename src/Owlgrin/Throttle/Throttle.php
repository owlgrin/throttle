<?php namespace Owlgrin\Throttle;

use Owlgrin\Throttle\Biller\Biller;
use Owlgrin\Throttle\Subscriber\SubscriberRepo as Subscriber;
use Owlgrin\Throttle\Plan\PlanRepo as Plan;
use Owlgrin\Throttle\Exceptions;
use Owlgrin\Throttle\Pack\PackRepo;
use Owlgrin\Throttle\Period\PeriodRepo;
use Owlgrin\Throttle\Limiter\LimiterInterface;
/**
 * The Throttle core
 */
class Throttle {

	protected $biller; 
	protected $subscriber;
	protected $plan;
	protected $pack;
	protected $periodRepo;	
	protected $limiter;

	protected $user = null;
	protected $subscription = null;

	public function __construct(Biller $biller, Subscriber $subscriber, Plan $plan, PackRepo $pack, PeriodRepo $periodRepo, LimiterInterface $limiter)
	{
		$this->biller = $biller;
		$this->subscriber = $subscriber;
		$this->plan = $plan;
		$this->pack = $pack;
		$this->periodRepo = $periodRepo;
		$this->limiter = $limiter;
	}

	//sets users details at the time of initialisation
	public function user($user)
	{
		$this->user = $user;
		$this->subscription = $this->subscriber->subscription($this->user);
		$this->features = $this->plan->getFeaturesByPlan($this->subscription['plan_id']);
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

	public function getPeriod()
	{
		return $this->period;
	}

	//subscribes a user to a specific plan
	public function subscribe($planIdentifier, $user = null)
	{
		$user = is_null($user) ? $this->user : $user;

		 if($this->subscriber->subscription($user))
			throw new Exceptions\ForbiddenException('Subscription already exists');

		$this->subscriber->subscribe($user, $planIdentifier);
		 
		$this->user($user);
	}

	//unsubscribes a user to a specific plan
	public function unsubscribe($planIdentifier)
	{
		if(is_null($this->subscription))
			throw new Exceptions\ForbiddenException('No Subscription exists');

		return $this->subscriber->unsubscribe($this->user);
	}
	
	public function usage()
	{
		if(is_null($this->subscription))
			throw new Exceptions\ForbiddenException('No Subscription exists');

		return $this->subscriber->getUserUsage($this->subscription['subscription_id'], $this->period['starts_at'], $this->period['ends_at']);
	}

	public function setPeriod(PeriodInterface $period)
	{
		$this->period = ['starts_at' => $period->start(), 'ends_at' => $period->end()];

		return $this;
	}

	public function addPeriod(PeriodInterface $period)
	{
		if(is_null($this->subscription))
			throw new Exceptions\ForbiddenException('No Subscription exists');

		$this->periodRepo->unsetPeriod($this->subscription['subscription_id']);

		$this->periodRepo->store($this->subscription['subscription_id'], $period->start(), $period->end());

		return $this->user($this->user);
	}

	public function attempt($identifier, $count = 1)
	{
		if(is_null($this->subscription))
			throw new Exceptions\ForbiddenException('No Subscription exists');

		$this->limiter->attempt($this->subscription['subscription_id'], $identifier, $count, $this->period['starts_at'], $this->period['ends_at']);
	}

	public function softAttempt($identifier, $count = 1)
	{
		if(is_null($this->subscription))
			throw new Exceptions\ForbiddenException('No Subscription exists');

		return $this->limiter->softAttempt($this->subscription['subscription_id'], $identifier, $count, $this->period['starts_at'], $this->period['ends_at']);
	}

	public function bill()
	{
		if(is_null($this->subscription))
			throw new Exceptions\ForbiddenException('No Subscription exists');

		return $this->biller->bill($this->user, $this->period['starts_at'], $this->period['ends_at']);
	}

	public function estimate($usages)
	{
		return $this->biller->estimate($usages);
	}

	//increments usage of a particular identifier
	public function increment($identifier, $quantity = 1)
	{
		if(is_null($this->subscription))
			throw new Exceptions\ForbiddenException('No Subscription exists');

		return $this->subscriber->increment($this->subscription['subscription_id'], $identifier, $quantity);
	}

	//increments usage of a particular identifier
	public function redeem($identifier, $quantity = 1)
	{
		if(is_null($this->subscription))
			throw new Exceptions\ForbiddenException('No Subscription exists');

		return $this->increment($identifier, -$quantity);
	}
	
	public function plan($plan)
	{
		return $this->plan->add($plan);
	}

	public function getPacks()
	{
		if(is_null($this->subscription))
			throw new Exceptions\ForbiddenException('No Subscription exists');

		$this->pack->getPacksForSubscription($this->subscription['subscription_id']);
	}

	public function addPack($packId, $units)
	{
		if(is_null($this->subscription))
			throw new Exceptions\ForbiddenException('No Subscription exists');

		$this->pack->addPackForUser($this->subscription['subscription_id'], $packId, $units);
	}

	public function removePack($packId, $units)
	{
		if(is_null($this->subscription))
			throw new Exceptions\ForbiddenException('No Subscription exists');

		$this->pack->removePacksForUser($this->subscription['subscription_id'], $packId, $units);
	}
}