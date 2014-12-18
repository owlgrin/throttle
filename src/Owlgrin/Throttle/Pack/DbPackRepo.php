<?php namespace Owlgrin\Throttle\Pack;

use Illuminate\Database\DatabaseManager as Database;
use Owlgrin\Throttle\Pack\PackRepo;
use Owlgrin\Throttle\Subscriber\SubscriberRepo as Subscriber;
use Owlgrin\Throttle\Exceptions;
use Owlgrin\Throttle\Period\PeriodInterface;
use Owlgrin\Throttle\Period\PeriodRepo;

use PDOException, Exception, Config, Carbon\Carbon;

class DbPackRepo implements PackRepo {

	protected $db;
	protected $subscriber;
	protected $period;

	public function __construct(Database $db, Subscriber $subscriber, PeriodRepo $period)
	{
		$this->db = $db;
		$this->subscriber = $subscriber;
		$this->period = $period;
	}

	public function store($pack)
	{
		try
		{
			return $this->db->table(Config::get('throttle::tables.packs'))->insertGetId([
				'name' 		  => $pack['name'],
				'plan_id'  	  => $pack['plan_id'],
				'feature_id'  => $pack['feature_id'],
				'price'       => $pack['price'],
				'quantity'    => $pack['quantity']
			]);
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException("Something went wrong with database");	
		}
	}

	public function addPackForUser($subscriptionId, $packId, $units)
	{
		try
		{
			//starting a transition
			$this->db->beginTransaction();

			if(! $this->isPackExists($packId)) throw new Exceptions\InvalidInputException('No such pack exists');

			$subscriptionPeriod = $this->period->getPeriodBySubscription($subscriptionId);

			//updates packs for user
			$packForUser = $this->updatePackForUser($subscriptionId, $packId, $units, $subscriptionPeriod['id']);

			if(! $packForUser)
			{
				$this->db->table(Config::get('throttle::tables.user_pack'))->insert([
					'subscription_id'      => $subscriptionId,
					'pack_id'  	  	       => $packId,
					'units'                => $units,
					'status'               => 1,
					'period_id'            => $subscriptionPeriod['id']
				]);
			}
	
			$this->subscriber->incrementLimit($subscriptionId, $pack['feature_id'], $units*$pack['quantity']);

			//commition the work after processing
			$this->db->commit();
		}
		catch(PDOException $e)
		{
			//rollback if failed
			$this->db->rollback();

			throw new Exceptions\InternalException("Something went wrong with database");	
		}	
	}

	private function isPackExists($packId)
	{
		$pack = $this->find($packId);

		if($pack === null)
		{
			return false;
		}

		return true;	
	}

	public function find($packId)
	{
		try
		{
			$pack = $this->db->table(Config::get('throttle::tables.packs'))
				->where('id', $packId)
				->first();

			if( ! $pack) throw new Exceptions\InvalidInputException('No such pack exists');		

			return $pack;
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException("Something went wrong with database");	
		}	
	}

	public function removePacksForUser($subscriptionId, $packId, $units = 1)
	{
		try
		{
			//starting a transition
			$this->db->beginTransaction();

			$pack = $this->find($packId);

			if( ! $this->isPackExistsForUser($subscriptionId, $packId, $units))
			{
				throw new Exceptions\InvalidInputException('No such pack with ' . $units . ' unit exists for user');	
			}

			if( ! $this->suscriber->canReduceLimit($subscriptionId, $pack['feature_id'], $units*$pack['quantity']))
			{
				throw new Exceptions\InvalidInputException('Cannot reduce ' . $units . ' ' . $pack['name']);		
			}

			$subscriptionPeriod = $this->period->getPeriodBySubscription($subscriptionId);		
			
			$userPack= $this->getPackBySubscriptionId($subscriptionId, $packId);

			if($userPack['units'] == $units)
			{
				$this->db->table(Config::get('throttle::tables.user_pack'))
					->where('pack_id', $packId)
					->where('subscription_id', $subscriptionId)
					->where('status', 1)
					->where('period_id', $subscriptionPeriod['id'])
					->update(['units' => 0]);
			}
			else
			{
				$this->db->table(Config::get('throttle::tables.user_pack'))
					->where('pack_id', $packId)
					->where('subscription_id', $subscriptionId)
					->where('status', 1)
					->where('period_id', $subscriptionPeriod['period_id'])
					->decrement('units', $units);
			}

			$this->subscriber->incrementLimit($subscriptionId, $pack['feature_id'], (-1)*$units*$pack['quantity']);
		
			//commition the work after processing
			$this->db->commit();
		}
		catch(PDOException $e)
		{
			//rollback if failed
			$this->db->rollback();
			
			throw new Exceptions\InternalException("Something went wrong with database");	
		}
	}

