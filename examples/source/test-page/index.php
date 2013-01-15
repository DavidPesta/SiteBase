<?php

include LIBRARY . "/Page.php";

$page = new Page();

$page->addViewFiles([
	"layout" => "/test-page/layout.phtml",
	"sublayout1" => "/test-page/sublayout1.phtml",
	"sublayout2" => "/test-page/sublayout2.phtml",
	"content1" => "/test-page/content1.phtml",
	"content2" => "/test-page/content2.phtml"
]);

$page->var1 = "one";
$page->var2 = "two";

$page->show();
