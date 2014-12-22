<?php namespace Owlgrin\Throttle\Biller;

interface Biller {

	public function bill($userId, $startDate, $endDate);
	public function estimate($usages);

}
