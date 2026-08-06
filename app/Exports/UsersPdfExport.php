<?php

namespace App\Exports;

use App\Enums\BrandingIdentityMode;
use App\Models\User;
use App\Settings\BrandingSettings;
use App\Settings\GeneralSettings;
use App\Settings\SettingsResolver;
use App\Support\Documents\DocumentStylesheet;
use App\Support\InstantFormatter;
use App\Support\Users\SelectedUsers;
use Illuminate\Support\Facades\Storage;

/**
 * A printable document of the users a request selected.
 *
 * Like the spreadsheet, it carries only what the table already shows: no
 * password, token, or other credential material reaches the page.
 *
 * Unlike the spreadsheet, it is a picture of what an operator saw rather than a
 * data file, so its timestamps are rendered as text in the reader's own zone
 * instead of as machine-readable UTC instants.
 */
final readonly class UsersPdfExport
{
    public function __construct(
        private InstantFormatter $instantFormatter,
        private DocumentStylesheet $stylesheet,
    ) {}

    /**
     * Everything the document template renders.
     *
     * @param  list<string>  $ids
     * @param  string  $timeZone  The reader's own zone, since Chromium runs in the server's.
     * @return array{
     *     title: string,
     *     stylesheet: string,
     *     companyName: string,
     *     logoPath: string|null,
     *     generatedAt: string,
     *     generatedBy: string,
     *     headings: list<string>,
     *     rows: list<array{name: string, email: string, role: string, status: string, createdAt: string}>,
     * }
     */
    public function viewData(array $ids, string $timeZone, User $actor): array
    {
        $general = SettingsResolver::tryResolve(GeneralSettings::class);
        $dateFormat = $general?->dateFormat->value ?? 'Y-m-d';
        $locale = app()->getLocale();

        return [
            'title' => __('master_data.user.title'),
            'stylesheet' => $this->stylesheet->contents(),
            'companyName' => $this->companyName($general),
            'logoPath' => $this->logoPath(),
            'generatedAt' => $this->instantFormatter->format(
                now()->toDateTimeImmutable(),
                $dateFormat,
                $timeZone,
                $locale,
            ),
            'generatedBy' => $actor->name,
            'headings' => $this->headings(),
            'rows' => $this->rows($ids, $dateFormat, $timeZone, $locale),
        ];
    }

    /**
     * The column titles, localized like the table they came from.
     *
     * @return list<string>
     */
    private function headings(): array
    {
        return [
            __('master_data.user.label.name'),
            __('master_data.user.label.email'),
            __('master_data.user.label.role'),
            __('master_data.user.label.status'),
            __('master_data.user.label.created_at'),
        ];
    }

    /**
     * @param  list<string>  $ids
     * @return list<array{name: string, email: string, role: string, status: string, createdAt: string}>
     */
    private function rows(array $ids, string $dateFormat, string $timeZone, string $locale): array
    {
        return array_values(
            SelectedUsers::inSelectionOrder($ids)
                ->map(fn (User $user): array => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $this->roleLabel($user),
                    'status' => $user->status->label(),
                    'createdAt' => $this->instantFormatter->format(
                        $user->created_at,
                        $dateFormat,
                        $timeZone,
                        $locale,
                    ),
                ])
                ->all(),
        );
    }

    /**
     * The name the document is published under.
     *
     * A fresh installation has persisted neither settings group, so the
     * configured application name is the last resort rather than a formality.
     */
    private function companyName(?GeneralSettings $general): string
    {
        $branding = SettingsResolver::tryResolve(BrandingSettings::class);

        if ($branding instanceof BrandingSettings && $branding->companyName !== '') {
            return $branding->companyName;
        }

        return $general instanceof GeneralSettings
            ? $general->applicationName
            : (string) config('app.name');
    }

    /**
     * The role label, or the words this application uses for having none.
     *
     * The translator's contract allows a key to hold an array, so the string
     * case is established here rather than assumed; an unresolvable key falls
     * back to itself, exactly as the client-side translator does.
     */
    private function roleLabel(User $user): string
    {
        $label = $user->primaryRole()?->label();

        if ($label !== null) {
            return $label;
        }

        $missing = __('master_data.user.role_missing');

        return is_string($missing) ? $missing : 'master_data.user.role_missing';
    }

    /**
     * The logo as a path on disk, or null when the document should print a name.
     *
     * Chromium is handed the file rather than a URL: the template is rendered
     * from an HTML string, so a URL would make the browser fetch back into the
     * application, which is exactly the round trip that fails silently behind a
     * firewall. A fresh installation has no logo at all, which is why the name
     * has to be a real fallback rather than a theoretical one.
     */
    private function logoPath(): ?string
    {
        $branding = SettingsResolver::tryResolve(BrandingSettings::class);

        if (! $branding instanceof BrandingSettings) {
            return null;
        }

        if ($branding->identityMode !== BrandingIdentityMode::Logo) {
            return null;
        }

        if ($branding->logoPath === null || $branding->logoPath === '') {
            return null;
        }

        $path = Storage::disk('public')->path($branding->logoPath);

        return is_file($path) ? $path : null;
    }
}
