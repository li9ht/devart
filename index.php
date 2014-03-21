<?php
ini_set('xdebug.var_display_max_depth', '10');    
require_once __DIR__.'/vendor/autoload.php';

use \Yangqi\Htmldom\Htmldom as Htmldom;
use Desarrolla2\Cache\Cache;
use Desarrolla2\Cache\Adapter\File;

$app = new Silex\Application();
$app['debug'] = true;

//libs
$app->curl = new Curl;
//cache config
$cacheDir = 'storage';
$adapter = new File($cacheDir);
$app->cache = new Cache($adapter); 


$app->get('/',function (){
	return '';
});

function getFromCacheOrHtml($url){

	$cacheDir = 'storage';
	$adapter = new File($cacheDir);
	$cache = new Cache($adapter); 


	$makeCacheVal = $cache->get(base64_encode($url) );
	if($makeCacheVal){
		$htmlStr = $makeCacheVal;
	}else{
		
		$htmlModel = new Htmldom($url);
		$htmlStr   = $htmlModel;
		$cache->set(base64_encode($url) , $htmlStr, 9600);

	}

	return $htmlStr;
}

$app->get('/site',function () use ($app){
	
	$url  = 'http://www.deviantart.com/';

	//$htmlModel = new Htmldom($url);
	$htmlModel = new Htmldom();
	$htmlModel->load(getFromCacheOrHtml($url));


	foreach($htmlModel->find('a.cat-depth-0') as $element){
		if(!empty($element->href)){
			$model[] =$element->href;

			//$subHtml = new Htmldom($element->href);
			$subHtml = new Htmldom();
			$subHtml->load(getFromCacheOrHtml($element->href));
			foreach ($subHtml->find('a.cat-depth-2') as $subElement) {
				$mainModel[$element->href][] = $subElement->href;
			}

		}					
	}

	//var_dump($mainModel);
	echo '<string-array name="parent_nav">';
	foreach ($mainModel as $key => $value) {

		$parentName = str_replace("http://www.deviantart.com/", "", $key);
		$parentName = str_replace("/", "", $parentName);

		
		echo '<item>'.$parentName.'</item>';


		// echo '<string-array name="'.$parentName.'">';
		// foreach ($value as $subItem) {
		// 	$child = str_replace("http://www.deviantart.com/".$parentName, "", $subItem);
		// 	echo '<item>'.$child.'</item>';
		// }
		//echo '</string-array>';

	}
	echo '</string-array>';

	return '';
	
});


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



	$query = trim($query);
	$query = urlencode($query);

	//$query ='sort:time meta:all boost:popular max_age:48h';
	//$url  = 'http://backend.deviantart.com/rss.xml?type=deviation&q=boost%3Apopular';
	
	$url   = "http://backend.deviantart.com/rss.xml?offset=$offset&q=";
	$url   = $url.$query;
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


	if(!empty($art['link_next'])){
		$url = parse_url($art['link_next'][0]);
		$query = parse_str($url['query'],$args);
		$art['offset'] = $args['amp;offset'];
	}else{
		$art['offset'] = "0";
	}


	foreach ($items as $item)
	{
		$tmp = new stdClass(); 
		$tmp->title = $item->get_title() ;
		$tmp->link = $item->get_link() ;
		
		if ($enclosure = $item->get_enclosure()){

			//$tmp->date     = $enclosure->get_date();
			$tmp->media_link 	= $enclosure->get_link();
			$tmp->media_ratings	   = $enclosure->get_ratings();
			$tmp->media_thumbnails = $enclosure->get_thumbnails();
			$tmp->categories	= $enclosure->get_categories();
			$tmp->category	= $tmp->categories[0]->term;
			$tmp->credit    = $enclosure->get_credits();
			$tmp->author_name = $tmp->credit[0]->name;
			$tmp->author_avatar = $tmp->credit[1]->name;
			$tmp->copyright	= $enclosure->get_copyright();
			$tmp->description = $enclosure->get_description();
			//s$tmp->credit_img    = $enclosure->get_credit(1);
		}

		$tmp->thumbnail = trim($tmp->media_thumbnails[1]);

		if(empty($tmp->media_link)){
			$tmp->media_link = $tmp->media_thumbnails[2];
		}

		if(!empty($tmp->thumbnail)){
			$tmp_item[] = $tmp;
		}
		
	}
	if(!empty($tmp_item)){
		$art['items'] = $tmp_item;
		$art['error'] = 'no_error';
	}else{
		$art['error'] = 'no_data';
	}

	return $app->json($art, 201);
});


$app->run();