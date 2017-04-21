<?php namespace Syscover\Navtools\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Syscover\Navtools\Exceptions\ParameterFormatException;

class Navtools
{
    /**
     * @param   $request
     * @param   Closure $next
     * @return  mixed
     * @throws  ParameterFormatException
     */
    public function handle($request, Closure $next)
    {
        // if is false exit
        if(! config('pulsar.navtools.urlType'))
            return $next($request);

        // get parameter navtools from URL
        $paramenters    = $request->route()->parameters();
        $lang           = null; // language variable
        $country        = null; // country variable


        //********************************************************
        // Instance lang or country variable by parameter
        //********************************************************
        // get lang variable from parameters
        if(
            (config('pulsar.navtools.urlType') === 'lang-country' || config('pulsar.navtools.urlType') === 'lang') &&
            isset($paramenters['lang'])
        )
            $lang = $paramenters['lang'];

        // get country variable from parameters
        if(
            (config('pulsar.navtools.urlType') === 'lang-country' || config('pulsar.navtools.urlType') === 'country') &&
            isset($paramenters['country'])
        )
            $country = $paramenters['country'];


        //********************************************************
        // Instance lang or country variable by url
        //********************************************************
        if(
            config('pulsar.navtools.urlType') === 'lang-country' &&
            $request->segment(1) !== null &&
            ($lang === null || $country === null)
        )
        {
            // get values implements in url
            $urlSegment = explode('-', $request->segment(1));

            if(count($urlSegment) !== 2)
                throw new ParameterFormatException('Not found lang and country parameter, you need implement lang and country parameters in you URL or change NAVTOOLS_URL_TYPE parameter');

            if($lang === null)
                $lang = $urlSegment[0];

            if($country === null)
                $country = $urlSegment[1];
        }
        elseif(
            $request->segment(1) !== null && (
                (config('pulsar.navtools.urlType') === 'lang' && $lang === null) ||
                (config('pulsar.navtools.urlType') === 'country' && $country === null)
            )
        )
        {
            if(config('pulsar.navtools.urlType') === 'lang')
                $lang = $request->segment(1);
            elseif(config('pulsar.navtools.urlType') === 'country')
                $country = $request->segment(1);
        }

        //********************************************************
        // Instance lang or country variable by cookies
        //********************************************************
        if($lang === null && (config('pulsar.navtools.urlType') === 'lang-country' || config('pulsar.navtools.urlType') ==='lang') && $request->cookie('userLang') !== null)
        {
            $lang = $request->cookie('userLang');
        }

        if($country === null && (config('pulsar.navtools.urlType') === 'lang-country' || config('pulsar.navtools.urlType') ==='country') && $request->cookie('userCountry') != null)
        {
            $country = $request->cookie('userCountry');
        }

        //********************************************************
        // Instance lang or country variable by browser language
        //********************************************************
        if($lang === null)
        {
            // Routine to know language and get header HTTP_ACCEPT_LANGUAGE if there is this variable.
            // the bots like google don't have this variable, in this case we have to complete language data.
            // We find the language in all cases, then to know the country.
            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
            {
                $browserLang = \Syscover\NavTools\Services\NavToolsService::preferentialLanguage(config('pulsar.navtools.langs'));

                // instantiate browser language
                if(in_array($browserLang, config('pulsar.navtools.langs')))
                    $lang = $browserLang;
            }
        }

        if($country === null && $lang !== null)
        {
            // if is set locale, we get default country from locale
            if(isset(config('pulsar.navtools.countryLang')[$lang]))
            {
                // in the case we take the country as default language
                $country = config('pulsar.navtools.countryLang')[$lang];
            }
        }

        // Check exceptions
        if($lang !== null && ! in_array($lang, config('pulsar.navtools.langs')))
        {
            Cookie::queue(Cookie::forget('userLang'));
            Cookie::queue(Cookie::forget('userCountry'));

            if(env('APP_DEBUG'))
            {
                throw new ParameterFormatException('Variable lang has value \'' . $lang . '\', is not valid value, check NAVTOOLS_LANGS in your environment, will be a 404 error in production. You may be accessing a url without implementing the language');
            }
            else
            {
                abort(404);
            }
        }

        // Get resource from countries,
        // you can need load countries from other config file
        if(config('pulsar.navtools.resource') === 'env')
        {
            $countries = collect(config('pulsar.navtools.countries'));
        }
        else
        {
            $countries = collect(config(config('pulsar.navtools.resource')))->flatten();
        }

        // We make sure to convert the entire array to lowercase
        $countries->transform(function($item, $key){
            return strtolower($item);
        });

        if($country !== null && ! $countries->contains($country))
        {
            Cookie::queue(Cookie::forget('userLang'));
            Cookie::queue(Cookie::forget('userCountry'));

            if(env('APP_DEBUG'))
                throw new ParameterFormatException('Variable country is not valid value, check NAVTOOLS_COUNTRIES in your environment, will be a 404 error in production');
            else
                abort(404);
        }

        //****************
        // Set sessions
        //****************
        if (config('pulsar.navtools.urlType') === 'lang-country')
        {
            session(['userLang'     => $lang]);
            session(['userCountry'  => $country]);
        }
        elseif(config('pulsar.navtools.urlType') === 'lang')
        {
            session(['userLang' => $lang]);
        }
        elseif(config('pulsar.navtools.urlType') === 'country')
        {
            session(['userCountry' => $country]);
        }

        //**********************************
        // Set application language
        //**********************************
        if(
            config('pulsar.navtools.urlType') === 'lang-country' ||
            config('pulsar.navtools.urlType') === 'lang'
        )
        {
            App::setLocale(user_lang());
        }

        return $next($request);
    }
}