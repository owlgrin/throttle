<?php namespace Owlgrin\Throttle\Period;

use Carbon\Carbon;

class CurrentMonthPeriod implements PeriodInterface, PeriodByUserInterface, NextableInterface {

	protected $period;

	public function __construct($user = null)
	{
		$today = Carbon::today();
		$this->period = [
			'starts_at' => $today->startOfMonth(),
			'ends_at' => $today->endOfMonth()
		];
	}

	public function start($formatted = false)
	{
		return $formatted
			? $this->period['starts_at']->toFormattedDateString()
			: $this->period['starts_at']->toDateString();
	}

	public function end($formatted = false)
	{
		return $formatted
			? $this->period['ends_at']->toFormattedDateString()
			: $this->period['ends_at']->toDateString();
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
		$new = new static;
		$nextStart = $this->period['ends_at']->addDays(1);
		$nextEnd = $nextStart->copy()->endOfMonth();

		$new->setStart($nextStart->toDateString());
		$new->setEnd($nextEnd->toDateString());

		return $new;
	}
}