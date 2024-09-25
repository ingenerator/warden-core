Warden will be a framework-independent framework for user authentication, authentication
and management. It provides common base entities, interactors and UI across a set of packages
that can be assembled as required.

**Warden is under heavy development and not recommended for production use outwith inGenerator.**

# Installing warden-core

This isn't in packagist yet : you'll need to add our package repository to your composer.json:

```json
{
  "repositories": [
    {"type": "composer", "url": "https://php-packages.ingenerator.com"}
  ]
}
```

`$> composer require ingenerator/warden-core`

# Validation

A default validation interface is included, based on symfony validator.

## Using annotation based mapping

The warden-core package defines validation mapping by default with annotations on the
various entity and request objects. To use these, you need to configure annotation mapping.

To create a validator with annotation support, require the `doctrine/annotations` composer
package.

Then create a factory method like:

```php
use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Mapping\Cache\CacheInterface;

class ValidatorFactory
{
  /**
   * @return \Symfony\Component\Validator\Validator\ValidatorInterface
   */
  public static function buildSymfonyValidator(CacheInterface $cache) 
  {
     // The Doctrine annotation loader does not by default autoload because some PSR-0 autoloaders are badly behaved
     // and emit warnings/output/errors when a class can't be found. If yours doesn't, it's safe to just use 
     // class_exists as a global autoloader.
     AnnotationRegistry::registerLoader(function ($class) { return class_exists($class); });
     $builder = Validation::createValidatorBuilder();
     $builder->enableAnnotationMapping();
     
     if ($cache) {
       $builder->setMetadataCache($cache);
     }
     
     return $builder->getValidator();
  }
  
  public static function buildWardenValidator(ValidatorInterface $sf_validator)
  {
    return new SymfonyValidator($sf_validator);
  }
}
```

## Using alternate mapping

If you want to use an alternate validation mapping method (e.g. yaml files / xml files) you will
need to define the appropriate mappings based on the constraints specified in the warden class
annotations, and populate your validation builder appropriately.

# Contributing

Contributions are welcome but please contact us before you start work on anything to check your
plans line up with our thinking and future roadmap. 

# Contributors

This package has been sponsored by [inGenerator Ltd](http://www.ingenerator.com)

* Andrew Coulton [acoulton](https://github.com/acoulton) - Lead developer

# Licence

Licensed under the [BSD-3-Clause Licence](LICENSE)
