<?php

declare(strict_types=1);

namespace App\Grid\Columns;

use LaraGrid\Columns\Column;

/**
 * An app-defined column type — the whole extension seam in one small class.
 *
 * A custom column names three things and nothing else:
 *   · painterId()  — which client paint routine draws its cells (registered in JS as 'rating')
 *   · editorId()   — which floating editor opens on it, or null for display-only
 *   · parseSpec()  — how typed text becomes the model value, as a {kind} tag whose PHP cast
 *                    ('stars', registered on the CastRegistry) and JS twin must agree
 *
 * The renderer never learns the type: it asks the registry "which painter?" and calls it.
 * See App\Providers\AppServiceProvider for the PHP registrations and
 * resources/views/reports/index.blade.php for their JavaScript twins.
 */
final class RatingColumn extends Column
{
    protected function configureDefaults(): void
    {
        $this->defaultAlign('center');

        if ($this->width === null) {
            $this->width(110);
        }
    }

    public function painterId(): string
    {
        return 'rating';
    }

    /** Reuses the built-in number editor — a custom type need not ship a custom editor. */
    public function editorId(): ?string
    {
        return 'number';
    }

    /**
     * @return array<string, mixed>
     */
    public function parseSpec(): array
    {
        return ['kind' => 'stars'];
    }

    /**
     * Stars are numbers, so a selection of them gets a status-bar Sum / Average like any
     * other numeric column.
     */
    public function isSelectableNumeric(): bool
    {
        return true;
    }

    /**
     * Server rules the TYPE contributes on top of whatever the author declared.
     *
     * @return list<mixed>
     */
    public function implicitRules(): array
    {
        return ['integer', 'min:0', 'max:5'];
    }
}
