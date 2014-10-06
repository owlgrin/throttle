<?php namespace Owlgrin\Throttle\Biller;

use Owlgrin\Throttle\Biller\Biller;
use Owlgrin\Throttle\Subscriber\SubscriberRepo;

class PayAsYouGoBiller implements Biller{

	protected $subscriber;

	public function __construct(SubscriberRepo $subscriber)
	{
		$this->subscriber = $subscriber;
	}

	public function calculate($userId, $startDate, $endDate = null)
	{
		$userDetails = $this->subscriber->userDetails($userId, $startDate, $endDate);

		$totalPrice = 0;

		foreach($userDetails as $index => $usage) 
		{
			$totalPrice  += $this->priceCalculator($usage->plan_id, $usage->feature_id, $usage->used_quantity);
		}

		return $totalPrice;
	}

	public function estimate($planId, $features)
	{
		$totalPrice = 0;

		foreach($features as $featureId => $usage) 
		{
			$totalPrice += $this->priceCalculator($planId, $featureId, $usage);
		}

		return $totalPrice;
	}

	private function priceCalculator($planId, $featureId, $usage)
	{
		$bill = 0;

		$features = $this->subscriber->featureLimit($planId, $featureId);

		foreach($features as $index => $feature) 
		{
			if((int)$usage > (int)$feature->limit)
			{
				if((int) $feature->limit == null)
				{
					$bill += $feature->rate*$usage;
					break;
				}
				else
				{
					$bill += $feature->rate*$feature->limit;
					$usage = $usage - $feature->limit;
				}
			}
			else
			{
				$bill += $feature->rate*$usage;
				break;
			}
		}

		return $bill;
	}

	public function estimateSummary($planId, $features)
	{		
		$totalPrice = 0;
		$summary = [];

		foreach($features as $featureId => $usage) 
		{
			$summary = $this->summaryPriceCalculator($planId, $featureId, $usage);
			$totalPrice += $summary['bill'];

			$finalSummary[] = $summary;
		}

		return ['summary' => $finalSummary, 'total' => $totalPrice];
	}
	
	public function calculateSummary($userId, $startDate, $endDate = null)
	{
		//finding details of the user
		$userDetails = $this->subscriber->userDetails($userId, $startDate, $endDate);

		$totalPrice = 0;
		$summary = [];

		foreach($userDetails as $index => $usage) 
		{
			$summary  = $this->summaryPriceCalculator($usage->plan_id, $usage->feature_id, $usage->used_quantity);
			$totalPrice += $summary['bill'];

			$finalSummary[] = $summary;
		}

		return ['summary' => $finalSummary, 'total' =>$totalPrice];
	}
	
	private function summaryPriceCalculator($planId, $featureId, $usage)
	{
		$bill = 0;
		$summary = [];

		//finding limit of the feature
		$features = $this->subscriber->featureLimit($planId, $featureId);

		foreach($features as $index => $feature) 
		{
			$summaries = [];
			
			if((int)$usage > (int)$feature->limit)
			{

				if((int) $feature->limit == null)
				{
					$summaries['limit'] = $feature->limit;	
					$summaries['usage'] = $usage;
					$summaries['rate'] = $feature->rate;
					$summaries['amount'] = $usage*$feature->rate;
					$summary['record'][] = $summaries;

					$bill += $feature->rate*$usage;
					break;
				}
				else
				{
					$summaries['limit'] = $feature->limit;
					$summaries['usage'] = $feature->limit;
					$summaries['rate'] = $feature->rate;
					$summaries['amount'] = $feature->limit*$feature->rate;
					$summary['record'][] = $summaries;
				
					$bill += $feature->rate*$feature->limit;
					$usage = $usage - $feature->limit;
				}
			}
			else
			{
				$summaries['limit'] = $feature->limit;
				$summaries['usage'] = $usage;
				$summaries['rate'] = $feature->rate;
				$summaries['amount'] = $usage*$feature->rate;
				$summary['record'][] = $summaries;
				$bill += $feature->rate*$usage;
				break;
			}
		}

		$summary['feature_id'] = $featureId;
		$summary['feature_name'] = $feature->name;

		return ['summary' => $summary, 'bill' => $bill];
	}
}