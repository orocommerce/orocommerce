<?php

namespace Oro\Bundle\ShoppingListBundle\Validator\Constraints;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if line item's product and unit are unique in shopping list.
 */
class LineItemValidator extends ConstraintValidator
{
    /** @var ManagerRegistry */
    private ManagerRegistry $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param LineItem $value
     * @param Constraint|LineItem $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        $lineItemRepository = $this->registry->getRepository(LineItem::class);
        if ($lineItemRepository->findDuplicateInShoppingList($value, $value->getShoppingList())) {
            $this->context->addViolation($constraint->message);
        }
    }
}
