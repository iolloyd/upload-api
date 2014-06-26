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
use JMS\Serializer\SerializationContext;
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
        $app['paginator'] = $app->protect(function ($model, $groups, $options = []) use ($app)
        {
            $criteria = Criteria::create();
            $filterFields = count($options) && isset($options['filterFields'])
                ? $options['filterFields']
                : [];

            if (count($filterFields)) {
                foreach ($app['request']->query->all() as $field => $values) {
                    if (!is_array($values)) {
                        $values = [$values];
                    }

                    if (in_array($field, $filterFields)) {
                        $criteria = $criteria->where(Criteria::expr()->in($field, $values));
                    }
                }
            }

            $list = $app['em']->getRepository($model)->matching($criteria);

            if (!$list->count()) {
                $app['logger.doctrine']->error(
                    "No results found when requesting paginator using {model}",
                    ['model' => $model]
                );
            }

            $adapter = new DoctrineCollectionAdapter($list);
            $pager = new Pagerfanta($adapter);

            $page    = $app['request']->get('page')     ?: 1;
            $perPage = $app['request']->get('per_page') ?: 10;

            $pager
                ->setMaxPerPage($perPage)
                ->setCurrentPage($page);

            return $pager;
        });

        $app['serializer'] = $app->protect(function($results, $groups = []) use ($app) {
            $serializer = SerializerBuilder::create()
                ->setDebug($app['debug'])
                ->build();

            $context = SerializationContext::create()
                ->setSerializeNull(true);

            if (count($groups)) {
                $context->setGroups($groups);
            }

            $jsonContent = $serializer->serialize($results, 'json', $context);

            return json_decode($jsonContent);
        });

        $app['single.response.json'] = $app->protect(function ($model, $groups, $headerLink = true) use ($app) {

            $jsonContent = $app['serializer']($model, $groups);

            $response = $app->json($jsonContent);
            if ($headerLink) {
                $link = $app['request']->getSchemeAndHttpHost() .
                        $app['request']->getPathInfo();

                $response->headers->add(['Location' => $link]);
            }

            return $response;
        });

        $app['paginator.response.json'] = $app->protect(function ($model, $groups, $options = []) use ($app) {

            $hostUrl = $app['request']->getSchemeAndHttpHost() . $app['request']->getPathInfo();
            $params  = $app['request']->query->all();
            $pager   = $app['paginator']($model, $groups, $options);
            $navlinks = $this->getLinks($hostUrl, $params, $pager);
            $jsonContent = $app['serializer']($pager->getCurrentPageResults(), $groups);

            $response = $app->json($jsonContent);
            $response->headers->add(['Link' => $navlinks['link']]);
            $response->headers->add(['X-Pagination-Range' => $navlinks['range']]);

            return $response;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function boot(Application $app)
    {
    }

    protected function getLinks($hostUrl, $params, $pager)
    {
        $link = $this->getLink($hostUrl, $params, $pager);
        $currentPage = $pager->getCurrentPage();
        $pageSize = $pager->getMaxPerPage();
        $lastPage = $pager->getNbPages();
        $totalItemCount = $pager->getNbResults();

        $navlink = [
            $link('first', 1),
            $link('last', $lastPage),
            $pager->hasPreviousPage() ? $link('prev', $pager->getPreviousPage()) : "",
            $pager->hasNextPage()     ? $link('next', $pager->getNextPage())     : "",
        ];
        $rangelink = $this->getRangeLinks($currentPage, $pageSize, $lastPage, $totalItemCount);
        $navlink = str_replace(', ,', ', ', implode(', ', $navlink));

        return ['link' => $navlink, 'range' => $rangelink,];
    }

    protected function getLink($hostUrl, $params, $pager)
    {
        return function($rel, $page) use ($hostUrl, $params) {
            $params['page'] = $page;
            $params = http_build_query($params);
            $link = sprintf('<%s?%s>; rel="%s"', $hostUrl, $params, $rel);

            return $link;
        };

    }

    /**
     * X-Pagination-Range: items 1-10/250; pages 1/25
     */
    protected function getRangeLinks($currentPage, $pageSize, $lastPage, $totalItemCount)
    {
        $currentItem = ($currentPage * $pageSize) - $pageSize;
        $lastItemOfPage = min($currentItem + $pageSize - 1, $totalItemCount);
        $links = "X-Pagination-Range: items " 
               . "$currentItem-$lastItemOfPage/$totalItemCount; " 
               . "page $currentPage/$lastPage";

        return $links;
    }

}

