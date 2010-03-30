Service_Postmark is a Zend_Service (http://framework.zend.com/manual/en/zend.service.html) class that allows you to easily interact with the REST API available via postmark (http://postmarkapp.com/).

Usage
=====
Either include Service_Postmark.php in your application or use the auto-loader with it in your path. Instantiate a new Service_Postmark passing in your API key and you're good to go.

<php
  $service = new Service_Postmark('your_key');
  $service->getDeliveryStats();
  $service->getBounces( Service_Postmark::FILTER_BOUNCE_HARDBOUNCE, Service_Postmark::FILTER_BOUNCE_NA, '', '', 10, 0);
  $service->getBounce( 123, true );
  $service->activateBounce( 123 );
?>

Available methods include:

 getDeliveryStats() -> Return a summary of inactive emails and bounces by type
 getBounces()       -> Fetches a portion of the bounces according to the parameters
 getBounce()        -> Return details about a single bounce, optionally including the raw result
 getBounceTags()    -> Returns a list of tags used for the server
 activateBounce()   -> Activates a deactivated bounce

