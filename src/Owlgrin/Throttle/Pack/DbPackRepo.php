<?php namespace Owlgrin\Throttle\Pack;

use Illuminate\Database\DatabaseManager as Database;
use Owlgrin\Throttle\Pack\PackRepo;
use Owlgrin\Throttle\Subscriber\SubscriberRepo as Subscriber;
use Owlgrin\Throttle\Exceptions;
use Owlgrin\Throttle\Period\PeriodInterface;

use Exception, Config;

class DbPackRepo implements PackRepo {

	protected $db;
	protected $subscriber;

	public function __construct(Database $db, Subscriber $subscriber)
	{
		$this->db = $db;
		$this->subscriber = $subscriber;
	}

	public function store($pack)
	{
		try
		{
			$packId = $this->db->table(Config::get('throttle::tables.packs'))->insertGetId([
				'name' 		  => $pack['name'],
				'plan_id'  	  => $pack['plan_id'],
				'feature_id'  => $pack['feature_id'],
				'price'       => $pack['price'],
				'quantity'    => $pack['quantity']
			]);

			return $packId;
		}
		catch(\Exception $e)
		{
			throw new Exceptions\InvalidInputException;
		}
	}

	public function addPackForUser($packId, $subscriptionId, $units, PeriodInterface $period)
	{
		try
		{
			//starting a transition
			$this->db->beginTransaction();

			$pack = $this->find($packId);

			$packForUser = $this->updatePackForUser($subscriptionId, $packId, $units, $period->start(), $period->end());

			if(! $packForUser)
			{
				$this->db->table(Config::get('throttle::tables.user_pack'))->insert([
					'subscription_id'      => $subscriptionId,
					'pack_id'  	  	       => $packId,
					'units'                => $units,
					'status'               => 1,
					'period_start'         => $period->start(),
					'period_end'           => $period->end()
				]);
			}
	
			$this->subscriber->incrementLimit($subscriptionId, $pack['feature_id'], $units*$pack['quantity']);

			//commition the work after processing
			$this->db->commit();

		}
		catch(\Exception $e)
		{
			//rollback if failed
			$this->db->rollback();

			throw new Exceptions\InvalidInputException;
		}	
	}

	public function isPackExists($packId)
	{
		try
		{
			$pack = $this->find($packId);

			if($pack === null)
			{
				return false;
			}

			return true;
		}
		catch(\Exception $e)
		{
			throw new Exceptions\InvalidInputException('invalid pack id');
		}	
	}

	public function find($packId)
	{
		try
		{
			$pack = $this->db->table(Config::get('throttle::tables.packs'))
				->where('id', $packId)
				->first();

			return $pack;
		}
		catch(\Exception $e)
		{
			throw new Exceptions\InvalidInputException('invalid pack id');
		}	
	}

	public function removePacksForUser($packId, $subscriptionId, $units = 1, PeriodInterface $period)
	{
		try
		{
			$pack = $this->find($packId);

			if( ! $this->isPackExistsForUser($subscriptionId, $packId, $units))
			{
				throw new Exceptions\InvalidInputException('No such pack with ' . $units . ' unit exists for user');	
			}

			if( ! $this->subscriber->canReduceLimit($subscriptionId, $pack['feature_id'], $units*$pack['quantity']))
			{
				throw new Exceptions\InvalidInputException('Cannot reduce ' . $units . ' ' . $pack['name']);		
			}
			
			$userPack= $this->getPackBySubscriptionId($subscriptionId, $packId);

			if($userPack['units'] == $units)
			{
				$this->db->table(Config::get('throttle::tables.user_pack'))
					->where('pack_id', $packId)
					->where('subscription_id', $subscriptionId)
					->where('status', 1)
					->where('period_start', $period->start())
					->where('period_end', $period->end())
					->update(['units' => 0]);
			}
			else
			{
				$this->db->table(Config::get('throttle::tables.user_pack'))
					->where('pack_id', $packId)
					->where('subscription_id', $subscriptionId)
					->where('status', 1)
					->where('period_start', $period->start())
					->where('period_end', $period->end())
					->decrement('units', $units);
			}

			$this->subscriber->incrementLimit($subscriptionId, $pack['feature_id'], (-1)*$units*$pack['quantity']);
		}
		catch(\PDOException $e)
		{
			throw new Exceptions\InvalidInputException('Invalid pack');
		}
	}

	private function isPackExistsForUser($subscriptionId, $packId, $units)
	{
		$pack = $this->db->table(Config::get('throttle::tables.user_pack'))
				->where('pack_id', $packId)
				->where('subscription_id', $subscriptionId)
				->where('units', '>=', $units)
				->where('status', '1')
				->first();

		if($pack) return true;

		return false;
	}

	private function updatePackForUser($subscriptionId, $packId, $units, $startDate, $endDate)
	{
		$increment = $this->db->table(Config::get('throttle::tables.user_pack'))
				->where('pack_id', $packId)
				->where('subscription_id', $subscriptionId)
				->where('period_start', $startDate)
				->where('period_end', $endDate)
				->where('status', '1')
				->increment('units', $units);

		return $increment;
	}

	public function getPackBySubscriptionId($subscriptionId, $packId)
	{
		$pack = $this->db->table(Config::get('throttle::tables.user_pack'))
				->where('pack_id', $packId)
				->where('subscription_id', $subscriptionId)
				->where('status', '1')
				->first();

		return $pack;
	}

	public function getPacksByUserId($userId, $featureId)
	{
		$pack = $this->db->table(Config::get('throttle::tables.user_pack').' as up')
				->join(Config::get('throttle::tables.subscriptions').' as s', 's.id', '=', 'up.subscription_id')
				->join(Config::get('throttle::tables.packs').' as p', 'p.id', '=', 'up.pack_id')
				->where('p.feature_id', $featureId)
				->where('up.status', '1')
				->where('s.user_id', $userId)
				->select('p.id', 'p.price', 'up.units', 'p.quantity')
				->get();

		return $pack;
	}

	public function findLimitOfUserByPackId($subscriptionId, $packId)
	{
		return $this->db->table(Config::get('throttle::tables.user_feature_limit').' as ufl')
			->join(Config::get('throttle::tables.packs').' as p', 'p.feature_id', '=', 'ufl.feature_id')
			->where('ufl.subscription_id', $subscriptionId)
			->where('p.id', $packId)
			->select('ufl.limit')	
			->first();
	}

	public function getAllPacks()
	{
		$packs = $this->db->table(Config::get('throttle::tables.packs'))
			->get();

		return $packs;
	}

	public function seedPackForNewPeriod($subscriptionId, PeriodInterface $period)
	{
		$packs = $this->getPacksBySubscriptionId($subscriptionId);

		foreach ($packs as $pack) 
		{
			$this->db->table(Config::get('throttle::table.user_pack'))
				->where('id', $pack['id'])
				->update(['status' => 0]);
				
			$this->db->table(Config::get('throttle::tables.user_pack'))->insert([
				'subscription_id'      => $pack['subscription_id'],
				'pack_id'  	  	       => $pack['pack_id'],
				'units'                => $pack['units'],
				'status'               => 1,
				'period_start'         => $period->start(),
				'period_end'           => $period->end()
			]);
		}
	}

	public function getPacksBySubscriptionId($subscriptionId)
	{
		$packs = $this->db->table(Config::get('throttle::tables.user_pack'))
			->where('subscription_id', $subscriptionId)
			->where('status', '1')
			->get();

		return $packs;
	}
}