<?php namespace Owlgrin\Throttle;

use Owlgrin\Throttle\Biller\Biller;
use Owlgrin\Throttle\Subscriber\SubscriberRepo as Subscriber;
use Owlgrin\Throttle\Plan\PlanRepo as Plan;
use Owlgrin\Throttle\Exceptions;

/**
 * The Throttle core
 */
class Throttle {

	protected $biller; 
	protected $subscriber;
	protected $plan;
	protected $user = null;
	protected $subscription = null;

	public function __construct(Biller $biller, Subscriber $subscriber, Plan $plan)
	{
		$this->biller = $biller;
		$this->subscriber = $subscriber;
		$this->plan = $plan;
	}

	public function user($user)
	{
		$this->user = $user;
		$this->subscription = $this->subscriber->subscription($this->user);
		return $this;
	}

	public function getUser()
	{
		return $this->user;
	}

	public function getSubscription()
	{
		return $this->subscription;
	}

	public function subscribe($planId)
	{
		return $this->subscriber->subscribe($this->user, $planId);
	}

	public function can($identifier, $count)
	{
		return $this->subscriber->can($this->subscription['subscriptionId'], $identifier, $count);
	}

	public function bill($startDate, $endDate)
	{
		return $this->biller->bill($this->user, $startDate, $endDate);
	}

	public function estimate($usages)
	{
		return $this->biller->estimate($usages);
	}

	public function increment($identifier, $quantity = 1)
	{
		return $this->subscriber->increment($this->subscription['subscriptionId'], $identifier, $quantity);
	}
	
	public function plan($plan)
	{
		return $this->plan->add($plan);
	}

	public function left($identifier)
	{
		return $this->subscriber->left($this->subscription['subscriptionId'], $identifier);
	}
}