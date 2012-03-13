<?php

class BfoxPlansController extends BfoxPluginController {

	function init() {
		parent::init();

		require_once $this->dir . '/bfox_plan.php';
		require_once $this->apiDir . '/bfox_plan-template.php';
		require_once $this->core->refDir . '/bfox_plan_parser.php';
		require_once $this->core->refDir . '/bfox_plan_scheduler.php';
	}

	function wpInit() {
		wp_enqueue_style($this->slug . '-style', $this->url . '/theme/style-bfox_plan.css', array(), $this->version);
	}
}

?>