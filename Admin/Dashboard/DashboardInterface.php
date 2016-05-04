<?php
namespace ITF\AdminBundle\Admin\Dashboard;

use Symfony\Component\HttpFoundation\Response;

interface DashboardInterface
{
	/**
	 * @return string
	 */
	public function getTitle();

	/**
	 * @return Response
	 */
	public function renderDashboard();
}