<?php namespace Owlgrin\Throttle\Biller;

interface Biller {

	public function calculate($userId, $startDate, $endDate);
	public function estimate($planId, $features);
	public function estimateSummary($planId, $features);
	public function calculateSummary($userId, $startDate, $endDate);
}
