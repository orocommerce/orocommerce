<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides TWIG functions for product unit label formatting.
 */
class ProductUnitLabelExtension extends \Twig_Extension
{
    const NAME = 'oro_product_unit_label';

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return ProductUnitLabelFormatter
     */
    protected function getFormatter()
    {
        return $this->container->get('oro_product.formatter.product_unit_label');
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'oro_format_product_unit_label',
                [$this, 'format'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'oro_format_short_product_unit_label',
                [$this, 'formatShort'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * @param string $unitCode
     * @param bool $isShort
     * @param bool $isPlural
     * @return string
     */
    public function format($unitCode, $isShort = false, $isPlural = false)
    {
        return $this->getFormatter()->format($unitCode, $isShort, $isPlural);
    }

    /**
     * @param string $unitCode
     * @param bool $isPlural
     * @return string
     */
    public function formatShort($unitCode, $isPlural = false)
    {
        return $this->format($unitCode, true, $isPlural);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
