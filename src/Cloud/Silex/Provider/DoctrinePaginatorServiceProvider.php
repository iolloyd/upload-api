<?php
/**
 * cloudxxx-api (http://www.cloud.xxx)
 *
 * Copyright (C) 2014 Really Useful Limited.
 * Proprietary code. Usage restrictions apply.
 *
 * @copyright  Copyright (C) 2014 Really Useful Limited
 * @license    Proprietary
 */

namespace Cloud\Silex\Provider;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Tools\Pagination\Paginator;
use JMS\Serializer\SerializerBuilder;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Adapter\DoctrineCollectionAdapter;
use Pagerfanta\Pagerfanta;
use Silex\Application;
use Silex\ServiceProviderInterface;

class DoctrinePaginatorServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(Application $app)
    {
        $app['paginator'] = $app->protect(function ($model) use ($app) {
            $list = $app['em']->getRepository($model)->matching(new Criteria());
            $adapter = new DoctrineCollectionAdapter($list);
            $pager = new Pagerfanta($adapter);

            $page = $app['request']->get('page') ?: 1; 
            $perPage = $app['request']->get('per_page') ?: 10;

            $pager->setMaxPerPage($perPage);
            $pager->setCurrentPage($page);

            return $pager;
        });

        $app['paginator.response.json'] = $app->protect(function ($model, $groups) use ($app) {
            $hostUrl = $app['request']->getSchemeAndHttpHost() . $app['request']->getPathInfo();
            $pager   = $app['paginator']($model);
            $params  = $app['request']->query->all();
            $links   = $this->getLinks($hostUrl, $params, $pager);
            $totalRange = $this->getTotalRangeLink($hostUrl, $params, $pager);
            $range      = $this->getRangeLink($hostUrl, $params, $pager);

            $serializer  = SerializerBuilder::create()->build();
            $jsonContent = $serializer->serialize(
                $pager->getCurrentPageResults(),
                'json', 
                \JMS\Serializer\SerializationContext::create()->setGroups($groups)
            );

            $response = $app->json(json_decode($jsonContent));
            $response->headers->add(['Link' => $links]);
            $response->headers->add(['X-Total-Range' => $totalRange]);
            $response->headers->add(['X-Range' => $range]);

            return $response;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function boot(\Silex\Application $app)
    {
    }

    protected function getLinks($hostUrl, $params, $pager)
    {
        $link = $this->getLink($hostUrl, $params, $pager);

        $links = [
            $link(1, 'first'),
            $link(floor($pager->count() / $pager->getMaxPerPage()), 'last'),
            $pager->hasPreviousPage() ? $link($pager->getPreviousPage(), 'prev') : "",
            $pager->hasNextPage() ? $link($pager->getNextLink(), 'next') : "",
        ];

        return implode(', ', $links);
    }

    protected function getLink($hostUrl, $params, $pager)
    {
        return function($page, $rel) use ($hostUrl, $params) {

            $params['page'] = $page;
            $params = http_build_query($params);
            $link = sprintf('<%s?%s>; rel="%s"', $hostUrl, $params, $rel);

            return $link;
        };

    }
}

