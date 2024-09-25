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

A default validation interface is included, along with an implementation using symfony validator and a factory to create a validator.

## Using attribute based mapping

The warden-core package defines validation mapping by default with attributes on the 
various entity and request objects.

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
