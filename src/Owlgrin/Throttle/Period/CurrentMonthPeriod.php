<?php namespace Owlgrin\Throttle\Period;

use Carbon\Carbon;
use Owlgrin\Throttle\Period\PeriodInterface;
use Owlgrin\Throttle\Period\PeriodByUserInterface;

class CurrentMonthPeriod implements PeriodInterface, PeriodByUserInterface {

	public function __construct($user = null)
	{
		
	}

	public function start($formatted = false)
	{
		return $formatted ?  Carbon::today()->startOfMonth()->toFormattedDateString() : Carbon::today()->startOfMonth()->toDateString();
	}

	public function end($formatted = false)
	{
		return $formatted ? Carbon::today()->endOfMonth()->toFormattedDateString() : Carbon::today()->endOfMonth()->toDateString();
	}

	public function isNewPeriod()
	{
		return Carbon::today()->toDateString() === $this->start();
	}	
}