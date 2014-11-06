<?php namespace Owlgrin\Throttle\Biller;

use Owlgrin\Throttle\Biller\Biller;
use Owlgrin\Throttle\Subscriber\SubscriberRepo;
use Owlgrin\Throttle\Pack\PackRepo as Pack;

class PayAsYouGoBiller implements Biller{

	protected $subscription;
	protected $pack;

	public function __construct(SubscriberRepo $subscription, Pack $pack)
	{
		$this->subscription = $subscription;
		$this->pack = $pack;
	}

	public function estimate($usages)
	{		
		return $this->calculateByUsage($usages);
	}

	public function bill($userId, $startDate, $endDate)
	{
		$usages = $this->subscription->getUsage($userId, $startDate, $endDate);

		return $this->calculateByUsage($usages, $userId);
	}

	public function calculateByPacks($packs)
	{
		$allPacks = [];

		foreach($packs as $index => $pack) 
		{
			$allPacks[] = array_merge($pack, ['amount' => $pack['price']*$pack['units']]);
		}

		return $allPacks;
	}

	public function calculateByUsage($usages, $userId = null)
	{
		$amount = 0;
		$lines = [];

		foreach($usages as $index => $feature)
		{
			$tiers = $this->getTierByFeature($feature['plan_id'], $feature['feature_id']);
			
			$lineItem = $this->calculateByTier($tiers, $feature['feature_id'], $feature['used_quantity'], $userId);
		
			$amount += $lineItem['amount'];
			$lineItem['usage'] = (int) $feature['used_quantity'];
			$lines[] = $lineItem;
		}

		return ['lines' => $lines, 'amount' => $amount];
	}

	private function getTierByFeature($planId, $featureId)
	{
		//finding limit of the feature
		return $this->subscription->featureLimit($planId, $featureId);
	}

	private function calculateByTier($tiers, $featureId, $usage, $userId = null)
	{
		$bill = 0;
		$lineItem = [];
		$packs = [];

		if(! is_null($userId))
		{
			$packs = $this->pack->getPacksByUserId($userId, $featureId);	
		}

		if($packs)
		{
			$packs = $this->calculateByPacks($packs);
		}

		foreach($tiers as $index => $feature) 
		{
			$lineItems = [];

			if((int)$usage > (int)$feature['limit'])
			{
				if((int) $feature['limit'] == null)
				{
					$lineItems['limit'] = (int) $feature['limit'];	
					$lineItems['usage'] = (int) $usage;
					$lineItems['rate'] = (int) $feature['rate'];
					$lineItems['amount'] = $feature['rate']*($feature['limit']/$feature['per_quantity']);
					$lineItem[] = $lineItems;

					$bill += $lineItems['amount'];
					break;
				}
				else
				{
					$lineItems['limit'] = (int) $feature['limit'];
					$lineItems['usage'] = (int) $feature['limit'];
					$lineItems['rate'] = (int) $feature['rate'];
					$lineItems['amount'] = $feature['rate']*($feature['limit']/$feature['per_quantity']);
					$lineItem[] = $lineItems;
				
					$bill += $lineItems['amount'];
					$usage = $usage - $feature['limit'];
				}
			}
			else
			{			
				$lineItems['limit'] = (int) $feature['limit'];
				$lineItems['usage'] = (int) $usage;
				$lineItems['rate'] = (int) $feature['rate'];
				$lineItems['amount'] = $feature['rate']*($usage/$feature['per_quantity']);
				$lineItem[] = $lineItems;
				$bill += $lineItems['amount'];
				break;
			}
		}

		foreach($packs as $key => $pack) 
		{
			$bill = isset($pack['amount']) ? $bill + $pack['amount'] : $bill;			
		}

		return ['tiers' => $lineItem, 'feature_id' => $featureId, 'feature_name' => $feature['name'], 'amount' => $bill, 'packs' => $packs];
	}
}