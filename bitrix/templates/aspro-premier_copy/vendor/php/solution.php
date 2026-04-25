<?php

namespace {
    if (!defined('VENDOR_PARTNER_NAME')) {
        /* @const Aspro partner name */
        define('VENDOR_PARTNER_NAME', 'aspro');
    }

    if (!defined('VENDOR_SOLUTION_NAME')) {
        /* @const Aspro solution name */
        define('VENDOR_SOLUTION_NAME', 'premier');
    }

    if (!defined('VENDOR_MODULE_ID')) {
        /* @const Aspro module id */
        define('VENDOR_MODULE_ID', 'aspro.premier');
    }

    foreach ([
        'CPremier' => 'TSolution',
        'CPremierEvents' => 'TSolution\Events',
        'CPremierCache' => 'TSolution\Cache',
        'CPremierRegionality' => 'TSolution\Regionality',
        'CPremierCondition' => 'TSolution\Condition',
        'Aspro\Premier\Social\Instagram' => 'TSolution\Instagram',
        'Aspro\Premier\Social\Vk' => 'TSolution\Vk',
        'Aspro\Functions\CAsproPremier' => 'TSolution\Functions',
        'Aspro\Premier\Banner\Transparency' => 'TSolution\Banner\Transparency',
        'Aspro\Premier\Functions\Extensions' => 'TSolution\Extensions',
        'Aspro\Premier\Functions\CSKU' => 'TSolution\SKU',
        'Aspro\Premier\Functions\CSKUTemplate' => 'TSolution\SKU\Template',
        'Aspro\Premier\Functions\Basket' => 'TSolution\Basket',
        'Aspro\Premier\LinkableProperty' => 'TSolution\LinkableProperty',
        'Aspro\Premier\PhoneAuth' => 'TSolution\PhoneAuth',
        'Aspro\Premier\Property\CustomFilter' => 'TSolution\Property\CustomFilter',
        'Aspro\Premier\Property\IBInherited' => 'TSolution\Property\IBInherited',
        'Aspro\Premier\Itemaction\Basket' => 'TSolution\Itemaction\Basket',
        'Aspro\Premier\Itemaction\Compare' => 'TSolution\Itemaction\Compare',
        'Aspro\Premier\Itemaction\Favorite' => 'TSolution\Itemaction\Favorite',
        'Aspro\Premier\Itemaction\Subscribe' => 'TSolution\Itemaction\Subscribe',
        'Aspro\Premier\Itemaction\Service' => 'TSolution\Itemaction\Service',
        'Aspro\Premier\Product\Basket' => 'TSolution\Product\Basket',
        'Aspro\Premier\Product\Blocks' => 'TSolution\Product\Blocks',
        'Aspro\Premier\Product\Common' => 'TSolution\Product\Common',
        'Aspro\Premier\Product\Image' => 'TSolution\Product\Image',
        'Aspro\Premier\Product\MetaInfo' => 'TSolution\Product\MetaInfo',
        'Aspro\Premier\Product\Price' => 'TSolution\Product\Price',
        'Aspro\Premier\Product\Prices' => 'TSolution\Product\Prices',
        'Aspro\Premier\Product\Quantity' => 'TSolution\Product\Quantity',
        'Aspro\Premier\Product\Service' => 'TSolution\Product\Service',
        'Aspro\Premier\Product\Template' => 'TSolution\Product\Template',
        'Aspro\Premier\Template\Page' => 'TSolution\Template\Page',
        'Aspro\Premier\Template\DisplayTypes' => 'TSolution\Template\DisplayTypes',
        'Aspro\Premier\Template\Epilog\Blocks' => 'TSolution\Template\Epilog\Blocks',
        'Aspro\Premier\Menu' => 'TSolution\Menu',
        'Aspro\Premier\Iconset' => 'TSolution\Iconset',
        'Aspro\Premier\GS' => 'TSolution\GS',
        'Aspro\Premier\Comment\Review' => 'TSolution\Comment\Review',
        'Aspro\Premier\Search\Common' => 'TSolution\Search\Common',
        'Aspro\Premier\Notice' => 'TSolution\Notice',
        'Aspro\Premier\SearchQuery' => 'TSolution\SearchQuery',
        'Aspro\Premier\SearchTitle' => 'TSolution\SearchTitle',
        'Aspro\Premier\Video\Iframe' => 'TSolution\Video\Iframe',
        'Aspro\Premier\Video\Block' => 'TSolution\Video\Block',
        'Aspro\Premier\Popover\Base' => 'TSolution\Popover\Base',
        'Aspro\Premier\Popover\Tooltip' => 'TSolution\Popover\Tooltip',
        'Aspro\Premier\Popover\OrderStatus' => 'TSolution\Popover\OrderStatus',
        'Aspro\Premier\Scheme\Offers' => 'TSolution\Scheme\Offers',
        'Aspro\Premier\Scheme\Common' => 'TSolution\Scheme\Common',
        'Aspro\Premier\Scheme\Organization' => 'TSolution\Scheme\Organization',
        'Aspro\Premier\Scheme\Product' => 'TSolution\Scheme\Product',
        'Aspro\Premier\Scheme\CatalogSection' => 'TSolution\Scheme\CatalogSection',
        'Aspro\Premier\Scheme\CatalogList' => 'TSolution\Scheme\CatalogList',
        'Aspro\Premier\Scheme\List\Content' => 'TSolution\Scheme\List\Content',
        'Aspro\Premier\Scheme\List\Blog' => 'TSolution\Scheme\List\Blog',
        'Aspro\Premier\Scheme\List\News' => 'TSolution\Scheme\List\News',
        'Aspro\Premier\Grupper' => 'TSolution\Grupper',
        'Aspro\Premier\Utils' => 'Tsolution\Utils',
        'Aspro\Premier\Social\Factory' => 'TSolution\Social\Factory',
        'Aspro\Premier\Social\Video\Factory' => 'TSolution\Social\Video\Factory',
        'Aspro\Premier\Captcha' => 'TSolution\Captcha',
        'Aspro\Premier\Captcha\Service' => 'TSolution\Captcha\Service',
        'Aspro\Premier\CacheableUrl' => 'TSolution\CacheableUrl',
        'Aspro\Premier\Stories' => 'TSolution\Stories',
        'Aspro\Premier\Mainpage\Factory' => 'TSolution\Mainpage\Factory',
        'Aspro\Premier\Validation' => 'TSolution\Validation',
        'Aspro\Premier\Vendor\Include\Component' => 'TSolution\Vendor\Include\Component',
    ] as $original => $alias) {
        if (!class_exists($alias)) {
            class_alias($original, $alias);
        }
    }