	private function isPackExistsForUser($subscriptionId, $packId, $units)
	{
		try
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
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException("Something went wrong with database");	
		}
	}

	private function updatePackForUser($subscriptionId, $packId, $units, $periodId)
	{
		try
		{
			return $this->db->table(Config::get('throttle::tables.user_pack'))
				->where('pack_id', $packId)
				->where('subscription_id', $subscriptionId)
				->where('period_id', $periodId)
				->where('status', '1')
				->increment('units', $units);
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException("Something went wrong with database");	
		}
	}

	private function getPackBySubscriptionId($subscriptionId, $packId)
	{
		try
		{
			return $this->db->table(Config::get('throttle::tables.user_pack'))
				->where('pack_id', $packId)
				->where('subscription_id', $subscriptionId)
				->where('status', '1')
				->first();
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException("Something went wrong with database");	
		}
	}

	public function getPacksForSubscriptionFeature($subscriptionId, $featureId)
	{
		try
		{
			return $this->db->table(Config::get('throttle::tables.user_pack').' as up')
				->join(Config::get('throttle::tables.packs').' as p', 'p.id', '=', 'up.pack_id')
				->where('p.feature_id', $featureId)
				->where('up.status', '1')
				->where('up.subscription_id', $subscriptionId)
				->select('p.id', 'p.price', 'up.units', 'p.quantity')
				->get();
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException("Something went wrong with database");	
		}
	}

	public function isValidPackForUser($subscriptionId, $packId)
	{
		try
		{
			$limit =  $this->db->table(Config::get('throttle::tables.user_feature_limit').' as ufl')
				->join(Config::get('throttle::tables.packs').' as p', 'p.feature_id', '=', 'ufl.feature_id')
				->where('ufl.subscription_id', $subscriptionId)
				->where('p.id', $packId)
				->select('ufl.limit')
				->first();

			return ! is_null($limit);
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException("Something went wrong with database");	
		}
	}

	public function getAllPacks()
	{
		try
		{
			return $this->db->table(Config::get('throttle::tables.packs'))
				->get();
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException("Something went wrong with database");	
		}
	}

	public function seedPackForNewPeriod($subscriptionId)
	{
		try
		{		
			//starting a transition
			$this->db->beginTransaction();

			$packs = $this->getPacksBySubscription($subscriptionId);

			$period = $this->period->store($subscriptionId, Carbon::today()->toDateString(), Carbon::today()->addMonth()->toDateString());

			foreach ($packs as $pack) 
			{
				if($pack['units'] != 0)
				{
					$this->db->table(Config::get('throttle::table.user_pack'))
						->where('id', $pack['id'])
						->update(['status' => 0]);
						
					$this->db->table(Config::get('throttle::tables.user_pack'))->insert([
						'subscription_id'      => $pack['subscription_id'],
						'pack_id'  	  	       => $pack['pack_id'],
						'units'                => $pack['units'],
						'status'               => 1,
						'period_id'            => $period['id']
					]);
				}
			}

			//commition the work after processing
			$this->db->commit();
		}
		catch(PDOException $e)
		{
			//rollback if failed
			$this->db->rollback();

			throw new Exceptions\InternalException("Something went wrong with database");
		}
	}

	private function getPacksBySubscription($subscriptionId)
	{
		try
		{
			return $this->db->table(Config::get('throttle::tables.user_pack'). ' as up')
				->join(Config::get('throttle::tables.packs'). ' as p',  'up.pack_id', '=', 'p.id')
				->where('subscription_id', $subscriptionId)
				->where('status', '1')
				->select('p.id as id', 'p.name as name', 'p.price as price', 'p.quantity as quantity', 'up.units as units')
				->get();
		}
		catch(PDOException $e)
		{
			throw new Exceptions\InternalException("Something went wrong with database");	
		}
	}
}