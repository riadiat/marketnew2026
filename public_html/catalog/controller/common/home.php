<?php
class ControllerCommonHome extends Controller {
	public function index() {
		$this->document->setTitle($this->config->get('config_meta_title'));
		$this->document->setDescription($this->config->get('config_meta_description'));
		$this->document->setKeywords($this->config->get('config_meta_keyword'));

		if (isset($this->request->get['route'])) {
			$this->document->addLink($this->config->get('config_url'), 'canonical');
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

        $this->load->model('design/banner');
        $this->load->model('tool/image');
        $this->load->model('seller/seller');
        $this->load->model('catalog/videos');



        $data['slider_1'] = $this->setSlider($this->model_design_banner->getBanner(1),500,500);
        //$data['slider_2'] = $this->setSlider($this->model_design_banner->getBanner(2),500,500);
        //$data['slider_3'] = $this->setSlider($this->model_design_banner->getBanner(3),500,500);


        $data['banner_1'] = $this->setSlider($this->model_design_banner->getBanner(2),500,500);
        $data['banner_2'] = $this->setSlider($this->model_design_banner->getBanner(5),500,500);


        $data['featured_1'] = $this->setFeatured(38);
        $data['featured_2'] = $this->setFeatured(39);



        $seller_data = [
            'show_home'=>1
        ];
        $sellers = $this->model_seller_seller->getSellers($seller_data);

        $data['sellers'] = [];

        foreach ($sellers as $seller){
            if($seller['image']){
                $data['sellers'] []= [
                    'username'=>$seller['username'],
                    'name'=>$seller['firstname'].' '.$seller['lastname'],
                    'image'=>HTTPS_SERVER.'image/'.$seller['image'],
                    'link'=>HTTPS_SERVER.$seller['username'],
                ];
            }

        }



        $videos = $this->model_catalog_videos->getVideos();

        $vids = [];
        foreach ($videos as $video){
            $video_id = explode("?v=", $video['youtube_url']);
            $video_id = $video_id[1];
            $vids []=[
                'title'=>$video['title'],
                'youtube_url'=>$video['youtube_url'],
                'video_id'=>$video_id,
            ];
        }
        $data['videos'] = $vids;
		$this->response->setOutput($this->load->view('common/home', $data));
	}
	private function setSlider($results,$width,$height){
	    $output = [];
        foreach ($results as $result) {
            if (is_file(DIR_IMAGE . $result['image'])) {
                $output[] = array(
                    'title' => $result['title'],
                    'link'  => $result['link'],
                    'full_image'  => HTTPS_SERVER.'image/'.$result['image'],
                    'image' => $this->model_tool_image->resize($result['image'], $width, $height)
                );
            }
        }
        return $output;
    }

    private function setFeatured($id){
	    $output = [];
        $setting_featured_1 = $this->model_setting_module->getModule($id);
        $setting_featured_1['id'] = $id;
        if($setting_featured_1){
            $arr = explode('|',$setting_featured_1['name']);
            if(is_array($arr) && isset($arr[1])){
                $title_f1 = $arr[0];
                $title_f2 = $arr[1];

            }else{
                $title_f1 = $setting_featured_1['name'];
                $title_f2 = $setting_featured_1['name'];
            }
            $setting_featured_1['title'] = $title_f2;

            $output = [
                'id'  => $id,
                'image'  => HTTPS_SERVER.'image/'.$setting_featured_1['image'],
                'image_hover'  => HTTPS_SERVER.'image/'.$setting_featured_1['image_hover'],
                'title'=>($this->language->get('code')=='ar')?$title_f1:$title_f2,
                'html' => $this->load->controller('extension/module/featured',$setting_featured_1)
            ];
        }
        return $output;
    }
}
