<?php

use Symfony\Bundle\MakerBundle\Maker\Common\EntityIdTypeEnum;

?>
<?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?= $use_statements; ?>

final class <?= $class_name ?> extends <?php if (EntityIdTypeEnum::UUID === $id_type): ?>UuidV7<?php elseif (EntityIdTypeEnum::ULID === $id_type): ?>Ulid<?php else: ?>\Tito10047\TypeSafeIdBundle\IntId<?php endif ?> implements \Tito10047\TypeSafeIdBundle\GeneratableId
{
<?php if (EntityIdTypeEnum::UUID === $id_type): ?>
    public static function new(): self
    {
        return new self();
    }
<?php elseif (EntityIdTypeEnum::ULID === $id_type): ?>
    public static function new(): self
    {
        return new self();
    }
<?php else: ?>
    public static function new(): self
    {
        // IntId uses auto-increment, so we return an empty instance
        return new self();
    }
<?php endif; ?>
}
