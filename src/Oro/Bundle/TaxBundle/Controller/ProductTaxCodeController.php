<?php

namespace Oro\Bundle\TaxBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandler;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Form\Type\ProductTaxCodeType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD for product tax codes.
 */
class ProductTaxCodeController extends AbstractController
{
    /**
     * @Route("/", name="oro_tax_product_tax_code_index")
     * @Template
     * @AclAncestor("oro_tax_product_tax_code_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => ProductTaxCode::class
        ];
    }

    /**
     * @Route("/view/{id}", name="oro_tax_product_tax_code_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_tax_product_tax_code_view",
     *      type="entity",
     *      class="OroTaxBundle:ProductTaxCode",
     *      permission="VIEW"
     * )
     *
     * @param ProductTaxCode $productTaxCode
     * @return array
     */
    public function viewAction(ProductTaxCode $productTaxCode)
    {
        return [
            'entity' => $productTaxCode
        ];
    }

    /**
     * @Route("/create", name="oro_tax_product_tax_code_create")
     * @Template("@OroTax/ProductTaxCode/update.html.twig")
     * @Acl(
     *      id="oro_tax_product_tax_code_create",
     *      type="entity",
     *      class="OroTaxBundle:ProductTaxCode",
     *      permission="CREATE"
     * )
     *
     * @return array
     */
    public function createAction()
    {
        return $this->update(new ProductTaxCode());
    }

    /**
     * @Route("/update/{id}", name="oro_tax_product_tax_code_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_tax_product_tax_code_update",
     *      type="entity",
     *      class="OroTaxBundle:ProductTaxCode",
     *      permission="EDIT"
     * )
     *
     * @param ProductTaxCode $productTaxCode
     * @return array
     */
    public function updateAction(ProductTaxCode $productTaxCode)
    {
        return $this->update($productTaxCode);
    }

    /**
     * @param ProductTaxCode $productTaxCode
     * @return array|RedirectResponse
     */
    protected function update(ProductTaxCode $productTaxCode)
    {
        return $this->get(UpdateHandler::class)->handleUpdate(
            $productTaxCode,
            $this->createForm(ProductTaxCodeType::class, $productTaxCode),
            function (ProductTaxCode $productTaxCode) {
                return [
                    'route' => 'oro_tax_product_tax_code_update',
                    'parameters' => ['id' => $productTaxCode->getId()]
                ];
            },
            function (ProductTaxCode $productTaxCode) {
                return [
                    'route' => 'oro_tax_product_tax_code_view',
                    'parameters' => ['id' => $productTaxCode->getId()]
                ];
            },
            $this->get(TranslatorInterface::class)->trans('oro.tax.controller.product_tax_code.saved.message')
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
