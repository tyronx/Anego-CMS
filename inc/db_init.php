<?php
if (!function_exists('mysqli_connect')) {
	Bail(__('No PHP MySQL Support on this Server. Please install it.'));
}

/**** Init MySQL ****/
$sql_link=@mysqli_connect(HOST,SQLUSER,SQLPASS)
	or BailErr(__('Our Database is not reachable. Please try again later!'),mysqli_error($sql_link),true);

if(!@mysqli_select_db($sql_link, SQLDB)) {
	BailErr(__('Our Database is not reachable. Please try again later!'),mysqli_error($sql_link),true);
}

mysqli_query($sql_link, "SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");
mysqli_set_charset($sql_link, 'utf8');