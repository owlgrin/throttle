<?php namespace Owlgrin\Throttle\Exceptions;

class NotFoundException extends Exception {

	/**
	 * Message
	 */
	const MESSAGE = 'throttle::responses.message.not_found_error';

	/**
	 * Constructor
	 * @param mixed $messages
	 * @param array $replacers
	 */
	public function __construct($messages = self::MESSAGE, $replacers = array())
	{
		parent::__construct($messages, $replacers);
	}
}