<?php

use \Psr\Container\ContainerInterface;
use \Slim\Views\PhpRenderer;
use Slim\Http\UploadedFile;
use Google\Cloud\Translate\V2\TranslateClient;
use Stichoza\GoogleTranslate\GoogleTranslate;

class VideoController
{
    protected $container;
    public function __construct()
    {
        global $container;
        $this->container = $container;
    }

    //Get the video_id when uploading file if the video_id already exists, if not, insert into null value and get a new video_id.
    public function render($request, $response, $args)
    {
        $data = $request->getQueryParams();
        if (!isset($data['video_id']))      //If there doesn't exist a video_id.
        {
            $video = new Video($this->container->db);
            $data['user_id'] = $_SESSION['id'];
            $video_id = $video->insert_video($data);

            //Redirect the page whose url takes video_id with it.
            $response = $response->withRedirect("/develop/video?video_id={$video_id}", 301);
            return $response;
        }

        //If there exist the video_id.
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/login.html', []); //Render the page.
    }
    public function renderReactJS($request, $response, $args)
    {
        $data = $request->getQueryParams();
        //If there exist the video_id.
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/video/index.html', []); //Render the page.
    }

    //front-end example
    // public function render_first_stuff($request, $response, $args)
    // {
    //     $renderer = new PhpRenderer($this->container->view);
    //     return $renderer->render($response, '/video/first_stuff.php', []);
    // }

    public function getIndustryPicture($request, $response, $args){
        $file = $this->container->upload_directory . DIRECTORY_SEPARATOR . '1a0b96d6320d9ced.jpg';
        if (!file_exists($file)) {
            $response = $response->withStatus(500);
            return $response;
        }
        $response = $response->withHeader('Content-Description', 'File Transfer')
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Disposition', 'attachment;filename="' . '1a0b96d6320d9ced.jpg' . '"')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate')
            ->withHeader('Pragma', 'public')
            ->withHeader('Content-Length', filesize($file));
        ob_clean();
        ob_end_flush();
        $source = imagecreatefromjpeg($file);
        // Load

