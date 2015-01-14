<?php namespace Owlgrin\Throttle\Period;

use Carbon\Carbon;
use Owlgrin\Throttle\Period\PeriodInterface;
use Owlgrin\Throttle\Period\PeriodInterfaceByUser;

class CurrentMonthPeriod implements PeriodInterface, PeriodInterfaceByUser {

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