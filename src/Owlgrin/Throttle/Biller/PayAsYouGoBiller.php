<?php namespace Owlgrin\Throttle\Biller;

use Owlgrin\Throttle\Biller\Biller;
use Owlgrin\Throttle\Subscriber\SubscriberRepo;

class PayAsYouGoBiller implements Biller{

	protected $subscription;

	public function __construct(SubscriberRepo $subscription)
	{
		$this->subscription = $subscription;
	}

	public function estimate($usages)
	{		
		return $this->calculateByUsage($usages);
	}

	public function bill($userId, $startDate, $endDate)
	{
		$usages = $this->subscription->getUsage($userId, $startDate, $endDate);

		return $this->calculateByUsage($usages);
	}

	public function calculateByUsage($usages)
	{
		$amount = 0;

		foreach($usages as $index => $feature) 
		{
			$tiers = $this->getTierByFeature($feature['plan_id'], $feature['feature_id']);
			
			$lineItem = $this->calculateByTier($tiers, $feature['feature_id'], $feature['used_quantity']);
		
			$amount += $lineItem['amount'];
			$lines[] = $lineItem;
		}

		return ['lines' => $lines, 'amount' => $amount];
	}

	private function getTierByFeature($planId, $featureId)
	{
		//finding limit of the feature
		return $this->subscription->featureLimit($planId, $featureId);
	}

	private function calculateByTier($tiers, $featureId, $usage)
	{
		$bill = 0;
		$lineItem = [];

		foreach($tiers as $index => $feature) 
		{
			$lineItems = [];

			if((int)$usage > (int)$feature['limit'])
			{
				if((int) $feature['limit'] == null)
				{
					$lineItems['limit'] = $feature['limit'];	
					$lineItems['usage'] = $usage;
					$lineItems['rate'] = $feature['rate'];
					$lineItems['amount'] = $feature['rate']*($feature['limit']/$feature['per_quantity']);
					$lineItem[] = $lineItems;

					$bill += $lineItems['amount'];
					break;
				}
				else
				{
					$lineItems['limit'] = $feature['limit'];
					$lineItems['usage'] = $feature['limit'];
					$lineItems['rate'] = $feature['rate'];
					$lineItems['amount'] = $feature['rate']*($feature['limit']/$feature['per_quantity']);
					$lineItem[] = $lineItems;
				
					$bill += $lineItems['amount'];
					$usage = $usage - $feature['limit'];
				}
			}
			else
			{			
				$lineItems['limit'] = $feature['limit'];
				$lineItems['usage'] = $usage;
				$lineItems['rate'] = $feature['rate'];
				$lineItems['amount'] = $feature['rate']*($usage/$feature['per_quantity']);
				$lineItem[] = $lineItems;
				$bill += $lineItems['amount'];
				break;
			}
		}

		return ['tiers' => $lineItem, 'feature_id' => $featureId, 'feature_name' => $feature['name'], 'amount' => $bill];
	}
}