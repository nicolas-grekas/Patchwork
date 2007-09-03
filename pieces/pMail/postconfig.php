<?php

isset($CONFIG['pMail.debug_email']) || $CONFIG['pMail.debug_email'] = 'webmaster';
isset($CONFIG['pMail.from'])        || $CONFIG['pMail.from']        = '';
isset($CONFIG['pMail.backend'])     || $CONFIG['pMail.backend']     = 'mail';
isset($CONFIG['pMail.options'])     || $CONFIG['pMail.options']     = '';
