<?php namespace Owlgrin\Throttle\Period;

interface PeriodRepo {
	
	public function store($subscriptionId, $start, $end);

	public function getPeriodBySubscription($subscriptionId, $date);

	public function getPeriodByUser($subscriptionId, $date);

	public function unsetPeriod($subscriptionId);
}
