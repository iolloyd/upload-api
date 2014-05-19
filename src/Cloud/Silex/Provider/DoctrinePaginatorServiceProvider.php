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

            $serializer  = SerializerBuilder::create()->build();
            $jsonContent = $serializer->serialize(
                $pager->getCurrentPageResults(),
                'json', 
                \JMS\Serializer\SerializationContext::create()->setGroups($groups)
            );

            $response = $app->json(json_decode($jsonContent));
            $response->headers->add(['Link' => $links]);

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
        $first = $this->getFirstLink($hostUrl, $params, $pager);
        $last = $this->getLastLink($hostUrl, $params, $pager);
        $prev = $this->getPreviousLink($hostUrl, $params, $pager);
        $next = $this->getNextLink($hostUrl, $params, $pager);

        return implode(', ', [$first, $last, $prev, $next]);
    }

    protected function getFirstLink($hostUrl, $params, $pager)
    {
        $params['page'] = 1;
        $params = http_build_query($params);
        $prev = sprintf('<%s?%s>; rel="first"', $hostUrl, $params);

        return $prev;
    }

    protected function getLastLink($hostUrl, $params, $pager)
    {
        $params['page'] = floor($pager->count() / $pager->getMaxPerPage());
        $params = http_build_query($params);
        $prev = sprintf('<%s?%s>; rel="last"', $hostUrl, $params);

        return $prev;
    }

    protected function getPreviousLink($hostUrl, $params, $pager)
    {
        if ($pager->hasPreviousPage() ) {
            $params['page'] = $pager->getPreviousPage();
            $params = http_build_query($params);
            $prev = sprintf('<%s?%s>; rel="prev"', $hostUrl, $params);
        } else {
            $prev = '';
        }
        return $prev;
    }

    protected function getNextLink($hostUrl, $params, $pager)
    {
        if ($pager->hasNextPage() ) {
            $params['page'] = $pager->getNextPage();
            $params = http_build_query($params);
            $next  = sprintf('<%s?%s>; rel="next" ', $hostUrl, $params);
        } else {
            $next = '';

        }
        return $next;
    }

}

