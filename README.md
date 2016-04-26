# SimpleHMVCBundle
Simple HMVC Bundle for Symfony2

## Installation

### Composer

    composer require beyerz/simple-hmvc-bundle

### Application Kernel

Add SimpleHMVC to the `registerBundles()` method of your application kernel:

    public function registerBundles()
    {
        return array(
            new Beyerz\SimpleHMVCBundle\BeyerzSimpleHMVCBundle(),
        );
    }

## Usage

### Extending the PageController

### Extending the ElementController
