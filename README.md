# Sculpin Pages Bundle

## Setup

Add this bundle in your ```sculpin.json``` file:

```json
{
    // ...
    "require": {
        // ...
        "fab/sculpin-pages-bundle": "@dev"
    }
}
```

and install this bundle running ```sculpin update```.

Now you can register the bundle in ```SculpinKernel``` class available on ```app/SculpinKernel.php``` file:

```php
class SculpinKernel extends \Sculpin\Bundle\SculpinBundle\HttpKernel\AbstractKernel
{
    protected function getAdditionalSculpinBundles()
    {
        return array(
           'Fab\Sculpin\Bundle\PagesBundle\SculpinPagesBundle'
        );
    }
}
```
