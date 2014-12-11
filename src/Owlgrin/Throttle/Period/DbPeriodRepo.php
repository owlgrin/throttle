<?php namespace Owlgrin\Throttle\Period;

use Owlgrin\Throttle\Period\PeriodRepo;

class DbPeriodRepo implements PeriodRepo {

	public function store($subscriptionId, $start, $end)
	{
		return $this->db->table(Config::get('throttle::tables.subsciption_period'))->insertGetId([
			'subsciption_id' => $subscriptionId,
			'starts_at' => $start,
			'ends_at' => $end,
			'status' => 1
		]);
	}

	public function getPeriodBySubscription($subscriptionId)
	{
		return $this->db->table(Config::get('throttle::tables.subscription_period'))
			->where('subscription_id', $subscriptionId)
			->where('status', 1)
			->first();
	}
}