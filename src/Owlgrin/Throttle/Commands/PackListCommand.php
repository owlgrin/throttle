<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Owlgrin\Throttle\Pack\PackRepo;
use Config;

/**
 * Command to generate the required migration
 */
class PackListCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'throttle:pack:list';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Displays the list of all packs';

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
		$packs = $this->pack->getAllPacks();
		print_r($packs);
	}
}