<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 文件控制器
 * @package iPlacard
 * @since 2.0
 */
class Document extends CI_Controller
{
	/**
	 * @var string 文件路径
	 */
	private $path = '';
	
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
		
		//文件路径
		$this->path = './data/'.IP_INSTANCE_ID.'/document/';
		
		$this->ui->now('document');
	}
	
	/**
	 * 管理页面
	 */
	function manage()
	{
		//检查权限
		if(!$this->user_model->is_admin(uid()) || (!$this->admin_model->capable('administrator') && !$this->admin_model->capable('dais')))
		{
			redirect('');
			return;
		}
		
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
	 * 编辑或添加文件
	 */
	function edit($id = '')
	{
		$this->load->helper('number');
		$this->load->helper('file');
		
		//检查权限
		if(!$this->user_model->is_admin(uid()) || (!$this->admin_model->capable('administrator') && !$this->admin_model->capable('dais')))
		{
			redirect('');
			return;
		}
		
		if(!$this->admin_model->capable('administrator') && !$this->admin_model->capable('dais'))
		{
			$this->ui->alert('需要管理员或主席权限以编辑文件。', 'warning', true);
			redirect('document/manage');
			return;
		}
		
		//设定操作类型
		$action = 'edit';
		if(empty($id))
			$action = 'add';
		
		if($action == 'edit')
		{
			$document = $this->document_model->get_document($id);
			if(!$document)
				$action = 'add';
			elseif(!$this->admin_model->capable('administrator') && $document['user'] != uid())
			{
				$this->ui->alert('仅此文件的发布者可以编辑此文件。', 'warning', true);
				redirect('document/manage');
				return;
			}
			
			$access = $this->document_model->get_documents_accessibility($id);
			if($access !== true)
			{
				$document['access_select'] = $access;
				$document['access_type'] = 'committee';
			}
			else
			{
				$document['access_select'] = array();
				$document['access_type'] = 'global';
			}
			
			$vars['document'] = $document;
			
			$this->ui->title($document['title'], '文件管理');
		}
		else
		{
			$this->ui->title('添加文件');
		}
		
		//委员会信息
		$committees = array();
		
		$committee_ids = $this->committee_model->get_committee_ids();
		foreach($committee_ids as $committee_id)
		{
			$committee = $this->committee_model->get_committee($committee_id);
			$committees[$committee_id] = "{$committee['name']}（{$committee['abbr']}）";
		}
		
		$vars['committees'] = $committees;
		
		//文件大小上限
		$file_max_size = byte_format(ini_max_upload_size(option('file_max_size', 10 * 1024 * 1024)), 0);
		$vars['file_max_size'] = $file_max_size;
		
		//预上传文件版本
		if($this->input->post('new_upload'))
		{
			//操作上传图像
			$this->load->helper('string');
			$config['file_name'] = time().'_'.random_string('alnum', 32);
			$config['allowed_types'] = '*';
			$config['max_size'] = ini_max_upload_size(option('file_max_size', 10 * 1024 * 1024)) / 1024;
			$config['upload_path'] = './temp/'.IP_INSTANCE_ID.'/upload/document/';

			if(!file_exists($config['upload_path']))
				mkdir($config['upload_path'], DIR_WRITE_MODE, true);

			$this->load->library('upload', $config);

			//储存上传文件
			if(!$this->upload->do_upload('file'))
			{
				$error = $this->upload->display_errors('', '');
				
				$this->form_validation->set_message('_check_upload_error', $error);
				
				$this->form_validation->set_rules('file', '文件', 'callback__check_upload_error');
			}

			$upload_result = $this->upload->data();
		}
		
		$this->form_validation->set_error_delimiters('<div class="help-block">', '</div>');
		
		$this->form_validation->set_rules('title', '文件名称', 'trim|required');
		$this->form_validation->set_rules('access_type', '分发类型', 'trim|required');
		if($action == 'add')
		{
			$this->form_validation->set_rules('new_upload', '文件', 'required');
		}
		
		if($this->form_validation->run() == true)
		{
			$post = $this->input->post();
			
			//文件
			if($action == 'add')
			{
				$id = $this->document_model->add_document($post['title'], $post['description'], isset($post['highlight']) && $post['highlight'] ? true : false);
				
				$this->ui->alert("已经成功添加新文件 #{$id}。", 'success', true);
				
				$this->system_model->log('document_added', array('id' => $id));
			}
			else
			{
				$data = array(
					'title' => $post['title'],
					'description' => $post['description'],
					'highlight' => $post['highlight'] ? true : false
				);
				
				$this->document_model->edit_document($data, $id);
				
				$this->ui->alert('文件已编辑。', 'success', true);

				$this->system_model->log('document_edited', array('id' => $id, 'data' => $data));
			}
			
			//权限
			if($action == 'edit')
				$this->document_model->delete_access($id);
			
			$access_committees = array();
			if($post['access_type'] == 'committee')
			{
				foreach($post['access_select'] as $access_one)
				{
					if($this->committee_model->get_committee($access_one))
						$access_committees[] = intval($access_one);
				}
			}
			else
			{
				$access_committees = 0;
			}
			
			$this->document_model->add_access($id, $access_committees);
			
			//文件版本
			if(isset($upload_result))
			{
				$file_id = $this->document_model->add_file($id, $upload_result['full_path'], $post['version'], $post['drm']);
				
				$this->document_model->edit_document(array('file' => $file_id), $id);
				
				if(!file_exists($this->path))
					mkdir($this->path, DIR_WRITE_MODE, true);
				
				rename($upload_result['full_path'], $this->path.$file_id.$upload_result['file_ext']);
				
				$this->ui->alert("已经上传文件版本 #{$file_id}。", 'success', true);

				$this->system_model->log('document_file_uploaded', array('id' => $file_id, 'document' => $id));
				
				//邮件通知
				$this->load->library('email');
				$this->load->library('parser');
				$this->load->helper('date');
				
				$email_data = array(
					'id' => $id,
					'title' => $post['title'],
					'url' => base_url("document/download/$id/$file_id"),
					'time' => unix_to_human(time())
				);
				
				if($access_committees == 0)
				{
					$users = $this->user_model->get_user_ids('id !=', uid());
				}
				else
				{
					$this->load->model('seat_model');
					
					$sids = $this->seat_model->get_seat_ids('committee', $access_committees, 'status', array('assigned', 'approved', 'locked'));
					if($sids)
					{
						$users = $this->seat_model->get_delegates_by_seats($sids);
					}
				}
				
				if($users)
				{
					foreach($users as $user)
					{
						if($action == 'add')
						{
							$this->email->subject('新的文件可供下载');
							$this->email->html($this->parser->parse_string(option('email_document_added', "新的文件《{title}》已经于 {time} 上传到 iPlacard，请访问\n\n"
									. "\t{url}\n\n"
									. "下载文件。"), $email_data, true));
						}
						else
						{
							$this->email->subject('文件已经更新');
							$this->email->html($this->parser->parse_string(option('email_document_updated', "文件《{title}》已经于 {time} 更新，请访问\n\n"
									. "\t{url}\n\n"
									. "下载文件更新。"), $email_data, true));
						}
						
						$this->email->to($this->user_model->get_user($user, 'email'));
						$this->email->send();
						$this->email->clear();
					}
				}
			}
			
			redirect('document/manage');
			return;
		}
		
		$vars['action'] = $action;
		$this->load->view('admin/document_edit', $vars);
	}
	
	/**
	 * 删除文件
	 */
	function delete($id)
	{
		//检查权限
		if(!$this->user_model->is_admin(uid()) || (!$this->admin_model->capable('administrator') && !$this->admin_model->capable('dais')))
		{
			redirect('');
			return;
		}
		
		//文件检查
		$document = $this->document_model->get_document($id);
		if(!$document)
		{
			$this->ui->alert('指定删除的文件不存在。', 'warning', true);
			redirect('document/manage');
			return;
		}
		
		if(!$this->admin_model->capable('administrator') && $document['user'] != uid())
		{
			$this->ui->alert('需要管理员权限以删除文件。', 'warning', true);
			redirect('document/manage');
			return;
		}
		
		$this->form_validation->set_rules('admin_password', '密码', 'trim|required|callback__check_admin_password[密码验证错误导致删除操作未执行，请重新尝试。]');
		
		if($this->form_validation->run() == true)
		{
			$count = 0;
			
			//删除数据
			$files = $this->document_model->get_document_files($id);
			if($files)
			{
				//删除文件版本
				foreach($files as $file_id)
				{
					$file = $this->document_model->get_file($file_id);
					
					unlink("{$this->path}{$file_id}.{$file['filetype']}");
					
					$this->document_model->delete_file($file_id);
				}
				
				$count = count($files);
			}
			
			$this->document_model->delete_document($id);
			$this->document_model->delete_access($id);
			
			//日志
			$this->system_model->log('document_deleted', array('ip' => $this->input->ip_address(), 'document' => $id, 'file' => $files));
			
			$this->ui->alert("文件 #{$id} 已经成功删除，同时此文件的 {$count} 个版本也已删除。", 'success', true);
			redirect('document/manage');
		}
		else
		{
			redirect("document/edit/{$id}");
		}
	}
	
	/**
	 * 下载文件
	 */
	function download($id, $version = 0)
	{
		$this->load->library('user_agent');
		$this->load->helper('file');
		$this->load->helper('download');
		
		$document = $this->document_model->get_document($id);
		if(!$document)
		{
			$this->ui->alert('请求下载的文件不存在。', 'danger', true);
			back_redirect();
			return;
		}
		
		//许可检查
		if($version == 0)
			$version = $document['file'];
		
		$file = $this->document_model->get_file($version);
		if(!$file)
		{
			$this->ui->alert('请求下载的文件不存在。', 'danger', true);
			back_redirect();
			return;
		}
		elseif($file['document'] != $id)
		{
			$this->ui->alert('参数错误。', 'danger', true);
			back_redirect();
			return;
		}
		
		//权限检查
		if(!$this->_check_document_access($id))
		{
			$this->ui->alert('无权下载此文件。', 'warning', true);
			back_redirect();
			return;
		}
		
		//读取文件内容
		$data = read_file("{$this->path}{$file['id']}.{$file['filetype']}");
		
		if(empty($data) || sha1($data) != $file['hash'])
		{
			$this->ui->alert('文件系统出现未知错误导致无法下载文件，请重新尝试下载。', 'danger', true);
			back_redirect();
			return;
		}
		
		//版权标识
		list($data, $drm) = $this->_drm($data, $file['filetype']);
		
		$this->document_model->add_download($file['id'], uid(), $drm);
		
		//文件名
		$organization = option('organization', 'iPlacard');
		if(!empty($file['version']))
			$filename = "{$organization}-{$document['title']}-{$file['version']}.{$file['filetype']}";
		else
			$filename = "{$organization}-{$document['title']}-{$file['id']}.{$file['filetype']}";
		
		//弹出下载
		$this->output->set_content_type($file['filetype']);
		
		if($this->agent->is_mobile())
		{
			//手机访问不强制弹出下载
			$this->output->set_output($data);
		}
		else
		{
			force_download($filename, $data);
		}
	}
	
	/**
	 * 下载文件压缩包
	 */
	function zip()
	{
		$this->load->library('zip');
		$this->load->helper('file');
		$this->load->helper('download');
		
		if($this->user_model->is_delegate(uid()))
		{
			$this->load->model('delegate_model');
			
			//仅允许访问全局分发文件
			$committee = 0;
			
			//代表可访问委员会文件
			if($this->delegate_model->get_delegate(uid(), 'application_type') == 'delegate')
			{
				$this->load->model('committee_model');
				$this->load->model('seat_model');

				$seat = $this->seat_model->get_delegate_seat(uid());
				if($seat)
					$committee = $this->seat_model->get_seat($seat, 'committee');
			}
			
			$documents = $this->document_model->get_committee_documents($committee);
		}
		else
		{
			$documents = $this->document_model->get_document_ids();
		}
		
		if(!$documents)
		{
			$this->ui->alert('无文件可供下载。', 'danger', true);
			back_redirect();
			return;
		}
		
		//等待下载窗口弹出
		sleep(2);
		
		$organization = option('organization', 'iPlacard');
		
		//导入文件
		foreach($documents as $document_id)
		{
			$document = $this->document_model->get_document($document_id);

			//可用性检查
			$file = $this->document_model->get_file($document['file']);
			if(!$file)
			{
				$this->ui->alert('请求下载的文件不存在。', 'danger', true);
				back_redirect();
				return;
			}

			//读取文件内容
			$data = read_file("{$this->path}{$file['id']}.{$file['filetype']}");

			if(empty($data) || sha1($data) != $file['hash'])
			{
				$this->ui->alert('文件系统出现未知错误导致无法下载文件，请重新尝试下载。', 'danger', true);
				back_redirect();
				return;
			}

			//版权标识
			list($data, $drm) = $this->_drm($data, $file['filetype']);

			$this->document_model->add_download($file['id'], uid(), $drm);

			//文件名
			if(!empty($file['version']))
				$filename = "{$organization}-{$document['title']}-{$file['version']}.{$file['filetype']}";
			else
				$filename = "{$organization}-{$document['title']}-{$file['id']}.{$file['filetype']}";
			
			//将文件加入到归档
			$this->zip->add_data($filename, $data);
		}
		
		//弹出下载
		$time = date('Y-m-d-H-i-s');
		$this->zip->download("{$organization}-{$time}.zip");
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
					$operation = anchor("document/download/$id", icon('download', false).'下载');
					if($this->admin_model->capable('administrator') || ($this->admin_model->capable('dais') && $admin == $document['user']))
						$operation .= ' '.anchor("document/edit/$id", icon('edit', false).'编辑');
					
					//文件名称
					$this->load->helper('file');
					$title_line = sprintf('<span class="document_info" data-original-title="%1$s（%2$s）文件" data-toggle="tooltip">%3$s</span>', strtoupper($document['filetype']), get_mime_by_extension('.'.$document['filetype']), mime($document['filetype'])).$document['title'];
					if($document['highlight'])
						$title_line .= '<span class="text-primary document_info" data-original-title="重要文件" data-toggle="tooltip">'.icon('star', false).'</span>';
					
					//分发范围
					$access = $this->document_model->get_documents_accessibility($id);
					if($access)
					{
						$count_access = count($access);
						
						if($access === true)
							$access_line = '全局分发';
						elseif($count_access == 1)
							$access_line = $this->committee_model->get_committee($access[0], 'abbr');
						else
						{
							$access_line = "$count_access 委员会";
							
							$access_list = '';
							foreach($access as $one)
							{
								$access_list .= '<p>'.icon('archive').$this->committee_model->get_committee($one, 'name').'</p>';
							}
							
							$access_line .= '<a style="cursor: pointer;" class="committee_list" data-html="1" data-placement="right" data-trigger="click" data-original-title=\'可访问委员会\' data-toggle="popover" data-content=\''.$access_list.'\'>'.icon('info-circle', false).'</a>';
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
								
								if(empty($version_info['version']))
									$version_text = sprintf('<span class="text-muted">%s</span> ', date('n月j日', $version_info['upload_time']));
								else
									$version_text = $version_info['version'].sprintf('<span class="text-muted"> / %s</span> ', date('n月j日', $version_info['upload_time']));
								
								if($document['file'] == $one)
									$version_text .= '<span class="label label-primary">最新</span>';
								
								$version_list .= '<p>'.icon('file').$version_text.'</p>';
							}
							
							$version_line .= '<a style="cursor: pointer;" class="version_list" data-html="1" data-placement="right" data-trigger="click" data-original-title=\'历史版本\' data-toggle="popover" data-content=\''.$version_list.'\'>'.icon('info-circle', false).'</a>';
						}
						else
							$version_line = '原始版本';
						
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
				}
				
				$json = array('aaData' => $datum);
			}
			else
			{
				$json = array('aaData' => array());
			}
		}
		
		echo json_encode($json);
	}
	
	/**
	 * 增加版权标识
	 * @param type $data 文件数据
	 * @param type $type 文件类型
	 * @todo 支持版权标识
	 */
	function _drm($data, $type)
	{
		return array($data, NULL);
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
	 * 文件上传检查回调虚函数
	 */
	function _check_upload_error($str)
	{
		return false;
	}
	
	/**
	 * 检查是否有权限访问文件
	 */
	function _check_document_access($document, $user = '')
	{
		if(empty($user))
			$user = uid();
		
		//所有管理员有权访问
		if($this->user_model->is_admin($user))
			return true;
		
		$access = $this->document_model->get_documents_accessibility($document);
		
		//全局文件所有人有权访问
		if($access === true)
			return true;
		
		//指定委员会代表有权访问
		$this->load->model('seat_model');
		
		$seats = $this->seat_model->get_seat_ids('committee', $access, 'status', array('assigned', 'approved', 'locked'));
		if(!$seats)
			return false;
		
		$delegates = $this->seat_model->get_delegates_by_seats($seats);
		if(in_array($user, $delegates))
			return true;
		
		//其他情况无权访问
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