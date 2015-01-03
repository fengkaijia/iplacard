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
		$this->load->library('ui', array('side' => 'account'));
		$this->load->model('admin_model');
		$this->load->model('knowledgebase_model');
		$this->load->helper('form');
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
		$this->load->helper('text');
		
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
						$title_line = $article['title'].'<span class="text-primary system_article" data-original-title="iPlacard 系统文章" data-toggle="tooltip">'.icon('cog', false).'</span>';
					else
						$title_line = $article['title'];

					$data = array(
						$article['id'], //ID
						"KB{$article['kb']}", //知识库编号
						$title_line, //标题
						sprintf('%1$s（%2$s）', date('n月j日', empty($article['update_time']) ? $article['create_time'] : $article['update_time']), nicetime(empty($article['update_time']) ? $article['create_time'] : $article['update_time'])), //最后更新时间
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
}

/* End of file knowledgebase.php */
/* Location: ./application/controllers/knowledgebase.php */