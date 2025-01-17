<?php

class awesome_flow{
	
	static function env_setup(){
		if(WP_DEBUG){
			error_reporting(E_ALL);
			$old_error_handler = set_error_handler("aw2_error_log::awesome_error_handler");
		}
		
		//if($old_error_handler)restore_error_handler();
		try {
		if(AWESOME_DEBUG)\aw2\debug\setup([]);	
		if(AWESOME_DEBUG)\aw2\debug\flow(['main'=>'start initialize']);

		if(DEL_ENV_CACHE)aw2\global_cache\del(['main'=>ENV_CACHE],null,null);
		
		//get all the locations for code`
		$ref=&aw2_library::get_array_ref();
		$ref['code_connections']=array();
		
		if(defined('CONNECTIONS')){
			//put the locations in the env
			$ref['code_connections']=CONNECTIONS;
			
			if(defined('CODE_DEFAULT_CONNECTION')){
				//put the locations in the env
				$ref=&aw2_library::get_array_ref();
				$ref['code_connections']['#default']=$ref['code_connections'][CODE_DEFAULT_CONNECTION];
			}
		}	
		if(!isset($ref['code_connections']['#default']))
			$ref['code_connections']['#default']=array(
				'connection_service'=>'wp_conn',
				'db_host'=>DB_HOST,
				'db_user'=>DB_USER,
				'db_password'=>DB_PASSWORD,
				'db_name'=>DB_NAME,
				'redis_db'=>REDIS_DATABASE_GLOBAL_CACHE
			);

		if(USE_ENV_CACHE && aw2\global_cache\exists(["main"=>ENV_CACHE])){
				header('awesome_cache: used');
			$ref=&aw2_library::get_array_ref();
			
			$handlers=aw2\global_cache\hget(["main"=>ENV_CACHE,"field"=>"handlers"]);
			$ref['handlers']=unserialize($handlers);
			
			$ref['apps']=unserialize(aw2\global_cache\hget(["main"=>ENV_CACHE,"field"=>"apps"]));
			
			$ref['awesome_core']=unserialize(aw2\global_cache\hget(["main"=>ENV_CACHE,"field"=>"awesome_core"]));
			$ref['settings']=unserialize(aw2\global_cache\hget(["main"=>ENV_CACHE,"field"=>"settings"]));
			$ref['css']=unserialize(aw2\global_cache\hget(["main"=>ENV_CACHE,"field"=>"css"]));
			//These are content type stubs and not actual content types
			$ref['content_types']=unserialize(aw2\global_cache\hget(["main"=>ENV_CACHE,"field"=>"content_types"]));
		}
		else{
				header('awesome_cache: not used');
			
		// load core
			if(defined('AWESOME_CORE_POST_TYPE')){
				\aw2_library::add_service('core','core service refers to core posts for config etc.',['post_type'=>AWESOME_CORE_POST_TYPE]);
			}		

			//load all the apps
			self::load_apps();
			self::run_core('apps');

			self::run_core('services');
			self::run_core('less-variables');
				

			//self::run_core('config');
			self::load_env_settings();
			
			
			
			$ref=&aw2_library::get_array_ref();
			if(!isset($ref['content_types']))$ref['content_types']=array();
			self::run_core('content-types');
			
			if(SET_ENV_CACHE){
				$ref=aw2_library::get_array_ref();
				$handlers=serialize($ref['handlers']);
				aw2\global_cache\hset(["main"=>ENV_CACHE,"field"=>"handlers","value"=>$handlers]);				
				aw2\global_cache\hset(
				["main"=>ENV_CACHE,"field"=>"apps","value"=>serialize($ref['apps'])]);				
				
				aw2\global_cache\hset(["main"=>ENV_CACHE,"field"=>"settings","value"=>serialize($ref['settings'])]);
				aw2\global_cache\hset(["main"=>ENV_CACHE,"field"=>"css","value"=>serialize($ref['css'])]);

				$content_types=$ref['content_types'];
				$ct_arr=array();
				if($content_types){
					foreach($content_types as $field=>$def){
						aw2\global_cache\hset(["main"=>ENV_CACHE,"field"=>"#ct_" . $field,"value"=>serialize($def)]);					
						$ct_arr[$field]='#cached';
					}
					aw2\global_cache\hset(["main"=>ENV_CACHE,"field"=>"content_types","value"=>serialize($ct_arr)]);					
				}
			}	
		}	
		//time_zone
		$time_zone = aw2_library::get('settings.time_zone');
		if(!empty($time_zone))date_default_timezone_set($time_zone);
		
		//$timeConsumed = round(microtime(true) - $GLOBALS['curTime'],3)*1000; 
		//echo '/*' .  '::end initialize:' .$timeConsumed . '*/';
	}
		catch(Throwable $e){
			$reply=aw2_error_log::awesome_exception('env_setup',$e);
		}	
	}
	
		
	static function run_core($module){
		if(!defined('AWESOME_CORE_POST_TYPE'))return;
		
		$arr=\aw2_library::get_module(['post_type'=>AWESOME_CORE_POST_TYPE],$module);
		if($arr)\aw2_library::module_run(['post_type'=>AWESOME_CORE_POST_TYPE],$module);
		if(AWESOME_DEBUG)\aw2\debug\flow(['main'=>$module . ' Setup']);
	}
		