    // these alias declarations for IDE only
    if (false) {
        class TSolution extends CPremier
        {
        }
    }
}

// these alias declarations for IDE only

namespace TSolution {
    if (false) {
        class Events extends \CPremierEvents
        {
        }

        class Cache extends \CPremierCache
        {
        }

        class Regionality extends \CPremierRegionality
        {
        }

        class Condition extends \CPremierCondition
        {
        }

        class Instagram extends \Aspro\Premier\Social\Instagram
        {
        }

        class Vk extends \Aspro\Premier\Social\Vk
        {
        }

        class Functions extends \Aspro\Functions\CAsproPremier
        {
        }

        class Extensions extends \Aspro\Premier\Functions\Extensions
        {
        }

        class Basket extends \Aspro\Premier\Functions\Basket
        {
        }

        class SKU extends \Aspro\Premier\Functions\CSKU
        {
        }

        class PhoneAuth extends \Aspro\Premier\PhoneAuth
        {
        }

        class Menu extends \Aspro\Premier\Menu
        {
        }

        class Iconset extends \Aspro\Premier\Iconset
        {
        }

        class GS extends \Aspro\Premier\GS
        {
        }

        class Notice extends \Aspro\Premier\Notice
        {
        }

        class SearchQuery extends \Aspro\Premier\SearchQuery
        {
        }

        class Grupper extends \Aspro\Premier\Grupper
        {
        }

        class Utils extends \Aspro\Premier\Utils
        {
        }

        class Captcha extends \Aspro\Premier\Captcha
        {
        }

        class CacheableUrl extends \Aspro\Premier\CacheableUrl
        {
        }

        class LinkableProperty extends \Aspro\Premier\LinkableProperty
        {
        }

        class Stories extends \Aspro\Premier\Stories
        {
        }

        class Validation extends \Aspro\Premier\Validation
        {
        }

        class Vendor extends \Aspro\Premier\Vendor
        {
        }
    }
}

