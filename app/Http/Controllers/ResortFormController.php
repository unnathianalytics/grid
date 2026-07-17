<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Resort;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use LaraForm\Fields\CheckboxField;
use LaraForm\Fields\CheckboxGroupField;
use LaraForm\Fields\DateField;
use LaraForm\Fields\IntegerField;
use LaraForm\Fields\MoneyField;
use LaraForm\Fields\MultiSelectField;
use LaraForm\Fields\RadioField;
use LaraForm\Fields\SearchSelectField;
use LaraForm\Fields\SelectField;
use LaraForm\Fields\TextareaField;
use LaraForm\Fields\TextField;
use LaraForm\Fields\ToggleField;
use LaraForm\Fields\YesNoField;
use LaraForm\Form;
use LaraForm\Panel;

/**
 * New/Edit Resort — the LaraForm showcase: every field type on one form, entirely
 * keyboard-drivable (Enter advances, dates type freeform, selects open on focus, the
 * Manager field opens a package-rendered contact panel), no Livewire and no page JS.
 *
 * Grid tie-in: active maps to the visibility column and room_rate to comparison_tariff,
 * so a saved resort shows up correctly in the LaraGrid demo at /resorts.
 */
class ResortFormController extends Controller
{
    private const TYPES = ['Homestay', 'Resort', 'Hotel', 'Guest House', 'Package', 'Activities', 'Hospitality', 'Star Category'];

    private const AMENITIES = ['pool' => 'Swimming Pool', 'spa' => 'Spa', 'gym' => 'Gym', 'wifi' => 'Free WiFi', 'restaurant' => 'Restaurant', 'bar' => 'Bar', 'kids' => 'Kids Club', 'beach' => 'Beach Access'];

    private const FACILITIES = ['parking' => 'Parking', 'ev' => 'EV Charging', 'pets' => 'Pet Friendly', 'wheelchair' => 'Wheelchair Access'];

    private const STARS = ['3' => '3 Star', '4' => '4 Star', '5' => '5 Star', 'unrated' => 'Unrated'];

    private const CITIES = [
        'Agra', 'Ahmedabad', 'Bengaluru', 'Bhopal', 'Chandigarh', 'Chennai', 'Coimbatore',
        'Darjeeling', 'Dehradun', 'Goa', 'Gokarna', 'Hyderabad', 'Jaipur', 'Kochi', 'Kolkata',
        'Leh', 'Lucknow', 'Madurai', 'Manali', 'Mumbai', 'Munnar', 'Mysuru', 'Nainital',
        'Ooty', 'Pondicherry', 'Pune', 'Rishikesh', 'Shimla', 'Udaipur', 'Varanasi', 'Wayanad',
    ];

