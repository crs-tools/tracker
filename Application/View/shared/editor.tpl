<?php
	$this->contentFor('stylesheets', '<link rel="stylesheet" href="' . $this->Request->getRootURL() . 'css/codemirror.css" type="text/css" />');
	$this->contentFor('scripts', '<script src="' . $this->Request->getRootURL() . 'javascript/codemirror-3.20.js" type="text/javascript"></script>');
?>