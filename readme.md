# Navtools package to Laravel

<a href="https://packagist.org/packages/syscover/pulsar-navtools"><img src="https://poser.pugx.org/syscover/pulsar-navtools/downloads" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/syscover/pulsar-navtools"><img src="https://poser.pugx.org/syscover/pulsar-navtools/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/syscover/pulsar-navtools"><img src="https://poser.pugx.org/syscover/pulsar-navtools/license.svg" alt="License"></a>

## Installation

**1 - From the command line run**
```
composer require syscover/pulsar-navtools
```

**2 - To publish package, you must type on console**
```
php artisan vendor:publish --provider="Syscover\Navtools\NavtoolsServiceProvider"
```

**3 - Register middlewares pulsar.navtools on file app/Http/Kernel.php add to routeMiddleware array**
```
'pulsar.navtools' => \Syscover\Navtools\Middleware\Navtools::class,
```


## General configuration environment values

### Set NAVTOOLS_URL_TYPE options on environment file .env
Set url type for you web, you have three types, lang, country or lang-country, for urls type lang:
```
NAVTOOLS_URL_TYPE=lang
```
you can work with this urls
```
hrrp://mydomain.com/en/any-page
```

for urls type country
```
NAVTOOLS_URL_TYPE=country
```
you can work with this urls
```
hrrp://mydomain.com/us/any-page
```

for urls type lang-country
```
NAVTOOLS_URL_TYPE=lang-country
```
you can work with this urls
```
hrrp://mydomain.com/en-us/any-page
```


### Set NAVTOOLS_LANGS options on environment file .env
Set available languages in your web
```
NAVTOOLS_LANGS=en|es
```


### Set NAVTOOLS_COUNTRIES options on environment file .env
Set available countries in your web
```
NAVTOOLS_COUNTRIES=us|gb|es
```


### Set NAVTOOLS_DEFAULT_COUNTRY options on environment file .env
Set default country for your web
```
NAVTOOLS_DEFAULT_COUNTRY=es
```


### Routing with Navtools
On app\Http\routes.php file use this closure to implement routes with translation

```
Route::group(['middleware' => ['pulsar.navtools']], function() {

    // write here your routes

});

```

#### Route configuration

You have several url configuration options depends on the chosen NAVTOOLS_URL_TYPE parameter:

Write your routes with lang variable

```
Route::group(['middleware' => ['pulsar.navtools']], function() {
    Route::get('/',                         function(){ return view('www.index'); });
    Route::get('{lang}',                  function(){ return view('www.index'); });
    Route::post('{lang}/contact',         ['as'=>'contact',  'uses'=>'FrontEndController@contact']);
});

```

Or set lang variable on your routes

```
Route::group(['middleware' => ['pulsar.navtools']], function() {
    Route::get('/',                   function(){ return view('www.index'); });

    Route::get('en',                  function(){ return view('www.index'); });
    Route::get('es',                  function(){ return view('www.index'); });

    Route::post('en/contact',         ['as' => 'contact-en',          'uses'=>'FrontEndController@contact']);
    Route::post('es/contacto',        ['as' => 'contact-es',          'uses'=>'FrontEndController@contact']);
});

```

Or set constant lang but country variable

```
Route::group(['middleware' => ['pulsar.navtools']], function() {
    Route::get('/',                   function(){ return view('www.index'); });

    Route::get('/en-{country}',                  function(){ return view('www.index'); });
    Route::get('/es-{country}',                  function(){ return view('www.index'); });

    Route::post('en-{country}'/contact',         ['as' => 'contact-en',          'uses'=>'FrontEndController@contact']);
    Route::post('es-{country}'/contacto',        ['as' => 'contact-es',          'uses'=>'FrontEndController@contact']);
});

```

Or use lang and country variables to get language value.

```
Route::group(['middleware' => ['pulsar.navtools']], function() {
    Route::get('/',                   function(){ return view('www.index'); });

    Route::get('/{lang}-{country}',                  function(){ return view('www.index'); });

    Route::post('/{lang}-{country}/contact',         ['as' => 'contact-en',          'uses'=>'FrontEndController@contact']);
});

```

## Helper [user_country()]
Get country from user, a simple case
```
user_country(); // get country user
```

## Helper [user_lang()]
Get lang from user, a simple case
```
user_lang(); // get language user
```

## Helper [nt_route()]
To set routes you need to add lang or country parameters depending on NAVTOOLS_URL_TYPE.
```
route('routeName', ['lang' => 'en', 'country' => 'us']);
```

You can use a custom helper **nt_route()**, this helper inserts automatically variables lang and country, unless you specify these variables.
```
nt_route('routeName');
```

You can use **redirect()** helper without any trouble, we have extended Laravel core so that **redirect()->route()** does the same as **nt_route()**.


## Helper [change_lang()]
You can change the language with change_lang helper
```
change_lang('en');
```

## Helper [change_country()]
You can change the country with change_country helper
```
change_country('us');
```

## Helper [active_route()]
Active route is a helper for to know when any route is active, has this properties:
* route:string|array = Route name or array of route names to target
* calls:string default:null = If isset this variable, helper will return the indicated string or a boolean
* firstOccurrence:float = Check that the first section of the url matches the route
 
A simple case
```
<a class="{{ active_route('home', 'active') }}" href="{{ route('home') }}">HOME</a>
```
case with multiples routes
```
<a class="{{ active_route(['home', 'home-en', 'home-es'], 'active') }}" href="{{ route('home') }}">HOME</a>
```
case with nested route
```
<a class="{{ active_route('product', 'active', true) }}" href="{{ route('product-01') }}">PRODUCT</a>
```

## Helper [get_lang_route_name()]
Get the route name of current route in other language
```
<a href="{{ route(get_lang_route_name('en')) }}">Change Lang</a>
```

## Helper [get_lang_route()]
Get the url of current route in other language
```
<a href="{{ get_lang_route('en') }}">Change Lang</a>
```


## License

The Navtools is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).