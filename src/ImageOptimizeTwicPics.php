<?php
namespace pixelcode\imageoptimizetwicpics;

use craft\events\RegisterComponentTypesEvent;
use nystudio107\imageoptimize\services\Optimize;
use pixelcode\imageoptimizetwicpics\imagetransforms\TwicPicsImageTransform;
use yii\base\Event;

class ImageOptimizeTwicPics extends \craft\base\Plugin
{
    public function init(): void
    {
        parent::init();

        Event::on(Optimize::class,
            Optimize::EVENT_REGISTER_IMAGE_TRANSFORM_TYPES,
            static function(RegisterComponentTypesEvent $event) {
                $event->types[] = TwicPicsImageTransform::class;
            }
        );
    }
}
