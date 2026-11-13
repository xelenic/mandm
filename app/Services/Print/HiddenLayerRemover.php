<?php

namespace App\Services\Print;

use setasign\Fpdi\PdfParser\Filter\FilterException;
use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfParser\Type\PdfArray;
use setasign\Fpdi\PdfParser\Type\PdfDictionary;
use setasign\Fpdi\PdfParser\Type\PdfIndirectObjectReference;
use setasign\Fpdi\PdfParser\Type\PdfName;
use setasign\Fpdi\PdfParser\Type\PdfStream;
use setasign\Fpdi\PdfParser\Type\PdfType;
use setasign\Fpdi\PdfReader\PdfReader;

/**
 * Keeps layers that are switched off in a PDF invisible after its pages
 * are imported with FPDI.
 *
 * FPDI copies a page's drawing but not the document's layer on/off
 * settings, so a hidden layer (e.g. the full-page helper layer in the
 * card artwork) would suddenly show. This returns a copy of the PDF in
 * which painting inside hidden layers is suppressed (see
 * ContentStreamFilter). The copy is the original bytes plus a standard
 * PDF incremental update; the source file is never changed.
 */
final class HiddenLayerRemover
{
    public function apply(string $pdf): string
    {
        $parser = new PdfParser(StreamReader::createByString($pdf));
        $hiddenGroups = $this->hiddenGroups($parser);

        if ($hiddenGroups === []) {
            return $pdf;
        }

        $reader = new PdfReader($parser);
        $updates = [];

        for ($number = 1; $number <= $reader->getPageCount(); $number++) {
            $page = $reader->getPage($number);
            $names = $this->hiddenPropertyNames($page->getAttribute('Resources'), $parser, $hiddenGroups);

            if ($names === []) {
                continue;
            }

            $streams = $this->contentStreams($page->getPageDictionary(), $parser);

            if ($streams === null) {
                continue; // Can't safely rewrite this page; leave it untouched.
            }

            $content = implode("\n", array_column($streams, 'content'));
            $filtered = ContentStreamFilter::suppressPainting($content, $names);

            foreach ($streams as $index => $stream) {
                // All of the page's content goes into its first stream; the rest are emptied.
                $updates[$stream['ref']->value] = [$stream['ref']->generationNumber, $index === 0 ? $filtered : ''];
            }
        }

        return $updates === [] ? $pdf : $this->appendUpdate($pdf, $parser, $updates);
    }

    /**
     * Object numbers of the layers (optional content groups) that are off by default.
     *
     * @return list<int>
     */
    private function hiddenGroups(PdfParser $parser): array
    {
        $properties = PdfType::resolve(PdfDictionary::get($parser->getCatalog(), 'OCProperties'), $parser);

        if (! $properties instanceof PdfDictionary) {
            return [];
        }

        $config = PdfType::resolve(PdfDictionary::get($properties, 'D'), $parser);

        if (! $config instanceof PdfDictionary) {
            return [];
        }

        $baseState = PdfType::resolve(PdfDictionary::get($config, 'BaseState'), $parser);

        if ($baseState instanceof PdfName && $baseState->value === 'OFF') {
            $on = $this->references(PdfDictionary::get($config, 'ON'), $parser);

            return array_values(array_diff($this->references(PdfDictionary::get($properties, 'OCGs'), $parser), $on));
        }

        return $this->references(PdfDictionary::get($config, 'OFF'), $parser);
    }

