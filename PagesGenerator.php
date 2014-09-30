<?php

/*
 * This file is a part of Sculpin.
 *
 * (c) Dragonfly Development Inc.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fab\Sculpin\Bundle\PagesBundle;

use Sculpin\Core\Sculpin;
use Sculpin\Core\Event\SourceSetEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Pages Generator.
 *
 * @author Fabien Udriot <fabien@omic.ch>
 */
class PagesGenerator implements EventSubscriberInterface
{

    /**
     * @var array
     */
    protected $menu = array();

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            Sculpin::EVENT_BEFORE_RUN => 'beforeRun',
        );
    }

    /**
     * @param SourceSetEvent $sourceSetEvent
     * @return void
     */
    public function beforeRun(SourceSetEvent $sourceSetEvent)
    {
        $sourceSet = $sourceSetEvent->sourceSet();

//        $this->menu = array(); // reset value
        $menu = array();
        foreach ($sourceSet->updatedSources() as $source) {
            /** @var \Sculpin\Core\Source\FileSource $source */

            if ($source->isGenerated()) {
                // Skip generated sources.
                continue;
            }

            // Only takes pages that can be formatted (AKA *.md)
            if ($source->canBeFormatted()) {

                // Dynamically set the permalink.
                $segments = explode('/', $source->relativePathname());
                if (!empty($segments)) {
                    $shifted = array_shift($segments);
                    if ($shifted === '_pages') {

                        $slug = $this->getSlug($segments);
                        $navigationName = $this->getNavigationName($segments);
                        $permalink = $this->getPermalink($segments);

                        $source->data()->set('nav_name', $navigationName);
                        $source->data()->set('slug', $slug);
			            $source->data()->set('permalink', $permalink);

                        $slugs = explode('/', $slug);

                        // @todo finish me!
                        // Be careful of side effect when watching!
                        // Perhaps tree loops required!?
                        array(

                            'accueil' => array(
                                'title' => 'Accueil',
                                'slug' => 'accueil',
                                'items' => array(),
                            ),
                            'nouveaute' => array(
                                'title' => 'Nouveauté',
                                'slug' => 'nouveaute',
                                'items' => array(

                                    'foo' => array(
                                        'title' => 'Foo',
                                        'slug' => 'nouveaute/foo',
                                        'items' => array(),
                                    ),

                                    'bar' => array(
                                        'title' => 'Bar',
                                        'slug' => 'nouveaute/bar',
                                        'items' => array(),
                                    ),
                                ),
                            ),
                        );
                        $menu = array();
//                        foreach ($slugs as $part) {
//                            $menu = array('nav_name' => $part, $it)
//                        }

                        $menu = $this->feedMenu($slugs);
                        $this->mergeMenu($menu);
	                }
                }
            }
        }

        // Second loop to set the menu
        foreach ($sourceSet->updatedSources() as $source) {
            /** @var \Sculpin\Core\Source\FileSource $source */

            if ($source->isGenerated()) {
                // Skip generated sources.
                continue;
            }

            // Only takes pages that can be formatted (AKA *.md)
            if ($source->canBeFormatted()) {

                // Dynamically set the permalink.
                $segments = explode('/', $source->relativePathname());
                if (!empty($segments)) {
                    $shifted = array_shift($segments);
                    if ($shifted === '_pages') {
                        $source->data()->set('menu', $this->menu);
                    }
                }
            }
        }
    }

    /**
     * Feed the menu property
     *
     * @param array $slugs
     * @param array $menu
     * @return array
     */
    protected function feedMenu(array $slugs, $menu = array())
    {
        if (count($slugs) === 1) {
            $currentSlug = array_shift($slugs);
            $menu = array('title' => $currentSlug, 'items' => $menu);
        } else {
            $currentSlug = array_shift($slugs);
            $menu = array('title' => $currentSlug, 'items' => $this->feedMenu($slugs, $menu));
        }
        return $menu;
    }

    /**
     * Merge the menu
     *
     * @param array $menu
     * @return array
     */
    protected function mergeMenu($menu)
    {
        if (empty($menu['items'])) {
//            $position = array_search($menu['title'], $this->menu);
//            if ($position === FALSE) {
                $this->menu[] = $menu;
//            } else {
//
//            }
        } else {


//            print_r($this->menu);
//            print_r($menu);
////            $this->mergeMenu($menu['items'])
//            exit();

        }
    }


    /**
     * Return the "nav_name".
     *
     * @param array $segments
     * @return string
     */
    protected function getNavigationName(array $segments)
    {
        $poppedSegment = array_pop($segments);
        $segment = $this->removePrefix($poppedSegment);
        $info = pathinfo($segment);
        return basename($segment, '.' . $info['extension']);
    }

    /**
     * Return the "permalink".
     *
     * @param array $segments
     * @return string
     */
    protected function getSlug(array $segments)
    {

        $prettySegments = array();

        // Remove the prefix number e.g. 01-foo.md
        foreach ($segments as $segment) {
            $prettySegments[] = $this->removePrefix($segment);
        }

        // @todo textile case? Let see if there is the need.
        $slug = implode('/', $prettySegments);
        return str_replace('.md', '', $slug); // last clean-up.
    }

    /**
     * Return the "permalink".
     *
     * @param array $segments
     * @return string
     */
    protected function getPermalink(array $segments)
    {

        $prettySegments = array();

        // Remove the prefix number e.g. 01-foo.md
        foreach ($segments as $segment) {
            $prettySegments[] = $this->removePrefix($segment);
        }

        // @todo textile case? Let see if there is the need.
        $permalink = implode('/', $prettySegments);
        return str_replace('.md', '/index.html', $permalink); // last clean-up.
    }

    /**
     * @param string $segment
     * @return string
     */
    protected function removePrefix($segment)
    {
        return preg_replace('/^[0-9]{2}-/', '', $segment);
    }

}
function array_merge_recursive_new()
{
    $arrays = func_get_args();
    $base = array_shift($arrays);

    foreach ($arrays as $array) {
        reset($base); //important
        while (list($key, $value) = @each($array)) {
            if (is_array($value) && @is_array($base[$key])) {
                $base[$key] = array_merge_recursive_new($base[$key], $value);
            } else {
                if(isset($base[$key]) && is_int($key)) {
                    $key++;
                }
                $base[$key] = $value;
            }
        }
    }

    return $base;
}