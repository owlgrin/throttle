<?php namespace Owlgrin\Throttle\Usage;

interface UsageRepo {

	public function seedBase($userId, $subscriptions, $date);
}
