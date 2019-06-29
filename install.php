<?php
require realpath( __DIR__ . '/../../../../../../wp-blog-header.php' );
require realpath( __DIR__ . '/../../../vendor/autoload.php' );

use Mytory\Board\MytoryBoard;
use Mytory\Board\Setup;

$setup = new Setup( new MytoryBoard() );

$setup->flushRewriteRules();
echo "Flushed RewirteRules." . PHP_EOL;

$setup->addRole();
echo "Added board_writer(게시판 글쓴이) Role." . PHP_EOL;


