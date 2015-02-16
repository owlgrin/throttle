<?php namespace Owlgrin\Throttle\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

use Illuminate\Database\DatabaseManager as Database;

/**
 * Command to generate the required migration
 */
class DisplayPlanInJsonFormatCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'throttle:display-plan';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'To display plan in json format';


	protected $db;

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function __construct(Database $db)
	{
		$this->db = $db;
 		parent::__construct();
	}

	public function fire()
	{
		printf(json_encode($this->getJsonFormattedPlan()));
	}

	protected function getJsonFormattedPlan()
	{
		$data = $this->getPlanData();
		$plan = array_only($data[0], ['name', 'identifier', 'description']);
		$plan['features'] = $this->getFormattedFeatures($data);

		return ['plan' => $plan];
	}

	protected function getPlanData()
	{
		return $this->db->select('
				SELECT
					`tp`.`name` AS `name`,
					`tp`.`identifier` AS `identifier`,
					`tp`.`description` AS `description`,
					`tf`.`id` AS `feature_id`,
					`tf`.`name` AS `feature_name`,
					`tf`.`identifier` AS `feature_identifier`,
					`tf`.`aggregator` AS `feature_aggregator`,
					`tpf`.`rate` AS `feature_rate`,
					`tpf`.`per_quantity` AS `feature_quantity`,
					`tpf`.`tier` AS `feature_tier`,
					`tpf`.`limit` AS `feature_limit`
				FROM
					`_throttle_plans` AS `tp` JOIN
					`_throttle_features` AS `tf` JOIN
					`_throttle_plan_feature` AS `tpf`
					ON
						`tp`.`id` = `tpf`.`plan_id` AND
						`tf`.`id` = `tpf`.`feature_id`
					WHERE
					`tp`.`identifier` = :plan_id',
				[
					'plan_id' => $this->argument('plan')
				]
			);
	}

	protected function getFormattedFeatures($data)
	{
		$features = [];
		$featuresPushed = [];

		foreach($data as $index => $value)
		{
			if(! in_array($value['feature_identifier'], $featuresPushed))
			{
				array_push($features, [
					'name' => $value['feature_name'],
					'identifier' => $value['feature_identifier'],
					'aggregator' => $value['feature_aggregator'],
					'tier' => $this->getFormattedTiersForFeature($value['feature_identifier'], $data)
				]);

				array_push($featuresPushed, $value['feature_identifier']);
			}
		}

		return $features;
	}

	protected function getFormattedTiersForFeature($identifier, $data)
	{
		$tiers = [];
		foreach($data as $index => $value)
		{
			if($value['feature_identifier'] == $identifier)
			{
				array_push($tiers, [
					'rate' => $value['feature_rate'],
					'per_quantity' => $value['feature_quantity'],
					'limit' => $value['feature_limit']
				]);
			}
		}

		return $tiers;
	}


	protected function getArguments()
	{
		return array(
			array('plan', InputArgument::REQUIRED, 'The plan identifier of plan to be displayed.')
		);
	}
}