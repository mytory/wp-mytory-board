<?php
require '../../../../../../wp-blog-header.php';
require '../../../vendor/autoload.php';

use Mytory\Board\Setup;
use Mytory\Board\MytoryBoard;

$setup = new Setup(new MytoryBoard());

$setup->removeRole();
echo "Removed board_writer(게시판 글쓴이) Role." . PHP_EOL;

flush_rewrite_rules();
echo "Flushed RewirteRules." . PHP_EOL;



