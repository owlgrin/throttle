<?php namespace Owlgrin\Throttle\Period;

interface PeriodRepo {
	
	public function store($subscriptionId, $start, $end);

	public function getPeriodBySubscription($subscriptionId);

	public function unsetPeriod($subscriptionId);
}
