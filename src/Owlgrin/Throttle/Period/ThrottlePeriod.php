<?php namespace Owlgrin\Throttle\Period;

use Carbon\Carbon;
use Owlgrin\Throttle\Period\PeriodInterface;
use Owlgrin\Throttle\Period\PeriodRepo;

class ThrottlePeriod implements PeriodInterface {

	protected $period;
	protected $periodRepo;

	public function __construct($period, PeriodRepo $repo)
	{
		$this->period = $period;
		$this->periodRepo = $periodRepo;
	}

	public function set($subscriptionId)
	{
		$this->period = $this->periodRepo->getPeriodBySubscription($subscriptionId);

		return $this;
	}

	public function start($formatted = false)
	{
		return Carbon::createFromFormat('Y-m-d', $this->period['starts_at'])->toFormattedDateString() : $this->period['starts_at'];
	}

	public function end($formatted = false)
	{
		return Carbon::createFromFormat('Y-m-d', $this->period['ends_at'])->toFormattedDateString() : $this->period['ends_at'];
	}	

	public function isNewPeriod()
	{
		return Carbon::yesterday()->toDateString() === $this->end();
	}	

	// public function start($formatted = false)
	// {
	// 	return $formatted ?  Carbon::today()->startOfMonth()->toFormattedDateString() : Carbon::today()->startOfMonth()->toDateString();
	// }

	// public function end($formatted = false)
	// {
	// 	return $formatted ? Carbon::today()->endOfMonth()->toFormattedDateString() : Carbon::today()->endOfMonth()->toDateString();
	// }	

	// public function isNewPeriod()
	// {
	// 	return Carbon::today()->toDateString() === $this->start();
	// }	
}