        // Output
        imagejpeg($source);
        // $handle = fopen($file, "rb");
        // while (!feof($handle)) {
        //     echo fread($handle, 1000);
        // }
        return $response;
    }

    public function getCustomerPicture($request, $response, $args){
        $file = $this->container->upload_directory . DIRECTORY_SEPARATOR . '1a0b96d6320d9ced.jpg';
        if (!file_exists($file)) {
            $response = $response->withStatus(500);
            return $response;
        }
        $response = $response->withHeader('Content-Description', 'File Transfer')
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Disposition', 'attachment;filename="' . '1a0b96d6320d9ced.jpg' . '"')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate')
            ->withHeader('Pragma', 'public')
            ->withHeader('Content-Length', filesize($file));
        ob_clean();
        ob_end_flush();
        $source = imagecreatefromjpeg($file);
        // Load

        // Output
        imagejpeg($source);
        // $handle = fopen($file, "rb");
        // while (!feof($handle)) {
        //     echo fread($handle, 1000);
        // }
        return $response;
    }

    function schedule_arrangement($request, $response, $args)
    {
        putenv('TMPDIR='.$this->container->upload_directory);
        $video_modal = new Video($this->container->db);
        $video_queue = $video_modal->get_video_in_queue();
        if(empty($video_queue)){
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson("There is no data anymore.");
            return $response;
        }
        $filename = "";
        foreach($video_queue as $key => $value){
            $filename = $value["video_file_name"];
        }
        // $response = $response->withHeader('Content-type', 'application/json');
        // $response = $response->withJson($video_queue);
        // return $response;

        $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
        $new_filename = sprintf('%s.%0.8s', $basename, 'mp4');
        $ffmpeg = FFMpeg\FFMpeg::create([
            'ffmpeg.binaries'=>'/usr/local/bin/ffmpeg',
            'ffprobe.binaries' => '/usr/local/bin/ffprobe'
        ]);
        try{
            $video = $ffmpeg->open($this->container->upload_directory . DIRECTORY_SEPARATOR . $filename);
        }
        catch(\Exception $e){
            $video = "";
        }
        if($video !== ""){
            $format = new FFMpeg\Format\Video\X264();
            $format->setAudioCodec("libmp3lame");
            // copy, aac, libvo_aacenc, libfaac, libmp3lame, libfdk_aac
            $video->save($format, $this->container->upload_directory . DIRECTORY_SEPARATOR . $new_filename);
            unlink($this->container->upload_directory . DIRECTORY_SEPARATOR . $filename);

            return $this->schedual_queue($request, $response, $new_filename, $filename);
        }
        else{
            $new_filename = "";
            return $this->schedual_queue($request, $response, $new_filename, $filename);
        }
    }

    function schedual_queue($request, $response, $new_filename, $filename)
    {
        $video_modal = new Video($this->container->db);
        $update_video_file_name = $video_modal->update_video_file_name($new_filename, $filename);
        foreach($update_video_file_name["video_id"] as $key => $value){
            $video_information = $video_modal->get_video_file_name($value);
        }
        $thumbnail_filename = "";
        try{
            $thumbnail_filename = $this->create_thumbnail($video_information);
        }
        catch(\Exception $e){
            $thumbnail_filename = '01c7290b3cef4526.jpg';
        }
        foreach($update_video_file_name["video_id"] as $key => $value){
            $update_video_thumbnail_file_name = $video_modal->update_video_thumbnail_file_name($thumbnail_filename, $value["id"]);
        }
        $delete_schedual_queue = $video_modal->delete_schedual_queue($filename);
        if($delete_schedual_queue["status"] === "failed"){
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson($delete_schedual_queue);
            $response = $response->withStatus(500);
            return $response;
        }
        else{
            $delete_schedual_queue["new_file_name"] = $new_filename;
            $delete_schedual_queue["thumbnail_file_name"] = $thumbnail_filename;
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($delete_schedual_queue);
        return $response;
    }

    //Move the uploaded file from register to the correct directory.
    function moveUploadedFile($directory, UploadedFile $uploadedFile)
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
        $filename = sprintf('%s.%0.8s', $basename, $extension);
        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

        // $position = strpos($filename, ".mp4");
        if(!(strpos($filename, ".mp4") || strpos($filename, ".flv"))){
            $file = $this->container->upload_directory . DIRECTORY_SEPARATOR . $filename;
            if(filesize($file) > 2000000){
                return $filename;
            }

            putenv('TMPDIR='.$this->container->upload_directory);

            // $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
            $new_filename = sprintf('%s.%0.8s', $basename, 'mp4');
            $ffmpeg = FFMpeg\FFMpeg::create([
                'ffmpeg.binaries'=>'/usr/local/bin/ffmpeg',
                'ffprobe.binaries' => '/usr/local/bin/ffprobe'
            ]);
            $video = $ffmpeg->open($this->container->upload_directory . DIRECTORY_SEPARATOR . $filename);
            
            $format = new FFMpeg\Format\Video\X264();
            $format->setAudioCodec("libmp3lame");
            // copy, aac, libvo_aacenc, libfaac, libmp3lame, libfdk_aac
            $video->save($format, $this->container->upload_directory . DIRECTORY_SEPARATOR . $new_filename);
            unlink($this->container->upload_directory . DIRECTORY_SEPARATOR . $filename);
            return $new_filename;
        }
        return $filename;
    }
    //Preview the uploaded video.
    public function preview_video_or_file($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $directory = $this->container->upload_directory;  //Get the directory of the space which stores pictures, video, etc.
        $uploadedFiles = $request->getUploadedFiles();    //Get the temporary file that stores in php's register.
        $uploadedFile = $uploadedFiles['inputFile'];
  
        //Check the file is no error.
        if ($uploadedFile->getError() === UPLOAD_ERR_OK) 
        {                
            //Move the uploaded file from register to the correct directory.
            $filename = $this->moveUploadedFile($directory, $uploadedFile);
            $video = new Video($this->container->db);
            $data['clientFileName'] = $uploadedFile->getClientFilename();
            $data['fileName'] = $filename;

            if(!(strpos($filename, ".mp4") || strpos($filename, ".flv"))){
                // $queue = $video->video_schedual_queue($filename);
                $result = [
                    "file_id" => null,
                    "status" => "success",
                    "clientFileName" => $data['clientFileName'],
                    "fileName" => "1df1ac5336676aa5.mp4",
                    "default_picture_location" => "/develop/video/default_picture",
                    "default_picture" => "video_default.png",
                    "file_name_in_queue" => $filename
                ];

                $response = $response->withHeader('Content-type', 'application/json');
                $response = $response->withJson($result);
                return $response;
            }
            
            //Store the file name from client, this function is to record the file name while the user wants to download the file he/she has uploaded.
            $result = $video->preview_video_or_file($data);
            $result['clientFileName'] = $data['clientFileName'];
            $result['fileName'] = $filename;
            // $file = $this->container->upload_directory . DIRECTORY_SEPARATOR . $filename;
            // if(filesize($file) > 2000000){
            //     $result["default_video"] = "1df1ac5336676aa5.mp4";
            //     $result["default_picture_location"] = "/develop/video/default_picture";
            //     $result["default_picture"] = "video_default.png";
            // }

            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson($result);
            return $response;
        }
        else{
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson($uploadedFiles);
            $response = $response->withStatus(500);
            return $response;
        }
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
    function get_video_default_picture($request, $response, $args)
    {
        $file = $this->container->upload_directory . DIRECTORY_SEPARATOR . 'video_default.png';
        if (!file_exists($file)) {
            $response = $response->withStatus(500);
            return $response;
        }
        $source = $this->compressImage($file, $file, 100);
        // imagejpeg($source);
        $response = $response->withHeader('Content-Description', 'File Transfer')
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Disposition', 'attachment;filename="' . 'video_default.png' . '"')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate')
            ->withHeader('Pragma', 'public')
            ->withHeader('Content-Length', filesize($file));
        ob_clean();
        ob_end_flush();
        // $source = imagecreatefromjpeg($file);
        // Load

        // Output
        imagejpeg($source);
        return $response;
    }

    //Get preview the uploaded video.
    public function get_preview_video_or_file($request, $response, $args)
    {
        $data = $args;
        $video = new Video($this->container->db);
        $result = $video->get_preview_video_or_file($data);
        // $response = $response->withHeader('Content-type', 'application/json');
        // $response = $response->withJson($result);
        // return $response;
        foreach($result as $key => $value){
            $stream = $this->container['upload_directory'] . "/" . $value['file_name'];
            $video = new VideoStream($stream);
            $video->start();
        }
        // foreach($result as $key => $value){
        //     $stream = $this->container['upload_directory'] . "/" . $value['file_name'];
        //     $response = $this->rangeDownload($stream);
        //     return $response;
        // }
    }

    public function update_video_after_foreign_key($request, $response, $data)
    {
        $video = new Video($this->container->db);
        foreach($data as $key => $value){
            // $data[$key]['video_user_id'] = 7;
            $data[$key]['user_id'] = $_SESSION['id'];
            if(array_key_exists('file_id', $value)){
                $file_name = $video->get_preview_video_or_file($value);
                foreach($file_name as $file_key => $file_value){
                    $data[$key]['video_file_name'] = $file_value['file_name'];
                }
            }
        }
        $result = $video->update_video($data);
        $delete = $video->delete_upload_file($data);
        if($delete['status'] == "delete failed"){
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson($delete);
            $response = $response->withStatus(500);
            return $response;
        }
        foreach($result as $key => $value){
            if($value['status'] != "failed")
            {
                foreach($data as $key => $value)
                {
                    $upload = [];
                    $video_information = $video->get_video_file_name($value);
                    $thumbnail_filename = "";
                    try{
                        $thumbnail_filename = $this->create_thumbnail($video_information);
                    }
                    catch(\Exception $e){
                        $thumbnail_filename = '01c7290b3cef4526.jpg';
                    }
                    $upload['thumbnail_filename'] = $thumbnail_filename;
                    $upload['video_id'] = $value['video_id'];
                    $result = $video->upload_video_thumbnail($upload);
                }
            }
        }
        return $result;
    }

    //Get youtube video title.
    public function get_youtube_name($request, $response, $args)
    {
        // $data = $request->getParsedBody();
        $data = $request->getQueryParams();
        
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($data["video_file_name"]);
        $html_tag = $doc->getElementsByTagName("iframe");
        $tag_value = $html_tag[0]->getAttribute('src');
        $youtube_id = substr($tag_value, 30, strlen($tag_value));
        
        // $title = "https://noembed.com/embed?url=https://www.youtube.com/watch?v=" . $youtube_id;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://noembed.com/embed?url=https://www.youtube.com/watch?v=" . $youtube_id);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $result = curl_exec($ch);
        $result = json_decode($result, true);

        $youtube_title = [];
        $youtube_title["video_name"] = $result["title"];
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($youtube_title);
        return $response;
    }
    public function insert_video($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $video = new Video($this->container->db);
        foreach($data as $key => $value){
            $data[$key]['video_user_id'] = $_SESSION['id'];
            $data[$key]['update_user_id'] = $_SESSION['id'];
            // $data[$key]['video_user_id'] = 7;
            // $data[$key]['update_user_id'] = 7;
            if(array_key_exists('file_id', $value)){
                if($value["file_id"] !== null){
                    $file_name = $video->get_preview_video_or_file($value);
                    foreach($file_name as $file_key => $file_value){
                        $data[$key]['video_file_name'] = $file_value['file_name'];
                    }
                }
                else{
                    $data[$key]['video_file_name'] = $value["file_name_in_queue"];
                    $data[$key]['video_thumbnail_file_name'] = $value["default_picture"];
                }
            }
            if(array_key_exists('video_file_name', $value)){
                if(substr($value["video_file_name"], 0, 7) === "<iframe"){
                    $doc = new DOMDocument();
                    libxml_use_internal_errors(true);
                    $doc->loadHTML($value["video_file_name"]);
                    $html_tag = $doc->getElementsByTagName("iframe");
                    $tag_value = $html_tag[0]->getAttribute('src');
                    
                    $youtube_id = substr($tag_value, 30, strlen($tag_value));
                    $data[$key]["video_thumbnail_file_name"] = "http://img.youtube.com/vi/{$youtube_id}/mqdefault.jpg";
                    // ltrim($value["video_file_name"], substr($value["video_file_name"], 0, stripos($value["video_file_name"], "watch?v=") + 8));
                }
            }
        }
        // $response = $response->withHeader('Content-type', 'application/json');
        // $response = $response->withJson($data);
        // return $response;
        $video_id = $video->insert_video($data);
        $delete = $video->delete_upload_file($data);
        if($delete['status'] == "delete failed"){
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson($delete);
            $response = $response->withStatus(500);
            return $response;
        }
        foreach($video_id as $key => $value){
            $upload = [];
            $video_information = $video->get_video_file_name($value);
            foreach($video_information as $key => $value){
                if(substr($value["video_file_name"], 0, 7) !== "<iframe"){
                    if($value["video_thumbnail_file_name"] !== "video_default.png"){
                        $thumbnail_filename = "";
                        try{
                            $thumbnail_filename = $this->create_thumbnail($video_information);
                        }
                        catch(\Exception $e){
                            $thumbnail_filename = '01c7290b3cef4526.jpg';
                        }
                        $upload['thumbnail_filename'] = $thumbnail_filename;
                        $upload['video_id'] = $value['id'];
                        $result = $video->upload_video_thumbnail($upload);
                    }
                    else{
                        $result = $video->video_schedual_queue($value["video_file_name"], $value["id"]);
                    }
                }
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($delete);
        return $response;
    }
    
    //Create thumbnail when the user successfully upload a video.
    public function create_thumbnail($video_information)
    {
        $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
        $filename = sprintf('%s.%0.8s', $basename, 'jpg');
        foreach($video_information as $key => $value){
            $ffmpeg = FFMpeg\FFMpeg::create([
                'ffmpeg.binaries'=>'/usr/local/bin/ffmpeg',
                'ffprobe.binaries' => '/usr/local/bin/ffprobe'
            ]);
            // $ffprobe = FFMpeg\FFProbe::create();

            $video = $this->container->upload_directory. DIRECTORY_SEPARATOR .$value['video_file_name'];
            // $video_dimensions = $ffprobe
            //     ->streams($video)   
            //     ->videos()
            //     ->first()
            //     ->getDimensions();
            // $width = $video_dimensions->getWidth();
            // $height = $video_dimensions->getHeight();
            // var_dump($width, $height);

            $video = new FFMpeg\Media\Video($video, $ffmpeg->getFFMpegDriver(), $ffmpeg->getFFProbe());
            $video
                ->filters()
                // ->resize(new FFMpeg\Coordinate\Dimension(320, 240), new FFMpeg\Filters\Video\ResizeFilter::RESIZEMODE_FIT, true)
                ->resize(new FFMpeg\Coordinate\Dimension(320, 240))
                // ->resize(new FFMpeg\Coordinate\Dimension($width, $height))
                ->synchronize();

            $video
                ->frame(FFMpeg\Coordinate\TimeCode::fromSeconds(10))
                ->save($this->container->upload_directory.DIRECTORY_SEPARATOR."{$filename}");
        }
        return $filename;
    }
    
    //Upload or update the video and get a new video_id.
    public function upload_video($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $directory = $this->container->upload_directory;  //Get the directory of the space which stores pictures, video, etc.
        $uploadedFiles = $request->getUploadedFiles();    //Get the temporary file that stores in php's register.
        $uploadedFile = $uploadedFiles['inputFile'];
        
        //Check the file is no error.
        if ($uploadedFile->getError() === UPLOAD_ERR_OK) 
        {                          
            //Move the uploaded file from register to the correct directory.
            $filename = $this->moveUploadedFile($directory, $uploadedFile);
            $video = new Video($this->container->db);

            //Store the file name from client, this function is to record the file name while the user wants to download the file he/she has uploaded.
            // $data['clientFileName'] = $uploadedFile->getClientFilename();
            $data['fileName'] = $filename;
            $data['user_id'] = $_SESSION['id'];
            $result = $video->upload_video($data);
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson([
                "file_id" => $result,
            ]);
            return $response;
        }
        else{
            $response = $response->withStatus(500);
            return $response;
        }
    }

    //Update the videos with the video_ids.
    public function update_video($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $video = new Video($this->container->db);
        foreach($data as $key => $value){
            // $data[$key]['update_user_id'] = 8;
            $data[$key]['update_user_id'] = $_SESSION['id'];
            if(array_key_exists('file_id', $value)){
                if($value["file_id"] !== null){
                    $file_name = $video->get_preview_video_or_file($value);
                    foreach($file_name as $file_key => $file_value){
                        $data[$key]['video_file_name'] = $file_value['file_name'];
                    }
                }
                else{
                    $data[$key]['video_file_name'] = $value["file_name_in_queue"];
                    $data[$key]['video_thumbnail_file_name'] = $value["default_picture"];
                }
            }
            if(array_key_exists('video_file_name', $value)){
                if(substr($value["video_file_name"], 0, 7) === "<iframe"){
                    $doc = new DOMDocument();
                    libxml_use_internal_errors(true);
                    $doc->loadHTML($value["video_file_name"]);
                    $html_tag = $doc->getElementsByTagName("iframe");
                    $tag_value = $html_tag[0]->getAttribute('src');
                    
                    $youtube_id = substr($tag_value, 30, strlen($tag_value));
                    $data[$key]["video_thumbnail_file_name"] = "http://img.youtube.com/vi/{$youtube_id}/mqdefault.jpg";
                    // ltrim($value["video_file_name"], substr($value["video_file_name"], 0, stripos($value["video_file_name"], "watch?v=") + 8));
                }
            }
        }
        $result = $video->update_video($data);
        $delete = $video->delete_upload_file($data);
        if($delete['status'] == "delete failed"){
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson($delete);
            $response = $response->withStatus(500);
            return $response;
        }
        foreach($result as $key => $value){
            if(!array_key_exists('status', $value))
                foreach($data as $key => $data_value)
                {
                    if(array_key_exists('file_id', $data_value)){
                    {
                        $upload = [];
                        $data_value["id"] = $data_value["video_id"];
                        $video_information = $video->get_video_file_name($data_value);
                        foreach($video_information as $key => $value){
                            if(substr($value["video_file_name"], 0, 7) !== "<iframe"){
                                if($value["video_thumbnail_file_name"] !== "video_default.png"){
                                    $thumbnail_filename = "";
                                    try{
                                        $thumbnail_filename = $this->create_thumbnail($video_information);
                                    }
                                    catch(\Exception $e){
                                        $thumbnail_filename = '01c7290b3cef4526.jpg';
                                    }
                                    $upload['thumbnail_filename'] = $thumbnail_filename;
                                    $upload['video_id'] = $value['id'];
                                    $result = $video->upload_video_thumbnail($upload);
                                }
                                else{
                                    $result = $video->video_schedual_queue($value["video_file_name"], $value["id"]);
                                }
                            }
                        }
                    }
                }
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    //Delete the specific video with the video_id.
    public function delete_video($request, $response, $args)
    {
        // '01c7290b3cef4526.jpg'
        $data = $request->getParsedBody();
        $video = new Video($this->container->db);
        $file_name = $video->get_multiple_video_file_name($data);
        foreach($file_name as $key => $value){
            if (file_exists($this->container->upload_directory . DIRECTORY_SEPARATOR . $value["video_file_name"])) {
                unlink($this->container->upload_directory . DIRECTORY_SEPARATOR . $value["video_file_name"]);
            }
            if($value["video_thumbnail_file_name"] !== "01c7290b3cef4526.jpg"){
                if($value["video_thumbnail_file_name"] !== "video_default.png"){
                    if (file_exists($this->container->upload_directory . DIRECTORY_SEPARATOR . $value["video_thumbnail_file_name"])) {
                        unlink($this->container->upload_directory . DIRECTORY_SEPARATOR . $value["video_thumbnail_file_name"]);
                    }
                }
            }
        }
        $result = $video->delete_video($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    //Get the information of the specific video with the video_id.
    public function get_video_information($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $video = new Video($this->container->db);
        $result = $video->get_video_information($data);
        foreach($result as $key => $value){
            if(substr($value["video_file_name"], 0, 7) === "<iframe"){
                $result[$key]["youtube_url"] = $value["video_file_name"];
                $result[$key]["youtube_thumbnail"] = $value["video_thumbnail_file_name"];
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    //Get preview the uploaded video.
    public function get_preview_specific_video_or_file($request, $response, $args)
    {
        $data = $args;
        $stream = $this->container->upload_directory . "/" . $data['file_name'];
        $video = new VideoStream($stream);
        $video->start();
        // $response = $this->rangeDownload($stream);
        // return $response;
    }

    // public function get_specific_video($request, $response, $args)
    // {
    //     $data = $request->getQueryParams();
    //     $video = new Video($this->container->db);
    //     $file_name = $video->get_specific_video_file_name($data);
        
    //     $file = $this->container->upload_directory . DIRECTORY_SEPARATOR . $file_name['video_file_name'];
    //     $response = $response->withHeader('Content-Description', 'File Transfer')
    //         // ->withHeader('Content-Type', 'application/octet-stream')
    //         ->withHeader('Content-Type', 'video/mp4')
    //         ->withHeader('Content-Disposition', 'attachment;filename="' . $file_name['video_file_name'] . '"')
    //         ->withHeader('Expires', '0')
    //         ->withHeader('Cache-Control', 'must-revalidate')
    //         ->withHeader('Pragma', 'public')
    //         ->withHeader('Content-Length', filesize($file));
    //     ob_clean();
    //     ob_end_flush();
    //     $handle = fopen($file, "rb");
    //     while (!feof($handle)) {
    //         echo fread($handle, 1000);
    //     }
    //     return $response;
    // }

    //Upload the tape or mp3 description of the specific video.
    public function upload_description_tape($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $video = new Video($this->container->db);
        if(array_key_exists('file_id', $data)){
            $file_name = $video->get_preview_video_or_file($data);
            foreach($file_name as $file_key => $file_value){
                $data['tape_file_name'] = $file_value['file_name'];
            }
        }
        // $data['description_user_id'] = 8;
        $data['description_user_id'] = $_SESSION['id'];
        $result = $video->upload_description_tape($data);
        $delete = $video->delete_upload_file_for_tape($data);
        if($delete['status'] == "delete failed"){
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson($delete);
            $response = $response->withStatus(500);
            return $delete;
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    //Upload the text description description of the specific video.
    public function upload_description($request, $response, $args)
    {
        $data = $request->getParsedBody();
        foreach($data as $key => $value){
            // $data[$key]['description_user_id'] = 7;
            $data[$key]['description_user_id'] = $_SESSION['id'];
        }
        // $data['user_id'] = $_SESSION['id'];
        $video = new Video($this->container->db);
        $result = $video->upload_description($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    //Update the tape or mp3 description of the specific video.
    public function update_description_tape($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $video = new Video($this->container->db);
        if(array_key_exists('file_id', $data)){
            $file_name = $video->get_preview_video_or_file($data);
            foreach($file_name as $file_key => $file_value){
                $data['tape_file_name'] = $file_value['file_name'];
            }
        }
        // $data['description_user_id'] = 8;
        $data['description_user_id'] = $_SESSION['id'];
        $result = $video->update_description_tape($data);
        $delete = $video->delete_upload_file_for_tape($data);
        if($delete['status'] == "delete failed"){
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson($delete);
            $response = $response->withStatus(500);
            return $delete;
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    //Update the text description description of the specific video.
    public function update_description($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $video = new Video($this->container->db);
        // $data['description_user_id'] = $_SESSION['id'];
        foreach($data as $key => $value){
            // $data[$key]['description_user_id'] = 8;
            $data[$key]['description_user_id'] = $_SESSION['id'];
        }
        $result = $video->update_description($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    //Delete the tape or mp3 description or the text description description of the specific video.
    public function delete_description($request, $response, $args)
    {
        $data = $request->getParsedBody();
        // $data['user_id'] = $_SESSION['id'];
        $video = new Video($this->container->db);
        $result = $video->delete_description($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        return $response;
    }

    //Get the information of the specific video, like translated language, video clip description, etc.
    public function get_specific_video_description($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $video = new Video($this->container->db);
        $description = $video->get_specific_video_description($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($description);
        return $response;
    }

    //Get the information of the specific video, like video clip description.
    //This function is separated from get_specific_video_information($request, $response, $args) because there still not have a perfect way for getting file and text at the same time.
    public function get_specific_video_tape_description($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $filename = $data['tape_file_name'];
        $video = $this->container['upload_directory'] . "/" . $filename;
        $response = $this->rangeDownload($video);

        // $file = $this->container->upload_directory . DIRECTORY_SEPARATOR . $data['tape_file_name'];
        // $response = $response->withHeader('Content-Description', 'File Transfer')
        //     ->withHeader('Content-Type', 'application/octet-stream')
        //     // ->withHeader('Content-Type', 'audio/mp3')
        //     ->withHeader('Content-Disposition', 'attachment;filename="' . $data['tape_file_name'] . '"')
        //     ->withHeader('Expires', '0')
        //     ->withHeader('Cache-Control', 'must-revalidate')
        //     ->withHeader('Pragma', 'public')
        //     ->withHeader('Content-Length', filesize($file));
        // ob_clean();
        // ob_end_flush();
        // $handle = fopen($file, "rb");
        // while (!feof($handle)) {
        //     echo fread($handle, 1000);
        // }
        return $response;
    }    

    //Download the video. 
    public function renderVideo($request, $response, $args)
    {
        $renderer = new PhpRenderer($this->container->view);
        return $renderer->render($response, '/video.html');
    }
    //Get the video from the upload directory for downloading to front-end.
    public function getVideo($request, $response, $args)
    {
        $filename = '40886a9160696dbb.mp4';
        $video = $this->container['upload_directory'] . "/" . $filename;
        $response = $this->rangeDownload($video);
        return $response;
    }
    //Download the video and output as streaming video.
    private function rangeDownload($file)
    {

        $fp = @fopen($file, 'rb');

        $size   = filesize($file); // File size
        $length = $size;           // Content length
        $start  = 0;               // Start byte
        $end    = $size - 1;       // End byte
        $contenttype = mime_content_type($file);
        // Now that we've gotten so far without errors we send the accept range header
        /* At the moment we only support single ranges.
	 * Multiple ranges requires some more work to ensure it works correctly
	 * and comply with the spesifications: http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
	 *
	 * Multirange support annouces itself with:
	 * header('Accept-Ranges: bytes');
	 *
	 * Multirange content must be sent with multipart/byteranges mediatype,
	 * (mediatype = mimetype)
	 * as well as a boundry header to indicate the various chunks of data.
	 */
        header("Accept-Ranges: 0-$length");
        // header('Accept-Ranges: bytes');
        // multipart/byteranges
        // http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
        if (isset($_SERVER['HTTP_RANGE'])) 
        {
            $c_start = $start;
            $c_end   = $end;
            // Extract the range string
            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
            // Make sure the client hasn't sent us a multibyte range
            if (strpos($range, ',') !== false) 
            {
                // (?) Shoud this be issued here, or should the first
                // range be used? Or should the header be ignored and
                // we output the whole content?
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                // (?) Echo some info to the client?
                exit;
            }
            // If the range starts with an '-' we start from the beginning
            // If not, we forward the file pointer
            // And make sure to get the end byte if spesified
            if ($range[0] == '-') 
            {
                // The n-number of the last bytes is requested
                $c_start = $size - substr($range, 1);
            } 
            else 
            {
                $range  = explode('-', $range);
                $c_start = $range[0];
                $c_end   = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
            }
            /* Check the range and make sure it's treated according to the specs.
		 * http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
		 */
            // End bytes can not be larger than $end.
            $c_end = ($c_end > $end) ? $end : $c_end;
            // Validate the requested range and return an error if it's not correct.
            if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) 
            {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                // (?) Echo some info to the client?
                exit;
            }
            $start  = $c_start;
            $end    = $c_end;
            $length = $end - $start + 1; // Calculate new content length
            fseek($fp, $start);
            header('HTTP/1.1 206 Partial Content');
        }
        // Notify the client the byte range we'll be outputting
        header("Content-Range: bytes $start-$end/$size");
        header("Content-Length: $length");
        header("Content-Type: $contenttype");

        // Start buffered download
        $buffer = 1024 * 8;
        while (!feof($fp) && ($p = ftell($fp)) <= $end) 
        {
            if ($p + $buffer > $end) 
            {
                // In case we're only outputtin a chunk, make sure we don't
                // read past the length
                $buffer = $end - $p + 1;
            }
            set_time_limit(0); // Reset time limit for big files
            echo fread($fp, $buffer);
            flush(); // Free up memory. Otherwise large files will trigger PHP's memory limit.
        }

        fclose($fp);
    }

    //Get the languages for the user so that they can choose which language they want or get the google translate codes for translation.
    // public function languages($request, $response, $args)
    // {
    //     $data = $request->getQueryParams();
    //     $video = new Video($this->container->db);
    //     $language = $video->languages($data);
    //     $response = $response->withHeader('Content-type', 'application/json');
    //     $response = $response->withJson($language);
    //     return $response;
    // }

    //Get video information for google translation.
    public function get_video_note($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $video = new Video($this->container->db);
        $language = $video->get_video_note($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($language);
        return $response;
    }

    //Get the translations for checking whether there is any translation for the specific video.
    public function translations($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $video = new Video($this->container->db);
        // $language_string = implode(",", array_values($data['language_id'])); //Implode $data['language_id'] to string, for $data['language_id'] sent from front-end is an array.
        // $data['language_chosen'] = $language_string;

        $language_counting = $video->translations($data); //Get the translations for checking whether there is any translation for the specific video.
        $data['language_counting'] = $language_counting;

        //Check whether there is any translation for the specific video.
        $language = $this->check_whether_there_is_translation($request, $response, $data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($language);
        return $response;
    }

    //Check whether there is any translation for the specific video.
    public function check_whether_there_is_translation($request, $response, $data)
    {
        $video = new Video($this->container->db);

        // $delete_translation = $video->delete_translation($data);

        //If one of or all the languages have not been translated, google translate them first, and then get the translations we want.
        if(!array_key_exists('counting', $data['language_counting']))
        {
            $data_for_translate = [];
            $note = $video->get_video_note($data);
            foreach($note as $key => $value){
                $data_for_translate['content_for_translate'] = $value['note'];
            }
            $data_for_translate['google_translate_code'] = [["chinese" => "zh-TW", "id" => 1], ["english" => "en", "id" => 2]];
            
            $translation = [];
            //Translate the selected language, and then insert into the video.note table one by one.
            foreach($data_for_translate['google_translate_code'] as $key => $value)
            {
                // return array_values($value);
                foreach($value as $language => $code)
                {   
                    if($language != "id"){
                        $data_for_translate['translate_code'] = $code;
                        $translation[$language] = $this->google_translate($request, $response, $data_for_translate); //Google translate the content the user want.
                    }
                }
                $data['translation'] = $translation;
                // array_merge($data['language']['id'], $value['id']);
                $data['language'] = $value['id'];
                // return $data;
                // $result = $video->delete_translation($data); //Delete the translation before insert new translation.
                $result = $video->insert_translation($data); //Insert the result of translation from google translate.
                if($result['status'] == "failed")
                {
                    $response = $response->withHeader('Content-type', 'application/json');
                    $response = $response->withJson($result['status']);
                    $response = $response->withStatus(500);
                    return $response;
                }
            }
            // return $result;
        }
        // $data = $request->getQueryParams();
        $result = $video->get_translations($data);
        return $result;
    }

    public function google_translate($request, $response, $data_for_translate)
    {
        $translation = new GoogleTranslate(); // Translates into English.
        $translation->setSource(); // Detect language automatically.
        $translation->setTarget($data_for_translate['translate_code']); // Translate.
        return $translation->translate($data_for_translate['content_for_translate']);

        // $data = $request->getQueryParams();
        
        // $curl = curl_init();
        
        // curl_setopt_array($curl, [
        //     CURLOPT_URL => "https://sa-translate.p.rapidapi.com/translate/text",
        //     CURLOPT_RETURNTRANSFER => true,
        //     CURLOPT_FOLLOWLOCATION => true,
        //     CURLOPT_ENCODING => "",
        //     CURLOPT_MAXREDIRS => 10,
        //     CURLOPT_TIMEOUT => 30,
        //     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //     CURLOPT_CUSTOMREQUEST => "POST",
        //     CURLOPT_POSTFIELDS => "{\r
        //         \"text\": \"{$data_for_translate['content_for_translate']}\",\r
        //         \"targetLanguage\": \"{$data_for_translate['translate_code']}\"
        //         \r
        //     }",
        //     CURLOPT_HTTPHEADER => [
        //         "content-type: application/json",
        //         "x-rapidapi-host: sa-translate.p.rapidapi.com",
        //         "x-rapidapi-key: 6ed911736cmshfa097c2bf258488p10be39jsn002b2f466d57"
        //     ],
        // ]);
        
        // $result = curl_exec($curl);
        // $err = curl_error($curl);

        // curl_close($curl);

        // $response = $response->withHeader('Content-type', 'application/json');
        
        // if ($err) 
        // {
        //     $response = $response->withJson($err);
        //     // return "cURL Error #:" . $err;
        // } 
        // else 
        // {
        //     $response = $response->withJson($result);
        // }
        // return $response;
    }

    //Get the pictures of the order of the specific video by MIL's Microsoft SQL server and Postgresql.
    public function get_SFCTA_TA_picture($request, $response, $args)
    {
        $data = $request->getQueryParams();
        $video = new Video($this->container->db);
        $foreign_key = $video->get_SFCTA_TA($data); //Get the SFCTA_TA of the specific video.
        // $response = $response->withHeader('Content-type', 'application/json');
        // $response = $response->withJson($foreign_key);
        // return $response;
        
        $file_name = $video->get_SFCTA_TA_picture_Microsoft_SQL($foreign_key); //Get the Microsoft_SQL of the SFCTA_TA pictures. 
        $files  = scandir($this->container->history_directory);
        foreach ($files as $key => $value) {
            foreach($file_name as $Key => $file_name_value)
            {
                $position = strpos($value, $file_name_value['TC004']);
                if(is_int($position)){
                    $base64 = $this->container->history_directory."\\".$value."\\".$file_name_value['TD201'].".jpg";
                    // $imagedata = file_get_contents($base64);
                    // // alternatively specify an URL, if PHP settings allow
                    // $base64 = base64_encode($imagedata);
                    // // $base64 = $this->container->history_directory.$value."/10-437-200.jpg";
                    // $result = array(
                    //     'status' => 'success',
                    //     'picture' => $base64
                    // );
                    // $response = $response->withHeader('Content-type', 'application/json');
                    // $response = $response->withJson($result);
                    // return $response;
                    // break;
                    
                    // $file = $this->container->upload_directory . DIRECTORY_SEPARATOR . '1a0b96d6320d9ced.jpg';
                    if (!file_exists($base64)) {
                        $response = $response->withStatus(500);
                        return $response;
                    }
                    $response = $response->withHeader('Content-Description', 'File Transfer')
                    ->withHeader('Content-Type', 'application/octet-stream')
                    ->withHeader('Content-Disposition', 'attachment;filename="' . $file_name_value['TD201'] . '.jpg' . '"')
                    ->withHeader('Expires', '0')
                    ->withHeader('Cache-Control', 'must-revalidate')
                    ->withHeader('Pragma', 'public')
                    ->withHeader('Content-Length', filesize($base64));
                    ob_clean();
                    ob_end_flush();
                    $source = imagecreatefromjpeg($base64);
                    // Load
                    
                    // Output
                    imagejpeg($source);
                }
            }
        }
        // $base64 = "/file/{$data['id']}";
        // $ack = array(
        //     'picture' => $base64
        // );
        $result = array('status' => 'failed');
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($result);
        $response = $response->withStatus(500);
        return $response;
    }

    //Get the video types.
    public function get_video_type($request, $response, $args)
    {
        $video = new Video($this->container->db);
        $type = $video->get_video_type_order(); 
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($type);
        return $response;
    }
    
    //Get the manufacturing_id.
    public function get_manufacturing_id($request, $response, $args)
    {
        $video = new Video($this->container->db);
        $manufacturing_id = $video->get_groups_of_videos_Microsoft_SQL(); 
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($manufacturing_id);
        return $response;
    }

    //Get groups of videos by MIL's Microsoft SQL server and Postgresql.
    public function get_groups_of_videos($request, $response, $args)
    {
        $data = $request->getQueryParams();
        // $data = $request->getParsedBody();
        $video = new Video($this->container->db);
        $foreign_key_video = [];

        if(array_key_exists('foreign_key', $data)){
            $foreign_key_video = $video->get_video_id_by_foreign_key($data); //Get the video_id by foreign keys first.
        }
        $groups = $video->get_groups_of_videos_Microsoft_SQL(); //Get the Microsoft_SQL of the mw001 and mw002 or manufacturing_id. 
        $videos = $video->get_groups_of_videos($groups, $data, $foreign_key_video); //Get the videos of the groups.
        foreach($videos["data"] as $key => $video){
            foreach($video as $video_key => $value){
                if(array_key_exists("video_file_name", $value)){
                    if(substr($value["video_file_name"], 0, 7) === "<iframe"){
                        $videos["data"][$key][$video_key]["youtube_url"] = $value["video_file_name"];
                        $videos["data"][$key][$video_key]["youtube_thumbnail"] = $value["video_thumbnail_file_name"];
                    }
                }
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($videos);
        return $response;
    }

    //Get groups of videos by MIL's Microsoft SQL server and Postgresql.
    public function get_groups_of_videos_single_line($request, $response, $args)
    {
        $data = $request->getQueryParams();
        // $data = $request->getParsedBody();
        $video = new Video($this->container->db);
        $foreign_key_video = [];

        if(array_key_exists('foreign_key', $data)){
            $foreign_key_video = $video->get_video_id_by_foreign_key($data); //Get the video_id by foreign keys first.
        }
        $groups = $video->get_groups_of_videos_Microsoft_SQL(); //Get the Microsoft_SQL of the mw001 and mw002 or manufacturing_id. 
        $multiple_line = false;
        $videos = $video->get_groups_of_videos($groups, $data, $foreign_key_video, $multiple_line); //Get the videos of the groups.
        foreach($videos["data"] as $key => $video){
            if(array_key_exists("video_file_name", $video)){
                if(substr($video["video_file_name"], 0, 7) === "<iframe"){
                    $videos["data"][$key]["youtube_url"] = $video["video_file_name"];
                    $videos["data"][$key]["youtube_thumbnail"] = $video["video_thumbnail_file_name"];
                }
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($videos);
        return $response;
    }

    //Get top three videos by MIL's Microsoft SQL server and Postgresql.
    public function get_top_three_videos($request, $response, $args)
    {
        $video = new Video($this->container->db);
        $groups = $video->get_groups_of_videos_Microsoft_SQL(); //Get the Microsoft_SQL of the mw001 and mw002 or manufacturing_id. 
        $videos = $video->get_top_three_videos($groups); //Get the top three videos.
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($videos);
        return $response;
    }

    public function get_video_thumbnail($request, $response, $args)
    {
        $data = $args;
        $video = new Video($this->container->db);
        $result = $video->get_video_thumbnail($data);
        // $response = $response->withHeader('Content-type', 'application/json');
        // $response = $response->withJson($result);
        // return $response;

        foreach($result as $key => $value){
            if(substr($value["video_thumbnail_file_name"], 0, 7) !== "http://"){
                $file = $this->container->upload_directory;
                if($value['video_thumbnail_file_name'] == "" || $value['video_thumbnail_file_name'] == null){
                    $file = $this->container->upload_directory . DIRECTORY_SEPARATOR . '1a0b96d6320d9ced.jpg';
                }
                else{
                    $file = $this->container->upload_directory . DIRECTORY_SEPARATOR . $value['video_thumbnail_file_name'];
                    if (!file_exists($file)) {
                        $response = $response->withStatus(500);
                        return $response;
                    }                
                }
                $source = $this->compressImage($file, $file, 100);
                $response = $response->withHeader('Content-Description', 'File Transfer')
                    ->withHeader('Content-Type', 'application/octet-stream')
                    ->withHeader('Content-Disposition', 'attachment;filename="' . $value['video_thumbnail_file_name'] . '"')
                    ->withHeader('Expires', '0')
                    ->withHeader('Cache-Control', 'must-revalidate')
                    ->withHeader('Pragma', 'public')
                    ->withHeader('Content-Length', filesize($file));
                ob_clean();
                ob_end_flush();
                // $source = imagecreatefromjpeg($file);
                // Load

                // Output
                imagejpeg($source);
                return $response;
            }
        }
        $output = ["status" => "okay"];
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($output);
        return $response;
    }

    //Get user name when uploading video.
    public function get_upload_user_name($request, $response, $args)
    {
        $data = $request->getQueryParams();
        // $data['id'] = 7;
        $data['id'] = $_SESSION['id'];
        $video = new Video($this->container->db);
        $name = $video->get_upload_user_name($data);
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($name);
        return $response;
    }

    //Get the video types no ordering.
    public function get_video_type_no_order($request, $response, $args)
    {
        $video = new Video($this->container->db);
        $type = $video->get_video_type_no_order(); 
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($type);
        return $response;
    }
    //Insert video type.
    public function insert_video_type($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $video = new Video($this->container->db);
        $type = $video->insert_video_type($data); 
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($type);
        return $response;
    }
    //Delete video type.
    public function delete_video_type($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $video = new Video($this->container->db);
        $videos = $video->get_video_of_delete_video_type($data); 
        $type = $video->delete_video_type($data); 
        $updated_videos = $video->update_video_type($videos);
        $data["delete"] = 0;
        $data["data"] = $videos;
        foreach($data["data"] as $key => $value){
            // $data["data"][$key]['delete_user_id'] = 9;
            $data["data"][$key]['delete_user_id'] = $_SESSION['id'];
        }
        $update = $video->update_video_garbage($data); 

        $get_type = $video->get_video_type();
        foreach($get_type as $key => $value){
            $get_type[$key]["video_type_id"] = $value["id"];
            $get_type[$key]["video_type_name"] = $value["name"];
            unset($get_type[$key]["id"]);
            unset($get_type[$key]["name"]);
            unset($get_type[$key]["order"]);
        }
        $type_order = $video->patch_video_type($get_type); 
        if($type_order[0]["status"] === "failed")
        {
            $response = $response->withHeader('Content-type', 'application/json');
            $response = $response->withJson($type_order);
            $response = $response->withStatus(500);
            return $response;
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($type_order);
        return $response;
    }
    //Update video type.
    public function patch_video_type($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $video = new Video($this->container->db);
        $type = $video->patch_video_type($data); 
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($type);
        return $response;
    }
    //Update video type name.
    public function patch_video_type_name($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $video = new Video($this->container->db);
        $type = $video->patch_video_type_name($data); 
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($type);
        return $response;
    }

    //Get groups of videos in garbage by MIL's Microsoft SQL server and Postgresql.
    public function get_groups_of_garbage_videos_single_line($request, $response, $args)
    {
        $data = $request->getQueryParams();
        // $data = $request->getParsedBody();
        $video = new Video($this->container->db);
        $foreign_key_video = [];

        if(array_key_exists('foreign_key', $data)){
            $foreign_key_video = $video->get_video_id_by_foreign_key($data); //Get the video_id by foreign keys first.
        }
        $groups = $video->get_groups_of_videos_Microsoft_SQL(); //Get the Microsoft_SQL of the mw001 and mw002 or manufacturing_id. 
        $multiple_line = false;
        $garbage = true;
        $videos = $video->get_groups_of_videos($groups, $data, $foreign_key_video, $multiple_line, $garbage); //Get the videos of the groups.
        foreach($videos["data"] as $key => $video){
            if(array_key_exists("video_file_name", $video)){
                if(substr($video["video_file_name"], 0, 7) === "<iframe"){
                    $videos["data"][$key]["youtube_url"] = $video["video_file_name"];
                    $videos["data"][$key]["youtube_thumbnail"] = $video["video_thumbnail_file_name"];
                }
            }
        }
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($videos);
        return $response;
    }
    //Update video_garbage.
    public function update_video_garbage($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $video = new Video($this->container->db);
        foreach($data["data"] as $key => $value){
            // $data["data"][$key]['delete_user_id'] = 9;
            $data["data"][$key]['delete_user_id'] = $_SESSION['id'];
        }
        $video = $video->update_video_garbage($data); 
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($video);
        return $response;
    }
    //Crontab job.
    public function get_garbage_videos_for_crontab($request, $response, $args)
    {
        $video = new Video($this->container->db);
        $videos = $video->get_garbage_videos_for_crontab(); //Get videos that put in trash and over 30 days.
        $delete = $video->delete_video($videos); //Delete videos that put in trash and over 30 days.
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($delete);
        return $response;
    }

    //Insert or update video_views.
    public function video_views($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $video = new Video($this->container->db);
        $video = $video->video_views($data); 
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($video);
        return $response;
    }

    //Delete videos which are in the garbage.
    public function delete_video_garbage($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $video = new Video($this->container->db);
        $video = $video->delete_video_garbage(); 
        $response = $response->withHeader('Content-type', 'application/json');
        $response = $response->withJson($video);
        return $response;
    }
}
