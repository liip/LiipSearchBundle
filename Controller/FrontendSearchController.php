<?php

/*
 * This file is part of the LiipSearchBundle
 *
 * (c) Liip AG
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\SearchBundle\Controller;

use Liip\SearchBundle\SearchFactoryInterface;
use Liip\SearchBundle\SearchInterface;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Controller to render frontend search.
 */
class FrontendSearchController
{
    /**
     * @var EngineInterface
     */
    private $templating;

    /**
     * @var array Hashmap with options to control the behaviour of this controller.
     */
    private $options;

    /**
     * Constructor
     *
     * Supported options are:
     *     - search_template   Template for the search page
     *     - template_options  Information to pass to the template as 'options'.
     *
     * @param EngineInterface $templating
     * @param array           $options
     */
    public function __construct(
        EngineInterface $templating,
        array $options
    ) {
        $this->templating = $templating;
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setRequired(array(
            'search_template',
        ));
        $optionsResolver->setDefaults(array(
            'template_options' => array(),
        ));
        $this->options = $optionsResolver->resolve($options);
    }

    /**
     * Search method
     *
     * @param Request $request Current request to fetch info from.
     *
     * @return Response
     */
    public function searchAction(Request $request)
    {
        return new Response($this->templating->render(
            $this->options['search_template'],
            array(
                'options' => $this->options['template_options'],
            )
        ));
    }
}
