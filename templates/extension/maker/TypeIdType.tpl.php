<?php

use Symfony\Bundle\MakerBundle\Maker\Common\EntityIdTypeEnum;

?>
<?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?= $use_statements; ?>

final class <?= $class_name ?> extends <?php if (isset($id_type) && (EntityIdTypeEnum::UUID === $id_type || EntityIdTypeEnum::ULID === $id_type)): ?>AbstractUidType<?php else: ?>\Tito10047\TypeSafeIdBundle\AbstractIntIdType<?php endif ?>

{
    public function getName(): string
    {
        return self::class;
    }

<?php if (isset($id_type) && (EntityIdTypeEnum::UUID === $id_type || EntityIdTypeEnum::ULID === $id_type)): ?>
    protected function getUidClass(): string
    {
        return <?= $id_class ?>::class;
    }
<?php else: ?>
    protected function getIntIdClass(): string
    {
        return <?= $id_class ?>::class;
    }
<?php endif; ?>
}