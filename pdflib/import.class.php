<?php
/*
   php pdf generation library - import extension
   Copyright (C) Potential Technologies 2002 - 2004
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

   $Id$
*/

class import
{
    var $pdf;       // reference to the parent class
    var $xref;      // Array holding the xref table data
    var $d;         // store the PDF stream object here
    var $ob;        // Array of data on each PDF object
    var $fonts;     // Mapping of old font names to new font names
    var $images;    // Mapping of old image names to new image names
    var $pages;     // array of page IDs in order

    // Actually append the specified PDF to the end of the current
    function append($data)
    {
        // Basic magic check
        if (substr($data, 0, 6) != '%PDF-1') {
            echo "Bad magic\n";
            return false;
        }
        // Set up default values
        $this->xref     =
        $this->pages    =
        $this->images   =
        $this->fonts    =
        $this->ob       = array();
        $this->d = new StreamHandler($data, false);
        if (!$this->capture_xref()) {
            echo "Couldn't find xref\n";
            return false;
        }
        /* Find the root object, and (starting there) recursively create
         * the objects in the actual PDF library
         */
        if (!$root = $this->find_root()) {
            echo "Failed to find the root node<br>";
            return false;
        }
        echo "Found root at $root<br>\n";
        // Grab each object in the PDF in turn
        foreach ($this->xref as $pdfid => $junk) {
            $this->extract($pdfid);
        }
        $this->make_catalog($root);
        echo '<pre>';
        print_r($this->pdf->objects);
        echo '</pre>';
        // Clean up some memory
        unset($this->xref);
        unset($this->d);
        unset($this->ob);
        return true;
    }
    
    function get_pages($pages = array())
    {
        foreach ($this->pages as $pageid) {
            $pages[] = $pageid;
        }
        return $pages;
    }
    
    function capture_xref()
    {
        $data = &$this->d;
        // First we find the start of the xref
        $data->end();
        while ($data->previous_word() != 'startxref')
            ;
        // Now we capture where the xref starts
        $data->next_word(); // slurp 'startxref'
        $xref = $data->next_word();
        $data->l = (int)$xref;
        echo "found xref at $xref<br>\n";
        return $this->extract_xref($xref);
    }
    
    function extract_xref($start)
    {
        $data = &$this->d;
        $data->l = $start;
        if ($data->next_word() != 'xref') {
            echo "Proposed xref location was bogus: $xref\n";
            return false;
        }
        $firstx = $data->next_word();
        $numx   = $data->next_word();
        echo "firstx=$firstx, numx=$numx<br>\n";
        for ($i = $firstx; $i < $firstx + $numx; $i++) {
            $loc = $data->next_word();
            $gen = $data->next_word();
            if ($data->next_word() == 'n') {
                $this->xref[$i] = $loc;
                echo "object $i found at $loc<br>\n";flush();
            }
        }
        return true;
    }
    
    function find_root()
    {
        // Find trailer
        $d = &$this->d;
        $d->end();
        echo "Looking backwards for 'trailer'<br>\n";flush();
        while ($d->previous_word() !== 'trailer')
            ;
        echo "Found 'trailer' ... looking forward for '/Root'<br>\n";flush();
        $found = true;
        while ($d->next_word() !== '/Root') {
            if ($d->l >= ($d->length - 1)) {
                $found = false;
                break;
            }
        }
        if (!$found) {
            /* This is probably a lineralized PDF, and the /Root entry will
             * be near the beginning of the file
             */
            echo "Looking for first trailer<br>\n";
            $d->start();
            while ($d->next_word() !== 'trailer') {
                if ($d->l >= ($d->length - 1)) {
                    echo "Error ... couldn't find /Root entry<br>\n";
                    return false;
                }
            }
            $trailer = $d->l;
            echo "Looking for additional xref table<br>\n";
            while ($d->next_word() !== '/Prev') {
                if ($d->l >= ($d->length - 1)) {
                    echo "Didn't find /Prev in the first trailer<br>\n";
                    return false;
                }
            }
            $this->extract_xref($d->next_word());
            $d->l = $trailer;
            echo "Looking for /Root in first trailer<br>\n";
            while ($d->next_word() !== '/Root') {
                if ($d->l >= ($d->length - 1)) {
                    echo "Didn't find /Root at the first trailer either<br>\n";
                    return false;
                }
            }
        }
        echo "Found /Root ... extracting location<br>\n";flush();
        return $d->next_word();
    }
    
