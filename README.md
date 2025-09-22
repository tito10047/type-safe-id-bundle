# Type-Safe Identifiers with Symfony and Doctrine

## Introduction
When working with Symfony and Doctrine, using UUIDs as entity identifiers is a common approach. Traditionally, IDs are stored as simple integers or as raw Uuid objects.
However, this can lead to type confusion, especially when working with Symfony Messenger or repository methods. A more robust and type-safe approach is to use dedicated
ID classes.
---

This package override bin/console make:entity and add Type safe identifiers. This is implementation
of [this article](https://sensiolabs.com/blog/2025/type-safe-identifiers-symfony-doctrine)

Its generates some like this

```bin/console make:entity Foo --with-ulid```

```php
#[ORM\Entity(repositoryClass: FooRepository::class)]
class Foo
{
    #[ORM\Id]
    #[ORM\Column(type: FooIdType::class, unique: true)]
    private FooId $id;

	public function __construct()
    {
        $this->id = new FooId();
    }

    public function getId(): FooId
    {
        return $this->id;
    }
}
```

```php
class FooRepository extends ServiceEntityRepository
{
    //...
    public function get(FooId $id): ?Foo    {
        return $this->find($id->toString());
    }
    //...
}
```

### Usage

```php
$foo = new Foo();
$this->em->persist($foo);
$this->em->flush();

$serializedId = $foo->getId()->toString();

$foo = $this->fooRepository->get(
    new FooId($serializedId)
);
```

## Installation

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Applications that use Symfony Flex

Open a command console, enter your project directory and execute:

```console
$ composer require tito10047/type-safe-id-bundle
```

### Applications that don't use Symfony Flex

#### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require tito10047/type-safe-id-bundle
```

#### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    Tito10047\TypeSafeIdBundle\TypeSafeIdBundle::class => ['all' => true],
];
```
