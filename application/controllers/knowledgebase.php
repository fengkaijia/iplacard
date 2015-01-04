<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 知识库控制器
 * @package iPlacard
 * @since 2.0
 */
class Knowledgebase extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->library('form_validation');
		$this->load->library('ui', array('side' => 'account'));
		$this->load->model('admin_model');
		$this->load->model('knowledgebase_model');
		$this->load->helper('form');
		$this->load->helper('text');
	}
	
	/**
	 * 查看知识库
	 */
	function index()
	{
		$ids = $this->knowledgebase_model->get_article_ids();
		if(!$ids)
		{
			$this->ui->alert('当前知识库无文章。', 'info', true);
			back_redirect();
			return;
		}
		$vars['count'] = count($ids);
		
		list($vars['highlight'], $vars['popular']) = $this->_get_top_kbs(10);
		
		$this->ui->title('知识库');
		$this->load->view('help/knowledgebase/homepage', $vars);
	}
	
	/**
	 * 搜索知识库
	 */
	function search()
	{
		$keyword = $this->input->get('keyword', true);
		if(empty($keyword))
		{
			redirect('knowledgebase');
			return;
		}
		$vars['keyword'] = $keyword;
		
		$ids = $this->knowledgebase_model->get_article_ids();
		if(!$ids)
		{
			$this->ui->alert('当前知识库无文章。', 'info', true);
			back_redirect();
			return;
		}
		$vars['count'] = count($ids);
		
		$result_ids = $this->knowledgebase_model->search_article($keyword);
		if(!$result_ids)
		{
			$this->ui->alert('知识库搜索结果为空，请尝试使用其他关键词。', 'info', true);
			redirect('knowledgebase');
			return;
		}
		
		//搜索结果
		$result = array();
		foreach($result_ids as $result_id)
		{
			$result[$result_id] = $this->knowledgebase_model->get_article($result_id);
		}
		$vars['result'] = $result;
		
		list($vars['highlight'], $vars['popular']) = $this->_get_top_kbs(10);
		
		$this->ui->title($keyword, '搜索知识库');
		$this->load->view('help/knowledgebase/search', $vars);
	}
	
	/**
	 * 查看知识库文章
	 */
	function article($kb)
	{
		$id = $this->knowledgebase_model->get_article_id('kb', substr($kb, 2));
		if(!$id)
		{
			$this->ui->alert('知识库文章不存在。', 'info', true);
			redirect('knowledgebase');
			return;
		}
		$vars['id'] = $id;
		
		list($vars['highlight'], $vars['popular']) = $this->_get_top_kbs(5);
		
		$article = $this->knowledgebase_model->get_article($id);
		$vars['article'] = $article;
		
		//查看计数
		$viewed = $this->session->userdata('knowledgebase_viewed');
		if(!$viewed || !in_array($id, $viewed))
		{
			$this->knowledgebase_model->view_article($id);
			$viewed[] = $id;
			$this->session->set_userdata('knowledgebase_viewed', $viewed);
		}
		
		$this->ui->title($article['title'], '知识库帮助');
		$this->load->view('help/knowledgebase/article', $vars);
	}
	
	/**
	 * 管理知识库文章
	 */
	function manage()
	{
		//检查权限
		if(!$this->admin_model->capable('administrator'))
		{
			redirect('');
			return;
		}
		
		$this->ui->title('知识库列表');
		$this->load->view('admin/knowledge_manage');
	}
	
	/**
	 * 编辑或添加知识库文章
	 */
	function edit($id = '')
	{
		//检查权限
		if(!$this->admin_model->capable('administrator'))
		{
			redirect('');
			return;
		}
		
		//设定操作类型
		$action = 'edit';
		if(empty($id))
			$action = 'add';
		
		if($action == 'edit')
		{
			$article = $this->knowledgebase_model->get_article($id);
			if(!$article)
				$action = 'add';
			elseif($article['system'])
			{
				$this->ui->alert('无法编辑 iPlacard 系统帮助文章。', 'warning', true);
				back_redirect();
				return;
			}
		}
		
		if($action == 'edit')
		{
			$vars['article'] = $article;
			
			$this->ui->title($article['title'], '编辑知识库文章');
		}
		else
		{
			$this->ui->title('添加知识库文章');
		}
		
		$this->form_validation->set_error_delimiters('<div class="help-block">', '</div>');
		
		$this->form_validation->set_rules('title', '文章标题', 'trim|required');
		$this->form_validation->set_rules('content', '文章内容', 'trim|required');
		$this->form_validation->set_rules('order', '文章排序', 'trim|required|is_natural');
		if($action == 'add')
			$this->form_validation->set_rules('kb', '知识库编号', 'trim|required|min_length[5]|max_length[7]|is_natural_no_zero|is_unique[knowledgebase.kb]');
		
		if($this->form_validation->run() == true)
		{
			$post = $this->input->post();
			
			$data = array(
				'title' => $post['title'],
				'content' => $post['content'],
				'order' => $post['order']
			);
			
			if($action == 'add')
			{
				$data['create_time'] = time();
				$data['kb'] = $post['kb'];
			}
			
			$new_id = $this->knowledgebase_model->edit_article($data, $action == 'add' ? '' : $id);
			
			if($action == 'add')
			{
				$this->ui->alert("已经成功添加新知识库文章 KB{$data['kb']}。", 'success', true);
				
				$this->system_model->log('knowledge_added', array('id' => $new_id, 'data' => $data));
			}
			else
			{
				$this->ui->alert('知识库文章已编辑。', 'success', true);

				$this->system_model->log('knowledge_edited', array('id' => $id, 'data' => $data));
			}
			
			redirect('knowledgebase/manage');
			return;
		}
		
		if($action == 'add')
		{
			//随机知识库编号
			$this->load->helper('string');
			
			do {
				$random_kb = random_string('nozero', 5);
			} while ($this->knowledgebase_model->kb_exists($random_kb));
			
			$vars['random_kb'] = $random_kb;
		}
		
		$vars['action'] = $action;
		$this->load->view('admin/knowledge_edit', $vars);
	}
	
	/**
	 * 删除知识库文章
	 */
	function delete($id)
	{
		//检查权限
		if(!$this->admin_model->capable('administrator'))
		{
			redirect('');
			return;
		}
		
		//知识库删除检查
		$article = $this->knowledgebase_model->get_article($id);
		if(!$article)
		{
			$this->ui->alert('指定删除的知识库文章不存在。', 'warning', true);
			redirect('knowledgement/manage');
			return;
		}
		elseif($article['system'])
		{
			$this->ui->alert('无法删除 iPlacard 系统帮助文章。', 'warning', true);
			back_redirect();
			return;
		}
		
		$this->form_validation->set_rules('admin_password', '密码', 'trim|required|callback__check_admin_password[密码验证错误导致删除操作未执行，请重新尝试。]');
		
		if($this->form_validation->run() == true)
		{
			//删除数据
			$this->knowledgebase_model->delete_article($id);
			
			//日志
			$this->system_model->log('knowledge_deleted', array('id' => $id));
			
			$this->ui->alert("知识库文章 KB{$article['kb']} 已经成功删除。", 'success', true);
			redirect('knowledgebase/manage');
		}
		else
		{
			redirect("knowledgebase/edit/{$id}");
		}
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
			
			$ids = $this->knowledgebase_model->get_article_ids();
			
			if($ids)
			{
				foreach($ids as $id)
				{
					$article = $this->knowledgebase_model->get_article($id);

					//操作
					$operation = anchor("knowledgebase/article/kb{$article['kb']}", icon('eye', false).'查看');
					if(!$article['system'])
						$operation .= ' '.anchor("knowledgebase/edit/$id", icon('edit', false).'编辑');
										
					//标题
					if($article['system'])
						$title_line = $article['title'].'<span class="text-primary system_article" data-original-title="iPlacard 系统帮助文章" data-toggle="tooltip">'.icon('cog', false).'</span>';
					else
						$title_line = $article['title'];

					$data = array(
						$article['id'], //ID
						"KB{$article['kb']}", //知识库编号
						$title_line, //标题
						sprintf('%1$s（%2$s）', date('n月j日', empty($article['update_time']) ? $article['create_time'] : $article['update_time']), nicetime(empty($article['update_time']) ? $article['create_time'] : $article['update_time'])), //最后更新时间
						$article['order'], //排序
						$article['count'] == 0 ? '<span class="text-danger">N/A</span>' : $article['count'], //阅读量
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
	 * 获取辅助模块知识库文章
	 */
	function _get_top_kbs($count = 5)
	{
		//置顶文章
		$highlight = array();
		
		$highlight_ids = $this->knowledgebase_model->get_ordered_articles('order', $count);
		foreach($highlight_ids as $one)
		{
			$kbdata = $this->knowledgebase_model->get_article($one);
			$highlight[$kbdata['kb']] = $kbdata;
		}
		
		//热门文章
		$popular = array();
		
		$popular_ids = $this->knowledgebase_model->get_ordered_articles('count', $count);
		foreach($popular_ids as $one)
		{
			$kbdata = $this->knowledgebase_model->get_article($one);
			$popular[$kbdata['kb']] = $kbdata;
		}
		
		return array($highlight, $popular);
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
}

/* End of file knowledgebase.php */
/* Location: ./application/controllers/knowledgebase.php */