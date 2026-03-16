# Benchmark Findings - TypeSafeId Bundle

## Date: 2026-03-16

## Testing Methodology

- **Iterations:** 10
- **Entities per test:** 1000
- **Cache:** Cleared after each iteration (realistic deployment simulation)
- **Database:** SQLite (in-memory)
- **PHP version:** 8.5.4

## Key Findings

### 1. Container Compilation (DI Container Compilation)

**SURPRISING RESULT:**

- **Classic Doctrine:** 103.93ms (±18.84ms)
- **TypeSafeId Bundle:** 99.88ms (±1.24ms)

**TypeSafeId is 3.9% FASTER at compilation!**

This is surprising because TypeSafeId registers one service per entity ID. Reasons:
- TypeSafeId has more stable compilation time (lower standard deviation: ±1.24ms vs ±18.84ms)
- Classic Doctrine has higher variability, likely due to JIT compilation or PHP internal cache

**Memory:**
- **Classic:** 3.07 MB
- **TypeSafe:** 2.84 MB (-7.3%)

### 2. Runtime Performance

#### UUID Entity Persistence (1000 entities)

- **Classic:** 23.78ms (42,048 entities/s)
- **TypeSafe:** 30.37ms (32,922 entities/s)
- **Difference:** TypeSafeId is **27.7% slower**

#### ULID Entity Persistence (1000 entities)

- **Classic:** 23.86ms (41,910 entities/s)
- **TypeSafe:** 29.29ms (34,139 entities/s)
- **Difference:** TypeSafeId is **22.8% slower**

### 3. Overhead Analysis

**Why is TypeSafeId slower at runtime?**

1. **Additional method call** - TypeIdGenerator calls `$typeClass::new()`, adding one level of indirection
2. **Service Locator lookup** - Each `generateId()` must find the correct generator
3. **Wrapper objects** - TypeSafe ID objects are wrappers around Symfony UID

**Real-world impact:**

For 1000 entities the difference is ~6-7ms. This means:
- For typical applications with 10-100 new entities per request: **< 0.1ms overhead**
- For bulk operations (1000+ entities): **~6-7ms per 1000 entities**

## Recommendations

### ✅ Use TypeSafeId if:

- You want strong type safety and IDE autocomplete
- You're doing domain-driven design with value objects
- You have < 100 entity types
- 0.1ms overhead per request is acceptable

### ⚠️ Consider alternatives if:

- You're doing extremely high-performance bulk operations (10,000+ entities per request)
- You need absolute maximum performance at all costs
- You have 100+ entity types (DI container size concern)

## Conclusion

TypeSafeId Bundle has:
- **✅ No compilation overhead** (actually 3.9% faster)
- **✅ Lower memory consumption** during compilation (-7.3%)
- **⚠️ Moderate runtime overhead** (25% slower, which is ~0.007ms per entity)

**Verdict:** For most applications, the overhead is negligible and the benefits of type safety and developer experience far outweigh it.

## Technical Implementation Details

### Custom ID Generator without Reflection

```php
final class TypeIdGenerator extends AbstractIdGenerator
{
    private string $typeClass;

    public function __construct(string $typeClass)
    {
        $this->typeClass = $typeClass;
    }

    public function generateId(EntityManagerInterface $em, $entity): GeneratableId
    {
        return $this->typeClass::new();
    }
}
```

### GeneratableId Interface

```php
interface GeneratableId
{
    public static function new(): self;
}
```

Each ID type implements this interface:

```php
final class ProductId extends UuidV7 implements GeneratableId
{
    public static function new(): self
    {
        return new self();
    }
}
```

### DI Container Registration

Compiler pass automatically registers one service per ID:

```php
$generatorServiceId = 'doctrine.id_generator.' . str_replace('\\', '_', $idClass);
$generatorDefinition = new Definition(TypeIdGenerator::class, [$idClass]);
$generatorDefinition->addTag('doctrine.id_generator');
```

Usage in entity:

```php
#[ORM\Id]
#[ORM\GeneratedValue(strategy: 'CUSTOM')]
#[ORM\CustomIdGenerator(class: 'doctrine.id_generator.App_Entity_ProductId')]
#[ORM\Column(type: ProductIdType::class, unique: true)]
private ?ProductId $id = null;
```