    function recursive_create($id)
    {
        echo "Converting Object # $id<br>\n";
        if (!isset($this->ob[$id]['/Type']))
            $this->ob[$id]['/Type'] = 'rawstream';
        switch ($this->ob[$id]['/Type']) {
        case '/Info' :
            return false;
            break;
            
        case '/XObject' :
            return $this->make_xobject($id);
            break;
            
        case '/Catalog' :
            return $this->make_catalog($id);
            break;
            
        case 'rawstream' :
            return $this->make_mstream($id);
            break;
            
        }
    }

    function make_catalog($id)
    {
        echo "Making catalog at $id<br>\n";
        $this->make_pages((int)$this->ob[$id]['/Pages']);
    }
    
    function make_pages($id)
    {
        echo "Making pages at $id<br>\n";
        $ob = $this->ob[$id];
        echo '<pre>'; print_r($ob); echo '</pre>';
        if (isset($ob['/Resources'])) {
            $this->make_resources((int)$ob['/Resources']);
        }
        foreach ($ob['/Kids'] as $key => $kid) {
            echo "Examining prospective page $key => $kid<br>\n";
            if ('itype' === $key) {
                echo "Not a real page<br>\n";
            } else {
                /* Note: This gets used a lot.  It's a cheesy, but effective
                 * method to convert a string like "5 0 R" into (int)5.  This
                 * is a result of PHPs type casting, and seems to work reliably
                 */
                $k = (int)$kid;
                // Calculate page width
                $w = $this->ob[$k]['/MediaBox'][2] -
                     $this->ob[$k]['/MediaBox'][0];
                $h = $this->ob[$k]['/MediaBox'][3] -
                     $this->ob[$k]['/MediaBox'][1];
                $this->pages[] = $p = $this->pdf->new_page("{$w}x{$h}");
                echo "Made page $p from $k<br>\n";
                // See if this page has a seperate resource dictionary
                if (isset($this->ob[$k]['/Resources'])) {
                    echo "Found resources for page $k<br>\n";
                    $this->make_resources($this->ob[$k]['/Resources']);
                }
                // Now we recursively create the contents ...
                $this->make_contents($p, $this->ob[$k]['/Contents']);
            }
            echo "Done Examining page $key => $kid<br>\n";
        }
    }

