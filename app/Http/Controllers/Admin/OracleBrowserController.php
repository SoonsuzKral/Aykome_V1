<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OracleBrowserController extends Controller
{
    public function index(): View
    {
        $connectionOk = false;
        $tables = [];
        $stats = [];
        $error = null;

        try {
            DB::connection('oracle')->getPdo();
            $connectionOk = true;

            $tables = DB::connection('oracle')->select("SELECT table_name FROM user_tables ORDER BY table_name");

            $stats['tables'] = DB::connection('oracle')->selectOne("SELECT COUNT(*) AS cnt FROM user_tables")->cnt;
            $stats['views'] = DB::connection('oracle')->selectOne("SELECT COUNT(*) AS cnt FROM user_views")->cnt;
            $stats['sequences'] = DB::connection('oracle')->selectOne("SELECT COUNT(*) AS cnt FROM user_sequences")->cnt;
            $stats['triggers'] = DB::connection('oracle')->selectOne("SELECT COUNT(*) AS cnt FROM user_triggers")->cnt;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        return view('admin.oracle.index', compact('connectionOk', 'tables', 'stats', 'error'));
    }

    public function query(Request $request): JsonResponse
    {
        $request->validate(['sql' => 'required|string']);

        try {
            $results = DB::connection('oracle')->select($request->sql);
            return response()->json(['success' => true, 'data' => $results, 'count' => count($results)]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function tableData(Request $request): JsonResponse
    {
        $request->validate(['table' => 'required|string']);

        try {
            $table = $request->table;
            $columns = DB::connection('oracle')->select("SELECT column_name, data_type, nullable, data_default FROM user_tab_cols WHERE table_name = ? ORDER BY column_id", [strtoupper($table)]);
            $rows = DB::connection('oracle')->select("SELECT * FROM \"$table\" OFFSET 0 ROWS FETCH NEXT 50 ROWS ONLY");
            $count = DB::connection('oracle')->selectOne("SELECT COUNT(*) AS cnt FROM \"$table\"")->cnt;

            return response()->json([
                'success' => true,
                'columns' => $columns,
                'rows' => $rows,
                'total' => $count,
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function migrate(Request $request): JsonResponse
    {
        try {
            $mysqlTables = DB::connection('mysql')->select("SHOW TABLES");
            $imported = 0;
            $errors = [];

            foreach ($mysqlTables as $tableRow) {
                $table = array_values((array)$tableRow)[0];

                $mysqlCols = DB::connection('mysql')->select("SHOW COLUMNS FROM `$table`");
                $oracleCols = DB::connection('oracle')->select("SELECT column_name FROM user_tab_cols WHERE table_name = ?", [strtoupper($table)]);

                if (empty($oracleCols)) {
                    $colDefs = [];
                    $colNames = [];
                    foreach ($mysqlCols as $col) {
                        $name = $col->Field;
                        $colNames[] = $name;
                        $type = 'VARCHAR2(4000)';
                        if (stripos($col->Type, 'int') !== false) $type = 'NUMBER';
                        elseif (stripos($col->Type, 'decimal') !== false || stripos($col->Type, 'float') !== false || stripos($col->Type, 'double') !== false) $type = 'NUMBER';
                        elseif (stripos($col->Type, 'text') !== false || stripos($col->Type, 'blob') !== false) $type = 'CLOB';
                        elseif (stripos($col->Type, 'date') !== false) $type = 'DATE';
                        elseif (stripos($col->Type, 'timestamp') !== false) $type = 'TIMESTAMP';
                        $nullable = $col->Null === 'YES' ? 'NULL' : 'NOT NULL';
                        $colDefs[] = "\"$name\" $type $nullable";
                    }

                    $pk = '';
                    foreach ($mysqlCols as $col) {
                        if ($col->Key === 'PRI') {
                            $pk = ", PRIMARY KEY (\"{$col->Field}\")";
                            break;
                        }
                    }

                    $seqName = strtoupper($table) . '_SEQ';
                    $createSql = "CREATE TABLE \"$table\" (" . implode(', ', $colDefs) . "$pk)";

                    try {
                        DB::connection('oracle')->statement($createSql);
                        if ($pk) {
                            DB::connection('oracle')->statement("CREATE SEQUENCE \"$seqName\" START WITH 1 INCREMENT BY 1");
                        }
                    } catch (Exception $e) {
                        $errors[] = "Tablo olusturma hatasi ($table): " . $e->getMessage();
                        continue;
                    }
                }

                $mysqlData = DB::connection('mysql')->table($table)->get();
                if ($mysqlData->isEmpty()) continue;

                $colNames = [];
                foreach ($mysqlCols as $col) $colNames[] = $col->Field;
                $colsSql = '"' . implode('", "', $colNames) . '"';
                $placeholders = ':' . implode(', :', $colNames);

                foreach ($mysqlData->chunk(100) as $chunk) {
                    foreach ($chunk as $row) {
                        $data = [];
                        foreach ($colNames as $colName) {
                            $val = $row->$colName ?? null;
                            if ($val === '0000-00-00' || $val === '0000-00-00 00:00:00') $val = null;
                            $data[$colName] = $val;
                        }
                        try {
                            DB::connection('oracle')->table($table)->insert($data);
                            $imported++;
                        } catch (Exception $e) {
                            $errors[] = "Veri aktarma hatasi ($table): " . $e->getMessage();
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'imported' => $imported,
                'errors' => $errors,
                'message' => "$imported kayit Oracle'a aktarildi.",
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
