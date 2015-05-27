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

Exemple :

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

Syntax exemple : 

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

### url

url is a function. It generates an absolute url for a given path or file.

```
url($path, $parameters = array(), $current = false, $file = null, $noAmp = false, $target = null)
```

parameters : 

Parameters | Description | Exemple
--- | --- | ---
path | The value of the path parameter is the route path you want to get as an URL | ```url("/product/")```
file | The value of the file parameter is the absolute path (from /web) of a real file, that will be served by your web server, and not processed by Thelia | ```url(null,[], false, "file.pdf")```
parameters | paremeters added to the query string | ```url("/product" ,{arg1:"val1", arg2:"val2"})```
current | generate absolute URL grom the current URL | ```url(null ,[], true)```
noAmp | escape all ```&``` as ```&amp;``` that may be present in the generated URL. | ```url("/product" ,[], false, null, true)```
target | Add an anchor to the URL | ```url("/product" ,[], false, null, false, "cart")```

Complete exemple : 

```
<p>
    <a href="{{ url("/product/", {id: 2, arg1: "val1"}) }}">my link</a>
</p>
```

generated link : http://domain.tld?id=2&arg1=val1

### How to add your own extension

The tag ```thelia.parser.add_extension``` allows you to add your own twig extension.

Exemple :

```
<service id="thelia.parser.loop_extension" class="TheliaTwig\Template\Extension\Loop">
    <argument type="service" id="thelia.parser.loop_handler" />
    <tag name="thelia.parser.add_extension" />
</service>
```

### Roadmap

* ~~loop~~
* ~~conditional loop~~
* ~~paginated loop~~
* Assetic integration
* I18n support
* Form support
* URL management
    * ~~url function~~
    * token_url function
    * navigate function
    * set_previous_url function
* Hook support
* date and money format
* Firewall support
* DataAccessFunction
