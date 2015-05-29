## Twig integration for Thelia

This module use [Twig](http://twig.sensiolabs.org) template engine as parser for Thelia and replace Smarty.

**This module is not stable and is still in development. See the RoadMap if you want to know which features are missing**

### Installation

You can only install this module with composer :

```
$ composer require thelia/twig-module:dev-master
```

### Activation

It is required to enable this module with the cli tools and then disable TheliaSmarty module :

```
$ php Thelia module:refresh
$ php Thelia module:activate TheliaTwig
$ php Thelia module:deactivate TheliaSmarty
```

### Syntax

#### Loop

loop feature is a Twig tag, you have to use it like a block. All loop's parameters use [literals](http://twig.sensiolabs.org/doc/templates.html#literals) syntax and are the same as the acutal parameters.
The tag start with ```loop``` and finish with ```endloop```

**example** :

```
<ul>
{% loop {type:"category", name:"cat", limit:"2"} %}
    <li>{{ ID }} : {{ TITLE }}
        <ul>
    {% loop {type:"product", name:"prod", category: ID} %}
        <li>Title : {{ TITLE }} </li>
    {% endloop %}
        </ul>
    </li>
{% endloop %}
</ul>
```

#### Conditional loop

Conditional loops are implemented. As for Smarty a ```ifloop``` can wrap a ```loop``` and can be used after the related loop.
```elseloop``` must be used after the related ```loop```

```
{% ifloop {rel:"cat"} %}
    <p>Before categories</p>
    <ul>
    {% loop {type:"category", name:"cat", limit:"2"} %}
        <li>{{ ID }} : {{ TITLE }}
            <ul>
        {% loop {type:"product", name:"prod", category: ID} %}
            <li>Title : {{ TITLE }} </li>
        {% endloop %}
            </ul>
        </li>
    {% endloop %}
    </ul>
<p>After categories</p>
{% endifloop %}
{% elseloop {rel:"cat"} %}
    <p>there is no category</p>
{% endelseloop %}
```

#### Paginated loop

Paginated loop works exactly like paginated loop for Smarty, just the syntax change. See the official documentation for
all parameters : http://doc.thelia.net/en/documentation/loop/index.html#page-loop

Syntax example :

```
<p>Products Loop</p>
<ul>
{% loop {type:"product", name:"pagination", limit:"5", page:"3"} %}
    <li>{{ TITLE }}</li>
{% endloop %}
</ul>

<p>Pagination</p>
<ul>
{% pageloop {rel: "pagination"} %}
    <li>{{ PAGE }} {% if CURRENT == PAGE %} current {% endif %} / last : {{ END }}</li>
{% endpageloop %}
</ul>
```

### Url management

#### url

url is a function. It generates an absolute url for a given path or file.

```
url($path, $parameters = array(), $current = false, $file = null, $noAmp = false, $target = null)
```

parameters :

Parameters | Description | example
--- | --- | ---
path | The value of the path parameter is the route path you want to get as an URL | ```url("/product/")```
file | The value of the file parameter is the absolute path (from /web) of a real file, that will be served by your web server, and not processed by Thelia | ```url(null,[], false, "file.pdf")```
parameters | paremeters added to the query string | ```url("/product" ,{arg1:"val1", arg2:"val2"})```
current | generate absolute URL grom the current URL | ```url(null ,[], true)```
noAmp | escape all ```&``` as ```&amp;``` that may be present in the generated URL. | ```url("/product" ,[], false, null, true)```
target | Add an anchor to the URL | ```url("/product" ,[], false, null, false, "cart")```

Complete example :

```
<p>
    <a href="{{ url("/product/", {id: 2, arg1: "val1"}) }}">my link</a>
</p>
```

generated link : http://domain.tld?id=2&arg1=val1

#### url_token

same as ```url``` function. This function just add a token paremeter in the url to prevent CSRF security issue.

**example** :

```
<a href="{{ url_token("/product/", {id: 2, arg1: "val1"}) }}">my tokenized link</a>
```

generated link : http://domain.tld?id=2&arg1=val1&_token=UniqueToken

#### current_url

return the current url

```
current_url()
```

**example** :

```
<a href="{{ current_url() }}">current link</a>
```

#### previous_url

return the previous url saved in session

```
previous_url
```

**example** :

```
<a href="{{ previous_url() }}">previous link</a>
```

#### index_url

return the homepage url

```
index_url()
```

**example** :

```
<a href="{{ index_url() }}">index link</a>
```

### How to add your own extension

The tag ```thelia.parser.add_extension``` allows you to add your own twig extension.

**example** :

```
<service id="thelia.parser.loop_extension" class="TheliaTwig\Template\Extension\Loop">
    <argument type="service" id="thelia.parser.loop_handler" />
    <tag name="thelia.parser.add_extension" />
</service>
```

### Translation

#### default_domain

default_domain is a tag for defining the default translation domain. If defined you don't need to specify it when you want to translation a string in the current template.

**Usage** :

```
{% default_domain "fo.default" %}
```

#### default_locale

tag for defining a locale and don't use the locale stored in session.

**Usage** :

```
{% default_locale "fr_FR" %}
```

#### intl

function for string translation

```
intl($id, $parameters = [], $domain = null, $locale = null)
```

parameters :

Parameters | Description | Example
--- | --- | ---
id | the string to translate | ```intl('secure payment')```
parameters | variable use if a placeholder is used in the string to translate | ```intl('secure payment %payment', {'%payment' => 'atos'})``` => secure payment atos
domain | message domain, will override domain defined with tag ```default_domain``` | ```{{ intl('secure payment', [], 'front') }}```
locale | specific locale to use for this translation. Will override locale defined with tag ```default_locale``` and the locale defined in session | ```{{ intl('Secure payment', [], null, 'en_US') }}```

**Complete example** :

```
{% default_domain "fo.default" %}
{% default_locale "fr_FR" %}
<p>
    translation : {{ intl('Secure payment', [], null, 'en_US') }} <br>
    translation 2 : {{ intl('Secure payment') }} <br>
    translation 3 : {{ intl('Sorry, an error occurred: %error', {'%error': 'foo'}, 'front') }} <br>
</p>
```

### Security

#### checkAuth

tag checking if a user has access granted.

example : 

```
{% auth {role: "CUSTOMER", login_tpl:"login"} %}
```

Parameters : 

Parameters | Description
--- | --- 
role | In Thelia 2, a user can only have one of these two roles: ADMIN and/or CUSTOMER
resource | if a user can access to a specific resource. See : http://doc.thelia.net/en/documentation/templates/security.html#resource
module | Name of the module(s) which the user must have access
access | access mode : CREATE, VIEW, UPDATE, DELETE
login_tpl |This argument is the name of the view name (the login page is "login"). If the user is not granted and this argument is defined, it redirects to this view.

### Roadmap

* ~~loop~~
* ~~conditional loop~~
* ~~paginated loop~~
* Assetic integration
* ~~I18n support~~
* Form support
* ~~URL management~~
    * ~~url function~~
    * ~~token_url function~~
    * ~~navigate function~~
    * ~~set_previous_url function~~
* Hook support
* date and money format
* Firewall support
* DataAccessFunction
* Security
    * checkAuth
    * checkCartNotEmpty
    * checkValidDelivery