	static function load_env_settings(){
		$settings=&aw2_library::get_array_ref('settings');
		$settings=array();
		
		$exists=aw2_library::module_exists_in_collection(['post_type'=>AWESOME_CORE_POST_TYPE],'settings');
		if(!$exists) return;
			
		$all_post_meta = aw2_library::get_module_meta(['post_type'=>AWESOME_CORE_POST_TYPE],'settings');
		
		foreach($all_post_meta as $key=>$meta){
			
			//ignore private keys
			if(strpos($key, '_') === 0 )
				continue;
			
			$settings[$key] = $meta;
		}
		if(AWESOME_DEBUG)\aw2\debug\flow(['main'=>'Env Setup']);

	}

	static function init(){
		try{	
		self::run_core('init');
		if(AWESOME_DEBUG)\aw2\debug\flow(['main'=>'Init fired']);			

		//custom init for debugging purpose		
		if(DEVELOP_FOR_AWESOMEUI && isset($_COOKIE['debug_init_module']) && !empty($_COOKIE['debug_init_module'])){
			$user_init_module = $_COOKIE['debug_init_module'];
			self::run_core($user_init_module);
		} 	


		//Decide caching or not caching
		$cache=array();
		$cache['enable']='no';
		if(isset($_SERVER['REQUEST_METHOD'])&& $_SERVER['REQUEST_METHOD']==='GET'){
			if(!isset($_SERVER['QUERY_STRING']) || empty($_SERVER['QUERY_STRING'])){
				if(!isset($_SERVER['HTTP_REFERER']) || empty($_SERVER['HTTP_REFERER'])){
					if(!(array_key_exists('wordpress_logged_in',$_COOKIE) || array_key_exists('aw2_vsession',$_COOKIE) || array_key_exists('wordpress_no_cache',$_COOKIE))){
						if(!IS_WP){
							$cache['failed']='Not WP';
						}
						else{
							if(!is_user_logged_in()){
								$cache['enable']='yes';
							}
							else{
								$cache['failed']='Logged in User';
							}
						}
					}
					else{
						$cache['failed']='Restricted Cookies are there';
					}
				}
				else{
					$cache['failed']='Referrer is there';
				}
			}
			else{
				$cache['failed']='Query String is there';
			}
		}
		else{
			$cache['failed']='Not GET Method';
			
		}
		$env=&aw2_library::get_array_ref();
		$env['cache']=$cache;
	}
		catch(Throwable $e){
			$reply=aw2_error_log::awesome_exception('init',$e);
		}
	}
	
	static function load_apps(){
		$registered_apps=&aw2_library::get_array_ref('apps');
		if(!defined('AWESOME_APPS_POST_TYPE')){
			$registered_apps=array();
			return;
		}

		$app_posts= aw2_library::get_collection(["post_type"=>AWESOME_APPS_POST_TYPE]);
		foreach($app_posts as $app_post){
			$app = array();

			//path has to be handled correctly
			$app['base_path']=AWESOME_APP_BASE_PATH .'/'.$app_post['module'];
			$app['path']=AWESOME_APP_BASE_PATH .'/'.$app_post['module'];
			$app['name']=$app_post['title'];
			$app['slug']=$app_post['module'];
			$app['post_id']=$app_post['id'];
			$app['hash']=$app_post['hash'];
			
			$app['collection']=array();

			$app_meta=aw2_library::get_module_meta(["post_type"=>AWESOME_APPS_POST_TYPE],$app['slug']);
			$app_config=isset($app_meta['config_collection']) ? $app_meta['config_collection'] :'' ;
			if($app_config){
				$app['collection']['config']['post_type']=$app_config;
			}
			
			$modules=isset($app_meta['modules_collection']) ? $app_meta['modules_collection'] :'' ;
			if($modules){
				$app['collection']['modules']['post_type']=$modules;
			}
			
			$pages=isset($app_meta['pages_collection']) ? $app_meta['pages_collection'] :'' ;
			if($pages){
				$app['collection']['pages']['post_type']=$pages;
			}	
			
			$posts=isset($app_meta['posts_collection']) ? $app_meta['posts_collection'] :'' ;
			if($posts){
				$app['collection']['posts']['post_type']=$posts;
			}
			
			$registered_apps[$app_post['module']]=$app;
			if(AWESOME_DEBUG)\aw2\debug\flow(['main'=>'Apps Loaded']);

		}
		
		
	}
		
