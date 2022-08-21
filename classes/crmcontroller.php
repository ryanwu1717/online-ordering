<?php

use \Psr\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use Slim\Http\UploadedFile;
use \PHPMailer\PHPMailer\PHPMailer;
use \PHPMailer\PHPMailer\Exception;

class CRMController
{
	protected $container;
	public function __construct()
	{
		global $container;
		$this->container = $container;
	}
	public function renderMeetOverView($request, $response, $args)
	{
		$renderer = new PhpRenderer($this->container->view);
		return $renderer->render($response, '/crm/meetOverView.html');
	}
	public function renderComplaintOverView($request, $response, $args)
	{
		$renderer = new PhpRenderer($this->container->view);
		return $renderer->render($response, '/crm/complaintOverView.html');
	}
	public function renderComplaint($request, $response, $args)
	{
		$renderer = new PhpRenderer($this->container->view);
		return $renderer->render($response, '/crm/complaint.html');
	}
	public function renderQualityForm($request, $response, $args)
	{
		$renderer = new PhpRenderer($this->container->view);
		return $renderer->render($response, '/crm/complaintForm.html');
	}
	public function renderMeet($request, $response, $args)
	{
		$renderer = new PhpRenderer($this->container->view);
		return $renderer->render($response, '/crm/meet.html');
	}

