<?php namespace Owlgrin\Throttle\Exceptions;

class PlanExpiredException extends Exception {

	/**
	 * Message
	 */
	const MESSAGE = 'throttle::responses.message.plan_expired';

	/**
	 * Code
	 */
	const CODE = 400;

	/**
	 * Constructor
	 * @param mixed $messages
	 * @param array $replacers
	 */
	public function __construct($messages = self::MESSAGE, $replacers = array())
	{
		parent::__construct($messages, $replacers, self::CODE);
	}
}