	static function app_takeover($query){
		try {
		if(AWESOME_DEBUG)\aw2\debug\flow(['main'=>'App Takeover']);
		
		$request=$query->request;

		//remove REQUEST_START_POINT
		if(defined('REQUEST_START_POINT'))
			$request=substr($request, strlen(REQUEST_START_POINT));	


		if(\aw2_library::startsWith($request,'/'))
			$request=substr($request, 1);	
			
		if(\aw2_library::endswith($request,'/'))
			$request=substr($request, 0,-1);


		if(empty($request)){
			self::initialize_root(); // it is front page hence request is not set so setup root.
			return;
		}

		$pieces = explode('/',urldecode($request));
		
		// do we own the app?
		$app_slug= $pieces[0];
		if($app_slug == 'wp-admin') return;

		$app = new awesome_app();

		//is it a ticket
		if($app_slug==='t'){
			$ticket=$pieces[1];
			$app_slug=$app->get_app_ticket($ticket);
			array_unshift($pieces,$app_slug);
		}
		if($app_slug==='ts'){
			$ticket=$pieces[1];
			$app_slug=$app->get_app_ts($ticket);
			array_unshift($pieces,$app_slug);
		}
		
		$cs=aw2_library::get_array_ref('handlers','controllers');

		if(isset($cs[$app_slug])){
			$o=new stdClass();
			$o->pieces=$pieces;
			$name=array_shift($o->pieces);
			\aw2_library::service_run('controllers.' . $name,['o'=>$o],null,'service'); // run the controller service, it is responsible for handling echo and exit.
		}
		
		if($app->exists($app_slug)){
			//yes - setup app
			$app->setup($app_slug);
			array_shift($pieces); 
		}
		else if($app->exists('root')){
			//No - Root Exists?  - setup root app
			$app->setup('root');
		}
		else{
			//No - possible issue
			return;
		}

		$app->load_settings();
		$app->setup_collections();
		if(AWESOME_DEBUG)\aw2\debug\flow(['main'=>'App Setup Done']);				
		$arr=array();
		$arr['status']='';
		$arr=$app->check_rights($request);

		// run init
		$app->run_init();

		if(AWESOME_DEBUG)\aw2\debug\flow(['main'=>'App Init done']);		
		//now resolve the route.
		
		if($app->slug!='root'){
			$app->resolve_route($pieces,$query);
		}
		if(AWESOME_DEBUG)\aw2\debug\flow(['main'=>'Wordpress Theme taking Over']);	
	} 
		catch(Throwable $e){
			$reply=aw2_error_log::awesome_exception('app_takeover',$e);
				die($reply);
		}	
	} 
	
	static function initialize_root(){

		$app = new awesome_app();
		if($app->exists('root')){
			$app->setup('root');
			$app->load_settings();
			$app->setup_collections();
			$app->run_init();
		}	
		
	}
	
    static function head(){
		
		if(defined('AWESOME_CORE_POST_TYPE')){
			$arr=\aw2_library::get_module(['post_type'=>AWESOME_CORE_POST_TYPE],'scripts');
			if($arr)echo \aw2_library::module_run(['post_type'=>AWESOME_CORE_POST_TYPE],'scripts');
		}
		
		$app = &aw2_library::get_array_ref('app');
		if(isset($app['collection']['config'])){
			$arr=\aw2_library::get_module($app['collection']['config'],'scripts');
			if($arr)echo \aw2_library::module_run($app['collection']['config'],'scripts');
		}		
		
	}
	
	static function footer(){
		self::run_core('footer-scripts');
	}
}
