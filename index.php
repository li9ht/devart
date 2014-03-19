<?php
ini_set('xdebug.var_display_max_depth', '10');    
require_once __DIR__.'/vendor/autoload.php';

$app = new Silex\Application();
$app['debug'] = true;

//libs
$app->curl = new Curl;


$app->get('/',function (){
	return '';
});

// $app->get('/popular',function () use ($app){
	
// 	$url  = 'http://backend.deviantart.com/rss.xml?type=deviation&q=boost%3Apopular';


// 	$response = $app->curl->get($url);
// 	$xml = simplexml_load_string($response);
// 	$namespaces = $xml->getNamespaces(true); // get namespaces

// 	// iterate items and store in an array of objects
// 	$items = array();
// 	foreach ($xml->channel->item as $item) {

// 	  $tmp = new stdClass(); 
// 	  $tmp->title = trim((string) $item->title);
// 	  $tmp->link  = trim((string) $item->link);
// 	  // etc... 
// 	  // now for the url in media:content
// 	  //
// 	  $tmp->rating = trim((string)$item->children($namespaces['media'])->content->attributes()->rating);

// 	  // add parsed data to the array
// 	  $items[] = $tmp;
// 	}
// 	var_dump($items);
// });


$app->get('/search/',function () use($app){

	$boost = $app['request']->get('b');
	$range = $app['request']->get('r');
	$cat = $app['request']->get('c');
	$sort = $app['request']->get('s');
	$offset = $app['request']->get('o');

	if($boost){
		$query = 'boost:'.$boost;
	}

	if($range){
		$query .= ' max_age:'.$range;
	}

	if($cat){
		$query .=' in:'.$cat;
	}

	if($sort){
		$query .= ' sort:'.$sort;
	}

	// if($offset){
	// 	$query .= 'offset:'.$offset;
	// }

	$query = trim($query);
	$query = urlencode($query);

	//$query ='sort:time meta:all boost:popular max_age:48h';
	//$url  = 'http://backend.deviantart.com/rss.xml?type=deviation&q=boost%3Apopular';
	
	$url   = 'http://backend.deviantart.com/rss.xml?q=';
	$url   = $url.$query;
	//var_dump($url);
	// Parse it
	$feed = new SimplePie();
	$feed->set_feed_url($url);
	$feed->set_cache_location(__DIR__.'/storage/cache');
	$feed->enable_cache(true);
	$feed->enable_order_by_date(false);
	$feed->init();

	$items = $feed->get_items();


	$art['link_next'] = $feed->get_links('next');
	$art['title']	= $feed->get_title();
	$art['link']	= $feed->get_link();

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

		$tmp->thumbnail = $tmp->media_thumbnails[0];

		if(!empty($tmp->thumbnail)){
			$tmp_item[] = $tmp;
		}
		
	}

	$art['items'] = $tmp_item;
	
	//var_dump($art);

	return $app->json($art, 201);

});


$app->run();