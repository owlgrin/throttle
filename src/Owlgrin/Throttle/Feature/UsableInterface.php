<?php namespace Owlgrin\Throttle\Feature;

interface UsableInterface {

	public function getUsageForDate($userId, $date);

}