<?php namespace Owlgrin\Throttle\Period;

use App;
use Carbon\Carbon;
use Owlgrin\Throttle\Period\PeriodInterface;
use Owlgrin\Throttle\Period\PeriodByUserInterface;

class ActiveSubscriptionPeriod implements PeriodInterface, PeriodByUserInterface {

	protected $period;

	public function __construct($user)
	{
		$this->period = App::make('Owlgrin\Throttle\Period\PeriodRepo')->getPeriodByUser($user);
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