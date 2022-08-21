<?php

use \Psr\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use Slim\Http\UploadedFile;

class DevelopController
{
	protected $container;
	public function __construct()
	{
		global $container;
		$this->container = $container;
	}
	public function renderProcessesSetting($request, $response, $args)
	{
		$renderer = new PhpRenderer($this->container->view);
		return $renderer->render($response, '/develop/processesSetting.html', []);
	}
	public function renderStandardProcessOverView($request, $response, $args)
	{
		$renderer = new PhpRenderer($this->container->view);
		return $renderer->render($response, '/develop/standardProcessOverView.html', []);
	}
	public function renderStandardProcess($request, $response, $args)
	{
		$renderer = new PhpRenderer($this->container->view);
		return $renderer->render($response, '/develop/standardProcess.html', []);
	}
	public function renderMaterial($request, $response, $args)
	{
		$renderer = new PhpRenderer($this->container->view);
		return $renderer->render($response, '/develop/material.php', []);
	}

	public function renderProcess($request, $response, $args)
	{
		$data = $request->getQueryParams();
		$develop = new develop($this->container->db);
		$develop->checkProcessResult($data);
		$renderer = new PhpRenderer($this->container->view);
		return $renderer->render($response, '/develop/process.php', []);
	}

	public function getComponentMatch($request, $response, $args)
	{

		$home = new Home($this->container->db);
		$business = new Business($this->container->db);

		$data = $args;
		foreach ($request->getQueryParams() as $key => $value) {
			$data[$key] = $value;
		}
		#var_dump($request->getParsedBody());
		$result = $home->getComponentMatch($data);
		$result['process'] = $business->getMaterialStuffByOrderSerial($result);
		$result['status'] = $home->getProcessStatus($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}
	public function renderSample($request, $response, $args)
	{
		$renderer = new PhpRenderer($this->container->view);
		return $renderer->render($response, '/develop/sampleHistoryCNC1.php', []);
	}

	function get_processes_type($request, $response, $args)
	{
		global $container;
		$develop = new develop($container->db);
		$data = $request->getQueryParams();

		$result = $develop->get_processes_type($data);

		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function post_processes_type($request, $response, $args)
	{
		global $container;
		$develop = new develop($container->db);
		$data = $request->getParsedBody();

		$result = $develop->post_processes_type($data);

		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function delete_processes_type($request, $response, $args)
	{
		global $container;
		$develop = new develop($container->db);
		$data = $request->getParsedBody();

		$result = $develop->delete_processes_type($data);

		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_processes($request, $response, $args)
	{
		global $container;
		$develop = new develop($container->db);
		$data = $request->getQueryParams();

		$result = $develop->get_processes($data);

		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function post_processes_template($request, $response, $args)
	{
		global $container;
		$develop = new develop($container->db);
		$data = $request->getParsedBody();

		$result = $develop->post_processes_template($data);

		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_processes_template_processes($request, $response, $args)
	{
		global $container;
		$develop = new develop($container->db);
		$data = $request->getQueryParams();

		$result = $develop->get_processes_template_processes($data);

		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function post_processes_template_processes($request, $response, $args)
	{
		global $container;
		$develop = new develop($container->db);
		$data = $request->getParsedBody();

		$result = $develop->post_processes_template_processes($data);

		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function postStandardProcesses($request, $response, $args)
	{
		global $container;
		$develop = new develop($container->db);
		$data = $request->getParsedBody();

		if (!isset($data['standard_processes_id']) || $data['standard_processes_id'] == "") {
			$result = $develop->postStandardProcesses($data);
			foreach ($data['processes'] as $key => $data_) {
				$data['processes'][$key]['standard_processes_id'] = $result['standard_processes_id'];
				$develop->postStandardProcess($data['processes'][$key]);
			}
		} else {
			$result = $develop->updateStandardProcesses($data);
			$develop->deleteStandardProcesses($data);
			foreach ($data['processes'] as $key => $data_) {
				$data['processes'][$key]['standard_processes_id'] = $result['standard_processes_id'];
				$develop->postStandardProcess($data['processes'][$key]);
			}
		}

		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function getCustomProcessesOne($request, $response, $args)
	{
		global $container;
		$develop = new develop($container->db);
		$params = $request->getQueryParams();
		$result = $develop->readCustomProcessesOne($params);
		foreach ($result as $key => $value) {
			if ($develop->isJson($value)) {
				$result[$key] = json_decode($value, true);
			}
		}
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function getCustomProcessesAll($request, $response, $args)
	{
		global $container;
		$develop = new develop($container->db);
		$params = $request->getQueryParams();
		$result = $develop->readCustomProcessesAll($params);
		if (array_key_exists('data', $result)) {
			foreach ($result['data'] as $key => $value) {
				foreach ($value as $key2 => $value2) {
					if ($develop->isJson($value2)) {
						$result['data'][$key][$key2] = json_decode($value2, true);
					}
				}
			}
		} else {
			foreach ($result as $key => $value) {
				foreach ($value as $key2 => $value2) {
					if ($develop->isJson($value2)) {
						$result[$key][$key2] = json_decode($value2, true);
					}
				}
			}
		}
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function deleteCustomProcesses($request, $response, $args)
	{
		global $container;
		$develop = new develop($container->db);
		$data = $request->getParsedBody();
		foreach ($data['standard_processes_id'] as $key => $value) {
			$params = [];
			$params['standard_processes_id'] = $value;
			$develop->deleteStandardProcesses($params);
			$result = $develop->deleteCustomProcesses($params);
		}
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}
}
