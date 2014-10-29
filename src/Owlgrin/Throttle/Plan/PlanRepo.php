<?php namespace Owlgrin\Throttle\Plan;

interface PlanRepo {

	public function add($plan);
	public function getFeaturesByPlan($planId)
}
