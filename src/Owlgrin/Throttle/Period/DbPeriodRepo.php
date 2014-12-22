<?php namespace Owlgrin\Throttle\Period;

use Illuminate\Database\DatabaseManager as Database;

use Owlgrin\Throttle\Period\PeriodRepo;

use Config;

class DbPeriodRepo implements PeriodRepo {

	public function __construct(Database $db)
	{
		$this->db = $db;
	}

	public function store($subscriptionId, $startDate, $endDate)
	{
		return $this->db->table(Config::get('throttle::tables.subscription_period'))->insertGetId([
			'subscription_id' => $subscriptionId,
			'starts_at' => $startDate,
			'ends_at' => $endDate,
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

	public function unsetPeriod($subscriptionId)
	{
		$this->db->table(Config::get('throttle::tables.subscription_period'))
			->where('subscription_id', $subscriptionId)
			->where('status', 1)
			->update('status', 0);
	}
}