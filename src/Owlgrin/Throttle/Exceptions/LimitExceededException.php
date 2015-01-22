<?php namespace Owlgrin\Throttle\Exceptions;

class LimitExceededException extends Exception {

	/**
	 * Message
	 */
	const MESSAGE = 'throttle::responses.message.limit_excedeed';

	/**
	 * Code
	 */
	const CODE = 403;

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