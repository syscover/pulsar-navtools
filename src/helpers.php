<?php

if (! function_exists('nt_route'))
{
    /**
     * Generate the URL to a named route.
     *
     * @param  string  $name
     * @param  array   $parameters
     * @param  bool    $absolute
     * @return string
     */
    function nt_route($name, $parameters = [], $absolute = true)
    {
        $routeCollection    = \Illuminate\Support\Facades\Route::getRoutes();
        $route              = $routeCollection->getByName($name);

        if($route !== null)
        {
            $urlParameters = $routeCollection->getByName($name)->parameterNames();

            if(! isset($parameters['lang']) && in_array('lang', $urlParameters))
                $parameters['lang'] = user_lang();

            if(! isset($parameters['country']) && in_array('country', $urlParameters))
                $parameters['country'] = user_country();
        }

        return route($name, $parameters, $absolute);
    }
}

if (! function_exists('change_lang'))
{
    /**
     * Change the language, to set a cookie to execute on the next response
     *
     * @param $lang
     * @return void
     */
    function change_lang($lang)
    {
        \Illuminate\Support\Facades\Cookie::queue(\Illuminate\Support\Facades\Cookie::forever('pulsar.user_lang', strtolower($lang)));
        session(['pulsar.user_lang' => $lang]);
    }
}

if (! function_exists('change_country'))
{
    /**
     * Change the language, to set a cookie to execute on the next response
     *
     * @param $country
     * @return void
     */
    function change_country($country)
    {
        \Illuminate\Support\Facades\Cookie::queue(\Illuminate\Support\Facades\Cookie::forever('pulsar.user_country', strtolower($country)));
        session(['pulsar.user_country' => $country]);
    }
}

if (! function_exists('user_lang'))
{
    /**
     * Get user lang from session.
     *
     * @return string
     */
    function user_lang()
    {
        return session('pulsar.user_lang') === null?
            config('app.locale') : session('pulsar.user_lang');
    }
}

if (! function_exists('user_country'))
{
    /**
     * Get user country from session.
     *
     * @return string
     */
    function user_country()
    {
        return session('pulsar.user_country') === null?
            config('pulsar-navtools.default_country') : session('pulsar.user_country');
    }
}

if (! function_exists('get_lang_route_name'))
{
    /**
     * Return route name, given current url, depending of language
     *
     * @param   string  $lang
     * @return  string
     */
    function get_lang_route_name($lang)
    {
        $route = \Illuminate\Support\Facades\Route::getCurrentRoute();

        if($route !== null)
        {
            $routeName      = $route->getName();
            // route without parameter lang
            $originRoute    = substr($routeName, 0, strlen($routeName) - 2);
        }
        else
        {
            // if don't exist route, take root route
            $routeName      = app('router')->getRoutes()->match(app('request')->create('/'))->getName();
            // create originRoute to
            $originRoute    = $routeName;
        }

        // check routes
        if (\Illuminate\Support\Facades\Route::has($originRoute . $lang))
        {
            return $originRoute . $lang;
        }
        else
        {
            /// If exist route without lang sum new lang
            if (\Illuminate\Support\Facades\Route::has($routeName . '-' . $lang))
                return $routeName . '-' . $lang;
            else
                return $routeName;
        }
    }
}

if (! function_exists('get_lang_route'))
{
    /**
     * Return route, given current url, depending of language
     *
     * @param   string  $lang
     * @return  string
     */
    function get_lang_route($lang)
    {
        // if doesn't has any route with the current url, Request::route() will be null
        if(Request::route() === null)
            return null;

        $route              = \Illuminate\Support\Facades\Route::getCurrentRoute();
        $parameters         = $route->parameters();
        $routeName          = $route->getName();
        $routeWithoutLang   = substr($routeName, 0, strlen($routeName) - 2);

        // If exist route without lang sum new lang
        if(\Illuminate\Support\Facades\Route::has($routeWithoutLang . $lang))
        {
            return nt_route($routeWithoutLang . $lang, $parameters);
        }
        else
        {
            // Maybe can to be a route without lang that has other route with lang
            if(\Illuminate\Support\Facades\Route::has($routeName . '-' . $lang))
                return nt_route($routeName . '-' . $lang, $parameters);
            elseif(\Illuminate\Support\Facades\Route::has($routeName))
                return nt_route($routeName, $parameters);
            else
                return null;
        }
    }
}

if (! function_exists('active_route'))
{
    /**
     * Get user country from session.
     * @param   string      $routeNames         names of routes to check
     * @param   string      $class              class to return if route is active
     * @param   bool        $firstOccurrence    active to find first occurrence of route, this method is valid to active menu on subsections
     * @return  boolean
     */
    function active_route($routeNames, $class = 'active', $firstOccurrence = false)
    {
        // if doesn't has any route with the current url, Request::route() will be null
        if(Request::route() === null) return null;

        if(! is_array($routeNames)) $routeNames = [$routeNames];

        $found = false; // found occurrence

        if($firstOccurrence)
        {
            foreach ($routeNames as $routeName)
            {
                if(strpos(Request::url(), route($routeName)) !== 0)
                {
                    $found = true;
                    break;
                }
            }
        }
        else
        {
            // check that route exist
            if(Request::route() === null)
                return false;

            foreach ($routeNames as $routeName)
            {
                if(Request::route()->getName() === $routeName)
                {
                    $found = true;
                    break;
                }
            }
        }

        if($found) return $class;
    }
}