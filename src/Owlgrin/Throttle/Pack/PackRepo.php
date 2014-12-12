<?php namespace Owlgrin\Throttle\Pack;

interface PackRepo {

	public function store($pack);
	public function addPackForUser($packId, $subscriptionId, $units);
	public function removePacksForUser($packId, $subscriptionId, $units = 1);
	public function getPacksByUserId($userId, $featureId);
	public function getAllPacks();
	public function seedPackForNewPeriod($subscriptionId);
}
