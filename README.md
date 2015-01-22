LiipSearchBundle
================

This bundle provides a wrapper around search engines and a controller with
twig templates to render search forms and results.

Introduction
------------

This search bundle simplifies adding search to your site.

Provided for you are:

* A controller to render a search box and the search page with twig templates
* A service to query google site search
* A service which provides paging for the search results

### Built-in Search Engines Support

For now, only [Google site search](http://www.google.com/sitesearch/) is 
supported out of the box. The implementation uses the Google REST API.

Contributions for other services are welcome.

Installation
------------

Install the bundle with `composer require liip/search-bundle`.

Include the bundle in app/Kernel.php.

Usage
-----

You can display the default search box by rendering the showSearchBoxAction:

``` jinja
{{ render(controller('liip_search_default_controller:showSearchBoxAction', {'field_id':'query', 'query':'last_query'})) }}
```

The parameters you must pass are:

* field_id - The ID of the html text field for the search. This parameter allows you to have more than one search box in a single page
* query - [optional] Allows you to specify the last searched term with which the search input field will be populated

Create a route for the search action. The easiest is to just use the provided routing.yml from your main project routing.yml

    liip_search:
        resource: "@LiipSearchBundle/Resources/config/routing.yml"

It defaults to the URL `/search`. If you want a different route, configure your
own route with the `liip_search.controller.search:searchAction`.

### Customizing Templating

The templates provided by this bundle base on the 
LiipSearchBundle::layout.html.twig template. To integrate with the rest of your
site, override the layout.html.twig template in app/Resources and provide an empty
``liip_search_content`` block.

Of course you can also override any of the templates.
See http://symfony.com/doc/master/book/templating.html#overriding-bundle-templates

Configuration
-------------
These parameters can be configured in your config.yml:

``search_client``

**string**, default value: null

If you configure the `google` section, you do not need this field.
Otherwise, you need to set this to a service implementing 
`Liip\SearchBundle\SearchInterface`.

``search_route``

**string**, default value: liip_search

This is the name of the route that will handle submitted search requests

``restrict_language``

**boolean**, default value: false
  
Change this to true if you want to ask the search service to restrict to 
results that it thinks are in the language of the request.

``results_per_page``

**integer**, default value: 10

How many search results to display per page.

### Pager

To tweak the pager, a couple of options can be configured in the ``pager`` section.

The paging does not list all pages to avoid overly long lists when there are 
many search results. The default configuration leads to:

```
Previous page  1 2 ... 5 6 *7* 8 9 ... 17 18  Next page
               ^            ^              ^
         Head Items   Adjoining Items    Tail Items
```

``max_head_items``

**integer**, default value: 2

How many page links to always show at the beginning of the search results.
For example, with a value of 2, this always shows page 1 and 2 if there are that many pages in the result.
Set to 0 to not show any first page.

``max_tail_items``

**integer**, default value: 2

How many page links to always show at the end of the search results.
For example, with a value of 2, this always shows page n-1 and n, where n is the last page of results
Set to 0 to not show any last pages.

``max_adjoining_items``

**integer**, default value: 2

How many page links to always show before and after the current page.
For example, with a value of 2, on page 6 of the results, 2 would show 
``... 4 5 *6* 7 8 ...``

### Google Search Engine Integration

Configuring any of these options enables the google search engine service.

``api_key``

**string**, required

Your [Google API key](https://code.google.com/apis/console)

``search_key``

**string**, required

The key identifying your [Google Search Engine](https://www.google.com/cse/all)

``api_url``

**string**, default value: https://www.googleapis.com/customsearch/v1

The Google Search API URL for REST calls
   
``restrict_to_site``

**string**, default value: null

If left empty, all sites configured for the google search engines are searched.
Set to a a domain to limit to that domain.

TODO
----

* Use PagerFanta instead of custom pager
* Use guzzle to talk to google REST API
* Add support for refinements (more like this) with info in search result array 
  that can be passed to SearchInterface::refineSearch
* Adapters for other search systems.
* Expose more of the google search parameters
* Exctract google REST API client to a library or find an existing client implementation.
