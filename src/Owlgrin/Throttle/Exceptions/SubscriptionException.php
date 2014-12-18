<?php namespace Owlgrin\Throttle\Exceptions;

use Illuminate\Support\MessageBag;

class SubscriptionException extends Exception {

	/**
	 * Message
	 */
	const MESSAGE = 'throttle::responses.message.not_subscribed';

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