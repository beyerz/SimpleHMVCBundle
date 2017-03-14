# SimpleHMVCBundle
Simple HMVC Bundle for Symfony2

The HMVC design pattern, came about when trying resolve the problem of embedding widgets into pages.
HMVC allows us to cleanly separate Controller, Model and View logic's, enabling us to create easier to work with and clearly encompassed code.

Symfony2 does provide us with tools that allow us to "load" routes within our controllers and our views, but the process, behind the scenes,
is more complex than I like.

For info on embedding using the symfony provided options see:
http://symfony.com/doc/2.8/book/templating.html#templating-embedding-controller

HMVC (Hierarchical model-view-controller) symfony bundle enables you to "Widgetize" or as used in this bundle "Elementize" your controllers.
Architecturally this allows you to recursively include the same element one or many times across multiple pages without having to rebuild or restate the model logic.

# Installation

### Composer (Recommended)

    composer require beyerz/simple-hmvc-bundle

### Application Kernel

Add SimpleHMVC to the `registerBundles()` method of your application kernel:

```php
public function registerBundles()
{
    return array(
        new Beyerz\SimpleHMVCBundle\BeyerzSimpleHMVCBundle(),
    );
}
```

# Usage

### Creating Pages

### Creating Elements

## How it works

## Some useful articles
* http://www.javaworld.com/article/2076128/design-patterns/hmvc--the-layered-pattern-for-developing-strong-client-tiers.html

