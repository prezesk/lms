<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2010 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

$DB->BeginTrans();

$tables = array(
    'documents' => 'paytype',
    'customers' => 'paytype',
    'divisions' => 'inv_paytype',
);

// we cannot use trans() here
$paytypes = array(
    1   => array('cash', 'gotówka'),
    2   => array('transfer', 'przelew'),
    3   => array('transfer/cash', 'przelew/karta'),
    4   => array('card', 'karta'),
    5   => array('compensation','kompensata'),
    6   => array('barter'),
    7   => array('contract', 'umowa'),
);

foreach ($tables as $tab => $col)
{
    $DB->Execute("ALTER TABLE $tab ADD paytype2 smallint DEFAULT NULL");

    $types = $DB->GetCol("SELECT LOWER($col) AS paytype FROM $tab GROUP BY LOWER($col)");

    foreach ($types as $type) {
        foreach ($paytypes as $pid => $pname)
            if (in_array($type, $pname)) {
                $DB->Execute("UPDATE $tab SET paytype2 = $pid WHERE LOWER($col) = ?", array($type));
                break;
            }
    }

    $DB->Execute("ALTER TABLE $tab DROP $col");
    $DB->Execute("ALTER TABLE $tab RENAME paytype2 TO $col");
}

$cfg = $DB->GetOne("SELECT value FROM uiconfig WHERE var = 'paytype' AND section = 'invoices'");

if ($cfg) {
    foreach ($paytypes as $pid => $pname)
        if (in_array($cfg, $pname)) {
            $DB->Execute("UPDATE uiconfig SET value = $pid WHERE var = 'paytype' AND section = 'invoices'");
            break;
        }
}

$cfg = $DB->GetOne("SELECT value FROM daemonconfig WHERE var = 'paytype'");

if ($cfg) {
    foreach ($paytypes as $pid => $pname)
        if (in_array($cfg, $pname)) {
            $DB->Execute("UPDATE daemonconfig SET value = '$pid' WHERE var = 'paytype'");
            break;
        }
}

$DB->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2010061800', 'dbversion'));

$DB->CommitTrans();

?>