<?php namespace Owlgrin\Throttle\Period;

interface PeriodInterface {
	
	public function start($formatted);

	public function end($formatted);
	
	public function isNewPeriod();
}