    function make_contents($page, $contents)
    {
        // If $contents isn't an array, we'll turn it into one
        if (!is_array($contents)) {
            $nc[] = $contents;
            unset($contents);
            $contents = $nc;
        }
        foreach ($contents as $key => $target) {
            echo "Examining $key => $target for page $page<br>\n";
            if ($key !== 'itype') {
                // Look for things that need transferred ...
                $target = $this->followIR($target);
                /* Iterate through each token in the stream and look for
                 * things that need stripped out (currently "gs" for
                 * graphics states)
                 * This is SLOW ... need to find a
                 * better way!  It's also messy.  In general, it SUCKS
                 * and it's a hack anyway, we need to support importing these
                 * features ... but that's for future work.
                 */
                $d1 = $target['stream']->d;
                $instring = false; $d = ''; $ct = '';
                for ($i = 0; $i < strlen($d1); $i++) {
                    $c = $d1{$i};
                    if ($instring) {
                        if ($c === ')') {
                            $d .= $c;
                            $instring = false;
                        } else {
                            if ($c === '\\') {
                                if ($d1{$i + 1} === ')') {
                                    $d .= '\)';
                                    $i ++;
                                } else {
                                    $d .= $c;
                                }
                            } else {
                                $d .= $c;
                            }
                        }
                    } else {
                        if ($i >= strlen($d1)) {
                            $token = $c;
                        } else {
                            $token = substr($d1, $i, 2);
                        }
                        //echo "Examining '$token'<br>\n";
                        /* gs = graphics state: can get complicated
                         */
                        if ($token === 'gs')
                        {
                            echo "Found a graphic state to remove: $i<br>\n";
                            $pos = strrpos($d, '/');
                            $d = substr($d, 0, $pos);
                            $i++;
                        } else {
                            if ($c === '(') {
                                $instring = true;
                            }
                            $d .= $c;
                        }
                    }
                }
                foreach ($this->fonts as $old => $new) {
                    $d = str_replace("$old ", "/$new ", $d);
                }
                foreach ($this->images as $old => $new) {
                    echo "Replacing Image '$old ' with '/$new '<br>\n";
                    $d = str_replace("$old ", "/$new ", $d);
                }
                $temp['parent'] = $page;
                $this->pdf->add_raw_object($temp, $d);
            }
        }
    }
    
    /* Pass this a dictionary array
     * Some notes:
     * We ignore ProcSet because the PDF standard states it is obsolete
     */
    function make_resources($dict)
    {
        $dict = $this->followIR($dict);
        foreach ($dict as $key => $entry) {
        	echo "Resource Dictionary entry: $key<br>\n";
            switch ($key) {
            case '/Font' :
                $this->extract_fonts($entry);
                break;
            case '/XObject' :
                $this->extract_xobjects($entry);
                break;
            case '/ExtGState' :
                $this->extract_extgstates($entry);
                break;
            case '/ColorSpace' :
                $this->extract_colorspaces($entry);
                break;
            default :
                echo "Unknown Dictionary entry '$key'<br>\n";
            }
        }
    }
    
    function extract_colorspaces($list)
    {
        echo "Extracting ColorSpace<br>\n";
        $list = $this->followIR($list);
        foreach ($list as $name => $ir) {
            // We assume that we always have an IR (safe?)
            if ($name != 'itype') {
                $ir = $this->followIR($ir);
                $this->extract_colorspace($name, $ir);
            }
        }
    }
    
    function extract_colorspace($name, $ob)
    {
        /* Unlike other resources, we'll have total control over colorspaces
         * This is because the main library doesn't use them for anything,
         * and thus doesn't expect to have control of them
         */
        /* If the thing was extracted as a value, just put it in
         */
        if (isset($ob['value'])) {
	        $r = $this->pdf->_addnewoid();
            $this->pdf->objects[$r]['data'] = $ob['value'];
            $this->pdf->objects[$r]['type'] = 'value';
        } else {
	        if (isset($this->pdf->builddata['colorspaces'][$name])) {
    	    	echo "Colorspace $name already done<br>\n";
        		return;
	        }
    	    $new = array();
        	foreach ($ob as $key => $value) {
        		if ($key !== 'itype') {
            		if (is_array($value)) {
                		if ($value['itype'] === 'dictionary') {
                    		$temp = $value;
	                        unset($temp['itype']);
    	                    $value = $this->pdf->_makedictionary($temp);
        	            } else { // Must be a PDF array
            	        	$temp = $value;
                	        unset($temp['itype']);
                    	    $value = $this->pdf->_makearray($temp);
	                    }
    	            }
        	    	$new[substr($key, 1)] = $value;
            	}
	        }
    	    $r = $this->pdf->add_raw_object($new);
        }
        echo "Object $r is now colorspace $name<br>\n";
        $this->pdf->builddata['colorspaces'][substr($name, 1)] = $r;
    }
    
