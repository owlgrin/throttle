<?php namespace Owlgrin\Throttle\Pack;

interface PackRepo {

	public function store($pack);
	public function addPackForUser($packId, $subscriptionId, $units);
	public function find($packId);
	public function removePacksForUser($packId, $subscriptionId, $units);
	public function isValidPackForUser($subscriptionId, $packId);
	public function getAllPacks();
	public function seedPackForNewPeriod($subscriptionId);
	public function getPacksForSubscriptionFeature($subscriptionId, $featureId);
}
