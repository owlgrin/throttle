<?php namespace Owlgrin\Throttle\Biller;

use Owlgrin\Throttle\Biller\Biller;
use Owlgrin\Throttle\Subscriber\SubscriberRepo;

class PayAsYouGoBiller implements Biller{

	protected $subscriber;

	public function __construct(SubscriberRepo $subscriber)
	{
		$this->subscriber = $subscriber;
	}	

	public function billCalculate($userId, $startDate, $endDate = null, $detail = false)
	{
		return $detail == false ? $this->calculate($userId, $startDate, $endDate) : $this->calculateDetail($userId, $startDate, $endDate);
	}

	public function billEstimate($palnId, $features, $detail = false)
	{
		return $detail == false ? $this->estimate($palnId, $features) : $this->estimateDetail($palnId, $features);
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

	public function estimatedetail($planId, $features)
	{		
		$totalPrice = 0;
		$detail = [];

		foreach($features as $featureId => $usage) 
		{
			$detail = $this->detailPriceCalculator($planId, $featureId, $usage);
			$totalPrice += $detail['bill'];

			$finaldetail[] = $detail;
		}

		return ['detail' => $finaldetail, 'total' => $totalPrice];
	}
	
	public function calculatedetail($userId, $startDate, $endDate = null)
	{
		//finding details of the user
		$userDetails = $this->subscriber->userDetails($userId, $startDate, $endDate);

		$totalPrice = 0;
		$detail = [];

		foreach($userDetails as $index => $usage) 
		{
			$detail  = $this->detailPriceCalculator($usage->plan_id, $usage->feature_id, $usage->used_quantity);
			$totalPrice += $detail['bill'];

			$finaldetail[] = $detail;
		}

		return ['detail' => $finaldetail, 'total' =>$totalPrice];
	}
	
	private function detailPriceCalculator($planId, $featureId, $usage)
	{
		$bill = 0;
		$detail = [];

		//finding limit of the feature
		$features = $this->subscriber->featureLimit($planId, $featureId);

		foreach($features as $index => $feature) 
		{
			$details = [];
			
			if((int)$usage > (int)$feature->limit)
			{

				if((int) $feature->limit == null)
				{
					$details['limit'] = $feature->limit;	
					$details['usage'] = $usage;
					$details['rate'] = $feature->rate;
					$details['amount'] = $usage*$feature->rate;
					$detail['record'][] = $details;

					$bill += $feature->rate*$usage;
					break;
				}
				else
				{
					$details['limit'] = $feature->limit;
					$details['usage'] = $feature->limit;
					$details['rate'] = $feature->rate;
					$details['amount'] = $feature->limit*$feature->rate;
					$detail['record'][] = $details;
				
					$bill += $feature->rate*$feature->limit;
					$usage = $usage - $feature->limit;
				}
			}
			else
			{
				$details['limit'] = $feature->limit;
				$details['usage'] = $usage;
				$details['rate'] = $feature->rate;
				$details['amount'] = $usage*$feature->rate;
				$detail['record'][] = $details;
				$bill += $feature->rate*$usage;
				break;
			}
		}

		$detail['feature_id'] = $featureId;
		$detail['feature_name'] = $feature->name;

		return ['detail' => $detail, 'bill' => $bill];
	}
}