<?php

return array(

	'tables' => array(
		/**
		 * This table is required to keep track of the
		 * various plans of our product.
		 */
		'plans' => '_throttle_plans',
		/**
		 * This table is required to store all the features 
		 * of our product
		 */
		'features' => '_throttle_features',
		/**
		 * This table is required to map plans with features
		 * of our product
		 */
		'plan_feature' => '_throttle_plan_feature',
		/**
		 * This table is required to store subscriptions of the  
		 * users with a particular plan
		 */
		'subscriptions' => '_throttle_subscriptions',
		/**
		 * This table keeps the record of usage made by the user
		 * of a particular feature
		 */
		'user_feature_usage' => '_throttle_user_feature_usage',
		/**
		 * This table stores limit made by the user
		 * of a particular feature
		 */
		'user_feature_limit' => '_throttle_user_feature_limit',
		/**
		 * This table stores period used by the user
		 */
		'subscription_period' => '_throttle_subscription_period'
	),

	/**
	 * Set the default throttle plan.
	 */
	'plan' => 'your-plan-identifier',
	
	/**
	 * Define feature indetifier and its seeder class path
	 * which will be used while seeding daily base usage of features.
	 */
	'seeders' => array(
		'feature-identifier' => 'Seeder-class-path'
	)

);