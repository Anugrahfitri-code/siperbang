<?php
$finfo = finfo_open(FILEINFO_MIME_TYPE);
echo "MIME TYPE: " . finfo_file($finfo, 'D:\\260330 Percetakan Parahyangan 1.250.000.pdf') . "\n";
