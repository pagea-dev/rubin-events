<?php

declare(strict_types=1);

/*
 * This file is part of the package pagea-dev/rubin-events.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace PageaDev\RubinEvents\ViewHelpers\Format;

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Formats a date with localized month and weekday names from locallang.xlf.
 *
 * Supported format characters with localization:
 *   M  → abbreviated month name  (month.abbr.1 – month.abbr.12)
 *   F  → full month name         (month.1 – month.12)
 *   D  → abbreviated weekday     (weekday.abbr.1 – weekday.abbr.7, Mon=1)
 *   l  → full weekday name       (weekday.1 – weekday.7)
 *
 * All other PHP date() format characters work as usual.
 *
 * Usage:
 *   <rubin:format.localizedDate date="{event.eventStart}" format="d. M Y" />
 *   {event.eventStart -> rubin:format.localizedDate(format: 'd. M Y')}
 */
class LocalizedDateViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('date', 'mixed', 'Date to format (DateTime, timestamp, or date string)', false, null);
        $this->registerArgument('format', 'string', 'PHP date() format string', false, 'd.m.Y');
    }

    public function render(): string
    {
        $date = $this->arguments['date'] ?? $this->renderChildren();
        $format = (string)$this->arguments['format'];

        if ($date === null || $date === '') {
            return '';
        }

        $date = $this->toDateTime($date);

        $monthNumber   = (int)$date->format('n'); // 1–12
        $weekdayNumber = (int)$date->format('N'); // 1=Mon, 7=Sun

        $localized = [
            'M' => LocalizationUtility::translate('month.abbr.' . $monthNumber,   'RubinEvents') ?? $date->format('M'),
            'F' => LocalizationUtility::translate('month.' . $monthNumber,         'RubinEvents') ?? $date->format('F'),
            'D' => LocalizationUtility::translate('weekday.abbr.' . $weekdayNumber, 'RubinEvents') ?? $date->format('D'),
            'l' => LocalizationUtility::translate('weekday.' . $weekdayNumber,     'RubinEvents') ?? $date->format('l'),
        ];

        $output = '';
        for ($i = 0, $len = strlen($format); $i < $len; $i++) {
            $char = $format[$i];
            if ($char === '\\' && $i + 1 < $len) {
                $output .= $format[++$i];
                continue;
            }
            $output .= $localized[$char] ?? $date->format($char);
        }

        return $output;
    }

    private function toDateTime(mixed $date): \DateTimeInterface
    {
        if ($date instanceof \DateTimeInterface) {
            return $date;
        }
        if (is_int($date) || (is_string($date) && ctype_digit($date))) {
            return new \DateTime('@' . (int)$date);
        }
        return new \DateTime((string)$date);
    }
}
