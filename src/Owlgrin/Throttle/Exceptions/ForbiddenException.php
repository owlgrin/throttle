<?php namespace Owlgrin\Throttle\Exceptions;

class ForbiddenException extends Exception {

	/**
	 * Message
	 */
	const MESSAGE = 'throttle::responses.message.forbidden';

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