    function extract_extgstates($list)
    {
        echo "Extracting ExtGState<br>\n";
        $list = $this->followIR($list);
        foreach ($list as $name => $ir) {
            // We assume that we always have an IR (safe?)
            if ($name != 'itype') {
                $ir = $this->followIR($ir);
                $this->extract_extgstate($name, $ir);
            }
        }
    }
    
    function extract_extgstate($name, $ob)
    {
        /* Not currently implemented
         * Extended Graphic States can get arbitrarily complex, and can more
         * or less be ignored (if you're doing something where color matching
         * is important, you're probably not using the right library anyway)
         * When the main library has some understanding of ExtGStates, I'll
         * implement importing them as well, but I figure my time is better
         * spent figuring out how to import embedded fonts ...
         */
    }
    
    function extract_fonts($list)
    {
        echo "Extracting Fonts<br>\n";
        $list = $this->followIR($list);
        foreach ($list as $name => $ir) {
            // We assume that we always have an IR (safe?)
            if ($name != 'itype') {
                $ir = $this->followIR($ir);
                $this->extract_font($name, $ir);
            }
        }
    }
    
    function extract_font($oldname, $ob)
    {
        /* $r is the library id once the font is created.  This ID # is
         * appended to the string "F" to create the font name in the
         * resource dictionary, so we use it to identify the font
         */
        $r = $this->pdf->new_font(substr($ob['/BaseFont'], 1));
        /* Current hack ... if the new_font() fails, then we have an embedded
         * font, for now, just substitute Helvetica
         */
        if ($r === false) {
            $r = $this->pdf->new_font('Helvetica');
        }
        // Map old name to new name
        $name = "F$r";
        echo "Font '$oldname' is now '$name'<br>\n";
        $this->fonts[$oldname] = $name;
        return $name;
    }
    
    function extract_xobjects($list)
    {
        echo "Extracting XObjects (hopefully images)<br>\n";
        $list = $this->followIR($list);
        foreach ($list as $name => $ir) {
            // We assume that we always have an IR (safe?)
            if ($name != 'itype') {
                $ir = $this->followIR($ir);
                $this->make_xobject($name, $ir);
            }
        }
    }
    
    function followIR($ref)
    {
        /* Make sure the passed parameter is an array or dictionary ...
         * if not, follow the IR to its destination
         */
        if (!is_array($ref)) {
            return $this->ob[(int)$ref];
        }
        if (isset($ref['itype'])) {
            if ($ref['itype'] == 'dictionary' || $ref['itype'] == 'array') {
                return $ref;
            }
        }
        echo "Error!! Found something that's not IR, dict or array:<br>\n";
        print_r($ref);
        return false;
    }
    
    function make_xobject($name, $id)
    {
        switch ($id['/Subtype']) {
        case '/Image':
            return $this->make_image($name, $id);
            break;
            
        default:
            return false;
        }
    }
    
    function make_image($oldname, $ob)
    {
        $additional = array();
        echo 'Sorting out an image<pre>';
        print_r($ob);
        echo '</pre>';
        if (isset($ob['/DecodeParms'])) {
            $a = array();
            foreach($ob['/DecodeParms'] as $key => $value) {
                if ($key{0} == '/') {
                    $a[substr($key, 1)] = $value;
                }
            }
            /* We're breaking the API here ... this function should
             * not really be called from an extension
             */
            $additional['DecodeParms'] = $this->pdf->_makedictionary($a);
        }
        /* $r is the library id once the image is created.  This ID # is
         * appended to the string "Img" to create the image name in the
         * resource dictionary, so we use it to identify the image
         */
        if (!isset($ob['/Filter'])) $ob['/Filter'] = '';
        if (is_array($ob['/Filter'])) {
            unset($ob['/Filter']['itype']);
            // Broken API!
            $ob['/Filter'] = $this->pdf->_makearray($ob['/Filter']);
        }
        $r = $this->pdf->image_raw_embed(
            $ob['stream']->d,
            $ob['/ColorSpace'],
            $ob['/BitsPerComponent'],
            $ob['/Height'],
            $ob['/Width'],
            $ob['/Filter'],
            $additional
            );
        // Map old name to new name
        $name = "Img$r";
        echo "Image '$oldname' is now '$name'<br>\n";
        $this->images[$oldname] = $name;
        return $name;
    }
    
