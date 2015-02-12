LiipSearchBundle
================

[![Latest Stable Version](https://poser.pugx.org/liip/search-bundle/v/stable.svg)](https://packagist.org/packages/liip/search-bundle)
[![Latest Unstable Version](https://poser.pugx.org/liip/search-bundle/v/unstable.svg)](https://packagist.org/packages/liip/search-bundle)
[![Total Downloads](https://poser.pugx.org/liip/search-bundle/d/total.png)](https://packagist.org/packages/liip/search-bundle)

This bundle provides a uniform interface for full text search with various 
search engines and a controller with twig templates to render search forms and 
results.

Note: You are looking at Version 2 of this bundle, which saw large changes
compared to [Version 1](https://github.com/liip/LiipSearchBundle/tree/1.0).

Introduction
------------

This search bundle simplifies adding search to your site.

Provided for you are:

* A controller to render a search box and the search page with twig templates
* A service to query google site search
* A service which provides paging for the search results

### Built-in Search Engines Support

For now, [Google site search](http://www.google.com/sitesearch/) is supported 
out of the box. There is one implementation using the
[Google REST API](https://developers.google.com/custom-search/json-api/v1/overview)
and one implementation using the [Custom Search Element](https://developers.google.com/custom-search/docs/element)
feature that loads the search with only javascript in the frontend.

Contributions for other services are welcome.

Installation
------------

Install the bundle with `composer require liip/search-bundle`.

Include the bundle in app/Kernel.php.

Add your preferred search engine in app/config/config.yml:

```yaml
liip_search:
    clients:
        google_rest:
            api_key: '%google.api_key%'
            search_key: '%google.search_key%'
```

Or if you use the javascript Google custom search engine:

```yaml
liip_search:
    clients:
        google_cse:
            cse_id: '%google.search_key%'
```

Usage
-----

You can display a search box anywhere on the page with the liip_search_box twig function:

``` jinja
{{ liip_search_box(query, 'query-field-id', 'css-class') }}
```

You can customize the search box with these parameters:

* query - default query to display
* fieldId - HTML id to use for the search input field. Use different ids when 
  having more than one search box on your page, e.g. in the header and in content.
* cssClass - A css class to apply to the whole search box `<form>`.

Create a route for the search action. The easiest is to just use the provided 
routing.xml from your main project routing.xml:

```
    liip_search:
        resource: "@LiipSearchBundle/Resources/config/routing.xml"
```

It defaults to the URL `/search`. If you want a different route, use the `prefix` 
option when including the route or configure your own route using 
`%liip_search.controller.search_action%` as default value for `_controller`.

### Customizing Templating

The search result templates provided by this bundle extend the
`LiipSearchBundle::layout.html.twig` template. To integrate with the rest of your
site, you have two options:

* Create `app/Resources/LiipSearchBundle/views/layout.html.twig` and make it
  extend your base layout, putting a ``liip_search_content`` block where you
  want the search results.
* Create `app/Resources/LiipSearchBundle/views/Search/search.html.twig` and
  build your own templating structure - you should be able to `use` the
  `search_results.twig.html` template to get the `liip_search_content` block.

Of course you can also override any of the templates to customize what they
should do. See
http://symfony.com/doc/master/book/templating.html#overriding-bundle-templates

Configuration Reference
-----------------------

This is the full reference of what you can configure under the ``liip_search`` key:

``search_factory``

**string**, default value: null

Specify a custom service that implements the `Liip\SearchBundle\SearchFactoryInterface`. 
This service will be used by the controller to create `Pagerfanta` instances to handle
the search.

If you configure one of the search engine services, you do not need to set this 
field.

``search_route``

**string**, default value: liip_search

The name of the route that will handle submitted search requests.

``restrict_language``

**boolean**, default value: false
  
Change this to true if you want to ask the search service to restrict the
results to the language of the request.

### Google Search REST API Integration

Configuring any of these options enables the google search engine service. They 
are located under ``clients.google_rest``.

``api_key``

**string**, required

Your [Google API key](https://code.google.com/apis/console)

``search_key``

**string|array**, required

The key identifying your [Google Search Engine](https://www.google.com/cse).
May be a list of keys indexed by locale to use different engines per locale.
If you control locales through separate search engines, you do not need to set
`restrict_language` to true unless you want your custom search engines to 
receive a language restriction additionally.

``api_url``

**string**, default value: https://www.googleapis.com/customsearch/v1

The Google Search API URL for REST calls
   
``restrict_to_site``

**string**, default value: null

If left empty, all sites configured for the google search engines are searched.
Set to a a domain to limit to that domain.

### Google Custom Search Engine Integration

Configuring this section activates a different controller that renders the
Javascript fragment to enable the CSE search. This configuration is located
under ``clients.google_cse``.

``cse_id``

**string|array**, required

The key identifying your [Google Custom Search Engine](https://www.google.com/cse).
May be a list of keys indexed by locale to use different engines per locale.
CSE does *not* support the `restrict_language`, so different search engines per
language are your only option to restrict the language of search results.

Troubleshooting
---------------

### Google Custom Search Engine

If you get `SearchException` saying "Empty response received from Google Search
Engine API", try copying the URL that is output into a browser. You should get
JSON response, but likely it will haves an error status.

If you get a status 500 with an empty message, chances are that you need to
renew the search engine in the [Google admin panel](https://www.google.com/cse/all).

Adding your own Search Service
------------------------------

Implement the `Liip\SearchBundle\SearchInterface` and configure it as a service.
Then set `liip_search.search_client` to that service name.

TODO
----

* Use guzzle to talk to google REST API
* Add support for refinements (more like this) with info in search result array 
  that can be passed to SearchInterface::refineSearch
* Expose more of the google search parameters
