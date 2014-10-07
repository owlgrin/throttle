<?php namespace Owlgrin\Throttle\Biller;

interface Biller {
	public function billEstimate($planId, $features, $detail);
	public function billCalculate($userId, $startDate, $endDate, $detail);
	public function calculate($userId, $startDate, $endDate);
	public function estimate($planId, $features);
	public function estimateDetail($planId, $features);
	public function calculateDetail($userId, $startDate, $endDate);
}
