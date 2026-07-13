<?php
$conn = oci_connect('aykome_user', 'aykome123', 'host.docker.internal:1521/FREEPDB1');
if (!$conn) { $e = oci_error(); die('Oracle baglanti hatasi: ' . $e['message']); }

$mode = $_GET['mode'] ?? '';
$tab = $_GET['tab'] ?? 'data';
$msg = '';

// Export handler
if (isset($_GET['export']) && isset($_GET['table'])) {
    $table = $_GET['table'];
    $fmt = $_GET['export'];
    $colNames = getCols($conn, $table);
    $colsSql = implode(', ', array_map(fn($c) => '"' . $c['COLUMN_NAME'] . '"', $colNames));
    $st = oci_parse($conn, "SELECT $colsSql FROM \"$table\"");
    oci_execute($st);
    oci_fetch_all($st, $rows, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
    oci_free_statement($st);

    if ($fmt === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $table . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
        fputcsv($out, array_keys($rows[0] ?? []));
        foreach ($rows as $r) fputcsv($out, $r);
        fclose($out);
        exit;
    } elseif ($fmt === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    } elseif ($fmt === 'insert') {
        header('Content-Type: text/plain; charset=utf-8');
        foreach ($rows as $r) {
            $cols = []; $vals = [];
            foreach ($r as $k => $v) {
                $cols[] = '"' . $k . '"';
                $vals[] = $v !== null ? "'" . str_replace("'", "''", $v) . "'" : 'NULL';
            }
            echo "INSERT INTO \"$table\" (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n";
        }
        exit;
    }
}

if ($_POST) {
    $table = $_POST['table'];
    $cols = json_decode($_POST['cols'], true);
    if ($_POST['mode'] === 'insert') {
        $names = []; $vals = [];
        foreach ($cols as $c) {
            if ($c === 'ID' && empty($_POST['col_'.$c])) continue;
            $v = $_POST['col_'.$c] ?? '';
            $names[] = "\"$c\"";
            $vals[] = $v !== '' ? "'" . str_replace("'", "''", $v) . "'" : 'NULL';
        }
        if ($names) {
            $sql = "INSERT INTO \"$table\" (" . implode(',',$names) . ") VALUES (" . implode(',',$vals) . ")";
            $st = oci_parse($conn, $sql);
            if (oci_execute($st)) $msg = 'Eklendi'; else { $e = oci_error($st); $msg = 'Hata: ' . $e['message']; }
            @oci_free_statement($st);
        }
    } elseif ($_POST['mode'] === 'update') {
        $sets = [];
        foreach ($cols as $c) {
            $v = $_POST['col_'.$c] ?? '';
            $sets[] = "\"$c\" = " . ($v !== '' ? "'" . str_replace("'", "''", $v) . "'" : 'NULL');
        }
        $pkCol = $_POST['pk_col']; $pkVal = $_POST['pk_val'];
        if ($sets && $pkCol && $pkVal !== '') {
            $sql = "UPDATE \"$table\" SET " . implode(',',$sets) . " WHERE \"$pkCol\" = '" . str_replace("'", "''", $pkVal) . "'";
            $st = oci_parse($conn, $sql);
            if (oci_execute($st)) $msg = 'Guncellendi'; else { $e = oci_error($st); $msg = 'Hata: ' . $e['message']; }
            @oci_free_statement($st);
        }
    } elseif ($_POST['mode'] === 'delete') {
        $pkCol = $_POST['pk_col']; $pkVal = $_POST['pk_val'];
        if ($pkCol && $pkVal !== '') {
            $st = oci_parse($conn, "DELETE FROM \"$table\" WHERE \"$pkCol\" = '" . str_replace("'", "''", $pkVal) . "'");
            if (oci_execute($st)) $msg = 'Silindi'; else { $e = oci_error($st); $msg = 'Hata: ' . $e['message']; }
            @oci_free_statement($st);
        }
    }
}

function getPk($conn, $table) {
    $st = oci_parse($conn, "SELECT cols.column_name FROM all_constraints cons, all_cons_columns cols WHERE cols.table_name = UPPER('$table') AND cons.constraint_type = 'P' AND cons.constraint_name = cols.constraint_name AND cons.owner = cols.owner AND ROWNUM = 1");
    oci_execute($st); $r = oci_fetch_assoc($st); @oci_free_statement($st);
    return $r['COLUMN_NAME'] ?? null;
}
function getCols($conn, $table) {
    $st = oci_parse($conn, "SELECT column_name, data_type, nullable, data_default, char_length FROM user_tab_cols WHERE table_name = UPPER('$table') ORDER BY column_id");
    oci_execute($st); $r = []; while ($row = oci_fetch_assoc($st)) $r[] = $row;
    @oci_free_statement($st); return $r;
}
function getIndexes($conn, $table) {
    $st = oci_parse($conn, "SELECT index_name, column_name, column_position, descend, uniqueness FROM user_ind_columns i JOIN user_indexes u USING(index_name) WHERE i.table_name = UPPER('$table') ORDER BY index_name, column_position");
    oci_execute($st); $r = []; while ($row = oci_fetch_assoc($st)) $r[] = $row;
    @oci_free_statement($st); return $r;
}
function getTriggers($conn, $table) {
    $st = oci_parse($conn, "SELECT trigger_name, trigger_type, triggering_event, status FROM user_triggers WHERE table_name = UPPER('$table') ORDER BY trigger_name");
    oci_execute($st); $r = []; while ($row = oci_fetch_assoc($st)) $r[] = $row;
    @oci_free_statement($st); return $r;
}
function getProcedures($conn) {
    $st = oci_parse($conn, "SELECT object_name, object_type, status FROM user_objects WHERE object_type IN ('PROCEDURE','FUNCTION','PACKAGE','PACKAGE BODY') ORDER BY object_name");
    oci_execute($st); $r = []; while ($row = oci_fetch_assoc($st)) $r[] = $row;
    @oci_free_statement($st); return $r;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>AYKOME Oracle Browser</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; background:#0f172a; color:#e2e8f0; font-size:13px; }
.header { background:linear-gradient(135deg,#1e293b,#0f172a); border-bottom:1px solid #334155; padding:12px 20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; }
.header h1 { font-size:16px; font-weight:600; display:flex; align-items:center; gap:10px; }
.header h1 a { color:#f8fafc; text-decoration:none; }
.header h1 .badge { font-size:10px; background:#2563eb; color:#fff; padding:2px 8px; border-radius:4px; font-weight:400; }
.header nav { display:flex; gap:4px; }
.header nav a { color:#94a3b8; text-decoration:none; font-size:12px; padding:5px 12px; border-radius:6px; transition:.15s; }
.header nav a:hover { background:#1e293b; color:#f8fafc; }
.header nav a.active { background:#2563eb; color:#fff; }
.header .info { font-size:11px; color:#64748b; }
.container { display:flex; min-height:calc(100vh - 49px); }
.sidebar { width:220px; background:#1e293b; border-right:1px solid #334155; padding:6px 0; overflow-y:auto; flex-shrink:0; }
.sidebar h3 { font-size:10px; text-transform:uppercase; color:#64748b; padding:10px 14px 4px; letter-spacing:.8px; }
.sidebar a { display:flex; justify-content:space-between; padding:5px 14px; color:#94a3b8; text-decoration:none; font-size:12px; }
.sidebar a:hover { background:#334155; color:#e2e8f0; }
.sidebar a.active { background:#2563eb20; color:#60a5fa; border-right:2px solid #2563eb; font-weight:500; }
.sidebar a .cnt { font-size:10px; color:#475569; }
.content { flex:1; padding:16px 20px; overflow-x:auto; min-width:0; }
.msg { background:#065f4620; border:1px solid #065f46; color:#6ee7b7; padding:8px 14px; border-radius:6px; margin-bottom:12px; font-size:12px; }
.msg.err { background:#7f1d1d20; border-color:#7f1d1d; color:#fca5a5; }
table { border-collapse:collapse; width:100%; background:#1e293b; border-radius:6px; overflow:hidden; }
th { background:#334155; color:#e2e8f0; font-weight:500; padding:8px 10px; text-align:left; font-size:10px; text-transform:uppercase; letter-spacing:.5px; white-space:nowrap; }
td { padding:6px 10px; border-bottom:1px solid #334155; font-size:12px; max-width:240px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#cbd5e1; }
tr:hover td { background:#33415540; }
.actions a { text-decoration:none; margin:0 2px; opacity:.6; transition:.15s; font-size:12px; }
.actions a:hover { opacity:1; }
.null { color:#475569; font-style:italic; }
.bar { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; flex-wrap:wrap; gap:8px; }
.bar h2 { font-size:16px; font-weight:600; }
.count { color:#64748b; font-size:11px; }
.pagination { margin:10px 0; display:flex; gap:4px; align-items:center; flex-wrap:wrap; }
.pagination a, .pagination strong { padding:4px 9px; border-radius:4px; font-size:11px; text-decoration:none; }
.pagination a { background:#334155; color:#94a3b8; }
.pagination a:hover { background:#475569; color:#e2e8f0; }
.pagination strong { background:#2563eb; color:#fff; }
.search-box { display:flex; gap:6px; margin-bottom:10px; }
.search-box input { flex:1; padding:7px 12px; background:#1e293b; border:1px solid #334155; border-radius:6px; font-size:12px; color:#e2e8f0; }
.search-box input:focus { border-color:#2563eb; outline:none; }
.search-box input::placeholder { color:#475569; }
form { background:#1e293b; padding:16px; border-radius:6px; max-width:700px; }
form label { display:block; font-size:10px; color:#94a3b8; margin:8px 0 3px; text-transform:uppercase; letter-spacing:.5px; }
form input, form textarea { width:100%; padding:7px 10px; background:#0f172a; border:1px solid #334155; border-radius:4px; font-size:12px; color:#e2e8f0; font-family:inherit; }
form input:focus, form textarea:focus { border-color:#2563eb; outline:none; }
.btn { display:inline-flex; align-items:center; gap:4px; padding:6px 14px; background:#2563eb; color:#fff; border:none; border-radius:6px; font-size:12px; cursor:pointer; text-decoration:none; font-weight:500; transition:.15s; }
.btn:hover { background:#1d4ed8; }
.btn-sm { padding:4px 8px; font-size:10px; border-radius:4px; }
.btn-green { background:#059669; }
.btn-green:hover { background:#047857; }
.btn-red { background:#dc2626; }
.btn-red:hover { background:#b91c1c; }
.btn-outline { background:transparent; color:#94a3b8; border:1px solid #334155; }
.btn-outline:hover { background:#334155; color:#e2e8f0; }
.tabs { display:flex; gap:2px; margin-bottom:12px; border-bottom:1px solid #334155; padding-bottom:0; }
.tabs a { padding:7px 16px; color:#64748b; text-decoration:none; font-size:12px; border-bottom:2px solid transparent; margin-bottom:-1px; transition:.15s; }
.tabs a:hover { color:#e2e8f0; }
.tabs a.active { color:#60a5fa; border-bottom-color:#2563eb; font-weight:500; }
.stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(120px,1fr)); gap:10px; margin-bottom:20px; }
.stat { background:#1e293b; padding:16px; border-radius:8px; text-align:center; }
.stat .n { font-size:28px; font-weight:700; color:#60a5fa; }
.stat .l { font-size:10px; color:#64748b; text-transform:uppercase; letter-spacing:.5px; margin-top:4px; }
.struct-table th, .struct-table td { font-size:11px; padding:5px 8px; }
.dropdown { position:relative; display:inline-block; }
.dropdown-menu { display:none; position:absolute; right:0; top:100%; background:#1e293b; border:1px solid #334155; border-radius:6px; min-width:140px; z-index:10; }
.dropdown-menu a { display:block; padding:7px 14px; color:#94a3b8; text-decoration:none; font-size:12px; }
.dropdown-menu a:hover { background:#334155; color:#e2e8f0; }
.dropdown:hover .dropdown-menu { display:block; }
.welcome { text-align:center; padding:40px 20px; }
.welcome h2 { font-size:20px; margin-bottom:6px; color:#f8fafc; }
.welcome p { color:#64748b; font-size:13px; margin-bottom:20px; }
.welcome .big { font-size:48px; color:#2563eb; font-weight:700; }
.welcome .sub { font-size:12px; color:#475569; margin-top:4px; }
.sql-editor { background:#0f172a; border:1px solid #334155; border-radius:6px; font-family:monospace; font-size:12px; color:#e2e8f0; padding:10px; width:100%; min-height:120px; resize:vertical; }
</style>
</head>
<body>
<div class="header">
    <h1><a href="?">AYKOME Oracle</a> <span class="badge">v3</span></h1>
    <nav>
        <a href="?" class="<?= !isset($_GET['table']) && !isset($_GET['action']) ? 'active' : '' ?>">Tablolar</a>
        <a href="?action=procs" class="<?= ($_GET['action'] ?? '') === 'procs' ? 'active' : '' ?>">Proc/Func</a>
        <a href="?action=sql" class="<?= ($_GET['action'] ?? '') === 'sql' ? 'active' : '' ?>">SQL</a>
    </nav>
    <span class="info">aykome_user@freepdb1</span>
</div>
<div class="container">
<div class="sidebar">
    <h3>Tablolar</h3>
    <?php
    $st = oci_parse($conn, "SELECT table_name, (SELECT COUNT(*) FROM user_tab_columns WHERE table_name = t.table_name) cols FROM user_tables t ORDER BY table_name");
    oci_execute($st);
    while ($row = oci_fetch_assoc($st)) {
        $t = $row['TABLE_NAME'];
        $active = ($_GET['table'] ?? '') === $t ? " class='active'" : '';
        echo "<a href='?table=$t'$active><span>$t</span><span class='cnt'>{$row['COLS']}</span></a>";
    }
    oci_free_statement($st);
    ?>
</div>
<div class="content">
<?php if ($msg): ?><div class="msg<?= str_starts_with($msg, 'Hata') ? ' err' : '' ?>"><?= $msg ?></div><?php endif; ?>

<?php if (($_GET['action'] ?? '') === 'sql'): ?>
    <form method="get">
        <input type="hidden" name="action" value="sql">
        <label>SQL Sorgusu</label>
        <textarea name="query" class="sql-editor"><?= htmlspecialchars($_GET['query'] ?? 'SELECT * FROM users') ?></textarea>
        <br><br>
        <button class="btn" type="submit">Calistir</button>
        <a href="?" class="btn btn-outline">Geri</a>
    </form>
    <?php if (!empty($_GET['query'])): ?>
        <br>
        <?php
        $st = @oci_parse($conn, $_GET['query']);
        if ($st && @oci_execute($st)) {
            @oci_fetch_all($st, $rows, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
            if (is_array($rows) && count($rows) > 0) {
                echo '<table><tr>';
                foreach (array_keys($rows[0]) as $col) echo '<th>' . htmlspecialchars($col) . '</th>';
                echo '</tr>';
                foreach ($rows as $row) {
                    echo '<tr>';
                    foreach ($row as $val) echo '<td>' . htmlspecialchars((string)($val ?? 'NULL')) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                echo '<div class="count" style="margin-top:6px;">' . count($rows) . ' satir</div>';
            } else {
                echo '<div class="count" style="margin-top:6px;">Sorgu calisti.</div>';
            }
            @oci_free_statement($st);
        } else {
            $e = oci_error($st);
            echo '<div class="msg err">' . htmlspecialchars($e['message'] ?? 'Hata') . '</div>';
        }
        ?>
    <?php endif; ?>

<?php elseif (($_GET['action'] ?? '') === 'procs'): ?>
    <h2 style="font-size:16px;margin-bottom:12px;">Procedures & Functions</h2>
    <?php $procs = getProcedures($conn); if ($procs): ?>
    <table>
        <tr><th>Name</th><th>Type</th><th>Status</th></tr>
        <?php foreach ($procs as $p): ?>
        <tr>
            <td><?= $p['OBJECT_NAME'] ?></td>
            <td><?= $p['OBJECT_TYPE'] ?></td>
            <td style="color:<?= $p['STATUS'] === 'VALID' ? '#6ee7b7' : '#fca5a5' ?>;"><?= $p['STATUS'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: echo '<div class="count">Prosedur veya fonksiyon bulunamadi.</div>'; endif; ?>

<?php elseif ($mode === 'edit' && isset($_GET['table'])): ?>
    <?php
    $table = $_GET['table']; $pk = $_GET['pk'];
    $pkCol = getPk($conn, $table);
    $colNames = getCols($conn, $table);
    $st = oci_parse($conn, "SELECT * FROM \"$table\" WHERE \"$pkCol\" = '" . str_replace("'", "''", $pk) . "'");
    oci_execute($st); $rowData = oci_fetch_assoc($st); @oci_free_statement($st);
    if (!$rowData) { echo '<div class="msg err">Kayit bulunamadi</div>'; exit; }
    ?>
    <div class="bar"><h2>Duzenle: <?= $table ?></h2><span class="count"><?= $pkCol ?> = <?= htmlspecialchars($pk) ?></span></div>
    <form method="post">
        <input type="hidden" name="mode" value="update">
        <input type="hidden" name="table" value="<?= $table ?>">
        <input type="hidden" name="pk_col" value="<?= $pkCol ?>">
        <input type="hidden" name="pk_val" value="<?= htmlspecialchars($pk) ?>">
        <input type="hidden" name="cols" value='<?= json_encode(array_map(fn($c) => $c['COLUMN_NAME'], $colNames)) ?>'>
        <?php foreach ($colNames as $col): ?>
            <label><?= $col['COLUMN_NAME'] ?> <span style="color:#475569;text-transform:none;letter-spacing:0;">(<?= $col['DATA_TYPE'] ?>)</span></label>
            <?php $val = $rowData[$col['COLUMN_NAME']] ?? ''; ?>
            <?php if (str_contains($col['DATA_TYPE'], 'CLOB') || mb_strlen($val ?? '') > 60): ?>
                <textarea name="col_<?= $col['COLUMN_NAME'] ?>" rows="3"><?= htmlspecialchars($val ?? '') ?></textarea>
            <?php else: ?>
                <input name="col_<?= $col['COLUMN_NAME'] ?>" value="<?= htmlspecialchars($val ?? '') ?>">
            <?php endif; ?>
        <?php endforeach; ?>
        <br><br>
        <button class="btn btn-green" type="submit">Kaydet</button>
        <a href="?table=<?= $table ?>" class="btn btn-outline">Iptal</a>
    </form>

<?php elseif ($mode === 'insert' && isset($_GET['table'])): ?>
    <?php
    $table = $_GET['table'];
    $colNames = getCols($conn, $table);
    ?>
    <div class="bar"><h2>Yeni Kayit: <?= $table ?></h2></div>
    <form method="post">
        <input type="hidden" name="mode" value="insert">
        <input type="hidden" name="table" value="<?= $table ?>">
        <input type="hidden" name="cols" value='<?= json_encode(array_map(fn($c) => $c['COLUMN_NAME'], $colNames)) ?>'>
        <?php foreach ($colNames as $col): ?>
            <label><?= $col['COLUMN_NAME'] ?> <span style="color:#475569;text-transform:none;letter-spacing:0;">(<?= $col['DATA_TYPE'] ?>)</span></label>
            <?php if (str_contains($col['DATA_TYPE'], 'CLOB')): ?>
                <textarea name="col_<?= $col['COLUMN_NAME'] ?>" rows="3"></textarea>
            <?php else: ?>
                <input name="col_<?= $col['COLUMN_NAME'] ?>">
            <?php endif; ?>
        <?php endforeach; ?>
        <br><br>
        <button class="btn btn-green" type="submit">Kaydet</button>
        <a href="?table=<?= $table ?>" class="btn btn-outline">Iptal</a>
    </form>

<?php elseif ($mode === 'delete' && isset($_GET['table'])): ?>
    <?php $table = $_GET['table']; $pkCol = $_GET['pk_col']; $pkVal = $_GET['pk']; ?>
    <div class="bar"><h2>Sil: <?= $table ?></h2></div>
    <p style="color:#94a3b8;margin-bottom:12px;">"<?= $pkCol ?> = <?= htmlspecialchars($pkVal) ?>" kaydini silmek istediginize emin misiniz?</p>
    <form method="post">
        <input type="hidden" name="mode" value="delete">
        <input type="hidden" name="table" value="<?= $table ?>">
        <input type="hidden" name="pk_col" value="<?= $pkCol ?>">
        <input type="hidden" name="pk_val" value="<?= htmlspecialchars($pkVal) ?>">
        <button class="btn btn-red" type="submit">Sil</button>
        <a href="?table=<?= $table ?>" class="btn btn-outline">Iptal</a>
    </form>

<?php elseif (isset($_GET['table'])): ?>
    <?php
    $table = $_GET['table'];
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 50;
    $offset = ($page - 1) * $perPage;
    $pkCol = getPk($conn, $table);
    $colNames = getCols($conn, $table);
    $search = $_GET['search'] ?? '';

    $where = '';
    if ($search) {
        $like = str_replace("'", "''", $search);
        $likes = [];
        foreach ($colNames as $col) {
            if (str_contains($col['DATA_TYPE'], 'CHAR') || str_contains($col['DATA_TYPE'], 'CLOB') || str_contains($col['DATA_TYPE'], 'VARCHAR')) {
                $likes[] = "\"{$col['COLUMN_NAME']}\" LIKE '%$like%'";
            }
        }
        if ($likes) $where = ' WHERE ' . implode(' OR ', $likes);
    }

    // Total count
    $st = oci_parse($conn, "SELECT COUNT(*) FROM \"$table\"$where");
    oci_execute($st); $total = oci_fetch_array($st)[0]; @oci_free_statement($st);
    $totalPages = ceil($total / $perPage);
    ?>

    <div class="bar">
        <div><h2><?= $table ?></h2> <span class="count"><?= $total ?> kayit · <?= count($colNames) ?> sutun</span></div>
        <div style="display:flex;gap:4px;align-items:center;">
            <div class="dropdown">
                <button class="btn btn-sm btn-outline">Export</button>
                <div class="dropdown-menu">
                    <a href="?export=csv&table=<?= $table ?>">CSV</a>
                    <a href="?export=json&table=<?= $table ?>">JSON</a>
                    <a href="?export=insert&table=<?= $table ?>">INSERT SQL</a>
                </div>
            </div>
            <a href="?mode=insert&table=<?= $table ?>" class="btn btn-green btn-sm">+ Yeni</a>
        </div>
    </div>

    <div class="tabs">
        <a href="?table=<?= $table ?>&tab=data" class="<?= $tab === 'data' ? 'active' : '' ?>">Veri</a>
        <a href="?table=<?= $table ?>&tab=structure" class="<?= $tab === 'structure' ? 'active' : '' ?>">Yapi</a>
        <a href="?table=<?= $table ?>&tab=indexes" class="<?= $tab === 'indexes' ? 'active' : '' ?>">Index</a>
        <a href="?table=<?= $table ?>&tab=triggers" class="<?= $tab === 'triggers' ? 'active' : '' ?>">Trigger</a>
    </div>

    <?php if ($tab === 'structure'): ?>
        <table class="struct-table">
            <tr><th>Kolon</th><th>Tip</th><th>Uzunluk</th><th>Null</th><th>Default</th><th>PK</th></tr>
            <?php foreach ($colNames as $c):
                $isPk = $c['COLUMN_NAME'] === $pkCol;
            ?>
            <tr>
                <td style="font-weight:500;color:#e2e8f0;"><?= $c['COLUMN_NAME'] ?></td>
                <td style="color:#60a5fa;"><?= $c['DATA_TYPE'] ?></td>
                <td><?= $c['CHAR_LENGTH'] ?: '-' ?></td>
                <td style="color:<?= $c['NULLABLE'] === 'Y' ? '#f59e0b' : '#6ee7b7' ?>;"><?= $c['NULLABLE'] === 'Y' ? 'YES' : 'NO' ?></td>
                <td class="null"><?= $c['DATA_DEFAULT'] ?? '<span class="null">-</span>' ?></td>
                <td><?= $isPk ? 'PK' : '' ?></td>
            </tr>
            <?php endforeach; ?>
        </table>

    <?php elseif ($tab === 'indexes'): ?>
        <?php $indexes = getIndexes($conn, $table); if ($indexes): ?>
        <table class="struct-table">
            <tr><th>Index</th><th>Kolon</th><th>Sirasi</th><th>Yon</th><th>Unique</th></tr>
            <?php foreach ($indexes as $ix): ?>
            <tr>
                <td style="font-weight:500;"><?= $ix['INDEX_NAME'] ?></td>
                <td><?= $ix['COLUMN_NAME'] ?></td>
                <td><?= $ix['COLUMN_POSITION'] ?></td>
                <td><?= $ix['DESCEND'] ?></td>
                <td><?= $ix['UNIQUENESS'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php else: echo '<div class="count">Index bulunamadi.</div>'; endif; ?>

    <?php elseif ($tab === 'triggers'): ?>
        <?php $triggers = getTriggers($conn, $table); if ($triggers): ?>
        <table class="struct-table">
            <tr><th>Trigger</th><th>Tip</th><th>Event</th><th>Durum</th></tr>
            <?php foreach ($triggers as $tr): ?>
            <tr>
                <td style="font-weight:500;"><?= $tr['TRIGGER_NAME'] ?></td>
                <td><?= $tr['TRIGGER_TYPE'] ?></td>
                <td><?= $tr['TRIGGERING_EVENT'] ?></td>
                <td style="color:<?= $tr['STATUS'] === 'ENABLED' ? '#6ee7b7' : '#fca5a5' ?>;"><?= $tr['STATUS'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php else: echo '<div class="count">Trigger bulunamadi.</div>'; endif; ?>

    <?php else: ?>
        <!-- DATA TAB -->
        <div class="search-box">
            <form method="get" style="display:flex;gap:6px;flex:1;background:none;padding:0;">
                <input type="hidden" name="table" value="<?= $table ?>">
                <input type="text" name="search" placeholder="Tum sutunlarda ara..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-sm" type="submit">Ara</button>
                <?php if ($search): ?><a href="?table=<?= $table ?>" class="btn btn-sm btn-outline">Temizle</a><?php endif; ?>
            </form>
        </div>
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <?= $p === $page ? "<strong>$p</strong>" : "<a href='?table=$table&tab=data&page=$p&search=$search'>$p</a>" ?>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <?php
        $colsSql = implode(', ', array_map(fn($c) => '"' . $c['COLUMN_NAME'] . '"', $colNames));
        $st = oci_parse($conn, "SELECT $colsSql FROM \"$table\"$where OFFSET $offset ROWS FETCH NEXT $perPage ROWS ONLY");
        oci_execute($st);
        $rows = []; @oci_fetch_all($st, $rows, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
        @oci_free_statement($st);
        ?>
        <table>
            <tr>
                <th style="width:60px;text-align:center;">Islem</th>
                <?php foreach ($colNames as $col): ?>
                    <th><?= $col['COLUMN_NAME'] ?> <span class="type" style="color:#64748b;font-weight:400;text-transform:none;letter-spacing:0;font-size:10px;"><?= $col['DATA_TYPE'] ?></span></th>
                <?php endforeach; ?>
            </tr>
            <?php foreach ($rows as $row): ?>
            <?php $pkVal = $pkCol ? ($row[$pkCol] ?? '') : ''; ?>
            <tr>
                <td style="text-align:center;" class="actions">
                    <?php if ($pkVal !== ''): ?>
                        <a href="?mode=edit&table=<?= $table ?>&pk=<?= urlencode((string)$pkVal) ?>" title="Düzenle">✏️</a>
                        <a href="?mode=delete&table=<?= $table ?>&pk=<?= urlencode((string)$pkVal) ?>&pk_col=<?= $pkCol ?>" title="Sil" onclick="return confirm('Emin misin?')">🗑️</a>
                    <?php endif; ?>
                </td>
                <?php foreach ($row as $val): ?>
                    <td title="<?= htmlspecialchars((string)($val ?? '')) ?>"><?= $val !== null ? htmlspecialchars(mb_substr((string)$val, 0, 120)) : '<span class="null">NULL</span>' ?></td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

<?php else: ?>
    <div class="welcome">
        <h2>AYKOME Oracle Veritabani Yoneticisi</h2>
        <p>phpMyAdmin benzeri Oracle Browser — CRUD + Yapi + Index + Trigger + Export</p>
        <?php
        $st = oci_parse($conn, "SELECT COUNT(*) FROM user_tables"); oci_execute($st);
        $tableCount = oci_fetch_array($st)[0]; @oci_free_statement($st);
        $st2 = oci_parse($conn, "SELECT COUNT(*) FROM user_views"); oci_execute($st2);
        $viewCount = oci_fetch_array($st2)[0]; @oci_free_statement($st2);
        $st3 = oci_parse($conn, "SELECT COUNT(*) FROM user_sequences"); oci_execute($st3);
        $seqCount = oci_fetch_array($st3)[0]; @oci_free_statement($st3);
        $st4 = oci_parse($conn, "SELECT COUNT(*) FROM user_triggers"); oci_execute($st4);
        $trgCount = oci_fetch_array($st4)[0]; @oci_free_statement($st4);
        ?>
        <div class="stats">
            <div class="stat"><div class="n"><?= $tableCount ?></div><div class="l">Tablo</div></div>
            <div class="stat"><div class="n"><?= $viewCount ?></div><div class="l">View</div></div>
            <div class="stat"><div class="n"><?= $seqCount ?></div><div class="l">Sequence</div></div>
            <div class="stat"><div class="n"><?= $trgCount ?></div><div class="l">Trigger</div></div>
        </div>
        <p style="color:#475569;font-size:12px;">Oracle 21c · FREEPDB1 · aykome_user · <?= $tableCount ?> tablo</p>
    </div>
<?php endif; ?>
</div>
</div>
</body>
</html>
<?php oci_close($conn); ?>