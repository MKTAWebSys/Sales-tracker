<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use ZipArchive;

class ImportController extends Controller
{
    private const MAX_ROWS = 10000;
    private const MAX_COLUMNS = 64;
    private const MAX_CELL_LENGTH = 5000;
    private const MAX_XML_BYTES = 20_000_000;

    private const ALLOWED_COMPANY_STATUSES = ['new', 'follow-up', 'meeting', 'deal', 'lost'];

    private const HEADER_ALIASES = [
        'company_name' => ['company_name', 'nazev', 'nazev_subjektu', 'firma', 'name', 'company'],
        'ico' => ['ico', 'ic', 'ico_cislo'],
        'turnover' => ['turnover', 'obrat', 'rocni_obrat', 'annual_turnover'],
        'nace' => ['nace', 'cz_nace'],
        'region' => ['region', 'kraj'],
        'website' => ['website', 'web', 'url', 'www'],
        'contact_name' => ['contact_name', 'kontakt', 'kontaktni_osoba', 'contact_person'],
        'phone' => ['phone', 'telefon', 'tel'],
        'mobile_phone' => ['mobile_phone', 'mobil', 'mobilni_telefon', 'mobile'],
        'email' => ['email', 'e-mail', 'mail'],
        'note' => ['note', 'poznamka', 'notes'],
        'status' => ['status', 'stav'],
        'assigned_user_email' => ['assigned_user_email', 'owner_email', 'resitel_email', 'assigned_email'],
        'assigned_user_name' => ['assigned_user_name', 'owner_name', 'resitel_name', 'kdo_oslovil'],
        'first_caller_email' => ['first_caller_email', 'firstcaller_email', 'first_caller', 'first_caller_mail'],
        'first_caller_name' => ['first_caller_name', 'firstcaller_name'],
        'address' => ['address', 'adresa', 'plna_adresa_subjektu'],
        'contact_path' => ['contact_path', 'cesta_ke_kontaktu'],
        'next_step' => ['next_step', 'dalsi_krok'],
        'date_contacted' => ['date_contacted', 'datum_osloveni'],
    ];

    private const IMPORT_FIELDS = [
        'company_name' => 'Nazev firmy',
        'ico' => 'ICO',
        'turnover' => 'Obrat',
        'nace' => 'NACE',
        'region' => 'Kraj',
        'website' => 'Web / URL',
        'contact_name' => 'Kontaktni osoba',
        'phone' => 'Telefon',
        'mobile_phone' => 'Mobil',
        'email' => 'E-mail',
        'note' => 'Poznamka',
        'status' => 'Stav firmy',
        'assigned_user_email' => 'Resitel (e-mail)',
        'assigned_user_name' => 'Resitel (jmeno)',
        'first_caller_email' => 'First caller (e-mail)',
        'first_caller_name' => 'First caller (jmeno)',
        'address' => 'Adresa',
        'contact_path' => 'Cesta ke kontaktu',
        'next_step' => 'Dalsi krok',
        'date_contacted' => 'Datum osloveni',
    ];

