<?php
ini_set('xdebug.var_display_max_depth', '10');    
require_once __DIR__.'/vendor/autoload.php';

$app = new Silex\Application();
$app['debug'] = true;

//libs
$app->curl = new Curl;

$app->get('/hello/{name}', function ($name) use ($app) {
  return 'Hello '.$app->escape($name);
});

$app->get('/popular',function () use ($app){
	
	$url  = 'http://backend.deviantart.com/rss.xml?type=deviation&q=boost%3Apopular';


	$response = $app->curl->get($url);
	$xml = simplexml_load_string($response);
	$namespaces = $xml->getNamespaces(true); // get namespaces

	// iterate items and store in an array of objects
	$items = array();
	foreach ($xml->channel->item as $item) {

	  $tmp = new stdClass(); 
	  $tmp->title = trim((string) $item->title);
	  $tmp->link  = trim((string) $item->link);
	  // etc... 
	  // now for the url in media:content
	  //
	  $tmp->rating = trim((string)$item->children($namespaces['media'])->content->attributes()->rating);

	  // add parsed data to the array
	  $items[] = $tmp;
	}
	var_dump($items);
});


$app->get('/p',function () use($app){

	$url  = 'http://backend.deviantart.com/rss.xml?type=deviation&q=boost%3Apopular';
	// Parse it
	$feed = new SimplePie();
	$feed->set_feed_url($url);
	$feed->enable_cache(false);
	$feed->enable_order_by_date(false);
	$feed->init();

	$items = $feed->get_items();

	foreach ($items as $item)
	{
		$tmp = new stdClass(); 
		$tmp->title = $item->get_title() ;
		$tmp->link = $item->get_link() ;

		if ($enclosure = $item->get_enclosure()){

			$tmp->media_link 	= $enclosure->get_link();
			$tmp->media_ratings	   = $enclosure->get_ratings();
			$tmp->media_thumbnails = $enclosure->get_thumbnails();
		}

		$art[] = $tmp;
	}

	var_dump($art);

	return '';

});


$app->run();