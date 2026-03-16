<?php

use Symfony\Bundle\MakerBundle\Maker\Common\EntityIdTypeEnum;

?>
<?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?= $use_statements; ?>

final class <?= $class_name ?> extends <?php if (EntityIdTypeEnum::UUID === $id_type): ?>UuidV7<?php elseif (EntityIdTypeEnum::ULID === $id_type): ?>Ulid<?php else: ?>\Tito10047\TypeSafeIdBundle\IntId<?php endif ?>
{
}
