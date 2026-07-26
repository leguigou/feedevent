<?php

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

class IcsParser
{
    /**
     * @return array<int, array{
     *     uid: ?string,
     *     title: string,
     *     description: ?string,
     *     date_start: DateTimeImmutable,
     *     date_end: ?DateTimeImmutable,
     *     location: ?string,
     *     organizer: ?string,
     *     source_url: ?string
     * }>
     */
    public function parse(string $contents): array
    {
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;
        $contents = str_replace(["\r\n", "\r"], "\n", $contents);
        $contents = preg_replace("/\n[ \t]/", '', $contents) ?? $contents;

        if (! str_contains($contents, 'BEGIN:VCALENDAR')) {
            throw new InvalidArgumentException('Le fichier ne contient pas de calendrier ICS valide.');
        }

        preg_match_all('/BEGIN:VEVENT\n(.*?)\nEND:VEVENT/s', $contents, $matches);

        if ($matches[1] === []) {
            throw new InvalidArgumentException('Aucun événement n’a été trouvé dans ce fichier.');
        }

        if (count($matches[1]) > 500) {
            throw new InvalidArgumentException('Le fichier dépasse la limite de 500 événements.');
        }

        $events = [];

        foreach ($matches[1] as $block) {
            $properties = $this->properties($block);
            $title = $this->text($properties['SUMMARY'][0]['value'] ?? '');
            $dateStart = $this->date($properties['DTSTART'][0] ?? null);

            if ($title === '' || $dateStart === null) {
                continue;
            }

            $organizer = $properties['ORGANIZER'][0] ?? null;
            $events[] = [
                'uid' => $this->nullableText($properties['UID'][0]['value'] ?? null),
                'title' => $title,
                'description' => $this->nullableText($properties['DESCRIPTION'][0]['value'] ?? null),
                'date_start' => $dateStart,
                'date_end' => $this->date($properties['DTEND'][0] ?? null),
                'location' => $this->nullableText($properties['LOCATION'][0]['value'] ?? null),
                'organizer' => $this->nullableText($organizer['params']['CN'] ?? null),
                'source_url' => $this->nullableText($properties['URL'][0]['value'] ?? null),
            ];
        }

        if ($events === []) {
            throw new InvalidArgumentException('Les événements du fichier n’ont ni titre ni date de début exploitables.');
        }

        return $events;
    }

    /**
     * @return array<string, array<int, array{params: array<string, string>, value: string}>>
     */
    private function properties(string $block): array
    {
        $properties = [];

        foreach (explode("\n", $block) as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }

            [$definition, $value] = explode(':', $line, 2);
            $parts = explode(';', $definition);
            $name = strtoupper((string) array_shift($parts));
            $params = [];

            foreach ($parts as $part) {
                if (! str_contains($part, '=')) {
                    continue;
                }

                [$key, $paramValue] = explode('=', $part, 2);
                $params[strtoupper($key)] = trim($paramValue, '"');
            }

            $properties[$name][] = ['params' => $params, 'value' => $value];
        }

        return $properties;
    }

    /**
     * @param  array{params: array<string, string>, value: string}|null  $property
     */
    private function date(?array $property): ?DateTimeImmutable
    {
        if ($property === null) {
            return null;
        }

        $value = trim($property['value']);
        $timezone = $this->timezone($property['params']['TZID'] ?? null);

        if (($property['params']['VALUE'] ?? null) === 'DATE' || preg_match('/^\d{8}$/', $value)) {
            return DateTimeImmutable::createFromFormat('!Ymd', $value, $timezone) ?: null;
        }

        if (str_ends_with($value, 'Z')) {
            return DateTimeImmutable::createFromFormat(
                '!Ymd\THis\Z',
                $value,
                new DateTimeZone('UTC'),
            ) ?: null;
        }

        foreach (['!Ymd\THis', '!Ymd\THi'] as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $value, $timezone);
            if ($date !== false) {
                return $date;
            }
        }

        return null;
    }

    private function timezone(?string $timezone): DateTimeZone
    {
        try {
            return new DateTimeZone($timezone ?: config('app.timezone'));
        } catch (\Exception) {
            return new DateTimeZone(config('app.timezone'));
        }
    }

    private function nullableText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = $this->text($value);

        return $value === '' ? null : $value;
    }

    private function text(string $value): string
    {
        return trim(strtr($value, [
            '\\n' => "\n",
            '\\N' => "\n",
            '\\,' => ',',
            '\\;' => ';',
            '\\\\' => '\\',
        ]));
    }
}
