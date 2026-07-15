<?php

namespace App\Support;

/**
 * One highlighted file, ready to drop into an <x-source-code> panel.
 */
class HighlightedSource
{
    /**
     * @param  string  $label  Tab caption — the bare filename unless the caller named it.
     * @param  string  $path  Project-relative path, shown under the code.
     * @param  string  $html  Highlighted markup; every value in it is already escaped.
     * @param  int  $lines  Line count, for the gutter.
     */
    public function __construct(
        public readonly string $label,
        public readonly string $path,
        public readonly string $html,
        public readonly int $lines,
    ) {}
}
