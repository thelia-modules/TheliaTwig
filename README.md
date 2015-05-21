## Twig integration for Thelia

This module use [Twig](http://twig.sensiolabs.org) template engine as parser for Thelia and replace Smarty.

**This module is not stable and is still in development. See the RoadMap if you want to know which features are missing**

### Installation

You can only install this module with composer : 

```
$ composer require thelia/twig-module:dev-master
```

### Activation

It is required to enabled this module with the cli tools and the disable TheliaSmarty module : 

```
$ php Thelia module:refresh
$ php Thelia module:activate TheliaTwig
$ php Thelia module:deactivate TheliaSmarty
```

### Syntax

#### Loop

loop feature is a Twig tag, you have to use it like a block. All loop's parameters use [literals](http://twig.sensiolabs.org/doc/templates.html#literals) syntax and are the same as the acutal parameters.
The tag starts with ```loop``` and finished with ```endloop```

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
* paginated loop
* Assetic integration
* I18n support
* Form support
* URL management
* Hook support
* date and money format
* Firewall support
* DataAccessFunction
