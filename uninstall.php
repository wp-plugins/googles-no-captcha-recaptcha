<?php

	if (!defined("WP_UNINSTALL_PLUGIN")) 
    	exit();

    delete_option("captcha_site_key");
    delete_option("captcha_secret_key");
