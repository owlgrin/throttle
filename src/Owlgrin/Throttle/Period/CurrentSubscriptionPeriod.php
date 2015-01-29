<?php namespace Owlgrin\Throttle\Period;

use App;
use Carbon\Carbon;

class CurrentSubscriptionPeriod implements PeriodInterface, PeriodByUserInterface, NextableInterface {

	protected $user;
	protected $period;

	public function __construct($user)
	{
		$this->user = $user;
		$this->period = App::make('Owlgrin\Throttle\Period\PeriodRepo')->getCurrentPeriodByUser($user);
	}

	public function start($formatted = false)
	{
		return $formatted
			? Carbon::createFromFormat('Y-m-d', $this->period['starts_at'])->toFormattedDateString()
			: $this->period['starts_at'];
	}

	public function end($formatted = false)
	{
		return $formatted
			? Carbon::createFromFormat('Y-m-d', $this->period['ends_at'])->toFormattedDateString()
			: $this->period['ends_at'];
	}

	public function setStart($date)
	{
		$this->period['starts_at'] = $date;
	}

	public function setEnd($date)
	{
		$this->period['ends_at'] = $date;
	}	

	public function next()
	{
		$new = new static($this->user);
		$nextStart = Carbon::createFromFormat('Y-m-d', $this->period['ends_at'])->addDays(1);
		$nextEnd = $nextStart->copy()->endOfMonth();

		$new->setStart($nextStart->toDateString());
		$new->setEnd($nextEnd->toDateString());

		return $new;
	}
}