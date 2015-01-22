<?php namespace Owlgrin\Throttle\Exceptions;

use Illuminate\Support\MessageBag;

class SubscriptionExistsException extends Exception {

	/**
	 * Message
	 */
	const MESSAGE = 'throttle::responses.message.subscription_exist';

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