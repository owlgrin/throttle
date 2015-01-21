<?php namespace Owlgrin\Throttle\Exceptions;

use Illuminate\Support\MessageBag;

class NoSubscriptionException extends Exception {

	/**
	 * Message
	 */
	const MESSAGE = 'throttle::responses.message.not_subscribed';

	/**
	 * Code
	 */
	const CODE = 404;

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