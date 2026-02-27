<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataTransferController extends Controller
{
    private const MAX_ROWS_PER_TABLE = 200000;

    private const TABLE_COLUMNS = [
        'users' => ['id', 'name', 'email', 'email_verified_at', 'password', 'remember_token', 'role', 'call_target_count', 'call_target_until', 'created_at', 'updated_at'],
        'companies' => ['id', 'name', 'ico', 'turnover', 'nace', 'address', 'region', 'website', 'contact_person', 'email', 'phone', 'status', 'assigned_user_id', 'first_caller_user_id', 'first_caller_assigned_at', 'first_contacted_at', 'notes', 'created_at', 'updated_at'],
        'company_contacts' => ['id', 'company_id', 'name', 'title', 'position', 'phone', 'email', 'created_at', 'updated_at'],
        'calls' => ['id', 'company_id', 'caller_id', 'handed_over_to_id', 'called_at', 'ended_at', 'outcome', 'summary', 'next_follow_up_at', 'meeting_planned_at', 'created_at', 'updated_at'],
        'follow_ups' => ['id', 'company_id', 'call_id', 'assigned_user_id', 'due_at', 'status', 'note', 'completed_at', 'created_at', 'updated_at'],
        'meetings' => ['id', 'company_id', 'call_id', 'scheduled_at', 'mode', 'status', 'note', 'created_at', 'updated_at'],
        'lead_transfers' => ['id', 'company_id', 'call_id', 'from_user_id', 'to_user_id', 'transferred_at', 'status', 'note', 'created_at', 'updated_at'],
    ];

    private const TABLE_ORDER_EXPORT = [
        'users',
        'companies',
        'company_contacts',
        'calls',
        'follow_ups',
        'meetings',
        'lead_transfers',
    ];

    private const TABLE_ORDER_IMPORT = [
        'users',
        'companies',
        'company_contacts',
        'calls',
        'follow_ups',
        'meetings',
        'lead_transfers',
    ];

    private const TABLE_ORDER_DELETE = [
        'lead_transfers',
        'meetings',
        'follow_ups',
        'calls',
        'company_contacts',
        'companies',
        'users',
    ];

    public function index(Request $request): View
    {
        $this->ensureManager($request);

        return view('crm.admin.data-transfer');
    }

    public function exportSnapshot(Request $request): StreamedResponse
    {
        $this->ensureManager($request);

        $payload = [
            'meta' => [
                'app' => 'Call CRM',
                'version' => 1,
                'exported_at' => now()->toIso8601String(),
                'exported_by' => $request->user()?->email,
            ],
            'tables' => [],
        ];

        foreach (self::TABLE_ORDER_EXPORT as $table) {
            $payload['tables'][$table] = DB::table($table)
                ->orderBy('id')
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();
        }

        $filename = 'call-crm-snapshot-'.now()->format('Y-m-d_His').'.json';

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, $filename, [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }

    public function importSnapshot(Request $request): RedirectResponse
    {
        $this->ensureManager($request);

        $data = $request->validate([
            'snapshot' => ['required', 'file', 'mimetypes:application/json,text/plain,text/json', 'max:51200'],
        ]);

        $fileContent = file_get_contents($data['snapshot']->getRealPath());
        $decoded = json_decode((string) $fileContent, true);

        if (! is_array($decoded)) {
            return redirect()
                ->route('admin.data-transfer.index')
                ->with('status', 'Import selhal: soubor neni validni JSON.');
        }

        $tables = $decoded['tables'] ?? null;
        if (! is_array($tables)) {
            return redirect()
                ->route('admin.data-transfer.index')
                ->with('status', 'Import selhal: chybi cast tables.');
        }

        foreach (self::TABLE_ORDER_IMPORT as $table) {
            if (! array_key_exists($table, $tables) || ! is_array($tables[$table])) {
                return redirect()
                    ->route('admin.data-transfer.index')
                    ->with('status', "Import selhal: chybi tabulka {$table}.");
            }
            if (count($tables[$table]) > self::MAX_ROWS_PER_TABLE) {
                return redirect()
                    ->route('admin.data-transfer.index')
                    ->with('status', "Import selhal: tabulka {$table} je prilis velka.");
            }
        }

        DB::transaction(function () use ($tables) {
            foreach (self::TABLE_ORDER_DELETE as $table) {
                DB::table($table)->delete();
            }

            foreach (self::TABLE_ORDER_IMPORT as $table) {
                $rows = array_values(array_filter($tables[$table], fn ($row) => is_array($row)));
                $allowedColumns = self::TABLE_COLUMNS[$table] ?? [];
                $rows = array_map(function (array $row) use ($allowedColumns) {
                    $cleanRow = [];
                    foreach ($allowedColumns as $column) {
                        if (array_key_exists($column, $row)) {
                            $cleanRow[$column] = $row[$column];
                        }
                    }

                    return $cleanRow;
                }, $rows);
                $rows = array_values(array_filter($rows, fn (array $row) => $row !== []));
                if (! empty($rows)) {
                    DB::table($table)->insert($rows);
                }
            }
        });

        return redirect()
            ->route('admin.data-transfer.index')
            ->with('status', 'Snapshot byl importovan. Data CRM byla obnovena.');
    }

    private function ensureManager(Request $request): void
    {
        abort_unless($request->user()?->isManager(), 403);
    }
}
