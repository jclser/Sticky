<?php
/**
 * 文章<span style='border-radius: 2px;padding: .10rem .20rem;margin: 0 .40rem 0 .40rem;position: relative;background-color: #f00;color: #fff;'>置顶</span> 
 * 
 * Mod 1.0 by <a href="http://kan.willin.org/typecho/">Willin Kan</a>, 1.0.1 by <a href="http://doufu.ru">Ryan</a>
 * @package Sticky
 * @author Jclser
 * @version 1.0.2
 * @update: 2020.03.25
 * @link http://dearfish.top/
 */
class Sticky_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Archive')->indexHandle = array('Sticky_Plugin', 'sticky');
		Typecho_Plugin::factory('Widget_Archive')->categoryHandle = array('Sticky_Plugin', 'stickyC');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $sticky_cids = new Typecho_Widget_Helper_Form_Element_Text(
          'sticky_cids', NULL, '',
          '首页置顶文章的 cid', '按照排序输入, 请以半角逗号或空格分隔');
        $form->addInput($sticky_cids);
        
        $sticky_cid =new Typecho_Widget_Helper_Form_Element_Text('sticky_cid', NULL, '', '分类置顶文章的 cid', '请以半角逗号或空格分隔');
        $form->addInput($sticky_cid);

        $sticky_html = new Typecho_Widget_Helper_Form_Element_Textarea(
          'sticky_html', NULL, "<span style='border-radius: 2px;font-weight: 400;padding: .1rem .25rem;font-size: 0.75rem;margin: 0 .25rem 0 .25rem;position: relative;top: -2px;background-color: #E5183B;color: #fff;'>置顶</span>",
          '置顶标题的 html', '这里的代码会自动插入置顶文章的标题后');
        $sticky_html->input->setAttribute('rows', '7')->setAttribute('cols', '50');
        $form->addInput($sticky_html);
		
		$sticky_cat = new Typecho_Widget_Helper_Form_Element_Radio('sticky_cat' , array('1'=>_t('开启'),'0'=>_t('关闭')),'0',_t('当前分类置顶'),_t('开启后只会在文章所属的分类中置顶'));
		$form->addInput($sticky_cat);
    }

    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    /**
     * 选取置顶文章
     * 
     * @access public
     * @param object $archive, $select
     * @return void
     */
    public static function sticky($archive, $select)
    {
        $config  = Typecho_Widget::widget('Widget_Options')->plugin('Sticky');
        $sticky_cids = $config->sticky_cids ? explode(',', strtr($config->sticky_cids, ' ', ',')) : ''; //获取至顶文章ID分隔
        if (!$sticky_cids) return;

        $pagesize = Helper::options()->pageSize;
        $db = Typecho_Db::get();
        $paded = $archive->request->get('page', 1);

        foreach($sticky_cids as $cid) {
          if ($cid && $sticky_post = $db->fetchRow($archive->select()->where('cid = ?', $cid))) {
              if ($paded == 1) {                               // 首頁 page.1 才會有置頂文章
                $sticky_post['title'] = $sticky_post['title'] . str_replace('"', '\'', $config->sticky_html);
                $archive->push($sticky_post);                  // 選取置頂的文章先壓入
                $pagesize = $pagesize - 1;
              }
              $select->where('table.contents.cid != ?', $cid); // 使文章输出不重覆
              if ($pagesize > 0) {                             // 防止列表數輸出錯誤
              $archive->parameter->pageSize = $pagesize;
              }
          }
        }
    }
    
	public static function stickyC($archive, $select)
	{
		$config  = Typecho_Widget::widget('Widget_Options')->plugin('Sticky');
        $sticky_cid = $config->sticky_cid ? explode(',', strtr($config->sticky_cid, ' ', ',')) : '';
        if (!$sticky_cid) return;

        $pagesize = Helper::options()->pageSize;
        $db = Typecho_Db::get();
        $paded = $archive->request->get('page', 1);

        foreach($sticky_cid as $cid) {
          $sticky_post = $db->fetchRow($archive->select()->where('cid = ?', $cid));
          if ($config->sticky_cat) {                           // 分類當前置頂開關
          	    $archive->widget('Widget_Archive@'.$cid, 'pageSize=1&type=post', 'cid='.$cid)->to($slug);
      	        $pattern = preg_match("/".$slug->category."/i", $_SERVER['PHP_SELF']);
          }else {
          	    $pattern = true;
          }
      	        
          if ($cid && $sticky_post && $pattern) {
              if ($paded == 1) {
                $sticky_post['title'] = $sticky_post['title'] . str_replace('"', '\'', $config->sticky_html);
                $archive->push($sticky_post);
                $pagesize = $pagesize - 1;
              }
              $select->where('table.contents.cid != ?', $cid);
              if ($pagesize > 0) {
              $archive->parameter->pageSize = $pagesize;
              }
          }

        }
	}
	

}