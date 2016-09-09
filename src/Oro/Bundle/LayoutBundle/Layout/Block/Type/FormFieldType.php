<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Symfony\Component\Form\FormView;

use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\Options;

class FormFieldType extends AbstractFormType
{
    const NAME = 'form_field';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setRequired(['form_name', 'field_path']);
    }

    public function buildView(BlockView $view, BlockInterface $block, Options $options)
    {
        $view->vars['field_path'] = $options->get('field_path', false);
        parent::buildView($view, $block, $options);

    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block)
    {
        $formAccessor = $this->getFormAccessor($block->getContext(), $view->vars);

        $view->vars['form'] = $formAccessor->getView($view->vars['field_path']);

        // prevent the form field rendering by form_rest() method,
        // if the corresponding layout block is invisible
        if ($view->vars['visible'] === false) {
            /** @var FormView $formView */
            $formView = $view->vars['form'];
            $formView->setRendered();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
