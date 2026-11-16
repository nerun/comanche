<?php
/*
The MIT License

Copyright (c) 2026 Daniel Dias Rodrigues

Permission is hereby granted, free of charge, to any person obtaining a
copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be included
in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$base = realpath(__DIR__);
$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$descFile = $base . '/comanche/descriptions.json';

$descriptions = [];

if (is_readable($descFile)) {
    $json = file_get_contents($descFile);
    $decoded = json_decode($json, true);

    if (is_array($decoded)) {
        $descriptions = $decoded;
    }
}

function get_description($dir, $file, $descriptions)
{
    $key = trim($dir . '/' . $file, '/');

    return $descriptions[$key] ?? '';
}

$dir = isset($_GET['dir']) ? $_GET['dir'] : '';
$dir = trim($dir, '/');

if (strpos($dir, '..') !== false) {
    $dir = '';
}

$path = realpath($base . '/' . $dir);

if ($path === false || strncmp($path, $base, strlen($base)) !== 0 || !is_dir($path)) {
    http_response_code(404);
    exit('Directory not found');
}

$CWD = '/' . $dir;
$CWD = $CWD === '/' ? '/' : rtrim($CWD, '/');
$safeCWD = htmlspecialchars($CWD, ENT_QUOTES, 'UTF-8');
$iconsFolder = "comanche/icons";

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="author" content="Daniel Dias Rodrigues">
        <meta name="copyright" content="© 2026 Daniel Dias Rodrigues">
        <meta name="description" content="Comanche directory indexer. Designed to replicate the Apache directory indexer with improvements.">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Index of <?php echo $safeCWD; ?></title>
        <style>
            .center {
                text-align: center;
            }
            .right {
                text-align: right;
            }
            .top {
                vertical-align: top;
            }
            .copyright {
                font-size: small;
                text-align: center;
            }
            .center-page {
                margin: auto;
                width: 980px;
            }
            #indexTable td:first-child {
                padding-right: 4px;
                width: 22px;
            }
            #indexTable td:nth-child(2),
            #indexTable td:nth-child(3),
            #indexTable td:nth-child(4),
            #indexTable td:nth-child(5) {
                vertical-align: top;
                line-height: 22px;
            }
            #indexTable td:nth-child(2) {
                padding-top: 3px;
            }
            #indexTable td:nth-child(5),
            #indexTable th:nth-child(5) {
                padding-left: 20px;
            }
            td.description {
                white-space: normal;
                word-break: break-word;
                max-width: 400px;
            }
        </style>
    </head>
    <body class="center-page">
        <h1>Index of <?php echo $safeCWD; ?></h1>
        <table id="indexTable" class="center-page">
            <thead>
                <tr>
                    <th class="top"><img src="<?php echo $iconsFolder?>/blank.gif" alt="[ICO]"></th>
                    <th><a href="#" onclick="sortTable(1, 'string'); return false;">Name</a></th>
                    <th><a href="#" onclick="sortTable(2, 'date'); return false;">Last modified</a></th>
                    <th><a href="#" onclick="sortTable(3, 'size'); return false;">Size</a></th>
                    <th><a href="#" onclick="sortTable(4, 'string'); return false;">Description</a></th>
                </tr>
                <tr>
                    <td colspan="5"><hr></td>
                </tr>
            </thead>
            <tbody>
<!-- --------------------------------------------------------------- -->
<?php
function human_filesize($bytes)
{
    $sz = ' KMGTP';
    $factor = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
    $value = $bytes / pow(1024, $factor);
    $decimals = ($factor > 0 && $value < 10) ? 1 : 0;
    $suffix = trim($sz[$factor]); // remove space from index 0 (bytes)
    return sprintf("%.{$decimals}f", $value) . $suffix;
}

function get_mtime($file)
{
    if (is_dir($file)) {
        foreach (['index.php', 'index.html', 'index.htm'] as $idx) {
            $path = "$file/$idx";

            if (file_exists($path) && is_readable($path)) {
                return date("Y-m-d H:i", filemtime($path));
            }
        }
    }
    return is_readable($file)
        ? date("Y-m-d H:i", filemtime($file))
        : '-';
}

function get_icon($file, $isDir)
{
    if ($isDir) {
        return 'folder.gif';
    }

    $basename = basename($file);
    $name = strtolower($basename);
    $ext = pathinfo($name, PATHINFO_EXTENSION);

    // README, README.txt and README.md
    if ($name === 'readme' || $name === 'readme.txt' || $name === 'readme.md') {
        return 'hand.right.gif';
    }

    // special cases (double extension)
    if (str_ends_with($name, '.wrl.gz')) {
        return 'world2.gif';
    }

    if ($ext === 'bin') {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $type = trim(finfo_file($finfo, $file) ?: '');
        finfo_close($finfo);

        if (str_contains($type, 'genesis-rom')) {
            return 'custom/game.gif';
        } else {
            return 'binary.gif';
        }
    }

    if ($ext === 'md') {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $type = trim(finfo_file($finfo, $file) ?: '');
        finfo_close($finfo);

        if (str_contains($type, 'genesis-rom')) {
            return 'custom/game.gif';
        } else {
            return 'text.gif';
        }
    }

    $map = [
        // audio
        'aac'   => 'sound2.gif',
        'aiff'  => 'sound2.gif',
        'flac'  => 'sound2.gif',
        'm4a'   => 'sound2.gif',
        'mid'   => 'sound2.gif',
        'midi'  => 'sound2.gif',
        'mp3'   => 'sound2.gif',
        'ogg'   => 'sound2.gif',
        'opus'  => 'sound2.gif',
        'wav'   => 'sound2.gif',
        'wma'   => 'sound2.gif',

        // compressed
        '7z'    => 'compressed.gif',
        'arj'   => 'compressed.gif',
        'br'    => 'compressed.gif',
        'bz2'   => 'compressed.gif',
        'gz'    => 'compressed.gif',
        'lz'    => 'compressed.gif',
        'lzma'  => 'compressed.gif',
        'lzo'   => 'compressed.gif',
        'rar'   => 'compressed.gif',
        'sdk'   => 'compressed.gif',
        'shk'   => 'compressed.gif',
        'tar'   => 'tar.gif',
        'tb2'   => 'compressed.gif',
        'tbz2'  => 'compressed.gif',
        'tbz'   => 'compressed.gif',
        'tgz'   => 'compressed.gif',
        'tlz'   => 'compressed.gif',
        'txz'   => 'compressed.gif',
        'tzo'   => 'compressed.gif',
        'tzst'  => 'compressed.gif',
        'xz'    => 'compressed.gif',
        'z'     => 'compressed.gif',
        'zip'   => 'compressed.gif',
        'zpaq'  => 'compressed.gif',
        'zst'   => 'compressed.gif',

        // document
        // a.gif = vector descriptions for printing
        'ai'    => 'a.gif',
        'dvi'   => 'dvi.gif',
        'eps'   => 'a.gif',
        'epsi'  => 'a.gif',
        'htm'   => 'layout.gif',
        'html'  => 'layout.gif',
        'pdf'   => 'custom/pdf.gif',
        'pdfa'  => 'custom/pdf.gif',
        'pdfx'  => 'custom/pdf.gif',
        'ps'    => 'a.gif',
        'rtf'   => 'rtf.gif',
        'shtml' => 'layout.gif',
        'xht'   => 'layout.gif',
        'xhtm'   => 'layout.gif',
        'xhtml' => 'layout.gif',

        // executable / binary
        //'bin'   => 'binary.gif',
        'exe'   => 'binary.gif',
        'hqx'   => 'binhex.gif',
        'uu'    => 'uuencoded.gif',

        // disk images
        'b5i'   => 'diskimg.gif',
        'b6i'   => 'diskimg.gif',
        'bwi'   => 'diskimg.gif',
        'ccd'   => 'diskimg.gif',
        'cue'   => 'diskimg.gif',
        'dmg'   => 'diskimg.gif',
        'dsk'   => 'diskimg.gif',
        'flp'   => 'diskimg.gif',
        'img'   => 'diskimg.gif',
        'ima'   => 'diskimg.gif',
        'iso'   => 'diskimg.gif',
        'isz'   => 'diskimg.gif',
        'mdf'   => 'diskimg.gif',
        'mds'   => 'diskimg.gif',
        'nrg'   => 'diskimg.gif',
        'qcow2' => 'diskimg.gif',
        'raw'   => 'diskimg.gif',
        'sub'   => 'diskimg.gif',
        'vdi'   => 'diskimg.gif',
        'vhd'   => 'diskimg.gif',
        'vhdx'  => 'diskimg.gif',
        'vmdk'  => 'diskimg.gif',

        // font
        'ttf'   => 'custom/font.gif',
        'otf'   => 'custom/font.gif',
        'woff'  => 'custom/font.gif',
        'woff2' => 'custom/font.gif',
        'eot'   => 'custom/font.gif',
        'pfa'   => 'custom/font.gif',
        'pfb'   => 'custom/font.gif',
        'pfm'   => 'custom/font.gif',
        'afm'   => 'custom/font.gif',
        'bdf'   => 'custom/font.gif',
        'fon'   => 'custom/font.gif',
        'fnt'   => 'custom/font.gif',
        'dfont' => 'custom/font.gif',

        // image
        'avif'  => 'image2.gif',
        'bmp'   => 'image2.gif',
        'bmp2'  => 'image2.gif',
        'bmp3'  => 'image2.gif',
        'dds'   => 'image2.gif',
        'exr'   => 'image2.gif',
        'gif'   => 'image2.gif',
        'gif87' => 'image2.gif',
        'group4' => 'image2.gif',
        'heic'  => 'image2.gif',
        'heif'  => 'image2.gif',
        'icb'   => 'image2.gif',
        'j2c'   => 'image2.gif',
        'j2k'   => 'image2.gif',
        'jp2'   => 'image2.gif',
        'jpc'   => 'image2.gif',
        'jpe'   => 'image2.gif',
        'jpeg'  => 'image2.gif',
        'jpg'   => 'image2.gif',
        'jps'   => 'image2.gif',
        'pbm'   => 'image2.gif',
        'pgm'   => 'image2.gif',
        'png'   => 'image2.gif',
        'png00' => 'image2.gif',
        'png24' => 'image2.gif',
        'png32' => 'image2.gif',
        'png48' => 'image2.gif',
        'png64' => 'image2.gif',
        'png8'  => 'image2.gif',
        'pnm'   => 'image2.gif',
        'ppm'   => 'image2.gif',
        'psd'   => 'image2.gif',
        'ptif'  => 'image2.gif',
        'qoi'   => 'image2.gif',
        'sgi'   => 'image2.gif',
        'sun'   => 'image2.gif',
        'tga'   => 'image2.gif',
        'tiff'  => 'image2.gif',
        'vda'   => 'image2.gif',
        'vst'   => 'image2.gif',
        'webp'  => 'image2.gif',
        'xbm'   => 'image2.gif',
        'xcf'   => 'image2.gif',
        'xpm'   => 'image2.gif',
        'xwd'   => 'image2.gif',

        // Microsoft
        'accdb' => 'custom/ms-access.gif',        // MS Access
        'doc'   => 'custom/ms-word.gif',          // MS Word
        'docm'  => 'custom/ms-office.gif',        // MS Office
        'docx'  => 'custom/ms-word.gif',          // MS Word
        'dot'   => 'custom/ms-office.gif',        // MS Office template
        'dotx'  => 'custom/ms-office.gif',        // MS Office template
        'mdb'   => 'custom/ms-access.gif',        // MS Access

        'one'   => 'custom/ms-onenote.gif',       // MS OneNote
        'onetoc' => 'custom/ms-onenote.gif',       // MS OneNote
        'onetoc2' => 'custom/ms-onenote.gif',      // MS OneNote

        'pot'   => 'custom/ms-powerpoint.gif',    // MS PowerPoint template
        'potx'  => 'custom/ms-powerpoint.gif',    // MS PowerPoint template
        'ppa'   => 'custom/ms-powerpoint.gif',    // MS PowerPoint add-in
        'ppam'  => 'custom/ms-powerpoint.gif',    // MS PowerPoint add-in
        'pps'   => 'custom/ms-powerpoint.gif',    // MS PowerPoint slideshow
        'ppsm'  => 'custom/ms-powerpoint.gif',    // MS PowerPoint macro slideshow
        'ppt'   => 'custom/ms-powerpoint.gif',    // MS PowerPoint
        'pptm'  => 'custom/ms-powerpoint.gif',    // MS PowerPoint macro
        'pptx'  => 'custom/ms-powerpoint.gif',    // MS PowerPoint

        'pub'   => 'custom/ms-publisher.gif',     // MS Publisher
        'pubxml' => 'custom/ms-publisher.gif',     // MS Publisher
        'mspub' => 'custom/ms-publisher.gif',     // MS Publisher

        'wri'   => 'custom/ms-office.gif',        // MS Write

        'vsd'   => 'custom/ms-office.gif',        // MS Visio
        'vsdm'  => 'custom/ms-office.gif',        // MS Visio macro
        'vsdx'  => 'custom/ms-office.gif',        // MS Visio

        'xls'   => 'custom/ms-excel.gif',         // MS Excel
        'xlsb'  => 'custom/ms-excel.gif',         // MS Excel binary
        'xlsm'  => 'custom/ms-excel.gif',         // MS Excel macro
        'xlsx'  => 'custom/ms-excel.gif',         // MS Excel
        'xlt'   => 'custom/ms-excel.gif',         // MS Excel template
        'xltm'  => 'custom/ms-excel.gif',         // MS Excel macro template
        'xltx'  => 'custom/ms-excel.gif',         // MS Excel template
        'xlw'   => 'custom/ms-excel.gif',         // MS Excel workspace

        'msg'   => 'custom/ms-outlook.gif',       // MS Outlook
        'pst'   => 'custom/ms-outlook.gif',       // MS Outlook
        'ost'   => 'custom/ms-outlook.gif',       // MS Outlook

        // OpenDocument (ODF)
        'odb' => 'odf6odb.png', // database
        'odc' => 'odf6odc.png', // chart
        'odf' => 'odf6odf.png', // formula
        'odg' => 'odf6odg.png', // graphics
        'odi' => 'odf6odi.png', // image
        'odm' => 'odf6odm.png', // text master
        'odp' => 'odf6odp.png', // presentation
        'ods' => 'odf6ods.png', // spreadsheet
        'odt' => 'odf6odt.png', // text

        // OpenDocument templates
        'otc' => 'odf6otc.png', // chart template
        'otf' => 'odf6otf.png', // formula template
        'otg' => 'odf6otg.png', // graphics template
        'oth' => 'odf6oth.png', // web text template
        'oti' => 'odf6oti.png', // image template
        'otp' => 'odf6otp.png', // presentation template
        'ots' => 'odf6ots.png', // spreadsheet template
        'ott' => 'odf6ott.png', // text template

        // ROMs / emulators
        'a26' => 'custom/game.gif', // Atari 2600
        'a52' => 'custom/game.gif', // Atari 5200
        'a78' => 'custom/game.gif', // Atari 7800
        'gb'  => 'custom/game.gif', // Game Boy
        'gba' => 'custom/game.gif', // Game Boy Advance
        'gbc' => 'custom/game.gif', // Game Boy Color
        'gen' => 'custom/game.gif', // Sega Genesis
        'gg'  => 'custom/game.gif', // Game Gear
        'lynx' => 'custom/game.gif', // Atari Lynx
        //'md'  => 'custom/game.gif', // Mega Drive
        'n64' => 'custom/game.gif', // Nintendo 64 (little-endian)
        'nds' => 'custom/game.gif', // Nintendo DS
        'nes' => 'custom/game.gif', // NES
        'pce' => 'custom/game.gif', // PC Engine
        'sfc' => 'custom/game.gif', // Super Famicom
        'smc' => 'custom/game.gif', // SNES
        'smd' => 'custom/game.gif', // Mega Drive (alt)
        'sms' => 'custom/game.gif', // Master System
        'v64' => 'custom/game.gif', // Nintendo 64 (byte-swapped)
        'z64' => 'custom/game.gif', // Nintendo 64 (big-endian)

        // text / code
        'asm'   => 'c.gif',
        'awk'   => 'script.gif',
        'bas'   => 'script.gif',
        'bat'   => 'script.gif',
        'bib'   => 'tex.gif',
        'bibtex' => 'tex.gif',
        'c'     => 'c.gif',
        'cfg'   => 'text.gif',
        'conf'  => 'text.gif',
        'cpp'   => 'c.gif',
        'cs'    => 'c.gif',
        'css'   => 'text.gif',
        'csh'   => 'script.gif',
        'diff'  => 'patch.gif',
        'eml'   => 'custom/email.gif',
        'env'   => 'text.gif',
        'f'     => 'f.gif',
        'f90'   => 'f.gif',
        'for'   => 'f.gif',
        'go'    => 'c.gif',
        'h'     => 'c.gif',
        'hpp'   => 'c.gif',
        'htm'   => 'layout.gif',
        'html'  => 'layout.gif',
        'ini'   => 'text.gif',
        'java'  => 'c.gif',
        'js'    => 'script.gif',
        'ksh'   => 'script.gif',
        'log'   => 'text.gif',
        'lua'   => 'script.gif',
        'm'     => 'text.gif',
        //'md'    => 'text.gif',
        'patch' => 'patch.gif',
        'php'   => 'script.gif',
        'pl'    => 'p.gif',
        'pm'    => 'p.gif',
        'ps1'   => 'script.gif',
        'py'    => 'p.gif',
        'r'     => 'script.gif',
        'rb'    => 'script.gif',
        'rs'    => 'c.gif',
        'sh'    => 'script.gif',
        'shar'  => 'script.gif',
        'sql'   => 'script.gif',
        'tcl'   => 'script.gif',
        'tex'   => 'tex.gif',
        'toml'  => 'text.gif',
        'txt'   => 'text.gif',
        'vbs'   => 'script.gif',
        'zsh'   => 'script.gif',

        // Structured Data Text
        'atom'  => 'xml.png',
        'csv'   => 'xml.png',
        'geojson' => 'xml.png',
        'ics'   => 'xml.png',
        'json'  => 'xml.png',
        'jsonc' => 'xml.png',
        'kml'   => 'xml.png',
        'rss'   => 'xml.png',
        'topojson' => 'xml.png',
        'tsv'   => 'xml.png',
        'vcf'   => 'xml.png',
        'xml'   => 'xml.png',
        'xsd'   => 'xml.png',
        'xsl'   => 'xml.png',
        'xslt'  => 'xml.png',
        'yaml'  => 'xml.png',
        'yml'   => 'xml.png',

        // video
        '3g2'   => 'movie.gif',
        '3gp'   => 'movie.gif',
        'avi'   => 'movie.gif',
        'flv'   => 'movie.gif',
        'm4v'   => 'movie.gif',
        'mkv'   => 'movie.gif',
        'mov'   => 'movie.gif',
        'mp4'   => 'movie.gif',
        'mpeg'  => 'movie.gif',
        'mpg'   => 'movie.gif',
        'rm'    => 'movie.gif',
        'rmvb'  => 'movie.gif',
        'webm'  => 'movie.gif',
        'wmv'   => 'movie.gif',

        // virtual reality (world)
        'iv'    => 'world2.gif',
        'vrm'   => 'world2.gif',
        'vrml'  => 'world2.gif',
        'wrl'   => 'world2.gif',
    ];

    return $map[$ext] ?? 'unknown.gif';
}

$items = scandir($path) ?: [];
$dirs = [];
$files = [];

# Apache httpd-autoindex.conf
$ignorePatterns = [
    '*~',
    '*#',
    'RCS',
    'CVS',
    '*,v',
    '*,t',
];

foreach ($items as $item) {
    if ($item === 'comanche') {
        continue;
    }

    if (in_array($item, ['index.php', 'index.htm', 'index.html'], true)) {
        continue;
    }

    if (str_starts_with($item, '.')) {
        continue;
    }

    foreach ($ignorePatterns as $pattern) {
        if (fnmatch($pattern, $item)) {
            continue 2;
        }
    }

    if (is_dir($path . '/' . $item)) {
        $dirs[] = $item;
    } else {
        $files[] = $item;
    }
}

sort($dirs, SORT_NATURAL | SORT_FLAG_CASE);
sort($files, SORT_NATURAL | SORT_FLAG_CASE);

$tab4 = "                ";

if ($dir !== '') {
    $parent = dirname($dir);
    $parent = $parent === '.' ? '' : $parent;

    if ($parent === '') {
        $parentUrl = $baseUrl . '/';
    } else {
        $parentUrl = $baseUrl . '/?dir=' . rawurlencode($parent);
    }

    echo "{$tab4}<tr>\n";
    echo "{$tab4}    <td class=\"top\"><img src=\"{$iconsFolder}/back.gif\" alt=\"[PARENTDIR]\"></td>\n";
    echo "{$tab4}    <td><a href=\"{$parentUrl}\">Parent Directory</a></td>\n";
    echo "{$tab4}    <td class=\"center\"></td>\n";
    echo "{$tab4}    <td class=\"right\">-</td>\n";
    echo "{$tab4}    <td>&nbsp;</td>\n";
    echo "{$tab4}</tr>\n";
}

foreach (array_merge($dirs, $files) as $file) {
    $full = $path . '/' . $file;
    $isDir = is_dir($full);
    $size = $isDir ? '-' : (is_readable($full) ? human_filesize(filesize($full)) : '-');
    $icon = get_icon($full, $isDir);
    $display = $isDir ? "$file/" : $file;
    $safe = htmlspecialchars($display, ENT_QUOTES, 'UTF-8');
    $description = get_description($dir, $file, $descriptions);
    $safeDesc = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
    $safeDesc = nl2br($safeDesc, false);

    if ($isDir) {
        $url = '?dir=' . rawurlencode(trim($dir . '/' . $file, '/'));
    } else {
        $url = rawurlencode(trim($dir . '/' . $file, '/'));
    }

    $url = str_replace('%2F', '/', $url);

    echo "{$tab4}<tr>\n";
    echo "{$tab4}    <td class=\"top\"><img src=\"{$iconsFolder}/{$icon}\" alt=\"" . ($isDir ? '[DIR]' : '[FILE]') . "\"></td>\n";
    echo "{$tab4}    <td><a href=\"{$url}\">{$safe}</a></td>\n";
    echo "{$tab4}    <td class=\"center\">" . get_mtime($full) . "</td>\n";
    echo "{$tab4}    <td class=\"right\">{$size}</td>\n";
    echo "{$tab4}    <td class=\"description\">{$safeDesc}</td>\n";
    echo "{$tab4}</tr>\n";
}
?>
<!-- --------------------------------------------------------------- -->
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5"><hr></td>
                </tr>
                <tr>
                    <td colspan="5" class="copyright">
                        Comanche Directory Indexer v1.0-20260427 &copy; 2026 Daniel Dias Rodrigues
                    </td>
                </tr>
                <tr>
                    <td colspan="5" class="copyright">
                        Released under the <a href="https://opensource.org/license/MIT"
                        target="_blank" rel="noopener noreferrer">MIT License</a>
                    </td>
                </tr>
            </tfoot>
        </table>

        <script>
            function parseSize(size) {
                if (size === '-' || size.trim() === '') return 0;

                const units = {
                    '': 1,
                    'K': 1024,
                    'M': 1024 * 1024,
                    'G': 1024 * 1024 * 1024,
                    'T': 1024 * 1024 * 1024 * 1024
                };

                const match = size.match(/^([\d\.]+)([KMGTP]?)$/i);
                if (!match) return 0;

                return parseFloat(match[1]) * (units[match[2].toUpperCase()] || 1);
            }

            function parseDate(str) {
                if (str === '-' || str.trim() === '') return 0;
                return new Date(str.replace(' ', 'T')).getTime() || 0;
            }

            function sortTable(colIndex, type) {
                const tbody = document.querySelector("#indexTable tbody");
                const rows = Array.from(tbody.querySelectorAll("tr"));

                // Filter only valid rows (with 5 columns)
                const validRows = rows.filter(row => row.children.length === 5);

                let dir = tbody.getAttribute("data-sort-dir") || "asc";
                dir = dir === "asc" ? "desc" : "asc";
                tbody.setAttribute("data-sort-dir", dir);

                validRows.sort((a, b) => {
                    let x = a.children[colIndex].innerText.trim();
                    let y = b.children[colIndex].innerText.trim();
                    x = x.replace(/\u00A0/g, '').trim();
                    y = y.replace(/\u00A0/g, '').trim();

                    let xVal, yVal;

                    if (type === 'size') {
                        xVal = parseSize(x);
                        yVal = parseSize(y);
                    } else if (type === 'date') {
                        xVal = parseDate(x);
                        yVal = parseDate(y);
                    } else {
                        xVal = x.toLowerCase();
                        yVal = y.toLowerCase();
                    }

                    if (dir === "asc") {
                        return xVal > yVal ? 1 : -1;
                    } else {
                        return xVal < yVal ? 1 : -1;
                    }
                });

                // Remove only valid lines and reattach.
                validRows.forEach(row => tbody.appendChild(row));
            }
        </script>

    </body>
</html>
