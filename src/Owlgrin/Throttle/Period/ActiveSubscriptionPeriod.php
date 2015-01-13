<?php namespace Owlgrin\Throttle\Period;

use Carbon\Carbon;
use Owlgrin\Throttle\Period\PeriodInterface;
use App;

class ActiveSubscriptionPeriod implements PeriodInterface {

	protected $period;

	public function __construct($userId, $isSubscription = false)
	{
		$periodRepo = App::make('Owlgrin\Throttle\Period\PeriodRepo');
		
		$this->period = $isSubscription 
			? $periodRepo->getPeriodBySubscription($userId)
			: $periodRepo->getPeriodByUser($userId);
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