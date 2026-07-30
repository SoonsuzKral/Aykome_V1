<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DatabaseSwitcherController extends Controller
{
    protected function checkPassword(Request $request): bool
    {
        $password = env('DB_SWITCH_PASSWORD', 'AykomeV6!2026');
        $session = $request->session()->get('db_switch_auth', false);
        return $session === $password;
    }

    public function login(Request $request)
    {
        if ($request->isMethod('post')) {
            $password = env('DB_SWITCH_PASSWORD', 'AykomeV6!2026');
            if ($request->password === $password) {
                $request->session()->put('db_switch_auth', $password);
                return redirect()->route('db-switch.index');
            }
            return back()->withErrors(['password' => 'Yanlis sifre']);
        }
        return view('db-switch.login');
    }

    public function index(Request $request)
    {
        if (!$this->checkPassword($request)) {
            return redirect()->route('db-switch.login');
        }

        $currentDb = config('database.default');
        $connections = [];

        foreach (['mysql', 'oracle'] as $driver) {
            try {
                DB::connection($driver)->getPdo();
                $connections[$driver] = [
                    'status' => 'connected',
                    'host' => config("database.connections.$driver.host"),
                    'port' => config("database.connections.$driver.port"),
                    'database' => $driver === 'oracle'
                        ? config("database.connections.$driver.service_name")
                        : config("database.connections.$driver.database"),
                    'username' => config("database.connections.$driver.username"),
                ];
            } catch (Exception $e) {
                $connections[$driver] = [
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return view('db-switch.index', compact('currentDb', 'connections'));
    }

    public function switch(Request $request): JsonResponse
    {
        if (!$this->checkPassword($request)) {
            return response()->json(['success' => false, 'error' => 'Yetkisiz erisim'], 403);
        }

        $target = $request->target;
        if (!in_array($target, ['mysql', 'oracle'])) {
            return response()->json(['success' => false, 'error' => 'Gecersiz hedef'], 422);
        }

        try {
            $envPath = base_path('.env');
            $content = File::get($envPath);

            $content = preg_replace(
                '/^DB_CONNECTION=.*/m',
                "DB_CONNECTION=$target",
                $content
            );

            File::put($envPath, $content);

            Artisan::call('config:clear');

            return response()->json([
                'success' => true,
                'message' => "Veritabani basariyla $target olarak degistirildi",
                'target' => $target,
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function status(Request $request): JsonResponse
    {
        if (!$this->checkPassword($request)) {
            return response()->json(['success' => false, 'error' => 'Yetkisiz erisim'], 403);
        }

        $currentDb = config('database.default');
        $connections = [];

        foreach (['mysql', 'oracle'] as $driver) {
            try {
                DB::connection($driver)->getPdo();
                $pdo = DB::connection($driver)->getPdo();
                $connections[$driver] = [
                    'status' => 'connected',
                    'version' => $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION),
                    'host' => config("database.connections.$driver.host"),
                    'port' => config("database.connections.$driver.port"),
                ];

                $tableCount = DB::connection($driver)->select(
                    $driver === 'oracle'
                        ? "SELECT COUNT(*) as cnt FROM user_tables"
                        : "SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = ?",
                    $driver === 'oracle' ? [] : [config("database.connections.$driver.database")]
                );
                $connections[$driver]['tables'] = (int) $tableCount[0]->cnt;
            } catch (Exception $e) {
                $connections[$driver] = [
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'current' => $currentDb,
            'connections' => $connections,
        ]);
    }

    public function migrate(Request $request): JsonResponse
    {
        if (!$this->checkPassword($request)) {
            return response()->json(['success' => false, 'error' => 'Yetkisiz erisim'], 403);
        }

        if (!$request->session()->has('migrate_confirm')) {
            return response()->json(['success' => false, 'error' => 'Lutfen once onaylayin'], 400);
        }

        try {
            $mysqlTables = DB::connection('mysql')->select("SHOW TABLES");
            $imported = 0;
            $errors = [];
            $skipped = 0;

            $fkConstraints = DB::connection('oracle')->select("
                SELECT table_name, constraint_name
                FROM user_constraints
                WHERE constraint_type = 'R'
            ");
            foreach ($fkConstraints as $fk) {
                DB::connection('oracle')->statement("ALTER TABLE \"{$fk->table_name}\" DISABLE CONSTRAINT \"{$fk->constraint_name}\"");
            }

            foreach ($mysqlTables as $tableRow) {
                $table = array_values((array)$tableRow)[0];
                $otable = strtoupper($table);

                $mysqlCols = DB::connection('mysql')->select("SHOW COLUMNS FROM `$table`");
                $oracleExists = DB::connection('oracle')->select(
                    "SELECT COUNT(*) as cnt FROM user_tables WHERE table_name = ?",
                    [$otable]
                );

                if ((int)$oracleExists[0]->cnt === 0) {
                    $colDefs = [];
                    $colNames = [];
                    $hasPk = false;

                    foreach ($mysqlCols as $col) {
                        $name = strtoupper($col->Field);
                        $colNames[] = $name;
                        $type = 'VARCHAR2(4000)';
                        if (stripos($col->Type, 'bigint') !== false) $type = 'NUMBER(20)';
                        elseif (stripos($col->Type, 'int') !== false) $type = 'NUMBER(10)';
                        elseif (stripos($col->Type, 'tinyint') !== false) $type = 'NUMBER(3)';
                        elseif (stripos($col->Type, 'decimal') !== false || stripos($col->Type, 'float') !== false || stripos($col->Type, 'double') !== false) $type = 'NUMBER';
                        elseif (stripos($col->Type, 'text') !== false || stripos($col->Type, 'blob') !== false) $type = 'CLOB';
                        elseif (stripos($col->Type, 'date') !== false) $type = 'DATE';
                        elseif (stripos($col->Type, 'timestamp') !== false) $type = 'TIMESTAMP';
                        elseif (stripos($col->Type, 'json') !== false) $type = 'CLOB';

                        $nullable = $col->Null === 'YES' ? 'NULL' : 'NOT NULL';
                        $default = $col->Default !== null ? " DEFAULT " . (is_numeric($col->Default) ? $col->Default : "'$col->Default'") : '';
                        $colDefs[] = "\"$name\" $type $nullable$default";

                        if ($col->Key === 'PRI') {
                            $hasPk = true;
                        }
                    }

                    $pk = '';
                    foreach ($mysqlCols as $col) {
                        if ($col->Key === 'PRI' && $col->Extra === 'auto_increment') {
                            $colName = strtoupper($col->Field);
                            $pk = ", PRIMARY KEY (\"$colName\")";
                            break;
                        }
                    }
                    if (!$pk && $hasPk) {
                        foreach ($mysqlCols as $col) {
                            if ($col->Key === 'PRI') {
                                $pk = ", PRIMARY KEY (\"" . strtoupper($col->Field) . "\")";
                                break;
                            }
                        }
                    }

                    $createSql = "CREATE TABLE \"$otable\" (" . implode(', ', $colDefs) . "$pk)";
                    DB::connection('oracle')->statement($createSql);

                    if ($pk) {
                        $seqName = $otable . '_SEQ';
                        $seqExists = DB::connection('oracle')->select("SELECT COUNT(*) as cnt FROM user_sequences WHERE sequence_name = ?", [$seqName]);
                        if ((int)$seqExists[0]->cnt === 0) {
                            DB::connection('oracle')->statement("CREATE SEQUENCE \"$seqName\" START WITH 1 INCREMENT BY 1");
                        }
                    }
                }

                $hasIdCol = false;
                foreach ($mysqlCols as $col) {
                    if (strtolower($col->Field) === 'id') { $hasIdCol = true; break; }
                }

                $mysqlData = DB::connection('mysql')->table($table)->get();
                if ($mysqlData->isEmpty()) continue;

                foreach ($mysqlData->chunk(100) as $chunk) {
                    $insertData = [];
                    foreach ($chunk as $row) {
                        $data = [];
                        foreach ($mysqlCols as $col) {
                            $val = $row->{$col->Field} ?? null;
                            if ($val === '0000-00-00' || $val === '0000-00-00 00:00:00') $val = null;
                            $data[strtoupper($col->Field)] = $val;
                        }
                        if ($hasIdCol && isset($data['ID'])) {
                            $existing = DB::connection('oracle')->select(
                                "SELECT COUNT(*) as cnt FROM \"$otable\" WHERE \"ID\" = ?",
                                [$data['ID']]
                            );
                            if ((int)$existing[0]->cnt > 0) {
                                $skipped++;
                                continue;
                            }
                        }
                        $insertData[] = $data;
                    }
                    if (empty($insertData)) continue;

                    try {
                        DB::connection('oracle')->table($otable)->insert($insertData);
                        $imported += count($insertData);
                    } catch (Exception $e) {
                        foreach ($insertData as $single) {
                            try {
                                DB::connection('oracle')->table($otable)->insert($single);
                                $imported++;
                            } catch (Exception $e2) {
                                $errors[] = $table . ': ' . $e2->getMessage();
                            }
                        }
                    }
                }
            }

            foreach ($fkConstraints as $fk) {
                DB::connection('oracle')->statement("ALTER TABLE \"{$fk->table_name}\" ENABLE NOVALIDATE CONSTRAINT \"{$fk->constraint_name}\"");
            }

            $request->session()->forget('migrate_confirm');

            $msg = "$imported kayit Oracle'a aktarildi.";
            if ($skipped > 0) $msg .= " $skipped kayit zaten var oldugu icin atlandi.";
            if (!empty($errors)) $msg .= ' ' . count($errors) . ' hata olustu (detay asagida).';

            return response()->json([
                'success' => true,
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
                'message' => $msg,
            ]);
        } catch (Exception $e) {
            try {
                foreach ($fkConstraints ?? [] as $fk) {
                    DB::connection('oracle')->statement("ALTER TABLE \"{$fk->table_name}\" ENABLE NOVALIDATE CONSTRAINT \"{$fk->constraint_name}\"");
                }
            } catch (Exception $ignore) {}
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function confirmMigrate(Request $request): JsonResponse
    {
        if (!$this->checkPassword($request)) {
            return response()->json(['success' => false, 'error' => 'Yetkisiz erisim'], 403);
        }
        $request->session()->put('migrate_confirm', true);
        return response()->json(['success' => true]);
    }

    public function logout(Request $request)
    {
        $request->session()->forget('db_switch_auth');
        return redirect()->route('db-switch.login');
    }
}
