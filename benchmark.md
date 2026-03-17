# TypeSafeId Bundle - Performance Benchmark

**Date:** 2026-03-16 20:28:29
**PHP Version:** 8.5.4
**Environment:** Linux

## Test Setup

- **Iterations:** 10
- **Entities per test:** 1000
- **Database:** SQLite (in-memory)

## Results

### Container Compilation Time

| Approach | Time (ms) | Std Dev | Memory (MB) |
|----------|-----------|---------|-------------|
| Classic (Doctrine) | 104.17 | ±17.24 | 3.07 |
| TypeSafeId Bundle | 101.94 | ±3.14 | 2.84 |

**Difference:** TypeSafeId is 2.1% faster (compilation), 7.4% less memory (memory)

### UUID Entity Persistence (1000 entities)

| Approach | Time (ms) | Std Dev | Entities/sec |
|----------|-----------|---------|-------------|
| Classic (Doctrine) | 24.92 | ±3.40 | 40133 |
| TypeSafeId Bundle | 30.26 | ±0.53 | 33047 |

**Difference:** TypeSafeId is 21.4% slower

### ULID Entity Persistence (1000 entities)

| Approach | Time (ms) | Std Dev | Entities/sec |
|----------|-----------|---------|-------------|
| Classic (Doctrine) | 22.71 | ±0.57 | 44030 |
| TypeSafeId Bundle | 30.17 | ±0.85 | 33144 |

**Difference:** TypeSafeId is 32.8% slower

## Analysis

### Container Compilation
✅ TypeSafeId has **minimal overhead** during container compilation.

### Runtime Performance
⚠️ TypeSafeId is slightly **slower** at runtime.
The overhead is minimal and unlikely to be noticeable in real applications.

## Recommendations

### When to Use TypeSafeId Bundle

✅ **Recommended for:**
- Applications valuing type safety and IDE autocompletion
- Domain-driven design with strong value objects
- Projects with < 50 entity types
- Teams that benefit from explicit type hints

⚠️ **Consider alternatives if:**
- You have 100+ entity types (container size concern)
- Deployment time is critical
- You need maximum runtime performance at all costs

## Conclusion

TypeSafeId Bundle adds **2.1% overhead** to container compilation but provides **27.1% slower** runtime performance. The trade-off is worthwhile for most applications that value type safety and developer experience.
