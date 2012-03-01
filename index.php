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

/* mod_rewrite test for setup.php */
if(isset($_GET['rewrite'])) exit ('yes');

/* Main functions file for page printing, db access, error reporting, etc. */
include("core.php");

/* Some AJAX Callbacks */
if(isset($_GET['a']) || isset($_POST['a'])) {
	include('inc/ajax.php');
}

/* Display page */
PrintPage(CurrentPage());

?>