    public function create(): View
    {
        return view('resorts.form', [
            'form' => $this->buildForm(),
            'heading' => 'New Resort',
            'latest' => Resort::query()->latest('id')->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $resort = $this->persist($request, new Resort());

        return redirect()->route('resorts.create')
            ->with('status', "Resort “{$resort->name}” saved (#{$resort->id}).");
    }

    public function edit(Resort $resort): View
    {
        return view('resorts.form', [
            'form' => $this->buildForm($resort),
            'heading' => "Edit Resort — {$resort->name}",
            'latest' => null,
        ]);
    }

    public function update(Request $request, Resort $resort): RedirectResponse
    {
        $this->persist($request, $resort);

        return redirect()->route('resorts.edit', $resort)
            ->with('status', "Resort “{$resort->name}” updated.");
    }

    /** Remote source for the city search-select: GET /api/cities?q= → [{value, label}]. */
    public function cities(Request $request): JsonResponse
    {
        $q = mb_strtolower(trim((string) $request->query('q', '')));

        $matches = array_values(array_filter(
            self::CITIES,
            fn (string $city): bool => $q === '' || str_contains(mb_strtolower($city), $q),
        ));

        return response()->json(array_map(
            fn (string $city): array => ['value' => $city, 'label' => $city],
            array_slice($matches, 0, 12),
        ));
    }

    /**
     * The whole form, declared fluently — create and edit differ only in defaults,
     * action and method. Every LaraForm field type appears once.
     */
    private function buildForm(?Resort $resort = null): Form
    {
        $editing = $resort !== null;

        return Form::make()
            ->action($editing ? route('resorts.update', $resort) : route('resorts.store'))
            ->method($editing ? 'PUT' : 'POST')
            ->submit($editing ? 'Update Resort' : 'Save Resort')
            ->fields([
                TextField::make('name')->label('Resort name')->required()->autofocus()
                    ->placeholder('e.g. Coral Cove Beach Resort')
                    ->rules('max:255')
                    ->default($resort?->name)->class('span-2'),

                TextField::make('shortcode')->label('Short code')->upper()
                    ->minLength(2)->maxLength(8)
                    ->help('Forced UPPERCASE · 2–8 characters')->default($resort?->shortcode),

                SelectField::make('type')->label('Type')->placeholder('Pick a type…')
                    ->options(self::TYPES)->required()->default($resort?->type),

                SearchSelectField::make('city')->label('City')
                    ->optionsUrl(route('cities.search'))
                    ->placeholder('Type to search…')
                    ->help('Remote combobox — fetches ?q= as you type')
                    ->default($resort?->city ? ['value' => $resort->city, 'label' => $resort->city] : null),

                DateField::make('opened_on')->label('Opened on')->financialYear(4)
                    ->help('Freeform: 31/12 · 31.12.24 · 311224 — FY-aware year inference')
                    ->default($resort?->opened_on),

                MoneyField::make('room_rate')->label('Room rate / night')->indian()->prefix('₹')
                    ->help('₹1000–₹3000 for the demo — Enter refuses values outside')
                    ->min(1000)
                    ->max(3000)
                    ->required()
                    ->default($resort?->room_rate),

                IntegerField::make('rooms')->label('Total rooms')->min(0)->default($resort?->rooms),

                RadioField::make('star_rating')->label('Star rating')->inline()
                    ->options(self::STARS)->default($resort?->star_rating),

                MultiSelectField::make('amenities')->label('Amenities')->max(5)
                    ->options(self::AMENITIES)
                    ->help('Enter toggles · Backspace removes the last chip · max 5')
                    ->default($resort?->amenities)->class('span-2'),

                CheckboxGroupField::make('facilities')->label('Facilities')->inline()
                    ->options(self::FACILITIES)->default($resort?->facilities)->class('span-2'),

                YesNoField::make('gst_applicable')->label('GST applicable?')
                    ->help('Type y / n — Space or ↑↓ toggles')
                    ->default($resort?->gst_applicable),

                ToggleField::make('active')->label('Visible on site')
                    ->help('Maps to the grid’s visibility column')
                    ->default($editing ? $resort->visibility === 'show' : true),

                CheckboxField::make('featured')->label('Feature on homepage')
                    ->default($resort?->featured),

                TextField::make('manager')->label('Manager')
                    ->placeholder('Name — Enter opens contact panel')
                    ->default($resort?->manager)
                    ->opensPanel(
                        Panel::make('manager-contact')
                            ->title('Manager contact')
                            ->fields([
                                TextField::make('contact_phone')->label('Phone')->type('tel')
                                    ->default($resort?->contact_phone),
                                TextField::make('contact_email')->label('Email')->email()
                                    ->default($resort?->contact_email),
                            ]),
                    ),

                TextareaField::make('description')->label('Description')->rows(3)
                    ->placeholder('Enter = newline here; Ctrl+Enter advances')
                    ->default($resort?->description)->class('span-2'),
            ]);
    }

    private function persist(Request $request, Resort $resort): Resort
    {
        // One source of truth: the form definition supplies presence + type + option
        // rules (required, string, numeric+min/max, date_format:Y-m-d, in:options,
        // array + members, in:0,1 — panel fields included). Only app-specific
        // constraints ride on top.
        $data = $this->buildForm()->validate($request, [
            'city' => [Rule::in(self::CITIES)],
            'manager' => ['max:255'],
            'contact_phone' => ['max:30'],
            'description' => ['max:2000'],
        ]);

        // 'active' is form-only (it maps to visibility below), never a column.
        $attributes = collect($data)->except('active')->all();

        $resort->fill([
            ...$attributes,
            'slug' => str($data['name'])->slug()->value(),
            'gst_applicable' => isset($data['gst_applicable']) ? (bool) $data['gst_applicable'] : null,
            'featured' => (bool) $data['featured'],
            // The two mappings that keep the LaraGrid demo page coherent:
            'visibility' => $data['active'] === '1' ? 'show' : 'hide',
            'comparison_tariff' => isset($data['room_rate']) ? (int) round((float) $data['room_rate']) : null,
        ]);

        $resort->save();

        return $resort;
    }
}
