<?php namespace Owlgrin\Throttle\Period;

interface PeriodRepo {
	
	public function store($subscriptionId, $start, $end);

	public function getSubscriptionPeriod($subscriptionId);

}