    function extract($id)
    {
        if (!isset($this->ob[$id])) {
            $location = $this->xref[$id];
            $data = &$this->d;
            $data->l = $location;
            $id = $data->next_word();
            $gn = $data->next_word();
            echo "Found $id $gn R at $location<br>\n";
            flush();
            $this->extract_obj($id);
        }
        return $this->ob[$id];
    }
    
    function extract_obj($id)
    {
        $d = &$this->d;
        // Magic test
        if ($d->next_word() !== 'obj') {
            echo "Didn't find an object here!<br>\n";
            return false;
        }
        while (true) {
            $d->skip_whitespace();
            if (substr($d->d, $d->l, 2) === '<<') {
                echo "Found a dictionary<br>\n";
                $this->ob[$id] = $this->extract_dictionary();
            } else {
                $w = $d->next_word();
                if ($w == 'endobj') break;
                if ($w == 'stream') {
                    echo "Found a stream: {$d->l}<br>\n";
                    $d->l -= 6;
                    $this->ob[$id]['stream'] =
                        $this->extract_stream($this->ob[$id]);
                    if ($this->ob[$id]['stream']->decompressed) {
                        $this->ob[$id]['/Filter'] = '';
                    }
                } else {
                    echo "Assuming a raw value in object<br>\n";
                    $this->ob[$id]['value'] = $w . ' ';
                    while (($w = $d->next_word()) !== 'endobj') {
                    	echo "Adding '$w' to object value<br>\n";
	                    $this->ob[$id]['value'] .= $w;
                        if (($w !== '<') && ($w !== '>')) {
                        	$this->ob[$id]['value'] .= ' ';
                        }
                    }
                    break;
                }
            }
        }
        echo "<pre>\n";
        $temp = $this->ob[$id];
        if (isset($temp['stream']) && $temp['stream']->decompressed) {
//            $temp['stream']->d = '[STRIPPED]'; // kommentoin tämän, koska php 5:llä joudutaan tähän haaraan joka tuhoaa pdf importin. jos tätä ei tehdä niin homma ok. go figure! -joni
        }
        print_r($temp);
        echo "</pre>\n";
    }
    
    function extract_dictionary()
    {
        $d = &$this->d;
        if (substr($d->d, $d->l, 2) != '<<') {
            echo "Didn't find a dictionary here!<br>\n";
            return array();
        }
        $d->l += 2; // Slurp the '<<'
        $r = array();
        $r['itype'] = 'dictionary';
        $label = false;
        $state = array();
        while (true) {
            $d->skip_whitespace();
            if (substr($d->d, $d->l, 2) == '>>') {
                echo "Found end of dictionary<br>\n";
                $d->l += 2;
                if (count($state) > 0) {
                    $r[$l] = '';
                    foreach ($state as $v) {
                        $r[$l] .= $v . ' ';
                        echo "Popping remainer of stack for [$l] = '{$r[$l]}'<br>\n";
                    }
                }
                break;
            }
            if (substr($d->d, $d->l, 2) == '<<') {
                echo "Found subdictionary<br>\n";
                $r[$l] = $this->extract_dictionary();
                $label = false;
                continue;
            }
            if (substr($d->d, $d->l, 1) == '[') {
                echo "Analyzing array '" . substr($d->d, $d->l, 15) .
                     "...' for [$l]<br>\n";
                $r[$l] = $d->get_array();
                $label = false;
                continue;
            }
            $w = $d->next_word();
            if (!$label) {
                echo "Making '$w' a label<br>\n";
                $label = true;
                $l = $w;
            } else {
                echo "Current character = '" . $d->cc() . "'<br>\n";
                if ($w{0} == '/') {
                    if (!isset($state[0])) {
                        echo "Assigning '$w' as value of [$l]<br>\n";
                        $r[$l] = $w;
                    } else {
                        $d->rewind();
                        echo "Popping the stack for [$l]<br>\n";
                        $r[$l] = $state[0];
                        $state = array();
                    }
                } else if ($w{0} == '(' && $w{strlen($w) - 1} == ')') {
                    // We've got a string
                    echo "Assigning string value '$w' to [$l]<br>\n";
                    $r[$l] = $w;
                } else if ($w == 'R') {
                    $ir = $state[0] . ' ' . $state[1] . ' R';
                    echo "Stored IR $ir<br>\n";
                    $r[$l] = $ir;
                    $state = array();
                } else {
                    // Push this on the stack
                    echo "pushing '$w' on the stack<br>\n";
                    $state[] = $w;
                    continue;
                }
                $label = false;
            }
        }
        return $r;
    }
    
