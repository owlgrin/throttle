<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Owlgrin\Throttle\Pack\PackRepo;
use Config;

/**
 * Command to generate the required migration
 */
class RemovePackForUserCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'throttle:remove:user:pack';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Remove pack for user';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	protected $pack;

	public function __construct(PackRepo $pack)
	{
 		parent::__construct();
		$this->pack = $pack;
	}

	public function fire()
	{
		$packId = $this->option('pack_id');
		$subscriptionId = $this->option('subscription_id');
		$units = $this->option('units');

		$this->pack->removePacksForUser($packId, $subscriptionId, $units);
		$this->info('User With subscription id '.$subscriptionId.' has been added to pack with id '.$packId. 'with units - '.$units);
	}
	
	protected function getOptions()
	{
		return array(
			array('pack_id', null, InputOption::VALUE_OPTIONAL, 'The id of the pack user wants to use', null),
			array('subscription_id', null, InputOption::VALUE_OPTIONAL, 'The user\'s subscription id.', null),
			array('units', null, InputOption::VALUE_OPTIONAL, 'The number of units users wants to use.', null),
		);
	}
}