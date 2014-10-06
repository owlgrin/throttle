throttle
========
Throttle allows you to maintain  a integration between your plans and your features.

Installation
============
To install the package, include the following in your composer.json.
...
"sahil/throttle": "dev-master"
...

And then include the following service provider in your app.php.

...
'Owlgrin\Throttle\ThrottleServiceProvider'
...


And lastly, publish the config.
...
php artisan config:publish sahil/throttle
...

Usage

Write this command in your artisan to create migrations

...
throttle:table
...

Now migrate all the tables to your mysql db

...
php artisan migrate
..

Entry of New Plan

Its time to create a new plan by using Owlgrin\Plan\PlanRepo 's add function

plan's format is as follows

$plan = {
  "plan":{
      "name" : "Simple",
      "identifier" :"simple",
      "description" :"this is a simple plan",
    	"features": [
          {
            "name":"Horn",
            "identifier":"horn",
            "tier" :[
                {
                "rate":"4",
                "per_quantity":1,
                "limit":"500"
              },
               {
                "rate":"3",
                "per_quantity":1,
                "limit":"5000"
              }
            ]
   	 },
        {
            "name":"Mail",
            "identifier":"mail",
            "tier" :[
                {
                "rate":"4",
                "per_quantity":1,
                "limit":"100"
              },
               {
                "rate":"3",
                "per_quantity":1,
                "limit":"1000"
              }
            ]
   	 }
    ]
  }
}


Subscription of user

You can subscribe a user with plan id by using
...
Owlgrin\Throttle\Subscriber\SubscriberRepo
...

subscribe($userId, $planId)

Biller

You can calculate the bill by just using

...
Owlgrin\Throttle\Biller\Biller
...

calculate($userId)

or can estimate bill by

estimate($plan)

$plan = {'plan_id':1,
          'feature':{
            {feature_id}:{usage},
            {feature_id}:{usage}
          }
        }
        
