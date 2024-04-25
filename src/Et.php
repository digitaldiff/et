<?php

namespace digitaldiff\et;

use Craft;
use craft\base\Plugin;
use craft\elements\Entry;
use craft\events\ModelEvent;
use yii\base\Event;
use digitaldiff\et\services\GraphQlService;

/**
 * et plugin
 *
 * @method static Et getInstance()
 */
class Et extends Plugin
{
    public string $schemaVersion = '1.0.1';
    public bool $hasCpSettings = false;

    public static function config(): array
    {
        return [
            'components' => [
                // Define component configs here...
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        // Defer most setup tasks until Craft is fully initialized
        Craft::$app->onInit(function() {
            $this->attachEventHandlers();
            // ...
        });
    }

    private function attachEventHandlers(): void
    {
        Event::on(
            Entry::class,
            Entry::EVENT_AFTER_SAVE,
            function (ModelEvent $event) {

                $graphQL = new GraphQlService();
                $entryId = $graphQL->getEntryId();

                if ($entryId > 0) {
                    $graphQL->updateEntry();
                } else {
                    $graphQL->newEntry();
                }

            }
        );
    }
}
