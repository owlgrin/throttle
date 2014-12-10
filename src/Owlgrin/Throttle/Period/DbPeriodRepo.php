<?php namespace Owlgrin\Throttle\Period;

use Owlgrin\Throttle\Period\PeriodRepo;

class DbPeriodRepo implements PeriodRepo {

	public function __construct()
	{
	}

	public function store($start, $end)
	{
		$period = $this->isPeriodExist($start, $end);

		if( ! $period)
		{
			return $this->db->table(Config::get('throttle::tables.periods'))->insertGetId([
				'starts_at' => $start,
				'ends_at' => $end
			]);
		}

		return $period['id'];
	}

	public function isPeriodExist($start, $end)
	{
		$period = $this->db->table(Config::get('throttle::tables.periods'))
			->where('starts_at' => $start)
			->where('ends_at' => $end)
			->first();

		return $period;
	}

	public function get($start, $end)
	{
		$period = $this->db->table(Config::get('throttle::tables.periods'))
			->where('starts_at' => $start)
			->where('ends_at' => $end)
			->first();

		return $period;
	}

	public function addSubscriptionPeriod($subscriptionId, $periodId)
	{
		$this->db->table(Config::get('throttle::tables.subsciption_period'))->insert([
			'period_id' => $periodId,
			'subsciption_id' => $subscriptionId,
			'status' => 1
		]);
	}

	public function getSubscriptionPeriod($subscriptionId)
	{
		$subscriptionPeriod = $this->db->table(Config::get('throttle::tables.subscription_period'))
			->where('subscription_id' => $subscriptionId)
			->first();

		return $subscriptionPeriod;		
	}

	public function getPeriodBySubscription($subscriptionId)
	{
		return $this->db->table(Config::get('throttle::tables.periods').' as p')
			->join(Config::get('throttle::tables.subscription_period').' as sp', 'p.id', '=', 'sp.period_id')
			->where('sp.subscription_id', $subscriptionId)
			->select('sp.starts_at, sp.ends_at')	
			->first();
	}
}