<?php namespace Owlgrin\Throttle\Plan;

interface PlanRepo {

	public function add($plan);

	public function getFeaturesByPlan($planId);

	public function getPlanByIdentifier($identifier);

	public function getAllPlans();

	public function getFeaturesByPlanIdentifier($planIdentifier);

	public function getFeatureLimitByPlanIdentifier($planIdentifier);

	public function getTiersByPlanIdentifier($planIdentifier);

	public function getFeatureTiersByPlanIdentifier($planIdentifier, $featureIdentifier);

	public function updatePlan($plan);

	public function updateFeatureTiersOfPlan($planId, $featureIdentifier, $tiers);

	public function removeFeatureFromPlan($planId, $featureIdentifier);

}

