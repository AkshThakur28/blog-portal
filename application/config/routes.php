<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'blog';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

/* Application routes */
$route['login'] = 'auth/index';
$route['dashboard'] = 'dashboard/index';
$route['posts'] = 'dashboard/posts';

/* Public blog */
$route['post/(:any)'] = 'blog/view/$1';

/* API routes */
$route['api/login']['post'] = 'auth/login';

$route['api/posts']['get'] = 'api/posts/index';
$route['api/posts']['post'] = 'api/posts/create';
$route['api/posts/(:num)']['put'] = 'api/posts/update/$1';
$route['api/posts/(:num)']['post'] = 'api/posts/update/$1';
$route['api/posts/(:num)']['delete'] = 'api/posts/delete/$1';
$route['api/posts/(:num)/restore']['post'] = 'api/posts/restore/$1';
$route['api/posts/(:num)/hard']['delete'] = 'api/posts/hard_delete/$1';

$route['api/pixabay/search']['get'] = 'api/pixabay/search';
$route['api/posts/(:num)']['get'] = 'api/posts/show/$1';
$route['posts/create']['get'] = 'dashboard/create';
$route['posts/edit/(:num)']['get'] = 'dashboard/edit/$1';

/* Dev helpers (non-production) */
$route['dev-login']['get'] = 'auth/dev_login';
$route['set-token']['get'] = 'auth/set_token';
