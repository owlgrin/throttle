<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Owlgrin\Throttle\Feature\FeatureRepo;
use Config;

/**
 * Command to generate the required migration
 */
class FeatureListCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'throttle:feature:list';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Displays the list of all features';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	protected $Feature;

	public function __construct(FeatureRepo $feature)
	{
 		parent::__construct();
		$this->feature = $feature;
	}

	public function fire()
	{		
		$features = $this->feature->getAllFeatures();

		print_r($features);
	}
}