<?php namespace Owlgrin\Throttle;

use Illuminate\Support\Facades\Facade;

/**
 * The Throttle Facade
 */
class ThrottleFacade extends Facade
{
	/**
	 * Returns the binding in IoC container
	 * @return string
	 */
	protected static function getFacadeAccessor()
	{
		return 'throttle';
	}
}