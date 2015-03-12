<?php namespace Owlgrin\Throttle\Left;

interface LeftRepo {

	public function leftOnAttempt($subscriptionId, $identifier, $start, $end);
	public function left($limit, $usage);
	public function getLimitOfFeatureSubscribed($subscriptionId, $featureIdentifier);
	public function leftOnRefresh($subscriptionId, $featureIdentifier, $usage);
}
