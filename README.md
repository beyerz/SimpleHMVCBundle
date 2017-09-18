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

Pages and Elements can be created by hand or generated using the provided commands.
If you choose to create them manually or need to update manually, please consider the expected file structure

### Creating Pages
Using the command
```bash
php app/console hmvc:page
```
For additional options or help
```bash
php app/console hmvc:page --help
```
This command will generate a controller with one or many actions according to your specification.
For every controller action the following classes will be created, {action}Model, {action}Context, {action}Input
and a view in snake case format.

The creation of a page with results in the following directory structure
The Controller name used here is: BeyerzTestingBundle:GitExample

```
├── Context
│   └── Page
│       └── GitExample
│           ├── FirstExampleContext.php
│           └── SecondExampleContext.php
├── Controller
│   └── Page
│       └── GitExampleController.php
├── Input
│   └── Page
│       └── GitExample
│           ├── FirstExampleInput.php
│           └── SecondExampleInput.php
├── Model
│   └── Page
│       └── GitExample
│           ├── FirstExampleModel.php
│           └── SecondExampleModel.php
├── Resources
│   ├── config
│   │   ├── routing.yml
│   │   └── services.yml
│   └── views
│       └── Page
│           └── GitExample
│               ├── first_example.html.twig
│               └── second_example.html.twig
└── Tests
    └── Controller
        └── Page
            └── GitExampleControllerTest.php
```

You can also create controllers under different directories by specifying the path during the creation.
A simple sub-controller would generate this file structure
The Controller name used here is: BeyerzTestingBundle:GitExample/SubExample

```
├── Context
│   └── Page
│       └── GitExample
│           └── SubExample
│               ├── FirstSubExampleContext.php
│               └── SecondSubExampleContext.php
├── Controller
│   └── Page
│       └── GitExample
│           └── SubExampleController.php
├── Input
│   └── Page
│       └── GitExample
│           └── SubExample
│               ├── FirstSubExampleInput.php
│               └── SecondSubExampleInput.php
├── Model
│   └── Page
│       └── GitExample
│           └── SubExample
│               ├── FirstSubExampleModel.php
│               └── SecondSubExampleModel.php
├── Resources
│   ├── config
│   │   ├── routing.yml
│   │   └── services.yml
│   └── views
│       └── Page
│           └── GitExample
│               └── SubExample
│                   ├── first_sub_example.html.twig
│                   └── second_sub_example.html.twig
└── Tests
    └── Controller
        └── Page
            └── GitExample
                └── SubExampleControllerTest.php
```
### Creating Elements

Elements are very similar to pages, except that no controller is generated for elements.

Using the command
```bash
php app/console hmvc:element
```
For additional options or help
```bash
php app/console hmvc:element --help
```
This command will generate the following classes, {element}Model, {element}Context, {element}Input
and a view in snake case format.

The creation of an element results in the following directory structure
The Element name used here is: BeyerzTestingBundle:GitElement

```
├── Context
│   └── Element
│       └── GitElementContext.php
├── Controller
├── DependencyInjection
│   ├── BeyerzTestingExtension.php
│   └── Configuration.php
├── Input
│   └── Element
│       └── GitElementInput.php
├── Model
│   └── Element
│       └── GitElementModel.php
├── Resources
│   ├── config
│   │   ├── routing.yml
│   │   └── services.yml
│   └── views
│       └── Element
│           └── git_element.html.twig
└── Tests
```

You can also create elements under different directories by specifying the path during the creation.
A simple sub-element would generate this file structure
The element name used here is: BeyerzTestingBundle:GitExample/SubElement

```
├── Context
│   └── Element
│       └── GitExample
│           └── SubElementContext.php
├── Controller
├── DependencyInjection
│   ├── BeyerzTestingExtension.php
│   └── Configuration.php
├── Input
│   └── Element
│       └── GitExample
│           └── SubElementInput.php
├── Model
│   └── Element
│       └── GitExample
│           └── SubElementModel.php
├── Resources
│   ├── config
│   │   ├── routing.yml
│   │   └── services.yml
│   └── views
│       └── Element
│           └── GitExample
│               └── sub_element.html.twig
└── Tests
```
## How it works

## Some useful articles
* http://www.javaworld.com/article/2076128/design-patterns/hmvc--the-layered-pattern-for-developing-strong-client-tiers.html

