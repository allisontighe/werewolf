<?php
require_once 'Database.class.php';
echo json_encode((new Database())->viewLog());