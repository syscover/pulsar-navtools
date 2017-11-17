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
        if(! config('pulsar-navtools.url_type'))
            return $next($request);

        // get parameter navtools from URL
        $parameters     = $request->route()->parameters();
        $lang           = null; // language variable
        $country        = null; // country variable

        // Get resource from countries, you can need load countries from other config file
        if(config('pulsar-navtools.countries_resource') === null)
            $countries = collect(config('pulsar-navtools.countries'));
        else
            $countries = collect(config(config('pulsar-navtools.countries_resource')))->flatten();

        // We make sure to convert the entire array to lowercase
        $countries->transform(function($item, $key){
            return strtolower($item);
        });

        // Get resource from langs, you can need load langs from other config file
        if(config('pulsar-navtools.langs_resource') === null)
            $langs = collect(config('pulsar-navtools.langs'));
        else
            $langs = collect(config(config('pulsar-navtools.langs_resource')))->flatten();

        // We make sure to convert the entire array to lowercase
        $langs->transform(function($item, $key){
            return strtolower($item);
        });


        //********************************************************
        // Instance lang or country variable by parameter
        //********************************************************
        // get lang variable from parameters
        if(
            (config('pulsar-navtools.url_type') === 'lang-country' || config('pulsar-navtools.url_type') === 'lang') &&
            isset($parameters['lang']) && $langs->contains($parameters['lang'])
        )
        {
            $lang = $parameters['lang'];
        }

        // get country variable from parameters
        if(
            (config('pulsar-navtools.url_type') === 'lang-country' || config('pulsar-navtools.url_type') === 'country') &&
            isset($parameters['country']) && $countries->contains($parameters['country'])
        )
        {
            $country = $parameters['country'];
        }


        //********************************************************
        // Instance lang or country variable by url
        //********************************************************
        if(
            config('pulsar-navtools.url_type') === 'lang-country' &&
            $request->segment(1) !== null &&
            ($lang === null || $country === null)
        )
        {
            // get values implements in url
            $urlSegment = explode('-', $request->segment(1));

            if(count($urlSegment) !== 2)
                throw new ParameterFormatException('Not found lang and country parameter, you need implement lang and country parameters in you URL or change NAVTOOLS_URL_TYPE parameter');

            if($lang === null && $langs->contains($urlSegment[0]))
                $lang = $urlSegment[0];

            if($country === null && $countries->contains($urlSegment[1]))
                $country = $urlSegment[1];
        }
        elseif(
            $request->segment(1) !== null && (
                (config('pulsar-navtools.url_type') === 'lang' && $lang === null) ||
                (config('pulsar-navtools.url_type') === 'country' && $country === null)
            )
        )
        {
            if(config('pulsar-navtools.url_type') === 'lang' && $langs->contains($request->segment(1)))
            {
                $lang = $request->segment(1);
            }
            elseif(config('pulsar-navtools.url_type') === 'country' && $countries->contains($request->segment(1)))
            {
                $country = $request->segment(1);
            }
        }


        //********************************************************
        // Instance lang or country variable by cookies
        //********************************************************
        if(
            $lang === null &&
            (config('pulsar-navtools.url_type') === 'lang-country' || config('pulsar-navtools.url_type') ==='lang') &&
            $request->cookie('pulsar.user_lang') !== null &&
            $langs->contains($request->cookie('pulsar.user_lang'))
        )
        {
            $lang = $request->cookie('pulsar.user_lang');
        }

        if(
            $country === null &&
            (config('pulsar-navtools.url_type') === 'lang-country' || config('pulsar-navtools.url_type') ==='country') &&
            $request->cookie('pulsar.user_country') != null &&
            $countries->contains($request->cookie('pulsar.user_country'))
        )
        {
            $country = $request->cookie('pulsar.user_country');
        }


        //********************************************************
        // Instance lang or country variable by browser language
        //********************************************************
        if(
            $lang === null
        )
        {
            // Routine to know language and get header HTTP_ACCEPT_LANGUAGE if there is this variable.
            // the bots like google don't have this variable, in this case we have to complete language data.
            // We find the language in all cases, then to know the country.
            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
            {
                $browserLang = \Syscover\Navtools\Services\NavtoolsService::preferentialLanguage($langs->toArray());

                // instantiate browser language
                if($langs->contains($browserLang))
                    $lang = $browserLang;
            }
        }

        // after know browser language, get country
        if(
            $country === null &&
            $lang !== null &&
            (config('pulsar-navtools.url_type') === 'lang-country' || config('pulsar-navtools.url_type') ==='country')
        )
        {
            // if is set locale, we get default country from locale
            if(isset(config('pulsar-navtools.country_lang')[$lang]))
            {
                // in the case we take the country as default language
                $country = config('pulsar-navtools.country_lang')[$lang];
            }
        }


        // Check exceptions
        if($lang !== null && ! $langs->contains($lang))
        {
            Cookie::queue(Cookie::forget('pulsar.user_lang'));
            Cookie::queue(Cookie::forget('pulsar.user_country'));

            // puede haber una url que no tenga idioma dentro dle grupo
            if(env('APP_DEBUG'))
            {
                throw new ParameterFormatException('Variable lang has value \'' . $lang . '\', is not valid value, check NAVTOOLS_LANGS in your environment, will be a 404 error in production. You may be accessing a url without implementing the language');
            }
            else
            {
                abort(404);
            }
        }

        if($country !== null && ! $countries->contains($country))
        {
            Cookie::queue(Cookie::forget('pulsar.user_lang'));
            Cookie::queue(Cookie::forget('pulsar.user_country'));

            if(env('APP_DEBUG'))
                throw new ParameterFormatException('Variable country is not valid value, check NAVTOOLS_COUNTRIES in your environment, will be a 404 error in production');
            else
                abort(404);
        }

        //****************
        // Set sessions
        //****************
        if (config('pulsar-navtools.url_type') === 'lang-country')
        {
            session(['pulsar.user_lang'     => $lang]);
            session(['pulsar.user_country'  => $country]);
        }
        elseif(config('pulsar-navtools.url_type') === 'lang')
        {
            session(['pulsar.user_lang' => $lang]);
        }
        elseif(config('pulsar-navtools.url_type') === 'country')
        {
            session(['pulsar.user_country' => $country]);
        }

        //**********************************
        // Set application language
        //**********************************
        if(
            config('pulsar-navtools.url_type') === 'lang-country' ||
            config('pulsar-navtools.url_type') === 'lang'
        )
        {
            App::setLocale(user_lang());
        }

        return $next($request);
    }
}