namespace TSolution\SKU {
    if (false) {
        class Template extends \Aspro\Premier\Functions\CSKUTemplate
        {
        }
    }
}

namespace TSolution\Product {
    if (false) {
        class Basket extends \Aspro\Premier\Product\Basket
        {
        }

        class Blocks extends \Aspro\Premier\Product\Blocks
        {
        }

        class Common extends \Aspro\Premier\Product\Common
        {
        }

        class Image extends \Aspro\Premier\Product\Image
        {
        }

        class MetaInfo extends \Aspro\Premier\Product\MetaInfo
        {
        }

        class Price extends \Aspro\Premier\Product\Price
        {
        }

        class Prices extends \Aspro\Premier\Product\Prices
        {
        }

        class Quantity extends \Aspro\Premier\Product\Quantity
        {
        }

        class Service extends \Aspro\Premier\Product\Service
        {
        }

        class Template extends \Aspro\Premier\Product\Template
        {
        }
    }
}

namespace TSolution\Comment {
    if (false) {
        class Review extends \Aspro\Premier\Comment\Review
        {
        }
    }
}

namespace TSolution\Search {
    if (false) {
        class Common extends \Aspro\Premier\Search\Common
        {
        }
    }
}

namespace TSolution\Itemaction {
    if (false) {
        class Compare extends \Aspro\Premier\Itemaction\Basket
        {
        }

        class Compare extends \Aspro\Premier\Itemaction\Compare
        {
        }

        class Favorite extends \Aspro\Premier\Itemaction\Favorite
        {
        }

        class Subscribe extends \Aspro\Premier\Itemaction\Subscribe
        {
        }

        class Service extends \Aspro\Premier\Itemaction\Service
        {
        }
    }
}

namespace TSolution\Property {
    if (false) {
        class CustomFilter extends \Aspro\Premier\Property\CustomFilter
        {
        }

        class IBInherited extends \Aspro\Premier\Property\IBInherited
        {
        }
    }
}

namespace TSolution\Video {
    if (false) {
        class Iframe extends \Aspro\Premier\Video\Iframe
        {
        }

        class Block extends \Aspro\Premier\Video\Block
        {
        }
    }
}

namespace TSolution\Popover {
    if (false) {
        class Base extends \Aspro\Premier\Popover\Base
        {
        }

        class Tooltip extends \Aspro\Premier\Popover\Tooltip
        {
        }

        class Orderstatus extends \Aspro\Premier\Popover\Orderstatus
        {
        }
    }
}

namespace TSolution\Scheme {
    if (false) {
        class Offers extends \Aspro\Premier\Scheme\Offers
        {
        }

        class Organization extends \Aspro\Premier\Scheme\Organization
        {
        }

        class Product extends \Aspro\Premier\Scheme\Product
        {
        }

        class Content extends \Aspro\Premier\Scheme\List\Content
        {
        }

        class Blog extends \Aspro\Premier\Scheme\List\Blog
        {
        }

        class News extends \Aspro\Premier\Scheme\List\News
        {
        }
    }
}

namespace TSolution\Banner {
    if (false) {
        class Transparency extends \Aspro\Premier\Banner\Transparency
        {
        }
    }
}

namespace TSolution\Template {
    if (false) {
        class DisplayTypes extends \Aspro\Premier\Template\DisplayTypes
        {
        }

        class Page extends \Aspro\Premier\Template\Page
        {
        }
    }
}

namespace TSolution\Template\Epilog {
    if (false) {
        class Blocks extends \Aspro\Premier\Template\Epilog\Blocks
        {
        }
    }
}

namespace TSolution\Social {
    if (false) {
        class Factory extends \Aspro\Premier\Social\Factory
        {
        }
    }
}

namespace TSolution\Social\Video {
    if (false) {
        class Factory extends \Aspro\Premier\Social\Video\Factory
        {
        }
    }
}

namespace TSolution\Mainpage {
    if (false) {
        class Factory extends \Aspro\Premier\Mainpage\Factory
        {
        }
    }
}

namespace TSolution\Vendor\Include {
    if (false) {
        class Component extends \Aspro\Premier\Vendor\Include\Component
        {
        }
    }
}
