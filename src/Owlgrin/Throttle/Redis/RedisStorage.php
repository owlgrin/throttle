<?php namespace Owlgrin\Throttle\Redis;

use Owlgrin\Throttle\Exceptions;
use Exception, Config, Redis;

class RedisStorage {

	protected $redis;

	public function __construct(Redis $redis)
	{
		$config = Config::get('database.redis.'.Config::get('throttle::redis.connections'));
		$this->redis = $redis;
		
		try
		{
			if(isset($config['host']) and isset($config['port']))
			{
				$this->redis->connect($config['host'], $config['port']);		
			}
		}
		catch(Exception $e)
		{
			throw new Exceptions\InvalidInputException('Config credentials must be right');
		}
	}

	public function hashSet($hash, $key, $value)
	{	
		$this->redis->hSet($hash, $key, $value);
	}

	public function hashIncrement($hash, $key, $value)
	{
		return $this->redis->hIncrBy($hash, $key, $value);
	}

	public function hashGet($hash, $key)
	{
		return $this->redis->hGet($hash, $key);
	}

	public function hashUnset($hash, $userId)
	{
		return $this->redis->hDel($hash, $userId);
	}
}