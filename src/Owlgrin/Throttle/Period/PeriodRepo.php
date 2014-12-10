<?php namespace Owlgrin\Throttle\Period;

interface PeriodRepo {
	public function store($start, $end);
	public function addSubscriptionPeriod($start, $end);
	public function isPeriodExist($start, $end);
	public function get($start, $end);
	public function getSubscriptionPeriod($subscriptionId);
}