	function get_meets($request, $response, $args)
	{
		global $container;
		$data = $request->getQueryParams();
		$crm = new CRM($container->db);
		$result = $crm->get_meets($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function post_meet($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$datas = $request->getParsedBody();

		//新增會議
		$meet_result = $crm->post_meet($datas);
		$result = $meet_result;

		foreach ($datas as $key => $data) {
			if (array_key_exists('participant', $data)) {
				$datas[$key]['meet_id'] = $meet_result[$key]['id'];
			}
		}

		//新增會議人員
		$crm->post_meet_participant($datas);

		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);

		return $response;
	}

	function patch_meet($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParsedBody();
		$meet_result = $crm->patch_meet($data);

		if (array_key_exists('participant', $data)) {
			$participant_data['meet_id'] = $data['id'];
			$participant_data['participant'] = $data['participant'];
			$participant_result = $crm->patch_meet_participant($participant_data);
		}

		if ($meet_result['status'] == "success" && $participant_result['status'] == "success") {
			$result['status'] = "success";
		} else {
			$result['status'] = "failed";
		}

		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function delete_meet($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParsedBody();
		$result = $crm->delete_meet($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_meet_participant($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getQueryParams();
		$result = $crm->get_meet_participant($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_complaint($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getQueryParams();
		$result = $crm->get_complaint($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_attach_frame($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$phasegallerycontroller = new PhaseGalleryController($container->db);
		$params = $request->getQueryParams();
		$result = $crm->read_complaint_position($params);
		// $result = $phasegallerycontroller->getPointList($delivery_meet_content_position);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function patch_attach_frame($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$params = $request->getParsedBody();
		$result = $crm->updateDeliveryMeetContentPosition($params);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function post_attach_frame($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$params = $request->getParsedBody();
		$params['position_id'] = $crm->createtPosition($params)['position_id'];
		foreach ($params['point_list'] as $key => $value) {
			$crm->createtPoint($params['position_id'], $key, $value);
		}
		$result = $crm->create_complaint_position($params);
		$result['position_id'] = $params['position_id'];
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function delete_attach_frame($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$params = $request->getParsedBody();
		foreach ($params as $params_) {
			$result = $crm->deleteDeliveryMeetContentPosition($params_);
		}
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	public function get_attach_file_paint($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getQueryParams();
		$result = $crm->get_attach_file_paint($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}
	public function upload_attach_paint($request, $response, $args)
	{
		$data = $request->getParams();
		$data['files'] = $request->getUploadedFiles();
		$crm = new CRM($this->container->db);
		$phasegallery = new PhaseGallery($this->container->db);
		foreach($data['files'] as $file){
			$file = $phasegallery->uploadFile(["files"=>["inputFile"=>$file]]);
			unset($data['files']);
			$file['user_id'] = 0;
			$data['file_id'] = $phasegallery->insertFile($file);
			$result = $crm->post_attach_file_paint($data);
		}
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	public function delete_attach_paint($request, $response, $args)
	{
		$data = $request->getParsedBody();
		$crm = new CRM($this->container->db);
		$result = $crm->delete_attach_paint($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_today_complaint($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getQueryParams();
		$result = $crm->get_today_complaint($data);

		foreach ($result['data'] as $key => $value) {
			if ($result['data'][$key]['file_id']) {
				$result['data'][$key]['file_id'] = explode(",", $value['file_id']);
			}
		}

		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function post_complaint($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParsedBody();
		$result = $crm->post_complaint($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	public function upload_complaint_file($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParams();
		$data['files'] = $request->getUploadedFiles();
		$data['files'] = $crm->upload_delivery_meet_content_file($data);
		$data['files'] = $crm->decompress_delivery_meet_content_file($data['files']);
		$data = $crm->insert_complaint_file($data);
		$result = $data;
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	public function get_attach_file_by_id($request, $response, $args)
	{
		$data = $args;
		$crm = new CRM($this->container->db);
		$file = $this->container->upload_directory . DIRECTORY_SEPARATOR . (empty($crm->get_attach_file_by_id($data)) ? '0' : $crm->get_attach_file_by_id($data));
		$source = $this->compressImage($file, $file, 100);
		imagejpeg($source);
		$response = $response->withHeader('Content-Description', 'File Transfer')
			->withHeader('Content-Type', 'application/octet-stream')
			->withHeader('Content-Disposition', 'attachment;filename="' . 'phasegallery.jpg' . '"')
			->withHeader('Expires', '0')
			->withHeader('Cache-Control', 'must-revalidate')
			->withHeader('Pragma', 'public');
		return $response;
	}

	function patch_complaint($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParsedBody();
		$result = $crm->patch_complaint($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function delete_complaint($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParsedBody();
		$result = $crm->delete_complaint($data);
		$result = $crm->get_complaint($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_record_meet($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getQueryParams();
		$result = $crm->get_all_meet($data);

		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function patch_record_meet($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParsedBody();

		$data['meet_id'] = $data['id'];
		$result = $crm->patch_meet($data);

		$result = $crm->post_modify_meet_record($data);

		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_sale_meet($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getQueryParams();
		$result = $crm->get_sale_meet($data);


		foreach ($result as $key => $value) {
			if ($value['participant']) {
				$result[$key]['participant'] = explode(",", $value['participant']);
			}
			if ($value['customer_img']) {
				$result[$key]['customer_img'] = explode(",", $value['customer_img']);
			}
			if ($value['factory_img']) {
				$result[$key]['factory_img'] = explode(",", $value['factory_img']);
			}
		}

		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_sale_meet_complaint_id($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getQueryParams();
		$result = $crm->get_sale_meet_complaint_id($data);

		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function patch_sale_meet($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParsedBody();

		$complaint_id = $crm->get_sale_meet_complaint_id($data);
		$data['complaint_id'] = $complaint_id;

		$crm->patch_meet_participant($data);

		//記錄人
		if (array_key_exists('recorder_user_id', $data)) {
			$meet_data['id'] = $data['meet_id'];
			$meet_data['recorder_user_id'] = $data['recorder_user_id'];
			$result = $crm->patch_meet($meet_data);
		}
		//上傳的mail
		if (array_key_exists('mail_name', $data)) {
			$complaint_data['complaint_id'] = $data['complaint_id'];
			$complaint_data['complaint_file_name'] = $data['mail_name'];
		}
		//會議記錄內容
		if (array_key_exists('record_content', $data)) {
			$complaint_data['complaint_id'] = $data['complaint_id'];
			$complaint_data['complaint_record'] = $data['record_content'];
		}

		$result = $crm->patch_complaint($complaint_data);

		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_attach_file($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getQueryParams();
		$result = $crm->get_attach_file($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function post_attach_file($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParsedBody();
		$result = $crm->post_attach_file($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function delete_attach_file($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParsedBody();
		$result = $crm->delete_attach_file($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function break_attach_file_link($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParsedBody();
		$result = $crm->break_attach_file_link($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_delivery_meet_content($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getQueryParams();

		if ($data['order_type'] == 'order') {
			//訂單
			$result = $crm->get_order($data);
		} else if ($data['order_type'] == 'quotation') {
			//報價單
			$result = $crm->get_quotation($data);
		}

		$result = $crm->get_quotation($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function upload_sale_meet_file($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParams();


		$data['complaint_id'] = $crm->get_sale_meet_complaint_id($data);

		$data['files'] = $request->getUploadedFiles();

		//上傳檔案
		$files = $crm->upload_file($data);
		$file = '';
		$result = [];
		foreach ($files as $file_name) {
			$file = $file_name;
		}

		$file_type = pathinfo($file, PATHINFO_EXTENSION);
		$img = [];

		if ($file_type === 'pdf' || $file_type === 'msg') {
			//切割pdf成圖片並回傳檔名
			$img = $crm->get_pdf_split($file);
			// 圖檔綁定complaint
			foreach ($img as $file_names) {
				foreach ($file_names as $file_name) {
					$data['name'] = $file_name;
					$attach_file_id = $crm->post_attach_file($data);

					if (array_key_exists('image_type', $data)) {
						$data['attach_file_id'] = $attach_file_id;
						$crm->post_file_image_type($data);
					}

					array_push($result, $attach_file_id);
				}
			}
		} else {
			$data['name'] = $file;
			$attach_file_id = $crm->post_attach_file($data);

			if (array_key_exists('image_type', $data)) {
				$data['attach_file_id'] = $attach_file_id;
				$crm->post_file_image_type($data);
			}

			array_push($result, $attach_file_id);
		}

		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function pdf_split($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParams();
		$data['files'] = $request->getUploadedFiles();

		//上傳檔案
		$files = $crm->upload_file($data);
		$file = '';
		$result = [];
		foreach ($files as $file_name) {
			$file = $file_name;
		}

		$file_type = pathinfo($file, PATHINFO_EXTENSION);

		if ($file_type === 'pdf' || $file_type === 'msg') {

			$original_file = $crm->get_pdf_split($file);

			//切割pdf成圖片並回傳檔名
			foreach ($original_file as $imgs) {
				$result = $imgs;
			}
		} else {
			array_push($result, $file);
		}

		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_message_parse($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParams();
		$data['files'] = $request->getUploadedFiles();

		$files = $crm->upload_file($data);
		$data = [];
		foreach ($files as $file_name) {
			array_push($data, $file_name);
		}
		$result = $crm->getMessageParseText($data);

		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_message_translate($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParams();
		$data['files'] = $request->getUploadedFiles();

		$files = $crm->upload_file($data);
		$result = [];
		$data = [];

		foreach ($files as $file_name) {
			array_push($data, $file_name);
			$result['file_name'] = $file_name;
		}
		$texts = $crm->getMessageParseText($data);

		foreach ($texts as $text) {
			foreach ($text as $key => $row) {
				switch ($key) {
					case "Header": {
							$pattern = '/\nSubject:(.+)\n/';
							preg_match($pattern, $row, $match);
							$result['original']['subject'] = $match[1];
							break;
						}
					case "Body": {
							$result['original']['body'] = $row;
							break;
						}
				}
			}
		}
		$translation = [];

		$data_for_translate['language'] = 'en';
		$data_for_translate['content_for_translate'] = $result['original']['body'];
		$translation['body'] = $crm->translate($data_for_translate);
		$data_for_translate['content_for_translate'] = $result['original']['subject'];
		$translation['subject'] = $crm->translate($data_for_translate);
		$result['english'] = $translation;

		$data_for_translate['language'] = 'zh-TW';
		$data_for_translate['content_for_translate'] = $result['original']['body'];
		$translation['body'] = $crm->translate($data_for_translate);
		$data_for_translate['content_for_translate'] = $result['original']['subject'];
		$translation['subject'] = $crm->translate($data_for_translate);
		$result['chinese'] = $translation;

		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	public function upload_delivery_meet_content_file($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParams();
		$data['files'] = $request->getUploadedFiles();
		$data['files'] = $crm->upload_delivery_meet_content_file($data);
		$data['files'] = $crm->decompress_delivery_meet_content_file($data['files']);
		$data = $crm->insert_delivery_meet_content_file($data);
		$result = $data;
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	public function get_delivery_meet_content_file($request, $response, $args)
	{
		$data = $args;
		$crm = new CRM($this->container->db);
		$file = $crm->get_delivery_meet_content_file($data);
		$source = $this->compressImage($file, $file, 100);
		imagejpeg($source);
		$response = $response->withHeader('Content-Description', 'File Transfer')
			->withHeader('Content-Type', 'application/octet-stream')
			->withHeader('Content-Disposition', 'attachment;filename="' . 'phasegallery.jpg' . '"')
			->withHeader('Expires', '0')
			->withHeader('Cache-Control', 'must-revalidate')
			->withHeader('Pragma', 'public');
		return $response;
	}

	function compressImage($source = false, $destination = false, $quality = 80, $filters = false)
	{
		$info = getimagesize($source);
		switch ($info['mime']) {
			case 'image/jpeg':
				/* Quality: integer 0 - 100 */
				if (!is_int($quality) or $quality < 0 or $quality > 100) $quality = 80;
				return imagecreatefromjpeg($source);

			case 'image/gif':
				return imagecreatefromgif($source);

			case 'image/png':
				/* Quality: Compression integer 0(none) - 9(max) */
				if (!is_int($quality) or $quality < 0 or $quality > 9) $quality = 6;
				return imagecreatefrompng($source);

			case 'image/webp':
				/* Quality: Compression 0(lowest) - 100(highest) */
				if (!is_int($quality) or $quality < 0 or $quality > 100) $quality = 80;
				return imagecreatefromwebp($source);

			case 'image/bmp':
				/* Quality: Boolean for compression */
				if (!is_bool($quality)) $quality = true;
				return imagecreatefrombmp($source);

			default:
				return;
		}
	}

	function post_delivery_meet($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$datas = $request->getParsedBody();
		//新增會議
		$meet_ids = $crm->post_meet($datas);

		foreach ($datas as $key => $data) {
			$datas[$key]["meet_id"] = $meet_ids[$key]['id'];
		}

		$result = [];

		//新增complaint
		$complaint_ids = $crm->post_complaint($datas);

		foreach ($datas as $key => $data) {
			$datas[$key]["complaint_id"] = $complaint_ids[$key]['complaint_id'];
		}

		//新增complaint_fk
		$complaint_ids = $crm->post_complaint_fk($datas);

		foreach ($datas as $key => $data) {

			$content_result = "";

			if (array_key_exists("coptd_td001", $data['fk'])) {
				//取得訂單內容
				$content_result = $crm->get_order($data['fk']);
			} else if (array_key_exists("coptb_tb001", $data['fk'])) {
				//取得報價單內容
				$content_result = $crm->get_quotation($data['fk']);
			}

			$tmp = [
				"meet_id" => $meet_ids[$key]['id'],
				"content" => $content_result
			];

			$result[$key] = $tmp;
		}

		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_modify_meet_record($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getQueryParams();
		$result = $crm->get_modify_meet_record($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function post_modify_meet_record($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParsedBody();
		$result = $crm->post_modify_meet_record($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function delete_modify_meet_record($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParsedBody();
		$result = $crm->delete_modify_meet_record($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_discuss($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getQueryParams();
		$result = $crm->get_discuss($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function post_discuss($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParsedBody();
		$result = $crm->post_discuss($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function patch_discuss_over($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParsedBody();
		$result = $crm->patch_discuss_over($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function delete_discuss($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParsedBody();
		$result = $crm->delete_discuss($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_tracking($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getQueryParams();
		$result = $crm->get_tracking($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function post_tracking($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParsedBody();
		$result = $crm->post_tracking($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function patch_tracking_complete($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParsedBody();
		$result = $crm->patch_tracking_complete($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function delete_tracking($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParsedBody();
		$result = $crm->delete_tracking($data['tracking_id']);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_tracking_process($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getQueryParams();
		$result = $crm->get_tracking_process($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function post_tracking_process($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParsedBody();
		$result = $crm->post_tracking_process($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function delete_tracking_process($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParsedBody();
		$result = $crm->delete_tracking_process($data['tracking_process_id']);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_user_module($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getQueryParams();
		$result = $crm->get_user_module($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_all_user_module($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$result = $crm->get_all_user_module();
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_all_module($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$result = $crm->get_all_module();
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_all_user($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getQueryParams();
		$result = $crm->get_all_user($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_user_participant($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getQueryParams();
		$result = $crm->get_user_participant($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_image_type($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$result = $crm->get_image_type();
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_frequent_user($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getQueryParams();
		$result = $crm->get_frequent_user($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_frequent_group($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getQueryParams();
		$data['id'] = $_SESSION['id'];
		$result = $crm->get_frequent_group($data);

		foreach ($result as $group_key => $group) {
			if ($group['participant']) {
				$participant_explode = explode(",", $group['participant']);

				$participant_array = [];
				foreach ($participant_explode as $user) {
					$user_array = explode('*', $user);

					$user_data = [];
					foreach ($user_array as $key => $value) {
						if ($key == 0) {
							$user_data['value'] = intval($value);
						} else if ($key == 1) {
							$user_data['label'] = $value;
						}
					}
					array_push($participant_array, $user_data);
				}

				$group['participant'] = $participant_array;
				$result[$group_key] = $group;
			}
		}

		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function post_frequent_group($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParsedBody();

		$data['frequent_group_id'] = $crm->post_frequent_group($data);
		$result = $crm->post_frequent_user($data);

		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function patch_frequent_group($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParsedBody();
		$crm->patch_frequent_group($data);
		$result = $crm->patch_frequent_user($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function delete_frequent_group($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParsedBody();
		$result = $crm->delete_frequent_group($data['frequent_group_id']);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_user($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = ["id" => $_SESSION['id']];
		$result = $crm->get_user($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function post_user($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$datas = $request->getParsedBody();
		$datas['editor_id']= $_SESSION['id'];
		$result = $crm->post_user($datas);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function delete_user($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$datas = $request->getParsedBody();
		$datas['user'] = $datas;
		$datas['editor_id'] = $_SESSION['id'];
		$result = $crm->delete_user($datas);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_meet_type($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$result = $crm->get_meet_type();
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function post_meet_type($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$datas = $request->getParsedBody();
		$result = $crm->post_meet_type($datas);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function delete_meet_type($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$datas = $request->getParsedBody();
		$result = $crm->delete_meet_type($datas);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_column_order($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$result = $crm->get_column_order();
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function post_complaint_fk($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$datas = $request->getParsedBody();
		$result = $crm->post_complaint_fk($datas);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function get_delivery_meet_content_id($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getQueryParams();
		$result = $crm->get_delivery_meet_content_id($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	function patch_customer_delivery_date($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParsedBody();
		$delivery_meet_content_ids = $crm->get_delivery_meet_content_id($data);

		if ($delivery_meet_content_ids) {
			//存在對應交期會議內容，修改

			foreach ($delivery_meet_content_ids as $delivery_meet_content_id) {

				$date_data = [
					'delivery_meet_content_id' => $delivery_meet_content_id['delivery_meet_content_id'],
					'customer_expected_delivery_date' => $data['customer_expected_delivery_date']
				];

				$result = $crm->patch_customer_delivery_date($date_data);
			}
		} else {
			//無對應交期會議內容，新增
			$delivery_meet_content_id = $crm->post_customer_delivery_date($data);

			//將會議內容id寫回complaint
			$no_sercverside = 0;
			$data['size'] = $no_sercverside;
			$complaints = $crm->get_complaint($data);

			foreach ($complaints as $complaint) {
				$complaint_data = [
					'complaint_id' => $complaint['complaint_id'],
					'delivery_meet_content_id' => $delivery_meet_content_id
				];

				$result = $crm->patch_complaint($complaint_data);
			}
		}

		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}

	public function upload_file($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getParams();
		$result = $crm->decompress_delivery_meet_content_file($data['files']);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}
	public function get_complaint_report($request, $response, $args)
	{
		$data = $request->getQueryParams();
		$crm = new CRM($this->container->db);
		$fetch = $crm->get_complaint_report($data);
		$fetch['meet_date'] !== NULL ? $timestamp = strtotime($fetch['meet_date']) : $timestamp = '';
		$date = date('Y-m-d', $timestamp);
		if ($data['type'] == "pdf") {
			$filename = "客戶抱怨處理單" . date("Y-m-d-H-i-s") . ".pdf";
			$tcpdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
			$tcpdf->setPrintHeader(false);  /* hide default header / footer */
			$tcpdf->setPrintFooter(false);
			$tcpdf->AddPage();  /* new page */
			$tcpdf->SetFont('msungstdlight', 'B', 20);  /* title */
			$pdf_title = <<<EOD
			龍畿企業股份有限公司\n客戶抱怨處理單
			EOD;
			$tcpdf->Write(0, $pdf_title, '', 0, 'C', true, 0, false, false, 0);
			$tcpdf->Ln();
			$tcpdf->SetFont('msungstdlight', '', 12);  /* table */
			$pdf_table = <<<EOD
			<table cellpadding="2" align="center" vertical-align="middle">
				<tr>
					<td width="10%" border="1">客戶代號</td>
					<td width="40%" border="1">{$fetch['complaint_customer_id']}</td>
					<td width="10%" border="1">填單日期</td>
					<td width="15%" border="1">{$date}</td>
					<td width="10%" rowspan="4" border="1">責 簽<br>任 名<br>單 日<br>位 期</td>
					<td width="15%" rowspan="4" border="1"></td>
				</tr>
				<tr>
					<td width="10%" border="1">品&emsp;&emsp;號</td>
					<td width="40%" border="1"></td>
					<td width="10%" border="1">出貨日期</td>
					<td width="15%" border="1"></td>
				</tr>
				<tr>
					<td width="10%" border="1">訂單號碼</td>
					<td width="40%" border="1"></td>
					<td width="10%" border="1">出貨數量</td>
					<td width="15%" border="1"></td>
				</tr>
				<tr>
					<td width="10%" border="1">圖&emsp;&emsp;號</td>
					<td width="40%" border="1">{$fetch['file_id']}</td>
					<td width="10%" border="1">不良數量</td>
					<td width="15%" border="1"></td>
				</tr>
				<tr>
					<td width="75%" colspan="3" align="left" border="1">客戶抱怨內容: {$fetch['content']}<br><br><br><br></td>
					<td width="10%" border="1">責 簽<br>任 名<br>單 日<br>位 期</td>
					<td width="15%" border="1"></td>
				</tr>
				<tr>
					<td width="50%" colspan="2" rowspan="2" align="left" style="border-left-width: 1px solid">現狀掌握:</td>
					<td width="10%" border="1">現有庫存</td>
					<td width="15%" border="1"></td>
					<td width="10%" rowspan="3" border="1">責 簽<br>任 名<br>單 日<br>位 期</td>
					<td width="15%" rowspan="3" border="1"></td>
				</tr>
				<tr>
					<td width="10%" border="1">現有訂單</td>
					<td width="15%" border="1"></td>
				</tr>
				<tr>
					<td width="75%" style="border-left-width: 1px solid">&emsp;<br></td>
				</tr>
				<tr>
					<td width="75%" colspan="3" align="left" border="1">問題分析與原因追查:<br><br><br><br></td>
					<td width="10%" border="1">責 簽<br>任 名<br>單 日<br>位 期</td>
					<td width="15%" border="1"></td>
				</tr>
				<tr>
					<td width="75%" colspan="3" align="left" border="1">改善對策:(庫存,現有訂單,未來訂單)<br><br><br><br></td>
					<td width="10%" border="1">責 簽<br>任 名<br>單 日<br>位 期</td>
					<td width="15%" border="1"></td>
				</tr>
				<tr>
					<td width="75%" colspan="3" align="left" border="1">內部追蹤:<br><br><br><br></td>
					<td width="10%" border="1">責 簽<br>任 名<br>單 日<br>位 期</td>
					<td width="15%" border="1"></td>
				</tr>
				<tr>
					<td width="75%" colspan="3" align="left" border="1">外部追蹤:<br><br><br><br></td>
					<td width="10%" border="1">責 簽<br>任 名<br>單 日<br>位 期</td>
					<td width="15%" border="1"></td>
				</tr>
				<tr>
					<td width="100%" align="left" border="1">備註:<br>分發單位簽名日期:</td>
				</tr>
			</table>
			EOD;
			$tcpdf->writeHTML($pdf_table, true, false, false, false, '');
			$result = $tcpdf->Output('complaint.pdf', 'I');  /* export file */
			header('Pragma: no-cache');
			header('Expires: 0');
			header('Content-Disposition: attachment;filename="' . $filename . '";');
			header('Content-Type: application/csv; charset=UTF-8');
			return $result;
		} else {
			$filename = "客戶抱怨處理單" . date("Y-m-d-H-i-s") . ".csv";
			header('Pragma: no-cache');
			header('Expires: 0');
			header('Content-Disposition: attachment;filename="' . $filename . '";');
			header('Content-Type: application/csv; charset=UTF-8');
			$csv_header = ['龍畿企業股份有限公司'];
			echo "\xEF\xBB\xBF";
			echo join(',', $csv_header) . "\n";
			echo "客戶抱怨處理單\n";
			echo "客戶代號," . "=\"{$fetch['code']}\"" . ",填單日期," . "{$date}" . ",責任單位簽名日期," . "\n";
			echo "品號," . "" . ",出貨日期," . "\n";
			echo "訂單號碼," . "" . ",出貨數量," . "\n";
			echo "圖號," . "=\"{$fetch['file_id']}\"" . ",不良數量," . "\n";
			echo "客戶抱怨內容," . "{$fetch['content']}" . "," . "," . ",責任單位簽名日期," . "\n";
			echo "現況掌握," . "," . "現有庫存" . "," . ",責任單位簽名日期," . "\n";
			echo "," . "," . "現有訂單" . "\n";
			echo "問題分析與原因追查," . "" . "," . "," . ",責任單位簽名日期," . "\n";
			echo "改善對策(庫存/現有訂單/未來訂單)," . "" . "," . "," . ",責任單位簽名日期," . "\n";
			echo "內部追蹤," . "" . "," . "," . ",責任單位簽名日期," . "\n";
			echo "外部追蹤," . "" . "," . "," . ",責任單位簽名日期," . "\n";
			echo "備份," . "\n";
			echo "分發單位簽名日期," . "\n";
		}
	}
	function getDeliveryMeetContentFileFrame($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$phasegallerycontroller = new PhaseGalleryController($container->db);
		$params = $request->getParsedBody();
		$delivery_meet_content_position = $crm->readDeliveryMeetContentPosition($params);
		$result = $phasegallerycontroller->getPointList($delivery_meet_content_position);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}
	function postDeliveryMeetContentFileFrame($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$params = $request->getParsedBody();
		$position_id = $crm->createtPosition($params)['position_id'];
		foreach ($params['point_list'] as $key => $value) {
			$crm->createtPoint($position_id, $key, $value);
		}
		$result = $crm->createDeliveryMeetContentPosition($params['delivery_meet_content_file_id'], $position_id);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}
	public function uploadFilePaint($request, $response, $args)
	{
		$data = $request->getParams();
		$data['files'] = $request->getUploadedFiles();
		$crm = new CRM($this->container->db);
		$phasegallery = new PhaseGallery($this->container->db);
		$file = $phasegallery->uploadFile($data);
		unset($data['files']);
		$file['user_id'] = 0;
		$data['file_id'] = $phasegallery->insertFile($file);
		$result = $crm->postFilePaint($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}
	function deleteFilePaint($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$params = $request->getParsedBody();
		$result = $crm->deleteFilePaint($params);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}
	public function getFilePaint($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getQueryParams();
		$result = $crm->getFilePaint($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}
	public function getComplaintContent($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$data = $request->getQueryParams();
		$result = $crm->getComplaintContent($data);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}
	public function postComplaintContent($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$params = $request->getParsedBody();
		$result = $crm->postComplaintContent($params);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}
	public function getImage($request, $response, $args)
	{
		$crm = new CRM($this->container->db);
		$phasegallery = new PhaseGallery($this->container->db);
		$params = $request->getQueryParams();
		$origin_data = [];
		$origin_data['id'] = $params['attach_file_id'];
		$paint = [];
		foreach ($params['file_id'] as $value) {
			$file_id['file_id'] = $value;
			$file = $phasegallery->getImage($file_id);
			if ($this->compressImage($file, $file, 100) != null) {
				$source = $this->compressImage($file, $file, 100);
				array_push($paint, $this->compressImage($file, $file, 100));
			}
		}
		$origin_file = $crm->getImage($origin_data);
		$newImage = $this->compressImage($origin_file, $origin_file, 100);
		$newImage = $this->PIPHP_ImageResize($newImage, imagesx($source), imagesy($source));
		ob_clean();
		$width = imagesx($source);
		$height = imagesy($source);
		foreach ($paint as $paint_) {
			imagecopy($newImage, $paint_, 0, 0, 0, 0, $width, $height);
		}
		imagejpeg($newImage);
		foreach ($paint as $paint_) {
			imagejpeg($paint_);
		}
		$response = $response->withHeader('Content-Description', 'File Transfer')
			->withHeader('Content-Type', 'application/octet-stream')
			->withHeader('Content-Disposition', 'attachment;filename="' . 'phasegallery.jpg' . '"')
			->withHeader('Expires', '0')
			->withHeader('Cache-Control', 'must-revalidate')
			->withHeader('Pragma', 'public');
		return $response;
	}
	public function postNewComplaint($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$params = $request->getParsedBody();
		$result = $crm->postNewComplaint($params);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}
	function PIPHP_ImageResize($image, $w, $h)
	{
		$oldw = imagesx($image);
		$oldh = imagesy($image);
		$temp = imagecreatetruecolor($w, $h);
		imagecopyresampled($temp, $image, 0, 0, 0, 0, $w, $h, $oldw, $oldh);
		return $temp;
	}
	function postComplaintForm($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$params = $request->getParsedBody();
		$result = $crm->insertComplaintForm($params);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}
	public function getComplaintForm($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$params = $request->getQueryParams();
		$result = $crm->readComplaintForm($params);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}
	public function deleteComplaintForm($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$body = $request->getParsedBody();
		$result = $crm->deleteComplaintForm($body);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}
	public function getComplaintReportWithData($request, $response, $args)
	{
		$params = $request->getQueryParams();
		$crm = new CRM($this->container->db);
		$fixed = $crm->get_complaint_report($params);  /* fixed data from meet.meet */
		$edited = $crm->readComplaintForm($params);  /* editable data from report form */
		$fixed['meet_date'] !== NULL ? $meet_date = date('Y-m-d', strtotime($fixed['meet_date'])) : $meet_date = '';  /* set meet_date with timestamp */
		$edited['shipping_date'] !== NULL ? $shipping_date = date('Y-m-d', strtotime($edited['shipping_date'])) : $shipping_date = '';  /* set shipping_date with timestamp */
		$filename = "客戶抱怨處理單" . date("Y-m-d-H-i-s") . ".pdf";
		$tcpdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$tcpdf->setPrintHeader(false);  /* hide default header / footer */
		$tcpdf->setPrintFooter(false);
		$tcpdf->AddPage();  /* new page */
		$tcpdf->SetFont('msungstdlight', 'B', 20);  /* title */
		$pdf_title = <<<EOD
			龍畿企業股份有限公司\n客戶抱怨處理單
			EOD;
		$tcpdf->Write(0, $pdf_title, '', 0, 'C', true, 0, false, false, 0);
		$tcpdf->Ln();
		$tcpdf->SetFont('msungstdlight', '', 12);  /* table */
		$pdf_table = <<<EOD
			<table cellpadding="2" align="center" vertical-align="middle">
				<tr>
					<td width="10%" border="1">客戶代號</td>
					<td width="40%" border="1">{$fixed['complaint_customer_id']}</td>
					<td width="10%" border="1">填單日期</td>
					<td width="15%" border="1">{$meet_date}</td>
					<td width="10%" rowspan="4" border="1">責 簽<br>任 名<br>單 日<br>位 期</td>
					<td width="15%" rowspan="4" border="1"></td>
				</tr>
				<tr>
					<td width="10%" border="1">品&emsp;&emsp;號</td>
					<td width="40%" border="1">{$edited['number']}</td>
					<td width="10%" border="1">出貨日期</td>
					<td width="15%" border="1">{$shipping_date}</td>
				</tr>
				<tr>
					<td width="10%" border="1">訂單號碼</td>
					<td width="40%" border="1">{$edited['order_num']}</td>
					<td width="10%" border="1">出貨數量</td>
					<td width="15%" border="1">{$edited['shipping_count']}</td>
				</tr>
				<tr>
					<td width="10%" border="1">圖&emsp;&emsp;號</td>
					<td width="40%" border="1">{$fixed['file_id']}</td>
					<td width="10%" border="1">不良數量</td>
					<td width="15%" border="1">{$edited['bad_count']}</td>
				</tr>
				<tr>
					<td width="75%" colspan="3" align="left" border="1">客戶抱怨內容:<br>{$fixed['content']}<br><br><br></td>
					<td width="10%" border="1">責 簽<br>任 名<br>單 日<br>位 期</td>
					<td width="15%" border="1"></td>
				</tr>
				<tr>
					<td width="50%" colspan="2" rowspan="2" align="left" style="border-left-width: 1px solid">現狀掌握:</td>
					<td width="10%" border="1">現有庫存</td>
					<td width="15%" border="1">{$edited['current_count']}</td>
					<td width="10%" rowspan="3" border="1">責 簽<br>任 名<br>單 日<br>位 期</td>
					<td width="15%" rowspan="3" border="1"></td>
				</tr>
				<tr>
					<td width="10%" border="1">現有訂單</td>
					<td width="15%" border="1">{$edited['current_order']}</td>
				</tr>
				<tr>
					<td width="75%" style="border-left-width: 1px solid; text-align: left">{$edited['current_situation']}<br></td>
				</tr>
				<tr>
					<td width="75%" colspan="3" align="left" border="1">問題分析與原因追查:<br>{$edited['problem']}<br><br><br></td>
					<td width="10%" border="1">責 簽<br>任 名<br>單 日<br>位 期</td>
					<td width="15%" border="1"></td>
				</tr>
				<tr>
					<td width="75%" colspan="3" align="left" border="1">改善對策:(庫存,現有訂單,未來訂單)<br>{$edited['improve_strategy']}<br><br><br></td>
					<td width="10%" border="1">責 簽<br>任 名<br>單 日<br>位 期</td>
					<td width="15%" border="1"></td>
				</tr>
				<tr>
					<td width="75%" colspan="3" align="left" border="1">內部追蹤:<br>{$edited['internal_tracking']}<br><br><br></td>
					<td width="10%" border="1">責 簽<br>任 名<br>單 日<br>位 期</td>
					<td width="15%" border="1"></td>
				</tr>
				<tr>
					<td width="75%" colspan="3" align="left" border="1">外部追蹤:<br>{$edited['external_tracking']}<br><br><br></td>
					<td width="10%" border="1">責 簽<br>任 名<br>單 日<br>位 期</td>
					<td width="15%" border="1"></td>
				</tr>
				<tr>
					<td width="100%" align="left" border="1">備註:{$edited['note']}<br>分發單位簽名日期:</td>
				</tr>
			</table>
		EOD;
		$tcpdf->writeHTML($pdf_table, true, false, false, false, '');
		$result = $tcpdf->Output('complaint.pdf', 'I');  /* export file */
		header('Pragma: no-cache');
		header('Expires: 0');
		header('Content-Disposition: attachment;filename="' . $filename . '";');
		header('Content-Type: application/csv; charset=UTF-8');
		return $result;
	}
	public function deleteAttachFile($request, $response, $args)
	{
		global $container;
		$crm = new CRM($container->db);
		$body = $request->getParsedBody();
		$result = $crm->deleteAttachFile($body);
		$response = $response->withHeader('Content-type', 'application/json');
		$response = $response->withJson($result);
		return $response;
	}
}
