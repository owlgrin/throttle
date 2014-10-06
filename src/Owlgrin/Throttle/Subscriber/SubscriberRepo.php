<?php namespace Owlgrin\Throttle\Subscriber;

interface SubscriberRepo {
	
	public function subscribe($userId, $planId);
	public function incrementUsage($subscriptionId, $featureId, $incrementCount);
	public function setLimit($subscriptionId, $featureId, $limit);
	public function userDetails($userId, $startDate, $endDate);
	public function featureLimit($planId, $featureId);
}
