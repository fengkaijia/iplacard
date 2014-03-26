<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 文件控制器
 * @package iPlacard
 * @since 2.0
 */
class Document extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->library('form_validation');
		$this->load->library('ui', array('side' => 'admin'));
		$this->load->model('admin_model');
		$this->load->model('document_model');
		$this->load->model('committee_model');
		$this->load->helper('form');
		
		//检查登录情况
		if(!is_logged_in())
		{
			login_redirect();
			return;
		}
		
		//检查权限
		if(!$this->user_model->is_admin(uid()) || (!$this->admin_model->capable('administrator') && !$this->admin_model->capable('dais')))
		{
			redirect('');
			return;
		}
		
		$this->ui->now('document');
	}
	
	/**
	 * 管理页面
	 */
	function manage()
	{
		//查询过滤
		$post = $this->input->get();
		$param = $this->_filter_check($post);
		
		//显示标题
		$title = '全部文件列表';
		
		if(isset($param['committee']))
		{
			$text_committee = array();
			foreach($param['committee'] as $one)
			{
				$text_committee[] = $this->committee_model->get_committee($one, 'name');
			}
			$title = sprintf("%s委员会文件列表", join('、', $text_committee));
		}
		
		$vars = array(
			'title' => $title,
		);
		
		$this->ui->title($title, '文件管理');
		$this->load->view('admin/document_manage', $vars);
	}

	/**
	 * AJAX
	 */
	function ajax($action = 'list')
	{
		$json = array();
		
		if($action == 'list')
		{
			$this->load->helper('date');
			
			$param = $this->_filter_check($this->input->get());
			$input_param = array();
			
			//委员会
			if(isset($param['committee']))
			{
				$documents = array();
				
				foreach($param['committee'] as $committee)
				{
					$document = $this->document_model->get_committee_documents($committee);
					
					if($document)
					{
						foreach($document as $one)
						{
							if(!in_array($one, $documents))
								$documents[] = $one;
						}
					}
				}
				
				$input_param['id'] = $documents;
			}
			
			$args = array();
			if(!empty($input_param))
			{
				foreach($input_param as $item => $value)
				{
					$args[] = $item;
					$args[] = $value;
				}
			}
			$ids = call_user_func_array(array($this->document_model, 'get_document_ids'), $args);
			
			$admin = $this->admin_model->get_admin(uid());
			
			if($ids)
			{
				foreach($ids as $id)
				{
					$document = $this->document_model->get_document($id);

					//操作
					$operation = anchor("document/download/$id", icon('download', false).'下载');;
					if($this->admin_model->capable('administrator') || ($this->admin_model->capable('dais') && $admin == $document['user']))
						$operation .= ' '.anchor("document/edit/$id", icon('edit', false).'编辑');
					
					//文件名称
					$title_line = mime($document['filetype']).$document['title'];
					if($document['highlight'])
						$title_line .= '<span class="text-primary">'.icon('star', false).'</span>';
					
					//分发范围
					$access = $this->document_model->get_documents_accessibility($id);
					if($access)
					{
						$count_access = count($access);
						
						if($access === true)
							$access_line = icon('files-o', false).'全局分发';
						elseif($count_access == 1)
							$access_line = icon('file-o', false).$this->committee_model->get_committee($access[0], 'abbr');
						else
						{
							$access_line = "$count_access 委员会";
							
							$access_list = '';
							foreach($access as $one)
							{
								$access_list .= '<p>'.icon('archive').$this->committee_model->get_committee($one, 'name').'</p>';
							}
							
							$access_line .= '<a href="#" class="committee_list" data-html="1" data-placement="right" data-trigger="click" data-original-title=\'可访问委员会\' data-toggle="popover" data-content=\''.$access_list.'\'>'.icon('info-circle', false).'</a>';
						}
					}
					else
						$access_line = '<span class="text-danger">N/A</span>';
					
					//版本下载量
					$version = $this->document_model->get_document_files($id);
					if($version)
					{
						$count_version = count($version);
						
						if($count_version > 1)
						{
							$version_line = "$count_version 版本";
							
							$version_list = '';
							foreach($version as $one)
							{
								$version_info = $this->document_model->get_file($one);
								
								$version_text = !empty($version_info['version']) ? $version_info['version'] : '';
								$version_text .= sprintf('<span class="text-muted"> / %s</span> ', date('n月j日', $version_info['upload_time']));
								if($one == $document['file'])
									$version_text .= '<span class="label label-primary">最新</span>';
								
								$version_list .= '<p>'.icon('file').$version_text.'</p>';
							}
							
							$version_line .= '<a href="#" class="version_list" data-html="1" data-placement="right" data-trigger="click" data-original-title=\'历史版本\' data-toggle="popover" data-content=\''.$version_list.'\'>'.icon('info-circle', false).'</a>';
						}
						else
							$version_line = '<span class="text-primary">原始版本</span>';
						
						//下载量
						$downloads = $this->document_model->get_download_ids('file', $version);
						$download_count = $downloads ? count($downloads) : 0;
					}
					else
					{
						$download_count = '<span class="text-danger">N/A</span>';
						$version_line = '<span class="text-danger">N/A</span>';
					}
					
					$data = array(
						$document['id'], //ID
						$title_line, //文件名称
						sprintf('%1$s（%2$s）', date('n月j日', $document['create_time']), nicetime($document['create_time'])), //上传时间
						$access_line, //分发范围
						$version_line, //版本
						$document['drm'] ? '<span class="text-success">'.icon('check-circle', false).'</span>' : '', //版权标识
						$download_count, //下载量
						$operation, //操作
					);
					
					$datum[] = $data;

					$json = array('aaData' => $datum);
				}
			}
			else
			{
				$json = array('aaData' => array());
			}
		}
		
		echo json_encode($json);
	}
	
	/**
	 * 密码检查回调函数
	 */
	function _check_admin_password($str, $global_message = '')
	{
		if($this->user_model->check_password(uid(), $str))
			return true;
		
		//全局消息
		if(!empty($global_message))
			$this->ui->alert($global_message, 'warning', true);
		
		return false;
	}
	
	/**
	 * 查询过滤
	 */
	function _filter_check($post, $return_uri = false)
	{
		$return = array();
		
		//委员会
		if(isset($post['committee']))
		{
			$committee = array();
			foreach(explode(',', $post['committee']) as $param_committee)
			{
				if($param_committee == 'u')
				{
					$param_committee = $this->admin_model->get_admin(uid(), 'committee');
					if(!$param_committee)
						$param_committee = 0;
				}
				
				if(in_array($param_committee, $this->committee_model->get_committee_ids()))
					$committee[] = $param_committee;
			}
			if(!empty($committee))
				$return['committee'] = $committee;
		}
		
		if(!$return_uri)
			return $return;
		
		if(empty($return))
			return '';
		
		return $this->_filter_build($return);
	}
	
	/**
	 * 建立查询URI
	 */
	function _filter_build($param)
	{
		foreach($param as $name => $value)
		{
			$param[$name] = join(',', $value);
		}
		return http_build_query($param);
	}
}

/* End of file document.php */
/* Location: ./application/controllers/document.php */