<?php

return array(
	/**
	 * The following options tell Horntell to work seamlessly with
	 * the the storage. We use this SQL tables to record certain 
	 * information about the plans
	 */
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
		'user_feature_limit' => 'user_feature_limit'
	),
);