    function extract_stream($meta)
    {
        $d = &$this->d;
        $t = $d->next_word();
        if ($t == 'stream') {
            echo "Check for indirect reference<br>\n";
            if ($d->strpos($meta['/Length'], 'R')) {
                // We must resolve an indirect reference
                echo "Resolving IR for /Length<br>\n";
                $t = $d->l;
                $this->extract((int)$meta['/Length']);
                $d->l = $t;
                $length = $this->ob[(int)$meta['/Length']]['value'];
            } else {
                $length = (int)$meta['/Length'];
            }
            echo "Stream should be {$length}<br>\n";
            if ($d->d{$d->l} === "\x0a") {
                $start = $d->l + 1;
            } else {
                if ($d->d{$d->l} === "\x0d") {
                    if ($d->d{$d->l + 1} === "\x0a") {
                        $start = $d->l +2;
                    } else {
                        echo "Data after keyword 'stream' is corrupt<br>\n";
                    }
                }
            }
            $stream = substr($d->d, $start, $length);
            $d->l += 1 + $length;
            $d->next_word(); // Consume "endstream"
            if (isset($meta['/Filter'])
                && !is_array($meta['/Filter'])
                && $meta['/Filter'] === '/FlateDecode'
                && (!isset($meta['/DecodeParms'])
                || count($meta['/DecodeParms']) == 0)) {
                    $decompress = true;
            } else {
                $decompress = false;
            }
            $r = new StreamHandler($stream, $decompress);
            return $r;
        } else {
            echo "I didn't find a stream here: '$t'<br>\n";
            $d->rewind();
            return '';
        }
    }
}

/*******************************************************************\
 * The goal of StreamHandler is to abstract the messy job of       *
 * looking through the PDF.  It should handle things like skipping *
 * whitespace and comments                                         *
\*******************************************************************/
class StreamHandler
{
    var $d;     // The data stream itself
    var $l;     // Current location
    var $decompressed;
    var $length;    // Length of the stream
    
    function StreamHandler($stream, $decompress)
    {
        $this->l = 0;
        /* If this sucker uses multiple encodings, we're not going
         * to want to mess with it ... we may have to, but we'll
         * dodge the issue for now
         */
        if ($decompress) {
            echo "Decompressing<br>\n";
            $this->d = gzuncompress($stream);
            if ($this->d) {
                $this->decompressed = true;
            } else {
                echo "Error decompressing!<br>Stream was #'$stream'#<br>\n";
            }
        } else {
            $this->d = $stream;
            $this->decompressed = false;
        }
        $this->d .= "\n";
        $this->length = strlen($this->d);
    }
    
