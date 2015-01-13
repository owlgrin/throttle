<?php namespace Owlgrin\Throttle\Usage;

interface UsageRepo {

	public function getBaseUsages($userId, $subscriptions, $date);
}
