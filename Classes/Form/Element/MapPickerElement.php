<?php

declare(strict_types=1);

namespace PageaDev\RubinEvents\Form\Element;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

final class MapPickerElement extends AbstractFormElement
{
    /**
     * Renders an OSM map picker that stores lat,lon in map_location field
     */
    public function render(): array
    {
        $extConf    = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('rubin_events');

        $result    = $this->initializeResultArray();
        $row       = $this->data['databaseRow'];
        $tableName = $this->data['tableName'];
        $uid       = $row['uid'];

        // parse existing map_location value "lat,lon"
        $mapLocation = $row['map_location'] ?? '';
        $parts       = !empty($mapLocation) ? explode(',', $mapLocation) : [];
        $currentLat  = isset($parts[0]) && is_numeric($parts[0]) ? (float)$parts[0] : null;
        $currentLon  = isset($parts[1]) && is_numeric($parts[1]) ? (float)$parts[1] : null;

        $centerLat  = $currentLat ?? (float)($extConf['defaultLat'] ?? 51.5);
        $centerLon  = $currentLon ?? (float)($extConf['defaultLon'] ?? 9.5);
        $zoom       = $currentLat !== null ? 13 : (int)($extConf['defaultZoom'] ?? 12);
        $hasMarker  = $currentLat !== null ? 'true' : 'false';
        $markerLat  = $currentLat ?? 0.0;
        $markerLon  = $currentLon ?? 0.0;
        $emptyText  = LocalizationUtility::translate('map.empty', 'rubin_events') ?? 'No location set — click on the map';
        $clearText  = LocalizationUtility::translate('map.clear', 'rubin_events') ?? 'Clear location';
        $coordsText = $currentLat !== null
            ? 'Lat: ' . $currentLat . ', Lon: ' . $currentLon
            : $emptyText;

        $mapId      = 'rubin-map-' . md5((string)$uid);
        $fieldName  = htmlspecialchars('data[' . $tableName . '][' . $uid . '][map_location]');
        $fieldValue = htmlspecialchars($mapLocation);

        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addJsFile('EXT:rubin_events/Resources/Public/JavaScript/Lib/Leaflet/leaflet.js');
        $pageRenderer->addJsFile('EXT:rubin_events/Resources/Public/JavaScript/MapPicker.js');

        $result['stylesheetFiles'][] = 'EXT:rubin_events/Resources/Public/JavaScript/Lib/Leaflet/leaflet.css';

        $emptyTextAttr = htmlspecialchars($emptyText);
        $clearTextEsc  = htmlspecialchars($clearText);

        $result['html'] = <<<HTML
<div class="formengine-field-item"
    data-map-picker="true"
    data-map-id="{$mapId}"
    data-center-lat="{$centerLat}"
    data-center-lon="{$centerLon}"
    data-zoom="{$zoom}"
    data-has-marker="{$hasMarker}"
    data-marker-lat="{$markerLat}"
    data-marker-lon="{$markerLon}"
    data-empty-text="{$emptyTextAttr}">
    <input type="hidden" id="{$mapId}-map-location" name="{$fieldName}" value="{$fieldValue}"/>
    <div id="{$mapId}-coords" style="margin-bottom:12px;font-size:12px;color:#aaa;">
        {$coordsText}
    </div>
    <div id="{$mapId}" style="height:450px; width:100%; border-radius:4px; margin-bottom:12px;"></div>
    <button type="button" id="{$mapId}-clear" class="btn btn-default">
        {$clearTextEsc}
    </button>
</div>
HTML;

        return $result;
    }
}