<?php

preg_match("/.*?\/\*(.*?(TH_BACKGROUND))\*\//", $yhtiorow['active_css'], $varitmatch);
preg_match("/(#[a-f0-9]{3,6});/i", $varitmatch[0], $varirgb);

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * DataTables features
 */
echo "
.dataTables_wrapper {
position: relative;
min-height: 302px;
clear: both;
_height: 302px;
zoom: 1; /* Feeling sorry for IE */
}

.dataTables_processing {
position: absolute;
top: 50%;
left: 50%;
width: 250px;
height: 30px;
margin-left: -125px;
margin-top: -15px;
padding: 14px 0 2px 0;
text-align: center;
font-size: 14px;
background-color: white;
}

.dataTables_length {
width: 40%;
float: right;
padding-bottom: 10px;
padding-top: 10px;
}

.dataTables_filter {
float: left;
text-align: left;
padding-top: 10px;
padding-bottom: 10px;
}

.dataTables_info {
width: 60%;
float: left;
}

.dataTables_paginate {
width: 44px;
  * width: 50px;
float: left;
text-align: left;
}

/* Pagination nested */
.paginate_disabled_previous, .paginate_enabled_previous, .paginate_disabled_next, .paginate_enabled_next {
height: 19px;
width: 19px;
margin-left: 3px;
float: left;
}

.paginate_disabled_previous {
background: $varirgb[1] url('{$palvelin2}DataTables/media/images/back_disabled.jpg');
}

.paginate_enabled_previous {
background: $varirgb[1] url('{$palvelin2}DataTables/media/images/back_enabled.jpg');
}

.paginate_disabled_next {
background: $varirgb[1] url('{$palvelin2}DataTables/media/images/forward_disabled.jpg');
}

.paginate_enabled_next {
background: $varirgb[1] url('{$palvelin2}DataTables/media/images/forward_enabled.jpg');
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * DataTables display
 */
table.display {
clear: both;
width: 90%;
}

table.display thead th {
font-weight: bold;
cursor: pointer;
  * cursor: hand;
}

table.display tfoot th {
font-weight: bold;
}

table.display td.center {
text-align: center;
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * DataTables sorting
 */

.sorting_asc {
background: $varirgb[1] url('{$palvelin2}DataTables/media/images/sort_asc.png') no-repeat center right;
}

.sorting_desc {
background: $varirgb[1] url('{$palvelin2}DataTables/media/images/sort_desc.png') no-repeat center right;
}

.sorting {
background: $varirgb[1] url('{$palvelin2}DataTables/media/images/sort_both.png') no-repeat center right;
}

.sorting_asc_disabled {
background: $varirgb[1] url('{$palvelin2}DataTables/media/images/sort_asc_disabled.png') no-repeat center right;
}

.sorting_desc_disabled {
background: $varirgb[1] url('{$palvelin2}DataTables/media/images/sort_desc_disabled.png') no-repeat center right;
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * DataTables row classes
 */
table.display tr.odd.gradeA {
}

table.display tr.even.gradeA {
}

table.display tr.odd.gradeC {
}

table.display tr.even.gradeC {
}

table.display tr.odd.gradeX {
}

table.display tr.even.gradeX {
}

table.display tr.odd.gradeU {
}

table.display tr.even.gradeU {
}

tr.odd {
}

tr.even {
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Misc
 */
.dataTables_scroll {
clear: both;
}

.dataTables_scrollBody {
  *margin-top: -1px;
}

.top, .bottom {
padding: 15px;
}

.top .dataTables_info {
float: none;
}

.clear {
clear: both;
}

.dataTables_empty {
text-align: center;
}

thead input {
margin: 0.5em 0;
width: 90%;
}

.example_alt_pagination div.dataTables_info {
width: 40%;
}

.paging_full_numbers {
width: 400px;
height: 22px;
line-height: 22px;
}

.paging_full_numbers a.paginate_button,
   .paging_full_numbers a.paginate_active {
-webkit-border-radius: 5px;
-moz-border-radius: 5px;
padding: 2px 5px;
margin: 0 3px;
cursor: pointer;
  *cursor: hand;
}

span.paginate_active {
  font-weight: bold;
}

/*
 * KeyTable
 */
table.KeyTable td {
border: 3px solid transparent;
}

div.box {
height: 90px;
padding: 10px;
overflow: auto;
}
// ==========
// = Banner =
// ==========
";
