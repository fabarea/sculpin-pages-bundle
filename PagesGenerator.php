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
use Sculpin\Core\Source\SourceSet;
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

        foreach ($sourceSet->updatedSources() as $source) {
            /** @var \Sculpin\Core\Source\FileSource $source */

            if ($source->isGenerated()) {
                // Skip generated sources.
                continue;
            }

            // Only takes pages that can be formatted (AKA *.md) and skip images, CSS, JS, ...
            if ($source->canBeFormatted()) {

                // Dynamically set the permalink.
                // $pathSegments corresponds to the file path segment exploded ex: 01-foo/10-bar.md
                $pathSegments = explode('/', $source->relativePathname());
                if (!empty($pathSegments)) {

                    // Takes the last part of the segment which corresponds to the file name
                    $shifted = array_shift($pathSegments);
                    if ($shifted === '_pages') {

                        $slug = $this->getSlug($pathSegments);
                        $title = $source->data()->get('title');
                        $permalink = $this->getPermalink($pathSegments);

                        $source->data()->set('slug', $slug);
                        $source->data()->set('permalink', $permalink);

                        // Initialize the items
                        $items = array(
                            'title' => $title,
                            #'nav_name' => $this->getNavigationName($slug), // see if useful?
                            'slug' => $slug,
                            'items' => array(),
                        );
                        $currentPosition = 0;

                        // Build the menu structure
                        $this->feedMenu($this->menu, $slug, $currentPosition, $items);
                    }
                }
            }
        }

        $this->setMenu($sourceSet);
    }

    /**
     * Now that the menu structure has been created, inject it back to the page.
     *
     * @param SourceSet $sourceSet
     * @return void
     */
    protected function setMenu(SourceSet $sourceSet)
    {

        // Second loop to set the menu which was initialized during the first loop
        foreach ($sourceSet->updatedSources() as $source) {
            /** @var \Sculpin\Core\Source\FileSource $source */

            if ($source->isGenerated()) {
                // Skip generated sources.
                continue;
            }

            // Only takes pages that can be formatted (AKA *.md)
            if ($source->canBeFormatted()) {

                // Dynamically set the permalink.
                $pathSegments = explode('/', $source->relativePathname());
                if (!empty($pathSegments)) {
                    $shifted = array_shift($pathSegments);
                    if ($shifted === '_pages') {
                        $source->data()->set('menu', $this->menu);
                    }
                }
            }
        }

    }

    /**
     * Create a structure for the menu.
     *
     * array(
     *   'foo' => array(
     *     'title' => 'Foo',
     *     'slug' => 'foo',
     *     'items' => array(
     *       'bar' => array(
     *         'title' => 'Bar',
     *         'slug' => 'foo/bar',
     *         'items' => array(),
     *        ),
     *      ),
     *   ),
     * );
     *
     * @param array $menu
     * @param string $slug
     * @param int $currentPosition
     * @param array $items
     * @return void
     */
    protected function feedMenu(array &$menu, $slug, $currentPosition = 0, array $items)
    {
        // Explode the slug
        $segments = explode('/', $slug);
        $slugFirst = array_shift($segments);

        if (empty($segments)) {

            // The position is needed to tell apart the first level
            if ($currentPosition > 0) {
                $menu['items'][$slugFirst] = $items;
            } else {
                if (isset($menu[$slugFirst])) {

                    // Only initialize the required value.
                    $menu[$slugFirst]['title'] = $items['title'];
                    $menu[$slugFirst]['nav_name'] = $items['nav_name'];
                    $menu[$slugFirst]['slug'] = $items['slug'];
                } else {
                    // Here we can simply put the items as nothing has been yet intialized.
                    $menu[$slugFirst] = $items;
                }
            }
        } else {

            // Initialize array here.
            // It happens Sculpin servers the children pages before the parents
            // foo/bar.md -> "bar.md" is given first and "foo" must be initialized.
            if (!isset($menu[$slugFirst])) {
                $menu[$slugFirst] = array(
                    'items' => array(),
                );
            }

            $remainingSlug = implode('/', $segments);
            $currentPosition++;
            $this->feedMenu($menu[$slugFirst], $remainingSlug, $currentPosition, $items); // recursion here.
        }
    }

    /**
     * Return the "nav_name". Simply take the last part of the slug.
     *
     * @param string $slug
     * @return string
     */
    protected function getNavigationName($slug)
    {
        $slugSegments = explode('/', $slug);
        return array_pop($slugSegments);
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