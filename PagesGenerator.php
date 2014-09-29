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
use Sculpin\Core\Source\SourceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Pages Generator.
 *
 * @author Marco Vito Moscaritolo <marco@fab.org>
 * @author Beau Simensen <beau@dflydev.com>
 */
class PagesGenerator implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            Sculpin::EVENT_BEFORE_RUN => 'beforeRun',
        );
    }

    public function beforeRun(SourceSetEvent $sourceSetEvent)
    {
        $sourceSet = $sourceSetEvent->sourceSet();

        foreach ($sourceSet->updatedSources() as $source) {

	        /** @var \Sculpin\Core\Source\FileSource $source */
            if ($source->isGenerated()) {
                // Skip generated sources.
                continue;
            }

	        var_dump($source->data()->get('pages'));
	        exit();
            if (!$source->data()->get('pages')) {
                // Skip source that do not have pages.
                continue;
            }

            foreach ($source->data()->get('pages') as $key => $pages) {
                // Clone current search with new sourceId.
                $generatedSource = $source->duplicate($source->sourceId() . ':' . $pages);

                // Set destination is original source.
                $generatedSource->data()->set('destination', $source);

                // Overwrite permalink.
                $generatedSource->data()->set('permalink', $pages);

                // Add pages.
                $generatedSource->data()->set('layout', 'pages');

                // Make sure Sculpin knows this source is generated.
                $generatedSource->setIsGenerated();

                // Add the generated source to the source set.
                $sourceSet->mergeSource($generatedSource);
            }
        }
    }
}
