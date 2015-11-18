<?php
namespace ITF\AdminBundle\Admin;

use ITF\AdminBundle\Admin\Dashboard\DashboardAdapter;
use ITF\AdminBundle\Admin\Dashboard\DashboardInterface;

class AdminFactory
{
	public function createDashboardAdapter(DashboardInterface $dashboard = NULL)
	{
		return new DashboardAdapter($dashboard);
	}
}