<?php

namespace Oro\Bundle\RedirectBundle\Form\Type;

use Symfony\Component\Form\FormView;

trait SlugifyFieldIntoSlugTrait
{
    /**
     * Get js component name
     *
     * @return string
     */
    abstract public function getComponent();

    /**
     * @param FormView $view
     * @param array $options
     */
    private function addComponentOptions(FormView $view, array $options)
    {
        $view->vars['slugify_component'] = $this->getComponent();
        $targetFullName = $view->parent->vars['full_name'].'['.$options['target_field_name'].']';
        $view->vars['slugify_component_options'] = [
            'target' => $targetFullName,
            'recipient' => $view->vars['full_name']
        ];
    }
}
