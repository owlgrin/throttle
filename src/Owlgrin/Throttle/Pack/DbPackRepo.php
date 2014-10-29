<?php namespace Owlgrin\Throttle\Pack;

use Illuminate\Database\DatabaseManager as Database;
use Owlgrin\Throttle\Pack\PackRepo;
use Owlgrin\Throttle\Subscriber\SubscriberRepo as Subscriber;
use Owlgrin\Throttle\Exceptions;
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

	public function addPackForUser($packId, $subsciptionId, $units)
	{
		try
		{
			//starting a transition
			$this->db->beginTransaction();

			$pack = $this->find($packId);

			$this->db->table(Config::get('throttle::tables.user_packs'))->insert([
				'subscription_id'      => $subsciptionId,
				'pack_id'  	  	       => $packId,
				'price'                => $pack['price'],
				'units'                => $units,
				'quantity_per_unit'    => $pack['quantity'],
				'status'               => 1
			]);

			$this->subscriber->incrementLimit($subsciptionId, $pack['feature_id'], $units*$pack['quantity']);

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

	public function updatePackForUser($subscriptionId, $packId, $units)
	{
		try
		{
			$this->db->beginTransaction();

			$pack = $this->find($packId);

			$this->db->table(Config::get('throttle::tables.user_packs'))
				->where('pack_id', $packId)
				->where('subscription_id', $subscriptionId)
				->where('status', '1')
				->increment('units', $units);

			$this->subscriber->incrementLimit($subsciptionId, $pack['feature_id'], $units*$pack['quantity']);

			//commition the work after processing
			$this->db->commit();
		}
		catch(\Exception $e)
		{
			$this->db->rollback();
			throw new Exceptions\InvalidInputException('invalid pack id');
		}	
	}

	public function removePackForUser($subsciptionId, $packId)
	{
		if($this->isPackExistsForUser($subsciptionId, $packId))
		{
			
		}
	}

	private function isPackExistsForUser($subsciptionId, $packId)
	{
		$pack = $this->db->table(Config::get('throttle::tables.user_packs'))
				->where('pack_id', $packId)
				->where('subscription_id', $subscription_id)
				->where('status', '1')
				->first();

		if($pack) return true;

		return false;
	}
}