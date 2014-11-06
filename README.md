# The Doc Gear
--------------------------------------------------------------------------------
[![Build Status](https://travis-ci.org/phpgearbox/doc.svg?branch=master)](https://travis-ci.org/phpgearbox/doc)
[![Latest Stable Version](https://poser.pugx.org/gears/doc/v/stable.svg)](https://packagist.org/packages/gears/doc)
[![Total Downloads](https://poser.pugx.org/gears/doc/downloads.svg)](https://packagist.org/packages/gears/doc)
[![License](https://poser.pugx.org/gears/doc/license.svg)](https://packagist.org/packages/gears/doc)

**API Documentation Generator using Markdown**

Think [Natural Docs](http://www.naturaldocs.org/) but using 100% markdown.

This project is designed to document any language that supports
the doc block comments syntax. This is what a doc block looks like:

```
/**
 * I am a doc block
 */
```

## How to Install
--------------------------------------------------------------------------------
Installation via composer is easy:

	composer require gears/doc:* --dev

Or you may wish to install the command globally on your system:

    composer global require gears/doc:*

## Running the Command gearsdoc
--------------------------------------------------------------------------------
Once installed you just need to run the command like so:

    /location/to/gearsdoc \
        --input="/the/path/to/your/source/code" \
        --output="/the/path/to/where/you/want/the/generated/docs/to/go"

There are many more options, either just use the ```--help```
option while at the terminal. Or have a look at:
[Method: configure](Console/Commands/Generate.php)

## Writing Doc Blocks for gearsdoc
--------------------------------------------------------------------------------
At a basic level to write a doc block that _gearsdoc_ can parse and understand
all you need to do is use the [Markdown](http://en.wikipedia.org/wiki/Markdown)
syntax, inside the doc block.

### Gotchas
--------------------------------------------------------------------------------
Make sure your doc blocks are spaced correctly,
for example the following is a bad doc block:

```
/**
 *foo
 */
```

Where as this is one is correct:

```
/**
 * foo
 */
```

**Apart from that there are no other hard requirements.**

However to make effective good looking documenation 
with _gearsdoc_ there are some things you need know:

### Titles:
--------------------------------------------------------------------------------
The first ```<h1>``` element in a doc block is considered the title for that
doc block. Obviously if the doc block does not contain a ```<h1>``` element
then we don't set the title attribute.

Here is a doc block that has a title:

```
/**
 * I AM THE DOC BLOCK TITLE
 * ========================
 * this is just normal text
 */
```

And here is what it looks like:

<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title">
			I AM THE DOC BLOCK TITLE
		</h3>
	</div>
	<div class="panel-body">
		<p>this is just normal text</p>
	</div>
</div>

### Contexts:
--------------------------------------------------------------------------------
A context is simply a CSS class that we apply to each bootstrap panel that
gets generated. The context is determined by the **title** thus if there is
no title there is no context.

Out of the box _gearsdoc_ will set some contexts for you. If you wish to add
your own contexts see: [Property: blockContexts](Generator.php).

Here is a doc block with the _Class_ context:

```
/**
 * Class: Baz
 * ==========
 */
```

And the resulting bootstrap panel:

<div class="panel panel-default panel-primary">
	<div class="panel-heading">
		<h3 class="panel-title">
			Class: Baz
		</h3>
	</div>
	<div class="panel-body">
	</div>
</div>

### Signatures:
--------------------------------------------------------------------------------
The very next line after a doc block is what we call the signature.
It is usally the code that defines a class, function, method or property.
But it can be anything at all. If the line is blank then that doc block will
not have a sigature associated with it.

Here is a doc block with signature:

```
/**
 * I AM THE DOC BLOCK TITLE
 * ========================
 * this is just normal text
 */
$foo = 'bar';
```

And here is what it looks like:

<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title">
			I AM THE DOC BLOCK TITLE
		</h3>
	</div>
	<div class="panel-body">
		<pre><code class="php">$foo = 'bar';</code></pre>
		<p>this is just normal text</p>
	</div>
</div>

### Internal Links:
--------------------------------------------------------------------------------
I am really very happy with this feature. Being able to link to various parts
of code in your doc blocks is invaluable. For example being able to link to the
exact line of code that consumes a class property... priceless :)

There are basically 3 types of internal links:

  - A link that references a doc block title.
  - A link that references a doc block signature.
  - A link that references a particular line of code.

Lets have some examples:

```
/**
 * [Method: Foo](#)
 * [private function Foo()](#)
 * [$this->foo = 'bar';](#)
 */
```

The pound (```#```) denotes the file that we will find the reference in.
If the reference is in the same file as the link then just leave the pound
as is.

> NOTE: There are a few working example in this page, see if you can find them.

--------------------------------------------------------------------------------
Developed by Brad Jones - brad@bjc.id.au