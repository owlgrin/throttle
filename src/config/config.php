<?php

return array(

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
		 * This table stores period used by the user
		 */
		'subscription_period' => 'subscription_period'
	),

	/**
	 * Set the default throttle plan.
	 */
	
	'plan' => 'your-plan-identifier',
	
	/**
	 * The default period class path to be used in throttle.
	 */
	
	'period-class' => 'Owlgrin\Throttle\Period\CurrentMonthPeriod'

);