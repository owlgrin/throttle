<?php namespace Owlgrin\Throttle\Biller;

interface Biller {

	public function bill($subscriptionId, $startDate, $endDate);
	public function estimate($usages);

}
