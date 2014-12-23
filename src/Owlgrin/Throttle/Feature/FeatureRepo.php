<?php namespace Owlgrin\Throttle\Feature;

interface FeatureRepo {
	
	public function featureLimit($planId, $featureId);

	public function allForUser($userId);

	public function getAllFeatures();
		
}