    public function xlsx(): View
    {
        $this->ensureManager(request());
        $users = User::query()->orderBy('name')->get(['id', 'name', 'email']);

        return view('crm.imports.xlsx', [
            'requiredColumns' => ['company_name'],
            'optionalColumns' => [
                'ico',
                'turnover',
                'nace',
                'region',
                'website',
                'contact_name',
                'phone',
                'email',
                'note',
                'status',
                'assigned_user_email',
                'first_caller_email',
            ],
            'previewToken' => null,
            'previewRows' => [],
            'previewSummary' => null,
            'importReport' => session('import_report'),
            'users' => $users,
            'defaultAssignedUserId' => null,
            'defaultFirstCallerUserId' => null,
            'rowLimit' => 100,
            'availableHeaders' => [],
            'mappingFields' => self::IMPORT_FIELDS,
            'mappingDefaults' => [],
        ]);
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $this->ensureManager($request);

        $data = $request->validate([
            'xlsx_file' => ['required', 'file', 'mimes:xlsx', 'max:20480'],
            'row_limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        try {
            $parsed = $this->parseXlsxRows($data['xlsx_file']->getRealPath());
        } catch (\Throwable $e) {
            return redirect()
                ->route('imports.xlsx')
                ->with('status', 'Import selhal: '.$e->getMessage());
        }

        $rowLimit = (int) ($data['row_limit'] ?? 100);
        if ($rowLimit <= 0) {
            $rowLimit = 100;
        }

        $availableHeaders = array_values(array_unique(array_map(fn ($h) => (string) $h, $parsed['headers'] ?? [])));
        $autoMapping = $this->detectMappingFromHeaders($availableHeaders);
        $userMapping = is_array($request->input('mapping')) ? $request->input('mapping') : [];
        $mapping = $this->mergeMapping($autoMapping, $this->sanitizeMapping($userMapping, $availableHeaders));

        $rawRows = collect($parsed['rows'])
            ->take($rowLimit)
            ->values()
            ->all();
        $rows = $this->normalizeImportRows($rawRows, $mapping);
        $previewRows = $this->buildPreviewRows($rows);

        $summary = [
            'total_rows' => count($previewRows),
            'new_rows' => collect($previewRows)->where('duplicate', false)->count(),
            'duplicate_rows' => collect($previewRows)->where('duplicate', true)->count(),
            'invalid_rows' => collect($previewRows)->where('valid', false)->count(),
        ];

        $token = (string) Str::uuid();
        Storage::put(
            $this->previewStoragePath($token),
            json_encode([
                'token' => $token,
                'created_at' => now()->toIso8601String(),
                'rows_raw' => $rawRows,
                'headers' => $availableHeaders,
                'mapping' => $mapping,
                'row_limit' => $rowLimit,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return view('crm.imports.xlsx', [
            'requiredColumns' => ['company_name'],
            'optionalColumns' => [
                'ico',
                'turnover',
                'nace',
                'region',
                'website',
                'contact_name',
                'phone',
                'email',
                'note',
                'status',
                'assigned_user_email',
                'first_caller_email',
            ],
            'previewToken' => $token,
            'previewRows' => $previewRows,
            'previewSummary' => $summary,
            'importReport' => null,
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'defaultAssignedUserId' => null,
            'defaultFirstCallerUserId' => null,
            'rowLimit' => $rowLimit,
            'availableHeaders' => $availableHeaders,
            'mappingFields' => self::IMPORT_FIELDS,
            'mappingDefaults' => $mapping,
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $this->ensureManager($request);

        $data = $request->validate([
            'preview_token' => ['required', 'regex:/^[a-f0-9\\-]{36}$/i'],
            'duplicate_mode' => ['required', 'in:skip,update'],
            'default_assigned_user_id' => ['nullable', 'exists:users,id'],
            'default_first_caller_user_id' => ['nullable', 'exists:users,id'],
            'mapping' => ['nullable', 'array'],
            'mapping.*' => ['nullable', 'string', 'max:120'],
        ]);

        $token = (string) $data['preview_token'];
        $path = $this->previewStoragePath($token);
        if (! Storage::exists($path)) {
            return redirect()
                ->route('imports.xlsx')
                ->with('status', 'Import session vyprsela. Nahraj soubor znovu.');
        }

        $payload = json_decode((string) Storage::get($path), true);
        $rawRows = collect(Arr::get($payload, 'rows_raw', []))
            ->filter(fn ($row) => is_array($row))
            ->values()
            ->all();
        $availableHeaders = collect(Arr::get($payload, 'headers', []))
            ->map(fn ($header) => (string) $header)
            ->filter()
            ->values()
            ->all();
        $savedMapping = is_array(Arr::get($payload, 'mapping', [])) ? Arr::get($payload, 'mapping', []) : [];
        $inputMapping = is_array($data['mapping'] ?? null) ? $data['mapping'] : [];
        $mapping = $this->mergeMapping($savedMapping, $this->sanitizeMapping($inputMapping, $availableHeaders));
        $rows = $this->normalizeImportRows($rawRows, $mapping);

        $usersByEmail = User::query()->get()->keyBy(fn (User $user) => Str::lower($user->email));
        $usersByName = User::query()->get()->keyBy(fn (User $user) => Str::lower(trim((string) $user->name)));

        $report = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped_duplicate' => 0,
            'skipped_invalid' => 0,
            'errors' => [],
        ];

        foreach ($rows as $row) {
            $report['processed']++;
            $rowNumber = (int) ($row['row_number'] ?? 0);
            $companyName = trim((string) ($row['company_name'] ?? ''));

            if ($companyName === '') {
                $report['skipped_invalid']++;
                $report['errors'][] = "Radek {$rowNumber}: chybi company_name.";
                continue;
            }

            $duplicate = $this->findDuplicateCompany(
                trim((string) ($row['ico'] ?? '')),
                $companyName
            );

            if ($duplicate && $data['duplicate_mode'] === 'skip') {
                $report['skipped_duplicate']++;
                continue;
            }

            $attributes = $this->buildCompanyAttributesFromRow(
                $row,
                $usersByEmail,
                $usersByName,
                isset($data['default_assigned_user_id']) && $data['default_assigned_user_id'] !== null
                    ? (int) $data['default_assigned_user_id']
                    : null,
                isset($data['default_first_caller_user_id']) && $data['default_first_caller_user_id'] !== null
                    ? (int) $data['default_first_caller_user_id']
                    : null
            );

            try {
                if ($duplicate) {
                    $duplicate->update($attributes);
                    $report['updated']++;
                } else {
                    Company::query()->create($attributes);
                    $report['created']++;
                }
            } catch (\Throwable $e) {
                $report['errors'][] = "Radek {$rowNumber}: ".$e->getMessage();
            }
        }

        Storage::delete($path);

        $logName = 'imports/logs/xlsx-import-'.now()->format('Ymd_His').'-'.Str::lower(Str::random(6)).'.json';
        Storage::put($logName, json_encode([
            'meta' => [
                'imported_at' => now()->toIso8601String(),
                'imported_by' => $request->user()?->email,
                'duplicate_mode' => $data['duplicate_mode'],
            ],
            'report' => $report,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return redirect()
            ->route('imports.xlsx')
            ->with('status', 'Import dokoncen. Vytvoreno: '.$report['created'].', aktualizovano: '.$report['updated'].', preskoceno (duplicity): '.$report['skipped_duplicate'].'.')
            ->with('import_report', array_merge($report, ['log_file' => $logName]));
    }

    private function previewStoragePath(string $token): string
    {
        return 'imports/previews/'.$token.'.json';
    }

    private function findDuplicateCompany(string $ico, string $companyName): ?Company
    {
        $ico = trim($ico);
        if ($ico !== '') {
            $byIco = Company::query()->where('ico', $ico)->first();
            if ($byIco) {
                return $byIco;
            }
        }

        return Company::query()->whereRaw('LOWER(name) = ?', [Str::lower(trim($companyName))])->first();
    }

    private function buildCompanyAttributesFromRow(array $row, $usersByEmail, $usersByName, ?int $defaultAssignedUserId = null, ?int $defaultFirstCallerUserId = null): array
    {
        $status = trim((string) ($row['status'] ?? ''));
        if (! in_array($status, self::ALLOWED_COMPANY_STATUSES, true)) {
            $status = 'new';
        }

        $note = Str::limit(trim((string) ($row['note'] ?? '')), self::MAX_CELL_LENGTH, '');

        $assignedUserId = $defaultAssignedUserId;
        $assignedEmail = Str::lower(trim((string) ($row['assigned_user_email'] ?? '')));
        if ($assignedEmail !== '' && isset($usersByEmail[$assignedEmail])) {
            $assignedUserId = $usersByEmail[$assignedEmail]->id;
        } else {
            $assignedName = Str::lower(trim((string) ($row['assigned_user_name'] ?? '')));
            if ($assignedName !== '' && isset($usersByName[$assignedName])) {
                $assignedUserId = $usersByName[$assignedName]->id;
            }
        }

        $firstCallerUserId = $defaultFirstCallerUserId;
        $firstCallerEmail = Str::lower(trim((string) ($row['first_caller_email'] ?? '')));
        if ($firstCallerEmail !== '' && isset($usersByEmail[$firstCallerEmail])) {
            $firstCallerUserId = $usersByEmail[$firstCallerEmail]->id;
        } else {
            $firstCallerName = Str::lower(trim((string) ($row['first_caller_name'] ?? '')));
            if ($firstCallerName !== '' && isset($usersByName[$firstCallerName])) {
                $firstCallerUserId = $usersByName[$firstCallerName]->id;
            }
        }

        return [
            'name' => Str::limit(trim((string) $row['company_name']), 255, ''),
            'ico' => Str::limit(trim((string) ($row['ico'] ?? '')), 32, '') ?: null,
            'turnover' => Str::limit(trim((string) ($row['turnover'] ?? '')), 64, '') ?: null,
            'nace' => Str::limit(trim((string) ($row['nace'] ?? '')), 64, '') ?: null,
            'region' => Str::limit(trim((string) ($row['region'] ?? '')), 100, '') ?: null,
            'address' => Str::limit(trim((string) ($row['address'] ?? '')), 255, '') ?: null,
            'website' => Str::limit(trim((string) ($row['website'] ?? '')), 255, '') ?: null,
            'contact_person' => Str::limit(trim((string) ($row['contact_name'] ?? '')), 255, '') ?: null,
            'email' => Str::limit(trim((string) ($row['email'] ?? '')), 255, '') ?: null,
            'phone' => $this->mergePhoneContacts($row),
            'status' => $status,
            'notes' => $note !== '' ? $note : null,
            'assigned_user_id' => $assignedUserId,
            'first_caller_user_id' => $firstCallerUserId,
            'first_caller_assigned_at' => $firstCallerUserId ? now() : null,
        ];
    }

    private function mergePhoneContacts(array $row): ?string
    {
        $phones = collect([
            trim((string) ($row['phone'] ?? '')),
            trim((string) ($row['mobile_phone'] ?? '')),
        ])
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($phones)) {
            return null;
        }

        return Str::limit(implode(' / ', $phones), 64, '');
    }

    private function buildPreviewRows(array $rows): array
    {
        $icos = collect($rows)
            ->pluck('ico')
            ->map(fn ($ico) => trim((string) $ico))
            ->filter()
            ->unique()
            ->values();

        $rawNames = collect($rows)
            ->pluck('company_name')
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values();

        $existingByIco = Company::query()
            ->when($icos->isNotEmpty(), fn ($q) => $q->whereIn('ico', $icos->all()))
            ->get()
            ->keyBy(fn (Company $company) => (string) $company->ico);

        $existingByName = Company::query()
            ->when($rawNames->isNotEmpty(), fn ($q) => $q->whereIn('name', $rawNames->all()))
            ->get()
            ->keyBy(fn (Company $company) => Str::lower($company->name));

        return collect($rows)->map(function (array $row) use ($existingByIco, $existingByName) {
            $name = trim((string) ($row['company_name'] ?? ''));
            $ico = trim((string) ($row['ico'] ?? ''));
            $valid = $name !== '';

            $existing = null;
            if ($ico !== '' && isset($existingByIco[$ico])) {
                $existing = $existingByIco[$ico];
            } elseif (isset($existingByName[Str::lower($name)])) {
                $existing = $existingByName[Str::lower($name)];
            }

            return [
                'row_number' => $row['row_number'] ?? null,
                'company_name' => $name,
                'ico' => $ico,
                'status' => $row['status'] ?? '',
                'assigned_user_email' => $row['assigned_user_email'] ?? '',
                'first_caller_email' => $row['first_caller_email'] ?? '',
                'valid' => $valid,
                'duplicate' => $existing !== null,
                'duplicate_label' => $existing ? ('#'.$existing->id.' '.$existing->name) : null,
            ];
        })->all();
    }

    private function normalizeImportRows(array $rows, array $mapping = []): array
    {
        $aliasMap = $this->aliasMap();

        return collect($rows)
            ->take(self::MAX_ROWS)
            ->map(function (array $row) use ($mapping, $aliasMap) {
                $normalized = [];
                $normalized['row_number'] = (int) ($row['row_number'] ?? 0);
                foreach (array_keys(self::IMPORT_FIELDS) as $target) {
                    $value = '';
                    $mappedHeader = (string) ($mapping[$target] ?? '');
                    if ($mappedHeader !== '' && array_key_exists($mappedHeader, $row)) {
                        $value = Str::limit(trim((string) $row[$mappedHeader]), self::MAX_CELL_LENGTH, '');
                    } else {
                        foreach (($aliasMap[$target] ?? []) as $alias) {
                            if (array_key_exists($alias, $row)) {
                                $value = Str::limit(trim((string) $row[$alias]), self::MAX_CELL_LENGTH, '');
                                break;
                            }
                        }
                    }
                    $normalized[$target] = $value;
                }

                $status = trim((string) ($normalized['status'] ?? ''));
                $normalized['status'] = in_array($status, self::ALLOWED_COMPANY_STATUSES, true) ? $status : 'new';

                return $normalized;
            })
            ->values()
            ->all();
    }

    private function parseXlsxRows(string $path): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new \RuntimeException('PHP extension ZipArchive neni dostupna.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Soubor XLSX se nepodarilo otevrit.');
        }

        $sheetStat = $zip->statName('xl/worksheets/sheet1.xml');
        if (! is_array($sheetStat) || (int) ($sheetStat['size'] ?? 0) <= 0) {
            $zip->close();
            throw new \RuntimeException('V XLSX chybi sheet1.');
        }
        if ((int) $sheetStat['size'] > self::MAX_XML_BYTES) {
            $zip->close();
            throw new \RuntimeException('XLSX je prilis velky (sheet1).');
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            $zip->close();
            throw new \RuntimeException('V XLSX chybi sheet1.');
        }

        $sharedStat = $zip->statName('xl/sharedStrings.xml');
        if (is_array($sharedStat) && (int) ($sharedStat['size'] ?? 0) > self::MAX_XML_BYTES) {
            $zip->close();
            throw new \RuntimeException('XLSX je prilis velky (sharedStrings).');
        }

        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();

        $sharedStrings = $this->parseSharedStrings($sharedStringsXml ?: '');
        $sheetRows = $this->parseSheetRows($sheetXml, $sharedStrings);

        if (count($sheetRows) < 2) {
            throw new \RuntimeException('XLSX neobsahuje data (chybi radky pod hlavickou).');
        }

        $header = array_map([$this, 'normalizeHeaderKey'], $sheetRows[0]['values']);
        $requiredAliases = $this->aliasMap()['company_name'] ?? ['company_name'];
        $hasCompanyNameHeader = collect($requiredAliases)->contains(fn ($alias) => in_array($alias, $header, true));
        if (! $hasCompanyNameHeader) {
            throw new \RuntimeException('Chybi sloupec pro nazev firmy (napr. company_name / nazev / nazev_subjektu / firma).');
        }

        $dataRows = [];
        for ($i = 1; $i < count($sheetRows); $i++) {
            $rowValues = $sheetRows[$i]['values'];
            $mapped = ['row_number' => $sheetRows[$i]['row_number']];
            foreach ($header as $index => $headerName) {
                if ($headerName === '') {
                    continue;
                }
                $mapped[$headerName] = trim((string) ($rowValues[$index] ?? ''));
            }

            $isEmpty = collect($mapped)
                ->except('row_number')
                ->every(fn ($value) => trim((string) $value) === '');
            if (! $isEmpty) {
                $dataRows[] = $mapped;
            }
        }

        return [
            'headers' => $header,
            'rows' => $dataRows,
        ];
    }

    private function parseSharedStrings(string $xml): array
    {
        if ($xml === '') {
            return [];
        }

        $root = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET);
        if (! $root) {
            return [];
        }

        $result = [];
        foreach ($root->si as $item) {
            if (isset($item->t)) {
                $result[] = (string) $item->t;
                continue;
            }

            $parts = [];
            foreach ($item->r as $run) {
                $parts[] = (string) $run->t;
            }
            $result[] = implode('', $parts);
        }

        return $result;
    }

    private function parseSheetRows(string $xml, array $sharedStrings): array
    {
        $root = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET);
        if (! $root) {
            throw new \RuntimeException('Neplatna XML struktura v sheet1.');
        }

        $namespaces = $root->getNamespaces(true);
        $mainNs = $namespaces[''] ?? null;
        if ($mainNs) {
            $root->registerXPathNamespace('x', $mainNs);
            $rowNodes = $root->xpath('//x:sheetData/x:row');
        } else {
            $rowNodes = $root->sheetData->row ?? [];
        }

        $rows = [];
        foreach ($rowNodes as $rowNode) {
            if (count($rows) >= self::MAX_ROWS + 1) {
                throw new \RuntimeException('XLSX obsahuje prilis mnoho radku. Maximum je '.self::MAX_ROWS.'.');
            }

            $cells = [];
            foreach ($rowNode->c as $cell) {
                $reference = (string) ($cell['r'] ?? '');
                $columnLetters = preg_replace('/[^A-Z]/', '', strtoupper($reference));
                if ($columnLetters === '') {
                    continue;
                }

                $columnIndex = $this->columnLettersToIndex($columnLetters);
                if ($columnIndex >= self::MAX_COLUMNS) {
                    continue;
                }
                $type = (string) ($cell['t'] ?? '');
                $rawValue = (string) ($cell->v ?? '');
                $value = '';

                if ($type === 's') {
                    $sharedIndex = (int) $rawValue;
                    $value = (string) ($sharedStrings[$sharedIndex] ?? '');
                } else {
                    $value = $rawValue;
                }

                $cells[$columnIndex] = Str::limit($value, self::MAX_CELL_LENGTH, '');
            }

            if (empty($cells)) {
                continue;
            }

            ksort($cells);
            $maxIndex = max(array_keys($cells));
            $values = [];
            for ($idx = 0; $idx <= $maxIndex; $idx++) {
                $values[] = (string) ($cells[$idx] ?? '');
            }

            $rows[] = [
                'row_number' => (int) ($rowNode['r'] ?? 0),
                'values' => $values,
            ];
        }

        return $rows;
    }

    private function columnLettersToIndex(string $letters): int
    {
        $index = 0;
        foreach (str_split($letters) as $char) {
            $index = ($index * 26) + (ord($char) - 64);
        }

        return $index - 1;
    }

    private function normalizeHeaderKey(string $header): string
    {
        $ascii = Str::of($header)
            ->lower()
            ->ascii()
            ->replace([' ', '-', '/', '\\'], '_')
            ->replaceMatches('/[^a-z0-9_]/', '')
            ->value();

        return preg_replace('/_+/', '_', trim($ascii, '_')) ?: '';
    }

    private function aliasMap(): array
    {
        $result = [];
        foreach (self::HEADER_ALIASES as $field => $aliases) {
            $result[$field] = collect($aliases)
                ->map(fn ($alias) => $this->normalizeHeaderKey((string) $alias))
                ->filter()
                ->values()
                ->all();
        }

        return $result;
    }

    private function detectMappingFromHeaders(array $headers): array
    {
        $headersMap = collect($headers)->flip();
        $mapping = [];
        foreach ($this->aliasMap() as $field => $aliases) {
            foreach ($aliases as $alias) {
                if ($headersMap->has($alias)) {
                    $mapping[$field] = $alias;
                    break;
                }
            }
        }

        return $mapping;
    }

    private function sanitizeMapping(array $incoming, array $availableHeaders): array
    {
        $available = collect($availableHeaders)->map(fn ($header) => (string) $header)->flip();
        $clean = [];
        foreach ($incoming as $field => $header) {
            $field = (string) $field;
            if (! array_key_exists($field, self::IMPORT_FIELDS)) {
                continue;
            }
            $header = $this->normalizeHeaderKey((string) $header);
            if ($header !== '' && $available->has($header)) {
                $clean[$field] = $header;
            }
        }

        return $clean;
    }

    private function mergeMapping(array $base, array $override): array
    {
        $merged = [];
        foreach (array_keys(self::IMPORT_FIELDS) as $field) {
            if (! empty($override[$field])) {
                $merged[$field] = (string) $override[$field];
            } elseif (! empty($base[$field])) {
                $merged[$field] = (string) $base[$field];
            }
        }

        return $merged;
    }

    private function ensureManager(Request $request): void
    {
        abort_unless($request->user()?->isManager(), 403);
    }
}
