<?
/* Anego CMS - A Ajax based content managment system
 * Copyright (C) 2011, Tyron Madlener <info@anego.at>
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * See http://creativecommons.org/licenses/GPL/2.0/ for details
 *
 * If you redistribute and/or modify this software, you must attribute the
 * original authors.
 */

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

/* mod_rewrite test for setup.php */
if(isset($_GET['rewrite'])) exit ('yes');

if (file_exists("setup.php")) echo "<h3 align=\"center\">setup.php found, please make sure to delete it!</h3>";

/* Main functions file for page printing, db access, error reporting, etc. */
include("core.php");

/* Some AJAX Callbacks */
if(isset($_GET['a']) || isset($_POST['a'])) {
 include('inc/ajax.php');
}


/* Display page */
PrintPage(CurrentPage());

?>
