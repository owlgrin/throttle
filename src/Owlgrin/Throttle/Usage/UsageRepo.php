<?php namespace Owlgrin\Throttle\Usage;

interface UsageRepo {

	public function seedBase($userId, $subscriptions, $date);
	public function getUsageForFeature($userId, $featureIdentifier, $date);
	public function addInitialUsagesForFeature($subscription, $featureId, $feature, $date);
}
