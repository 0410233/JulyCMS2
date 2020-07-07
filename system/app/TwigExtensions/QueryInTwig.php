<?php

namespace App\TwigExtensions;

use App\Models;
use App\ModelCollections\CatalogCollection;
use App\ModelCollections\NodeCollection;
use App\ModelCollections\NodeTypeCollection;
use App\ModelCollections\TagCollection;
use App\Models\Node;
use Illuminate\Support\Str;
use Twig\TwigFunction;
use Twig\TwigFilter;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class QueryInTwig extends AbstractExtension implements GlobalsInterface
{
    public function getGlobals(): array
    {
        return [
            '_host' => config('jc.url'),
            '_email' => config('jc.email'),
            '_catalog' => Models\Catalog::default(),
        ];
    }

    public function getFunctions()
    {
        return [
            // 获取配置
            new TwigFunction('config', function ($key) {
                return config($key) ?? config('jc.' . $key) ?? config('app.' . $key) ?? null;
            }),

            // 获取节点集
            new TwigFunction('nodes', [$this, 'nodes']),

            // 获取类型集
            new TwigFunction('types', [$this, 'node_types']),

            // 获取目录集
            new TwigFunction('catalogs', [$this, 'catalogs']),

            // 获取标签集
            new TwigFunction('tags', [$this, 'tags']),

            //
            new TwigFunction('in_path', [$this, 'in_path'], ['needs_environment' => true]),
        ];
    }

    public function getFilters()
    {
        return [
            // html_id 方法用于将字符串转换为可用做 HTML 元素 id 的形式
            new TwigFilter('html_id', function ($input) {

                $id = preg_replace('/\s+|[^\w\-]/', '_', trim($input));

                if (!$id) {
                    return 'jc_' . Str::random(5);
                }

                return $id;
            }),

            // html_class 方法用于将字符串转换为可用做 HTML 元素 class 的形式
            new TwigFilter('html_class', function ($input) {

                $class = preg_replace('/\s+|[^\w\-]/', '_', trim($input));

                if (!$class) {
                    return 'jc-' . Str::random(5);
                }

                return $class;
            }),

            // 使用 tags 过滤节点集
            new TwigFilter('tags', function($nodes, array $options = []) {
                if ($nodes instanceof NodeCollection && !empty($options)) {
                    $match = array_pop($options);
                    if (!is_int($match)) {
                        $options[] = $match;
                        $match = 1;
                    }

                    $options = collect($options)->flatten()->all();
                    if (!empty($options)) {
                        return $nodes->match_tags($options, $match);
                    }
                }

                return $nodes;
            }, ['is_variadic' => true]),

            // 按内容类型过滤节点集
            new TwigFilter('types', function($nodes, array $options = []) {

                if ($nodes instanceof NodeCollection) {
                    if (count($options) === 1 && is_array($options[0])) {
                        $options = $options[0];
                    }
                    if (!empty($options)) {
                        return $nodes->filter(function($node) use($options) {
                            return in_array($node->node_type, $options);
                        })->keyBy('id');
                    }
                }

                return $nodes;
            }, ['is_variadic' => true]),
        ];
    }

    /**
     * 获取节点集
     *
     * @param int|array $args 用于获取节点的参数，可以是：
     *  - 节点
     *  - 节点 id
     *  - 类型集
     *  - 目录集
     *  - 标签集
     *
     * @return \App\ModelCollections\NodeCollection
     */
    public function nodes(...$args)
    {
        $args = format_arguments($args);
        if (empty($args)) {
            return NodeCollection::findAll();
        }
        return NodeCollection::find($args);
    }

    /**
     * 获取类型集
     *
     * @param string|int|array $args 用于获取类型的参数，可以是：
     *  - 类型
     *  - 类型真名 (truename)
     *
     * @return \App\ModelCollections\NodeTypeCollection
     */
    public function node_types(...$args)
    {
        $args = format_arguments($args);
        if (empty($args)) {
            return NodeTypeCollection::findAll();
        }
        return NodeTypeCollection::find($args);
    }

    /**
     * 获取节点树集
     *
     * @param string|array $args 用于获取目录的参数，可以是：
     *  - 目录
     *  - 目录真名 (truename)
     *
     * @return \App\ModelCollections\CatalogCollection
     */
    public function catalogs(...$args)
    {
        $args = format_arguments($args);
        if (empty($args)) {
            return CatalogCollection::findAll();
        }
        return CatalogCollection::find($args);
    }

    /**
     * 获取标签集
     *
     * @param string|array $args 用于获取标签的参数，可以是：
     *  - 标签
     *  - 标签名
     *
     * @return \App\ModelCollections\TagCollection
     */
    public function tags(...$args)
    {
        $args = format_arguments($args);
        if (empty($args)) {
            return TagCollection::findAll();
        }
        return TagCollection::find($args);
    }

    /**
     * 判断给定节点是否在当前节点的路径中
     *
     * @param \Twig\Environment $twig
     * @param \App\Models\Node|int|string $node
     * @param string|null $active
     *
     * @return string|boolean
     */
    public function in_path(\Twig\Environment $twig, $node_id, string $active = null)
    {
        if ($node_id instanceof Node) {
            $node_id = $node_id->getKey();
        }
        $node_id = (int) $node_id;

        if (! $node_id) {
            return $active ? '' : false;
        }

        $globals = $twig->getGlobals();

        if ($path = $globals['_path'] ?? null) {
            if ($path->contains($node_id)) {
                return $active ?: true;
            }
        }

        if ($node = $globals['_node'] ?? null) {
            if ($node_id === 1*$node->getKey()) {
                return $active ?: true;
            }
        }

        return $active ? '' : false;
    }
}
