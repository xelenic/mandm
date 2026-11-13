<?php

namespace App\Services\Print;

/**
 * Rewrites a PDF page content stream so nothing inside hidden layers is
 * painted, the same way a PDF viewer treats a layer that is switched off.
 *
 * Per the PDF spec, content in a hidden layer still changes the graphics
 * state (q/Q, cm, clipping, colours, line widths...), only painting is
 * skipped. That matters because tools like Illustrator let q/Q pairs span
 * layer boundaries, so simply deleting the hidden section would corrupt
 * the rest of the page.
 */
final class ContentStreamFilter
{
    /** Path-painting operators; replaced by "n" (end path, paint nothing). */
    private const PAINT_PATH = ['f', 'F', 'f*', 'S', 's', 'B', 'B*', 'b', 'b*'];

    /** Operators that only paint; removed together with their operands. */
    private const PAINT_ONLY = ['Do', 'sh', 'Tj', 'TJ'];

    private const WHITESPACE = " \t\n\r\f\0";

    private const DELIMITERS = '()<>[]{}/%';

    /**
     * @param  list<string>  $hiddenNames  Resource names (without "/") of hidden layers, e.g. ["MC1"].
     */
    public static function suppressPainting(string $content, array $hiddenNames): string
    {
        $hidden = array_flip($hiddenNames);
        $out = [];
        $operands = [];
        $markedContent = []; // stack: true where a hidden layer starts
        $hiddenDepth = 0;
        $i = 0;

        while (($token = self::nextToken($content, $i)) !== null) {
            [$isOperator, $text] = $token;

            if (! $isOperator) {
                $operands[] = $text;

                continue;
            }

            if ($text === 'BI') {
                // Inline image: copy BI ... ID <binary> EI verbatim, or drop it when hidden.
                $start = $i - 2;
                $i = self::inlineImageEnd($content, $i);

                if ($hiddenDepth === 0) {
                    $out[] = substr($content, $start, $i - $start);
                }

                $operands = [];

                continue;
            }

            $line = self::paint($text, $operands, $hiddenDepth > 0);

            if ($text === 'BDC') {
                $startsHidden = count($operands) >= 2
                    && $operands[0] === '/OC'
                    && isset($hidden[ltrim($operands[1], '/')]);
                $markedContent[] = $startsHidden;
                $hiddenDepth += $startsHidden ? 1 : 0;
            } elseif ($text === 'BMC') {
                $markedContent[] = false;
            } elseif ($text === 'EMC' && array_pop($markedContent) === true) {
                $hiddenDepth--;
            }

            if ($line !== null) {
                $out[] = $line;
            }

            $operands = [];
        }

        if ($operands !== []) {
            $out[] = implode(' ', $operands);
        }

        return implode("\n", $out)."\n";
    }

    /**
     * The (possibly neutralised) instruction to write for one operator.
     *
     * @param  list<string>  $operands
     */
    private static function paint(string $operator, array $operands, bool $hidden): ?string
    {
        $verbatim = implode(' ', [...$operands, $operator]);

        if (! $hidden) {
            return $verbatim;
        }

        return match (true) {
            in_array($operator, self::PAINT_PATH, true) => 'n',
            in_array($operator, self::PAINT_ONLY, true) => null,
            // ' and " show text but also move to the next line; keep the movement.
            $operator === "'" => 'T*',
            $operator === '"' => count($operands) >= 2 ? "{$operands[0]} Tw {$operands[1]} Tc T*" : 'T*',
            default => $verbatim,
        };
    }

    /**
     * Next token from $offset: [isOperator, text], or null at the end.
     *
     * @return array{0: bool, 1: string}|null
     */
    private static function nextToken(string $s, int &$offset): ?array
    {
        $length = strlen($s);

        // Skip whitespace and comments.
        while ($offset < $length) {
            if (str_contains(self::WHITESPACE, $s[$offset])) {
                $offset++;
            } elseif ($s[$offset] === '%') {
                $offset += strcspn($s, "\r\n", $offset);
            } else {
                break;
            }
        }

        if ($offset >= $length) {
            return null;
        }

        $start = $offset;
        $char = $s[$offset];

        if ($char === '(') {
            $offset = self::literalStringEnd($s, $offset);

            return [false, substr($s, $start, $offset - $start)];
        }

        if ($char === '<' || $char === '>') {
            if (($s[$offset + 1] ?? '') === $char) {
                $offset += 2;

                return [false, $char.$char];
            }

            // Hex string <...>.
            $close = strpos($s, '>', $offset);
            $offset = $close === false ? $length : $close + 1;

            return [false, substr($s, $start, $offset - $start)];
        }

        if (str_contains('[]{}', $char)) {
            $offset++;

            return [false, $char];
        }

        // Name (/Foo) or a regular token (number, keyword or operator).
        $offset++;

        while ($offset < $length
            && ! str_contains(self::WHITESPACE, $s[$offset])
            && ! str_contains(self::DELIMITERS, $s[$offset])) {
            $offset++;
        }

        $text = substr($s, $start, $offset - $start);

        $isOperand = $char === '/'
            || preg_match('/^[+-]?(\d+\.?\d*|\.\d+)$/', $text) === 1
            || in_array($text, ['true', 'false', 'null'], true);

        return [! $isOperand, $text];
    }

    /** Offset just after a literal string starting at $offset, honouring nesting and escapes. */
    private static function literalStringEnd(string $s, int $offset): int
    {
        $length = strlen($s);
        $depth = 0;

        for ($i = $offset; $i < $length; $i++) {
            $char = $s[$i];

            if ($char === '\\') {
                $i++;
            } elseif ($char === '(') {
                $depth++;
            } elseif ($char === ')' && --$depth === 0) {
                return $i + 1;
            }
        }

        return $length;
    }

    /** Offset just after the "EI" that ends an inline image whose "BI" ended at $offset. */
    private static function inlineImageEnd(string $s, int $offset): int
    {
        $data = preg_match('/\sID\s/', $s, $m, PREG_OFFSET_CAPTURE, $offset) === 1
            ? $m[0][1] + strlen($m[0][0])
            : $offset;

        return preg_match('/\sEI(?=\s|$)/', $s, $m, PREG_OFFSET_CAPTURE, $data) === 1
            ? $m[0][1] + strlen($m[0][0])
            : strlen($s);
    }
}
