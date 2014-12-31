<?php namespace Owlgrin\Throttle\Limiter;

interface LimiterInterface {
	
	public function attempt($subscriptionId, $identifier, $count, $start, $end);

	public function softAttempt($subscriptionId, $identifier, $count, $start, $end);

}
