<?php namespace Owlgrin\Throttle\Period;

use Carbon\Carbon;

class ManualPeriod implements PeriodInterface {

	protected $start;
	protected $end;

	public function __construct($start, $end)
	{
		$this->start = $start;
		$this->end = $end;
	}

	public function start($formatted = false)
	{
		if($this->start instanceof Carbon)
		{
			return $formatted
				? $this->start->toFormattedDateString() 
				: $this->start->toDateString();
		}

		return $formatted 
			? Carbon::createFromFormat('Y-m-d', $this->start)->toFormattedDateString() 
			: $this->start;
	}

	public function end($formatted = false)
	{
		if($this->end instanceof Carbon)
		{
			return $formatted 
				? $this->end->toFormattedDateString() 
				: $this->end->toDateString();
		}

		return $formatted 
			? Carbon::createFromFormat('Y-m-d', $this->end)->toFormattedDateString() 
			: $this->end;
	}
}