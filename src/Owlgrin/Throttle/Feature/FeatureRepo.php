<?php namespace Owlgrin\Throttle\Feature;

interface FeatureRepo {
	
	public function featureLimit($planId, $featureId);
	
}