    /**
     * Names in the page's /Properties resources that point at hidden layers.
     *
     * @param  list<int>  $hiddenGroups
     * @return list<string>
     */
    private function hiddenPropertyNames(mixed $resources, PdfParser $parser, array $hiddenGroups): array
    {
        $resources = PdfType::resolve($resources ?? PdfDictionary::create(), $parser);
        $properties = $resources instanceof PdfDictionary
            ? PdfType::resolve(PdfDictionary::get($resources, 'Properties'), $parser)
            : null;

        if (! $properties instanceof PdfDictionary) {
            return [];
        }

        $names = [];

        foreach ($properties->value as $name => $value) {
            if ($value instanceof PdfIndirectObjectReference && in_array($value->value, $hiddenGroups, true)) {
                $names[] = $name;

                continue;
            }

            // An optional content membership dictionary groups several layers.
            $value = PdfType::resolve($value, $parser);

            if ($value instanceof PdfDictionary && $this->isHiddenMembership($value, $parser, $hiddenGroups)) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * @param  list<int>  $hiddenGroups
     */
    private function isHiddenMembership(PdfDictionary $dictionary, PdfParser $parser, array $hiddenGroups): bool
    {
        $type = PdfType::resolve(PdfDictionary::get($dictionary, 'Type'), $parser);

        if (! $type instanceof PdfName || $type->value !== 'OCMD') {
            return false;
        }

        $groups = PdfDictionary::get($dictionary, 'OCGs');
        $members = $groups instanceof PdfIndirectObjectReference ? [$groups->value] : $this->references($groups, $parser);

        if ($members === []) {
            return false;
        }

        $on = array_map(fn (int $group): bool => ! in_array($group, $hiddenGroups, true), $members);
        $policy = PdfType::resolve(PdfDictionary::get($dictionary, 'P'), $parser);

        $visible = match ($policy instanceof PdfName ? $policy->value : 'AnyOn') {
            'AllOn' => ! in_array(false, $on, true),
            'AnyOff' => in_array(false, $on, true),
            'AllOff' => ! in_array(true, $on, true),
            default => in_array(true, $on, true),
        };

        return ! $visible;
    }

    /**
     * The page's content streams with their decoded content, or null if any
     * of them can't be read (then the page is left alone).
     *
     * @return list<array{ref: PdfIndirectObjectReference, content: string}>|null
     */
    private function contentStreams(PdfDictionary $page, PdfParser $parser): ?array
    {
        $contents = PdfDictionary::get($page, 'Contents');

        if ($contents instanceof PdfIndirectObjectReference) {
            $resolved = PdfType::resolve($contents, $parser);
            $refs = $resolved instanceof PdfArray ? $resolved->value : [$contents];
        } else {
            $refs = $contents instanceof PdfArray ? $contents->value : [];
        }

        $streams = [];

        foreach ($refs as $ref) {
            if (! $ref instanceof PdfIndirectObjectReference) {
                return null;
            }

            $stream = PdfType::resolve($ref, $parser);

            if (! $stream instanceof PdfStream) {
                return null;
            }

            try {
                $streams[] = ['ref' => $ref, 'content' => $stream->getUnfilteredStream()];
            } catch (FilterException) {
                return null;
            }
        }

        return $streams === [] ? null : $streams;
    }

    /**
     * @return list<int>
     */
    private function references(mixed $value, PdfParser $parser): array
    {
        $value = PdfType::resolve($value ?? PdfArray::create(), $parser);

        if (! $value instanceof PdfArray) {
            return [];
        }

        return array_values(array_map(
            fn (PdfIndirectObjectReference $ref): int => $ref->value,
            array_filter($value->value, fn ($item): bool => $item instanceof PdfIndirectObjectReference),
        ));
    }

    /**
     * Append new versions of the given stream objects as a PDF incremental update.
     *
     * @param  array<int, array{0: int, 1: string}>  $updates  object number => [generation, content]
     */
    private function appendUpdate(string $pdf, PdfParser $parser, array $updates): string
    {
        preg_match_all('/startxref\s+(\d+)/', $pdf, $matches);
        $previousXref = (int) end($matches[1]);

        $trailer = $parser->getCrossReference()->getTrailer();
        $size = PdfType::resolve(PdfDictionary::get($trailer, 'Size'), $parser)->value;
        $root = PdfDictionary::get($trailer, 'Root');

        $out = rtrim($pdf, "\r\n")."\n";
        $offsets = [];

        ksort($updates);

        foreach ($updates as $number => [$generation, $content]) {
            $data = gzcompress($content);
            $offsets[$number] = [strlen($out), $generation];
            $out .= "{$number} {$generation} obj\n<</Length ".strlen($data)."/Filter/FlateDecode>>\nstream\n{$data}\nendstream\nendobj\n";
        }

        $xrefOffset = strlen($out);
        $out .= "xref\n";

        foreach ($offsets as $number => [$offset, $generation]) {
            $out .= "{$number} 1\n".sprintf("%010d %05d n\r\n", $offset, $generation);
        }

        return $out."trailer\n<</Size {$size}/Root {$root->value} {$root->generationNumber} R/Prev {$previousXref}>>\n"
            ."startxref\n{$xrefOffset}\n%%EOF\n";
    }
}
