<?php

#patchwork __patchwork__/pieces/pTask

$CONFIG += array(
    'pMail.sender'      => '',          // Default value for From and Return-Path headers
    'pMail.debug_email' => 'webmaster', // Used by pMail in test mode
    'pMail.backend'     => 'mail',      // See PEAR's Mail_mime constructor
    'pMail.options'     => '',          // See PEAR's Mail_mime constructor
);
