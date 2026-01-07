<?php
class ArticleSummaryExtension extends Minz_Extension
{


  protected array $csp_policies = [
    'default-src' => '*',
  ];

  public function init()
  {
    $this->registerHook('entry_before_display', array($this, 'addSummaryButton'));
    $this->registerController('ArticleSummary');
    Minz_View::appendStyle($this->getFileUrl('style.css', 'css'));
    Minz_View::appendScript($this->getFileUrl('axios.js', 'js'));
    Minz_View::appendScript($this->getFileUrl('marked.js', 'js'));
    Minz_View::appendScript($this->getFileUrl('script.js', 'js'));
  }

  public function addSummaryButton($entry)
  {
    $url_summary = Minz_Url::display(array(
      'c' => 'ArticleSummary',
      'a' => 'summarize',
      'params' => array(
        'id' => $entry->id()
      )
    ));

    $url_question = Minz_Url::display(array(
      'c' => 'ArticleSummary',
      'a' => 'question',
      'params' => array(
        'id' => $entry->id()
      )
    ));

    $entry->_content(
      '<div class="oai-summary-wrap" data-entry-id="' . $entry->id() . '">'
      . '<button data-request="' . $url_summary . '" class="oai-summary-btn"></button>'
      . '<div class="oai-summary-content"></div>'
      . '<button data-request="' . $url_question . '" class="oai-qa-button">Ask a Question</button>'
      . '<div class="oai-chatbox">'
      . '<div class="oai-chat-messages"></div>'
      . '<div class="oai-chat-input-area">'
      . '<textarea class="oai-chat-input" placeholder="Ask a question about this article..." rows="2"></textarea>'
      . '<button class="oai-chat-send">Send</button>'
      . '</div>'
      . '</div>'
      . '</div>'
      . $entry->content()
    );
    return $entry;
  }

  public function handleConfigureAction()
  {
    if (Minz_Request::isPost()) {
      FreshRSS_Context::$user_conf->oai_url = Minz_Request::param('oai_url', '');
      FreshRSS_Context::$user_conf->oai_key = Minz_Request::param('oai_key', '');
      FreshRSS_Context::$user_conf->oai_model = Minz_Request::param('oai_model', '');
      FreshRSS_Context::$user_conf->oai_prompt = Minz_Request::param('oai_prompt', '');
      FreshRSS_Context::$user_conf->oai_max_tokens = (int)Minz_Request::param('oai_max_tokens', 2048);
      FreshRSS_Context::$user_conf->oai_temperature = (float)Minz_Request::param('oai_temperature', 1.0);
      FreshRSS_Context::$user_conf->save();
    }
  }
}