    function get_array()
    {
        $s = new StreamHandler($this->read_array(), false);
        echo "Got array of '" . $s->d . "'<br>\n";
        $r = array();
        $r['itype'] = 'array';
        $as = array();
        while ($s->l < strlen($s->d) - 1) {
            $c = $s->next_word();
            if ($c == 'R') {
                $r[] = $as[0] . ' ' . $as[1] . ' R';
                $as = array();
            } else {
                $as[] = $c;
            }
        }
        if (count($as) > 0 && strlen(trim($as[0])) > 0) {
            $r = array_merge($r, $as);
        }
        return $r;
    }
    
    function read_array()
    {
        $this->back_to_array();
        if ($this->cc() != '[') {
            // No array here
            echo "I don't see an array: '" .
                 substr($this->d, $this->l, 15) . "...'<br>\n";
            return '';
        }
        $this->next();
        $nest = 1;
        $r = '';
        do {
            if ($this->cc() === '[') {
                echo "Found sub-array ...<br>\n";
                $nest++;
            }
            $r .= $this->cc();
            $this->l++;
            if ($this->cc() === ']') {
                $nest--;
            }
        } while ($nest > 0);
        $this->next();
        return $r;
    }
    
    function back_to_array()
    {
        if ($this->cc() != '[') {
            $this->l--;
        }
    }
    
    function end()
    {
        $this->l = $this->length - 1;
    }
    
    function start()
    {
        $this->l = 0;
    }
    
    function next_word()
    {
        $this->skip_whitespace();
        $r = '';
        // Slurp a PDF string
        if ($this->cc() == '(') {
            while ($this->cc() != ')' || $this->cc($this->l - 1) == '\\') {
                $r .= $this->cc();
                $this->next();
            }
            $r .= $this->cc();
            $this->next();
            return $r;
        }
        do {
            $r .= $this->cc();
            $this->next();
        } while (!$this->is_whitespace() &&
                 !$this->boundry() &&
                 $this->l < strlen($this->d));
        return $r;
    }
    
    function previous_word()
    {
        $this->rewind();
        $r = $this->next_word();
        $this->rewind();
        echo "Looking backward ... current word: '$r'<br>\n";flush();
        return $r;
    }
    
    // Backs up 1 word
    function rewind()
    {
        $this->skip_nonwords(false);
        do {
            $this->l--;
        } while (!$this->is_whitespace() && !$this->boundry());
    }
    
    // Skips past whitespace (strictly)
    function skip_whitespace($forward = true)
    {
        while ($this->is_whitespace()) {
            if ($forward) {
                $this->next();
            } else {
                $this->l--;
            }
            if ($this->l == 0 || $this->l == (strlen($this->d) - 1)) break;
        }
        return $this->l;
    }
    
    // Skips past whitespace and boundtry characters
    function skip_nonwords($forward = true)
    {
        while ($this->is_whitespace() || $this->boundry()) {
            if ($forward) {
                $this->next();
            } else {
                $this->l--;
            }
            if ($this->l == 0 || $this->l == (strlen($this->d) - 1)) break;
        }
        return $this->l;
    }
    
    function boundry($c = false)
    {
        if ($c === false) $c = $this->cc();
        if ($this->strpos('()/><[]', $c)) {
            return true;
        } else {
            return false;
        }
    }
    
    function is_whitespace($c = '')
    {
        if ($c == '') $c = $this->cc();
        if ($c == ' ' ||
            $c == "\x0a" ||
            $c == "\x0d" ||
            $c == "\t") {
             return true;
        } else {
             return false;
        }
    }
    
    // Returns current byte (character)
    function cc($l = false)
    {
        if ($l === false) $l = $this->l;
        return $this->d{$l};
    }
    
    function next()
    {
        $this->l++;
        if ($this->l >= strlen($this->d)) {
            echo "fell off the end!<br>\n";
            $this->end();
        }
    }
    
    function strpos($haystack, $needle)
    {
        if (strpos($haystack, $needle) === false) {
            return false;
        } else {
            return true;
        }
    }
}
?>