<?php
/**
 * Slova_Blog
 * Copyright © Slova. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Slova\Blog\Model;

use Magento\Framework\Locale\ResolverInterface;

/**
 * Post dates in the store language ("May 17, 2026", "17 mai 2026") and in ISO 8601.
 *
 * The date is shown as it was entered in the admin, without a time zone shift.
 */
class DateFormatter
{
    /**
     * @param ResolverInterface $localeResolver
     */
    public function __construct(
        private readonly ResolverInterface $localeResolver
    ) {
    }

    /**
     * Long date in the store locale, or '' when the date is empty or invalid.
     *
     * @param string|null $date
     * @return string
     */
    public function format(?string $date): string
    {
        $dateTime = $this->toDateTime($date);
        if ($dateTime === null) {
            return '';
        }
        $formatter = new \IntlDateFormatter(
            $this->localeResolver->getLocale(),
            \IntlDateFormatter::LONG,
            \IntlDateFormatter::NONE,
            'UTC'
        );
        $formatted = $formatter->format($dateTime);
        return is_string($formatted) ? $formatted : $dateTime->format('Y-m-d');
    }

    /**
     * ISO 8601 date-time, or ''.
     *
     * @param string|null $date
     * @return string
     */
    public function iso(?string $date): string
    {
        $dateTime = $this->toDateTime($date);
        return $dateTime ? $dateTime->format('c') : '';
    }

    /**
     * Store locale as a BCP 47 language tag, e.g. "en-US".
     *
     * @return string
     */
    public function getLanguageTag(): string
    {
        return str_replace('_', '-', (string)$this->localeResolver->getLocale());
    }

    /**
     * Parse a database date.
     *
     * @param string|null $date
     * @return \DateTimeImmutable|null
     */
    private function toDateTime(?string $date): ?\DateTimeImmutable
    {
        if ($date === null || trim($date) === '') {
            return null;
        }
        try {
            return new \DateTimeImmutable($date, new \DateTimeZone('UTC'));
        } catch (\Exception) {
            return null;
        }
    }
}
