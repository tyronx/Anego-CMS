<?php
if (!function_exists('mysql_connect')) {
	Bail(__('No PHP MySQL Support on this Server. Please install it.'));
}

/**** Init MySQL ****/
$sql_link=@mysql_connect(HOST,SQLUSER,SQLPASS)
	or BailErr(__('Our Database is not reachable. Please try again later!'),mysql_error(),true);

if(!@mysql_select_db(SQLDB)) {
	$sql_link=0;
	BailErr(__('Our Database is not reachable. Please try again later!'),mysql_error(),true);
}

mysql_query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");