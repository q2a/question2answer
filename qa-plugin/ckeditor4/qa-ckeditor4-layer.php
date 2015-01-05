<?php
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}
class qa_html_theme_layer extends qa_html_theme_base {
	function head_css() {
		qa_html_theme_base::head_css();
		if(qa_opt('ckeditor4_inline_editing')) {
			if($this->template == 'question' && is_array(@$this->content['q_view']['form']['buttons']['edit'])) {
				$this->output('<LINK REL="stylesheet" TYPE="text/css" HREF="'.qa_path_to_root().'qa-plugin/ckeditor4/qa-ckeditor4-inline.css"/>');
			}
		}
	}
	function head_script() {
		qa_html_theme_base::head_script();
		if(qa_opt('ckeditor4_inline_editing')) {
			if($this->template == 'question' && is_array(@$this->content['q_view']['form']['buttons']['edit'])) {
				$this->output('<script type="text/javascript">');
				$this->output('qa_ckeditor4_config.toolbar.unshift(Array("savebtn"));');
				$this->output('</script>');
			}
		}
	}
	function q_view_content($q_view) {
		if(qa_opt('ckeditor4_inline_editing')) {
			if(@$q_view['raw']['editable'] && is_array(@$q_view['form']['buttons']['edit'])) {
				$this->output('<div class="qa-q-view-content">');
				$id = strtolower($q_view['raw']['type'].$q_view['raw']['postid'].'_content');
				$this->output('<a name="'.$q_view['raw']['postid'].'"></a>');
				$this->output('<div class="entry-content cke_editable cke_editable_inline" contenteditable="true" id="'.$id.'">'.$q_view['raw']['content'].'</div>');
				$this->output('<script>');
				$this->output('var editor = CKEDITOR.inline( document.getElementById("'.$id.'"),  qa_ckeditor4_config);');
				$this->output('</script>');
				
				$this->output('</div>');
			} else
				qa_html_theme_base::q_view_content($q_view);
		} else
			qa_html_theme_base::q_view_content($q_view);
	}
	function a_item_content($a_item) {
		if(qa_opt('ckeditor4_inline_editing')) {
			if(@$a_item['raw']['editable'] && is_array(@$a_item['form']['buttons']['edit'])) {
				$this->output('<div class="qa-a-item-content">');
				$id = strtolower($a_item['raw']['type'].$a_item['raw']['postid'].'_content');
				$this->output('<a name="'.$a_item['raw']['postid'].'"></a>');
				$this->output('<div class="entry-content cke_editable cke_editable_inline" contenteditable="true" id="'.$id.'">'.$a_item['raw']['content'].'</div>');
				$this->output('<script>');
				$this->output('var editor = CKEDITOR.inline( document.getElementById("'.$id.'"), qa_ckeditor4_config);');
				$this->output('</script>');
				
				$this->output('</div>');
			} else
				qa_html_theme_base::a_item_content($a_item);
		} else
			qa_html_theme_base::a_item_content($a_item);
	}
}

/*
	Omit PHP closing tag to help avoid accidental output
*/