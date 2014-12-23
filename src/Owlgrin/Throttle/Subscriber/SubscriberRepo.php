<?php namespace Owlgrin\Throttle\Subscriber;

interface SubscriberRepo {
	
	public function all();
	public function getAllUserIds();
	public function subscribe($userId, $planId);
	public function getUsage($userId, $startDate, $endDate);
	public function seedPreparedUsages($preparedUsages);
	public function subscription($userId);
	public function increment($subscriptionId, $identifier, $count);
	public function left($subscriptionId, $identifier, $start, $left);
	public function canReduceLimit($subscriptionId, $featureId, $limit);
}
