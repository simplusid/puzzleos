<?php
/**
 * PuzzleOS
 * Build your own web-based application
 *
 * @author       Mohammad Ardika Rifqi <rifweb.android@gmail.com>
 * @copyright    2014-2018 MARAL INDUSTRIES
 */

/* This file responsible for handling system table structure */

/* Table `userdata` */
$a = new DatabaseTableBuilder;
$a->addColumn("app", "VARCHAR(50)");
$a->addColumn("identifier", "VARCHAR(500)");
$a->addColumn("physical_path", "VARCHAR(500)");
$a->addColumn("mime_type", "VARCHAR(100)");
$a->addColumn("ver", "INT");
$a->addColumn("secure", "TINYINT(1)");

$a->createIndex("main", ["app", "identifier"]);
$a->createIndex("path", ["physical_path"]);

Database::newStructure("userdata", $a);

/* Table `multidomain_config` */
$a = new DatabaseTableBuilder;
$a->addColumn("host", "VARCHAR(50)")->setAsPrimaryKey();
$a->addColumn("default_app", "VARCHAR(50)");
$a->addColumn("default_template", "VARCHAR(50)");
$a->addColumn("restricted_app");

$a->newInitialRow("{root}", "admin", "blank", "[]");

Database::newStructure("multidomain_config", $a);

/* Table `app_security` */
$a = new DatabaseTableBuilder;
$a->addColumn("rootname", "VARCHAR(50)")->setAsPrimaryKey();
$a->addColumn("group", "INT")->allowNull(true);
$a->addColumn("system", "INT")->defaultValue("0");

$a->newInitialRow("admin", null, 1);
$a->newInitialRow("bootstrap", null, 1);
$a->newInitialRow("fontawesome", null, 1);
$a->newInitialRow("menus", null, 1);
$a->newInitialRow("page_control", null, 1);
$a->newInitialRow("phpmailer", null, 1);
$a->newInitialRow("search_box", null, 1);
$a->newInitialRow("tinymce", null, 1);
$a->newInitialRow("upload_img_ajax", null, 1);
$a->newInitialRow("users", null, 1);

Database::newStructure("app_security", $a);

/* Table `sessions` */
$a = new DatabaseTableBuilder;
$a->addColumn("session_id", "CHAR(40)")->setAsPrimaryKey();
$a->addColumn("content", "TEXT");
$a->addColumn("client", "TEXT");
$a->addColumn("cnf", "TEXT");
$a->addColumn("start", "INT");
$a->addColumn("expire", "INT");
$a->addColumn("user", "INT")->allowNull(true);

$a->createIndex("ses", ["user", "session_id"]);
$a->createIndex("expire", ["expire"]);

Database::newStructure("sessions", $a);

/* Table `cron` */
$a = new DatabaseTableBuilder;
$a->addColumn("key", "VARCHAR(50)")->setAsPrimaryKey();
$a->addColumn("last_exec", "INT");

Database::newStructure("cron", $a);

unset($a);

?>
