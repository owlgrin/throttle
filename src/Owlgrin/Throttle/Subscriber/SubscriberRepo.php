<?php namespace Owlgrin\Throttle\Subscriber;

interface SubscriberRepo {

	public function all();
	public function getAllUserIds();
	public function subscribe($userId, $planIdentifier);
	public function getUsage($subscriptionId, $startDate, $endDate);
	public function subscription($userId);
	public function increment($subscriptionId, $identifier, $count);
	public function left($subscriptionId, $identifier, $start, $end);
	public function canReduceLimit($subscriptionId, $featureId, $limit);
	public function incrementLimit($subscriptionId, $featureIdentifier, $value);
	public function updateInitialLimitForFeatures($subscriptionId, $planId);
	public function removeUsagesOfSubscription($subscriptionId, $featureId);
	public function removeLimitsOfSubscription($subscriptionId, $featureId);
	public function addInitialLimitForNewFeature($subscriptionId, $planId, $featureId);
	public function findSubscribersByPlanId($planId);
	public function refreshUsage($subscriptionId, $identifier, $count, $date);
}
