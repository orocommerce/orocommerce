<?php

namespace Oro\Bundle\TaxBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandler;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Form\Type\TaxType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD for tax rates.
 */
class TaxController extends AbstractController
{
    /**
     * @Route("/", name="oro_tax_index")
     * @Template
     * @AclAncestor("oro_tax_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => Tax::class
        ];
    }

    /**
     * @Route("/view/{id}", name="oro_tax_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_tax_view",
     *      type="entity",
     *      class="OroTaxBundle:Tax",
     *      permission="VIEW"
     * )
     *
     * @param Tax $tax
     * @return array
     */
    public function viewAction(Tax $tax)
    {
        return [
            'entity' => $tax
        ];
    }

    /**
     * @Route("/create", name="oro_tax_create")
     * @Template("@OroTax/Tax/update.html.twig")
     * @Acl(
     *      id="oro_tax_create",
     *      type="entity",
     *      class="OroTaxBundle:Tax",
     *      permission="CREATE"
     * )
     *
     * @return array
     */
    public function createAction()
    {
        return $this->update(new Tax());
    }

    /**
     * @Route("/update/{id}", name="oro_tax_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_tax_update",
     *      type="entity",
     *      class="OroTaxBundle:Tax",
     *      permission="EDIT"
     * )
     *
     * @param Tax $tax
     * @return array
     */
    public function updateAction(Tax $tax)
    {
        return $this->update($tax);
    }

    /**
     * @param Tax $tax
     * @return array|RedirectResponse
     */
    protected function update(Tax $tax)
    {
        return $this->get(UpdateHandler::class)->handleUpdate(
            $tax,
            $this->createForm(TaxType::class, $tax),
            function (Tax $tax) {
                return [
                    'route' => 'oro_tax_update',
                    'parameters' => ['id' => $tax->getId()]
                ];
            },
            function (Tax $tax) {
                return [
                    'route' => 'oro_tax_view',
                    'parameters' => ['id' => $tax->getId()]
                ];
            },
            $this->get(TranslatorInterface::class)->trans('oro.tax.controller.tax.saved.message')
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                UpdateHandler::class,
            ]
        );
    }
}
