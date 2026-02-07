<?php
require_once 'includes/db.php';
execute("UPDATE matches SET observations = '' WHERE id = 559");
echo "Clear successful";
