<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Throttle;

/**
 * Command to generate the required migration
 */
class UserUnsubscribeCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'throttle:unsubscribe';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Unsubscribes the user';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */

	public function __construct()
	{
 		parent::__construct();
	}

	public function fire()
	{
		$userId = $this->option('user');

		Throttle::unsubscribe($userId);
		
		$this->info('User With id '.$userId.' has been unsubscribed');
	}

	protected function getOptions()
	{
		return array(
			array('user', null, InputOption::VALUE_OPTIONAL, 'The id of the user who wants to unsubscribe', null)
		);
	}
}