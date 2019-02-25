<?php

//tämä on huipputärkeä ominaisuus.. ;) t. joni

$ketka = array('Jarmo Rosenqvist', 'Johan Tötterman', 'Joni Kanerva');
shuffle($ketka);
for ($i=0; $i<count($ketka); $i++) $copyright .= $ketka[$i].", ";

$copyright .= "Heli Salmi";

echo "
<pre>
Pupesoft softa
Copyright &copy; 2002-".date("Y")." $copyright

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

<a href='http://www.gnu.org/licenses/gpl.html' target='_blank'>GNU General Public License</a><br />
</pre>

<hr>

<pre>
Barcode Render Class for PHP using the GD graphics library
Copyright (C) 2001  Karim Mribti

Version  0.0.7a  2001-04-01

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

Copy of GNU Lesser General Public License at: http://www.gnu.org/copyleft/lesser.txt

Source code home page: http://www.mribti.com/barcode/
Contact author at: barcode@mribti.com
</pre>

<hr>

<pre>
php pdf generation library

Copyright (C) Potential Technologies 2002 - 2003
http://www.potentialtech.com

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
</pre>

<hr>

<pre>
 RiTe-Bank for SQL-Ledger
 Copyright (c) 2002

  Author: Juha Tepponen & Janne Richter
   Email: oh0jute$kyamk.fi oh0jari@kyamk.fi

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
</pre>

<hr>

<pre>
Lullacons Pack 1 - Royalty Free Icons for Public Distribution
by Nathan Haug and the Lullabot Team
http://www.lullabot.com/icons
September 26, 2006

These icons are free to use, modify, and redistribute so long as this file is
not separated from any icon included in this package. This restriction is
applied to use of the iconset in part or in full. Original source files are
available for free download at http://www.lullabot.com/icons. All icons copyleft
Lullabot, 2006.

Whole license is located at <a href='pics/lullacons/lullacons-readme.txt'>pics/lullacons/lullacons-readme.txt</a>

</pre>
";
