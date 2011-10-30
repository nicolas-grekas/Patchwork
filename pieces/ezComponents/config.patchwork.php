<?php

#patchwork __patchwork__/core/superloader

/*
 * To use eZComponents, setup them using the PEAR method,
 * then import them in your config.patchwork.php with:
 * #patchwork __patchwork__/pieces/ezComponents
 */

Patchwork_Superloader::registerPrefix('ezc', array('adapter_ezc', 'getAutoload'));
