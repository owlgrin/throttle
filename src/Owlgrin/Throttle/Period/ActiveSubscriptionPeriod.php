<?php namespace Owlgrin\Throttle\Period;

use Carbon\Carbon;
use Owlgrin\Throttle\Period\PeriodInterface;
use Owlgrin\Throttle\Period\SetPeriodInterface;
use Owlgrin\Throttle\Period\PeriodRepo;

class ActiveSubscriptionPeriod implements PeriodInterface {

	protected $period;

	public function __construct(PeriodRepo $periodRepo)
	{
		$this->period = $periodRepo->getPeriodBySubscription($subscriptionId);
	}

	public function start($formatted = false)
	{
		return $formatted ? Carbon::createFromFormat('Y-m-d', $this->period['starts_at'])->toFormattedDateString() : $this->period['starts_at'];
	}

	public function end($formatted = false)
	{
		return $formatted ? Carbon::createFromFormat('Y-m-d', $this->period['ends_at'])->toFormattedDateString() : $this->period['ends_at'];
	}	

	public function isNewPeriod()
	{
		return Carbon::yesterday()->toDateString() === $this->end();
	}
}