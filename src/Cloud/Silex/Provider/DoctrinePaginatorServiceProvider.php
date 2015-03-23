<?php
/**
 * cloudxxx-api (http://www.cloud.xxx)
 *
 * Copyright (C) 2014 Really Useful Limited.
 * Proprietary code. Usage restrictions apply.
 *
 * @copyright Copyright (c) 2014 Really Useful Limited
 * @license   Proprietary
 */

namespace Cloud\Silex\Provider;

use Doctrine\Common\Collections\Criteria;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Handler\HandlerRegistry;
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
        $app['paginator'] = $app->protect(function ($model, $options = []) use ($app)
        {
            $criteria = Criteria::create();
            $filterFields = count($options) && isset($options['filterFields'])
                ? $options['filterFields']
                : [];

            if (count($filterFields)) {
                foreach ($app['request']->query->all() as $field => $values) {
                    if (!in_array($field, $filterFields)) {
                        continue;
                    }

                    if (!is_array($values)) {
                        $values = [$values];
                    }

                    $criteria->andWhere(Criteria::expr()->in($field, $values));
                }
            }

            $list = $app['em']->getRepository($model)->matching($criteria);

            if (!$list->count()) {
                $app['logger']->notice(
                    'Paginator got empty result set', [
                        'model'    => $model,
                        'criteria' => $criteria,
                    ]
                );
            }

            $adapter = new DoctrineCollectionAdapter($list);
            $pager = new Pagerfanta($adapter);

            // This causes an empty result in the case of requesting an impossible page
            $pager->setAllowOutOfRangePages(true);
            $page    = $app['request']->get('page')     ?: 1;
            $perPage = $app['request']->get('per_page') ?: 100;

            $pager
                ->setMaxPerPage($perPage)
                ->setCurrentPage($page);

            return $pager;
        });

        $app['serializer'] = $app->protect(function($results, $groups = []) use ($app)
        {
            $serializer = SerializerBuilder::create()
                ->setDebug($app['debug'])
                ->addDefaultHandlers()
                ->configureHandlers(
                    function(HandlerRegistry $registry) {
                        $registry->registerHandler('serialization', '\Cloud\Model\Category', 'json',
                            function($visitor, Category $obj, array $type) {
                                return $obj->getId();
                            }
                        );
                    }
                )
                ->build()
            ;
            $context = SerializationContext::create()->setSerializeNull(true);

            if (count($groups)) {
                $context->setGroups($groups);
            }
            $jsonContent = $serializer->serialize($results, 'json', $context);
            $result = json_decode($jsonContent);

            return $result;
        });

        $app['single.response.json'] = $app->protect(function ($model, $groups, $headerLink = true) use ($app)
        {
            $jsonContent = $app['serializer']($model, $groups);

            $response = $app->json($jsonContent);
            if ($headerLink) {
                $link = $app['request']->getSchemeAndHttpHost()
                      . $app['request']->getPathInfo();

                $response->headers->add(['Location' => $link]);
            }

            return $response;
        });

        $app['paginator.response.json'] = $app->protect(function ($model, $groups, $options = []) use ($app)
        {
            $pager         = $app['paginator']($model, $options);
            $requestUrl    = $app['base_url'] . $app['request']->getPathInfo();
            $requestParams = $app['request']->query->all();

<<<<<<< HEAD
            $hostUrl = $app['request']->getSchemeAndHttpHost() . $app['request']->getPathInfo();
            $params  = $app['request']->query->all();
            $pager   = $app['paginator']($model, $groups, $options);
            $navlinks = $this->getLinks($hostUrl, $params, $pager);
            $jsonContent = $app['paginator.serializer']($pager->getCurrentPageResults(), $groups);
=======
            $content = $app['serializer']($pager->getCurrentPageResults(), $groups);
            $headers = $this->getHeaders($requestUrl, $requestParams, $pager);
>>>>>>> feature/logging-improvement

            $response = $app->json($content);
            $response->headers->add($headers);

            return $response;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function boot(Application $app)
    {
    }

    /**
     * Returns the paginator related response headers
     *
     * @param string     $requestUrl
     * @param array      $requestParams
     * @param Pagerfanta $pager
     *
     * @return array
     */
    protected function getHeaders($requestUrl, array $requestParams, Pagerfanta $pager)
    {
        $curPage  = $pager->getCurrentPage();
        $prevPage = $pager->hasPreviousPage()
            ? $pager->getPreviousPage()
            : null;

        $nextPage = $pager->hasNextPage()
            ? $pager->getNextPage()
            : null;

        $lastPage = $pager->getNbPages();

        $pageSize  = $pager->getMaxPerPage();
        $itemCount = $pager->getNbResults();

        $pages = [
            'first' => 1,
            'last' => $lastPage,
            'next' => $nextPage,
            'prev' => $prevPage,
        ];
        $pages = array_filter($pages);

        $linkHeader  = $this->formatLink($requestUrl, $requestParams, $pages);
        $rangeHeader = $this->formatRange($curPage, $pageSize, $lastPage, $itemCount);

        return [
            'Link' => $linkHeader,
            'X-Pagination-Range' => $rangeHeader,
        ];
    }

    /**
     * Returns a link header with http links for first, last, next and prev
     * where applicable.
     *
     * @param string $requestUrl
     * @param array $params
     * @param array $pages
     *
     * @return string
     */
    protected function formatLink($requestUrl, array $params, array $pages)
    {
        $result = [];
        foreach ($pages as $rel => $page) {
            $newParams = $params;
            $newParams['page'] = $page;
            $newParams = http_build_query($newParams);
            $result[] = sprintf('<%s?%s>; rel="%s"', $requestUrl, $newParams, $rel);
        }

        return implode(', ', $result);
    }

    /**
     * Returns the value of the `X-Pagination-Range` header for the given page and item counts
     *
     *     X-Pagination-Range: items 1-10/250; pages 1/25
     *
     * @param int $currentPage
     * @param int $pageSize
     * @param int $lastPage
     * @param int $totalItemCount
     *
     * @return string
     */
    protected function formatRange($currentPage, $pageSize, $lastPage, $totalItemCount)
    {
        $currentItem    = (($currentPage * $pageSize) - $pageSize) + 1;
        $lastItemOfPage = min(($currentItem + $pageSize) - 1, $totalItemCount);

        $result = sprintf('items %d-%d/%d; pages %d/%d',
            $currentItem,
            $lastItemOfPage,
            $totalItemCount,
            $currentPage,
            $lastPage
        );

        return $result;
    }

}

