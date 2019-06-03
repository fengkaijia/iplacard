<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 学术测试问题生成类库
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright 2019 Kaijia Feng
 * @license Dual-licensed proprietary
 * @link http://iplacard.com/
 * @package iPlacard
 * @since 2.4
 */
class Question
{
	private $question_committees = array();
	private $question_exclusive_rules = array();
	
	function __construct()
	{
		$this->question_committees[0] = array();
	}
	
	/**
	 * 设定题目与委员会对应关系
	 * @param array $mapping 关系表
	 * @return $this
	 */
	function set_committee_rule($mapping)
	{
		foreach($mapping as $question => $committees)
		{
			if(count($committees) == 0)
			{
				$this->question_committees[0][] = $question; //公共题
			}
			else
			{
				foreach($committees as $committee)
				{
					if(!isset($this->question_committees[$committee]))
						$this->question_committees[$committee] = array();
					
					$this->question_committees[$committee][] = $question;
				}
			}
		}
		return $this;
	}
	
	/**
	 * 设定题目互斥规则
	 * @param array $rule 互斥规则
	 * @return $this
	 */
	function set_exclusive_rule($rule)
	{
		foreach($rule as $question => $exclusive_questions)
		{
			$this->question_exclusive_rules[$question] = $exclusive_questions;
		}
		return $this;
	}
	
	/**
	 * 抽题
	 * @param int $committee 委员会ID
	 * @param int $count 抽出题目数量
	 * @return array 选中题目编号（可能少于$count要求数量）
	 */
	function generate($committee, $count)
	{
		$selected = array();
		$pool = array_flip(array_merge($this->question_committees[$committee], $this->question_committees[0])); //全部有效问题ID为Key
		
		for($i = 0; $i < $count; $i++)
		{
			$one = array_rand($pool);
			
			//提出选中问题
			$selected[] = $one;
			unset($pool[$one]);
			
			//剔除与选中问题互斥问题
			if(isset($this->question_exclusive_rules[$one]))
			{
				foreach($this->question_exclusive_rules[$one] as $related)
				{
					unset($pool[$related]);
				}
			}
			
			if(count($pool) == 0)
				break;
		}
		
		return $selected;
	}
}

/* End of file Question.php */
/* Location: ./application/libraries/Question.php */