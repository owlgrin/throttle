<?php namespace Owlgrin\Throttle\Period;

interface PeriodRepo {
	
	public function store($subscriptionId, $start, $end);

	public function getActivePeriodBySubscription($subscriptionId);

	public function getActivePeriodByUser($subscriptionId);
	
	public function getCurrentPeriodBySubscription($subscriptionId, $date);
	
	public function getCurrentPeriodByUser($subscriptionId, $date);

	public function unsetPeriod($subscriptionId);
}
