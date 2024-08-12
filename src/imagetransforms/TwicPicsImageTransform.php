<?php
/**
 * TwicPics Image Transformer
 *
 * @copyright Copyright (c) 2024 Pixel&Code
 */

namespace pixelcode\imageoptimizetwicpics\imagetransforms;

use Craft;
use craft\elements\Asset;
use craft\helpers\App;
use craft\models\ImageTransform as CraftImageTransformModel;
use nystudio107\imageoptimize\imagetransforms\ImageTransform;

/**
 * Class TwicPicsImageTransform
 * @package pixelcode\imageoptimizetwicpics\imagetransforms
 *
 * @property-read null|string $settingsHtml
 */
class TwicPicsImageTransform extends ImageTransform
{
    // Constants
    // =========================================================================

    protected const TRANSFORM_ATTRIBUTES_MAP = [
        'width' => 'w',
        'height' => 'h',
        'quality' => 'q',
        'format' => 'format',
    ];

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public string $baseUrl = '';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('image-optimize', 'TwicPics');
    }

    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function getTransformUrl(Asset $asset, CraftImageTransformModel|string|array|null $transform): ?string
    {
        $baseUrl = $this->baseUrl;
        $baseUrl = App::parseEnv($baseUrl);

        // Use the parent method to get the asset URI
        $assetUri = $this->getAssetUri($asset);

        // Enhanced logging for debugging
        Craft::debug('Base URL: ' . $baseUrl, __METHOD__);
        Craft::debug('Asset URI: ' . $assetUri, __METHOD__);

        // Ensure assetUri is not null
        if ($assetUri === null) {
            Craft::error('Asset URI is null for asset: ' . $asset->id, __METHOD__);
            return null;
        }

        // Determine the transform mode
        $transformMode = 'cover'; // Default to 'cover'
        if ($transform) {
            // Check if the transform is an array or an instance of CraftImageTransformModel
            if (is_array($transform)) {
                $mode = $transform['mode'] ?? 'cover';
            } elseif ($transform instanceof CraftImageTransformModel) {
                $mode = $transform->mode ?? 'cover';
            } else {
                $mode = 'cover'; // Default mode if nothing is provided
            }

            // Map Craft transform modes to TwicPics modes
            switch ($mode) {
                case 'fit':
                    $transformMode = 'contain';
                    break;
                case 'crop':
                default:
                    $transformMode = 'cover';
                    break;
            }
        }

        // Handle focal point logic
        $focus = '';
        $focalPoint = $asset->getFocalPoint();

        if (!empty($focalPoint)) {
            // If focal point is set, use x and y coordinates
            $focus = "focus={$focalPoint['x']}x{$focalPoint['y']}";
        } else {
            // If no focal point is set, map the default position to TwicPics anchors
            $position = $transform->position ?? 'center-center';
            $focus = $this->mapPositionToTwicPicsFocus($position);
        }

        // Build the transform string based on provided transform settings
        $transformString = $transformMode;
        if (!empty($transform['width']) && !empty($transform['height'])) {
            $transformString .= "={$transform['width']}x{$transform['height']}";
        } else {
            // If width or height is not specified, use the asset dimensions
            $transformString .= "=" . ($transform['width'] ?? $asset->getWidth()) . "x" . ($transform['height'] ?? $asset->getHeight());
        }

        // Build the URL
        $url = "$baseUrl/{$assetUri}?twic=v1/output=auto/{$transformString}";
        if ($focus) {
            $url .= "/$focus";
        }

        // Check if revAssetUrls is enabled
        if (Craft::$app->getConfig()->getGeneral()->revAssetUrls) {
            // Cache-busting parameter using asset's last modified timestamp
            $cacheBust = $asset->dateModified->getTimestamp();
            $url .= "&v={$cacheBust}";
        }

        Craft::debug(
            'TwicPics transform created for: ' . $assetUri . ' - Transform Mode: ' . $transformMode . ' - Transform String: ' . $transformString . ' - Focus: ' . $focus . ' - URL: ' . $url,
            __METHOD__
        );

        return $url;
    }

    /**
     * Maps Craft CMS transform positions to TwicPics focus anchors.
     *
     * @param string $position
     * @return string
     */
    protected function mapPositionToTwicPicsFocus(string $position): string
    {
        $map = [
            'bottom-center' => 'focus=bottom',
            'bottom-left' => 'focus=bottom-left',
            'bottom-right' => 'focus=bottom-right',
            'center-left' => 'focus=left',
            'center-right' => 'focus=right',
            'top-center' => 'focus=top',
            'top-left' => 'focus=top-left',
            'top-right' => 'focus=top-right',
        ];

        // Default is empty (center-center in Craft corresponds to no focus in TwicPics)
        return $map[$position] ?? '';
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('imageoptimize-twicpics/settings/image-transforms/twicpics.twig', [
            'imageTransform' => $this,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $rules = parent::rules();

        return array_merge($rules, [
            [['baseUrl'], 'default', 'value' => ''],
            [['baseUrl'], 'string'],
        ]);
    }
}
