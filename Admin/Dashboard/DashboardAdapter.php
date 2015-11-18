<?php
namespace ITF\AdminBundle\Admin\Dashboard;

use Symfony\Component\HttpFoundation\Response;

class DashboardAdapter implements DashboardInterface
{
	private $dashboard_service;

	public function __construct(DashboardInterface $dashboard = NULL)
	{
		if ($dashboard instanceof DashboardInterface) {
			$this->dashboard_service = $dashboard;
		}
	}

	public function getDashboardService()
	{
		return $this->dashboard_service;
	}

	public function setDashboardService(DashboardInterface $dashboard)
	{
		$this->dashboard_service = $dashboard;
	}

	private function isDashboardSet()
	{
		return $this->getDashboardService() instanceof DashboardInterface;
	}

	public function getTitle()
	{
		if ($this->isDashboardSet()) {
			return $this->getDashboardService()->getTitle();
		}

		return 'Admin Dashboard';
	}

	public function renderView()
	{
		if ($this->isDashboardSet()) {
			$response = $this->getDashboardService()->renderView();
		} else {
			$response = new Response("");
		}

		return $response->getContent();
	}
}