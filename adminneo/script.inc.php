<?php

namespace AdminNeo;

header("Content-Type: text/javascript; charset=utf-8");

if ($_GET["script"] == "db") {
	$sums = ["Data_length" => 0, "Index_length" => 0, "Data_free" => 0];
	$data = [];

	// SQLite (sevenx_dse): the row counts come from dse_row_counts(), which reuses recent counts instead of reading
	// every table on every visit and labels reused ones; this request only reads, so the connection is read-only.
	$rows_meta = null;
	if (DIALECT == "sqlite" && function_exists('AdminNeo\dse_row_counts')) {
		Connection::get()->query("PRAGMA query_only = 1");
		$statuses = table_status('', true);
		$counted = [];
		foreach ($statuses as $name => $table_status) {
			if (!is_view($table_status)) {
				$counted[] = (string) $name;
			}
		}
		foreach (dse_row_counts($counted, !empty($_GET["dse_recount"]), $rows_meta) as $name => $rows) {
			$statuses[$name]["Rows"] = $rows;
		}
	} else {
		$statuses = table_status();
	}

	foreach ($statuses as $name => $table_status) {
		$data["Comment-$name"] = h($table_status["Comment"]);

		if (!is_view($table_status)) {
			foreach (["Engine", "Collation"] as $key) {
				$data["$key-$name"] = h($table_status[$key]);
			}
			foreach ($sums + ["Auto_increment" => 0, "Rows" => 0] as $key => $val) {
				if ($table_status[$key] != "") {
					$val = format_number($table_status[$key]);
					if ($val >= 0) {
						$data["$key-$name"] = ($key == "Rows" && (($val && $table_status["Engine"] == (DIALECT == "pgsql" ? "table" : "InnoDB")) || ($rows_meta && $rows_meta["stale"]))
							? "~ $val"
							: $val
						);
					}
					if (isset($sums[$key])) {
						// ignore innodb_file_per_table because it is not active for tables created before it was enabled
						$sums[$key] += ($table_status["Engine"] != "InnoDB" || $key != "Data_free" ? $table_status[$key] : 0);
					}
				} elseif (array_key_exists($key, $table_status)) {
					$data["$key-$name"] = "?";
				}
			}
		}
	}

	foreach ($sums as $key => $val) {
		$data["sum-$key"] = format_number($val);
	}
	if ($rows_meta) {
		$data["dse-rows-note"] = dse_row_counts_note($rows_meta);
	}

	echo json_encode($data, JSON_UNESCAPED_UNICODE);

} elseif ($_GET["script"] == "kill") {
	Connection::get()->query("KILL " . number($_POST["kill"]));

} else { // connect
	$data = [];
	foreach (count_tables(Admin::get()->getDatabases()) as $db => $val) {
		$data["tables-$db"] = $val;
		$data["size-$db"] = db_size($db);
	}

	echo json_encode($data, JSON_UNESCAPED_UNICODE);
}

throw new \AdminNeo\EzExit(); // don't print footer
