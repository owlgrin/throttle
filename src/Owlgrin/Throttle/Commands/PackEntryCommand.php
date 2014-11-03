<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Owlgrin\Throttle\Pack\PackRepo;
use Config;

/**
 * Command to generate the required migration
 */
class PackEntryCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'throttle:pack';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Enters new pack in the database';

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
		$pack = $this->argument('pack');

		$this->pack->store(json_decode($pack, true)['pack']);
	}
	
	protected function getArguments()
	{
		return array(
			array('pack', InputArgument::REQUIRED, 'Stores a new pack '),
		);
	}
}