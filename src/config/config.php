<?php

return array(
	/**
	 * The following options tell Horntell to work seamlessly with
	 * the the storage. We use this SQL tables to record certain 
	 * information about the plans
	 */
	'redis' => array(

		'cluster' => false,

		'default' => array(
			'host'     => '127.0.0.1',
			'port'     => 6379,
			'database' => 0,
		),

	),

	'tables' => array(
		/**
		 * This table is required to keep track of the
		 * various plans of our product.
		 */
		'plans' => 'plans',
		/**
		 * This table is required to store all the features 
		 * of our product
		 */
		'features' => 'features',
		/**
		 * This table is required to map plans with features
		 * of our product
		 */
		'plan_feature' => 'plan_feature',
		/**
		 * This table is required to store subscriptions of the  
		 * users with a particular plan
		 */
		'subscriptions' => 'subscriptions',
		/**
		 * This table keeps the record of usage made by the user
		 * of a particular feature
		 */
		'user_feature_usage' => 'user_feature_usage',
		/**
		 * This table stores limit made by the user
		 * of a particular feature
		 */
		'user_feature_limit' => 'user_feature_limit',
		/**
		 * This table stores different packs
		 * having price and quantity
		 */
		'packs' => 'packs',
		/**
		 * This table stores packs used by the user
		 * with units
		 */
		'user_pack' => 'user_pack'
	),
);