<?php

/*
 * This file is part of the Pagerfanta package.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Liip\SearchBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @author David Buchmann <mail@davidbu.ch>
 */
class SearchboxExtension extends \Twig_Extension
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * Supported options are:
     *     - search_route*     Name of route used for submitting search query.
     *     - query_param_name* Name of the URL GET parameter for the query.
     *     - box_template      Template for displaying the search box.
     *     - template_options  Information to pass to the template as 'options'.
     *
     * @param ContainerInterface    $container    To get templating from.
     * @param UrlGeneratorInterface $urlGenerator
     * @param array                 $options
     */
    public function __construct(
        ContainerInterface $container,
        UrlGeneratorInterface $urlGenerator,
        array $options
    ) {
        $this->container = $container;
        $this->urlGenerator = $urlGenerator;
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setRequired(array(
            'search_route',
            'query_param_name',
        ));

        $optionsResolver->setDefaults(array(
            'box_template' => 'LiipSearchBundle:Search:search_box.html.twig',
            'template_options' => array(),
        ));
        $this->options = $optionsResolver->resolve($options);
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('liip_search_box', array($this, 'renderSearchBox'), array('is_safe' => array('html'))),
        );
    }

    /**
     * Renders the search box.
     *
     * @param string|bool $query    Default search query to show in the box.
     * @param string      $fieldId  HTML id of the search input field, when you have
     *                              more than one search box on the page.
     * @param string      $cssClass The css class to apply to the whole form.
     *
     * @return string The rendered search box.
     */
    public function renderSearchBox($query = false, $fieldId = 'query', $cssClass = 'search')
    {
        return $this->container->get('templating')->render(
            $this->options['box_template'],
            array(
                'search_url' => $this->urlGenerator->generate($this->options['search_route']),
                'field_id' => $fieldId,
                'css_class' => $cssClass,
                'query' => $query,
                'query_param_name' => $this->options['query_param_name'],
                'options' => $this->options['template_options'],
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'liip_search';
    }
}
