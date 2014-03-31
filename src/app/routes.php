<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

Route::group(array('after'=>'theme:layouts.bootstrap'), function () {
  Route::get('/', 'ItemsController@index');

  Route::get('/armor/{part}', array(
      'as'=>'item.armor',
      'uses'=>'ItemsController@armor')
  );

  Route::get('/gun/{skill}', array(
      'as'=>'item.gun',
      'uses'=>'ItemsController@gun')
  );

  Route::get('/melee', array(
      'as'=>'item.melee',
      'uses'=>'ItemsController@melee')
  );

  Route::get('/books/{type}', array(
      'as'=>'item.books',
      'uses'=>'ItemsController@books')
  );

  Route::get('/qualities/{id?}', array(
      'as'=>'item.qualities',
      'uses'=>'ItemsController@qualities')
  );

  Route::get('/materials/{id?}', array(
      'as'=>'item.materials',
      'uses'=>'ItemsController@materials')
  );

  Route::get('/containers', array(
      'as'=>'item.containers',
      'uses'=>'ItemsController@containers')
  );

  Route::get('consumibles/{type}', array(
      'as'=>'item.comestibles',
      'uses'=>'ItemsController@comestibles')
  );

  Route::get('/search', array(
      'as'=>'item.search', 
      'uses'=>'ItemsController@search')
  );

  View::composer('layouts.bootstrap', function ($view) {
    $view->with('q', Input::get('q', ''));
    $view->with('sites', Config::get('cataclysm.sites'));
  });

  View::composer('items.menu', function ($view) {
    $view->with('areas', array(
      "view"=>array(
        "route"=>"item.view",
        "label"=>"View item",
      ),
      "craft"=>array(
        "route"=>"item.craft",
        "label"=>"Craft",
      ),
      "recipes"=>array(
        "route"=>"item.recipes",
        "label"=>"Recipes",
      ),
      "disassemble"=>array(
        "route"=>"item.disassemble",
        "label"=>"Disassemble",
      ),
    ));
  });

  Route::get('/{id}/craft', array(
      'as'=>'item.craft',
      'uses'=>'ItemsController@craft')
  )
    ->where('id', '[A-Za-z0-9_-]+');

  Route::get('/{id}/recipes', array(
      'as'=>'item.recipesProxy',
      'uses'=>'ItemsController@recipesProxy')
  )
  ->where('id', '[A-Za-z0-9_-]+');

  Route::get('/{id}/recipes/{category}', array(
      'as'=>'item.recipes',
      'uses'=>'ItemsController@recipes')
  )
    ->where('id', '[A-Za-z0-9_-]+')
    ->where('category', '[A-Z_]+');

  Route::get('/{id}', array(
        'as'=>'item.view',
        'uses'=>"ItemsController@view")
  )
    ->where('id', '[A-Za-z0-9_-]+');

  Route::get('/{id}/disassemble', array(
      'as'=>'item.disassemble',
      'uses'=>'ItemsController@disassemble')
  )
    ->where('id', '[A-Za-z0-9_-]+');

});

Route::get('/sitemap.xml', 'ItemsController@sitemap');

/////////
use Illuminate\Database\Eloquent\ModelNotFoundException;

App::error(function(ModelNotFoundException $e)
{
  return Response::view("notfound", array(), 404);
});

App::missing(function ($exception) {
  return Response::view("notfound", array(), 404);
});
