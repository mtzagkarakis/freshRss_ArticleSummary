<?php

class FreshExtension_ArticleSummary_Controller extends Minz_ActionController
{
  public function summarizeAction()
  {
    $this->view->_layout(false);
    // Set response header to JSON
    header('Content-Type: application/json');

    $oai_url = FreshRSS_Context::$user_conf->oai_url;
    $oai_key = FreshRSS_Context::$user_conf->oai_key;
    $oai_model = FreshRSS_Context::$user_conf->oai_model;
    $oai_prompt = FreshRSS_Context::$user_conf->oai_prompt;
    $oai_max_tokens = (int)(FreshRSS_Context::$user_conf->oai_max_tokens ?: 2048);
    $oai_temperature = (float)(FreshRSS_Context::$user_conf->oai_temperature ?: 1.0);

    if (
      $this->isEmpty($oai_url)
      || $this->isEmpty($oai_key)
      || $this->isEmpty($oai_model)
      || $this->isEmpty($oai_prompt)
    ) {
      echo json_encode(array(
        'response' => array(
          'data' => 'missing config',
          'error' => 'configuration'
        ),
        'status' => 200
      ));
      return;
    }

    $entry_id = Minz_Request::param('id');
    $entry_dao = FreshRSS_Factory::createEntryDao();
    $entry = $entry_dao->searchById($entry_id);

    if ($entry === null) {
      echo json_encode(array('status' => 404));
      return;
    }

    $content = $entry->content(); // Replace with article content

    // Process $oai_url
    $oai_url = rtrim($oai_url, '/'); // Remove trailing slash
    if (!preg_match('/\/v\d+\/?$/', $oai_url)) {
        $oai_url .= '/v1'; // If there is no version information, add /v1
    }
    // OpenAI API Input
    $successResponse = array(
      'response' => array(
        'data' => array(
          // Determine whether the URL ends with a version. If it does, no version information is added. If not, /v1 is added by default.
          "oai_url" => $oai_url . '/chat/completions',
          "oai_key" => $oai_key,
          "model" => $oai_model,
          "messages" => [
            [
              "role" => "system",
              "content" => $oai_prompt
            ],
            [
              "role" => "user",
              "content" => "input: \n" . $this->htmlToMarkdown($content),
            ]
          ],
          "max_completion_tokens" => $oai_max_tokens,
          "temperature" => $oai_temperature,
          "n" => 1 // Generate summary
        ),
        'error' => null
      ),
      'status' => 200
    );

    echo json_encode($successResponse);
    return;
  }

  public function questionAction()
  {
    $this->view->_layout(false);
    // Set response header to JSON
    header('Content-Type: application/json');

    $oai_url = FreshRSS_Context::$user_conf->oai_url;
    $oai_key = FreshRSS_Context::$user_conf->oai_key;
    $oai_model = FreshRSS_Context::$user_conf->oai_model;
    $oai_max_tokens = (int)(FreshRSS_Context::$user_conf->oai_max_tokens ?: 2048);
    $oai_temperature = (float)(FreshRSS_Context::$user_conf->oai_temperature ?: 1.0);

    // Q&A system prompt
    $qa_prompt = 'You are a helpful assistant answering questions about an article.

Instructions:
- Answer questions based ONLY on the article content provided
- Be conversational and helpful
- If the answer isn\'t in the article, say "This information is not mentioned in the article"
- Provide specific quotes or references when relevant
- Keep answers concise but complete
- Use markdown formatting when appropriate

Context: The user has read a summary and wants to dive deeper into specific aspects of the article.';

    if (
      $this->isEmpty($oai_url)
      || $this->isEmpty($oai_key)
      || $this->isEmpty($oai_model)
    ) {
      echo json_encode(array(
        'response' => array(
          'data' => 'missing config',
          'error' => 'configuration'
        ),
        'status' => 200
      ));
      return;
    }

    $entry_id = Minz_Request::param('id');
    $entry_dao = FreshRSS_Factory::createEntryDao();
    $entry = $entry_dao->searchById($entry_id);

    if ($entry === null) {
      echo json_encode(array('status' => 404));
      return;
    }

    // Get conversation history from request
    $history = json_decode(Minz_Request::param('history', '[]'), true);
    $question = Minz_Request::param('question', '');

    if ($this->isEmpty($question)) {
      echo json_encode(array(
        'response' => array(
          'data' => 'question required',
          'error' => 'validation'
        ),
        'status' => 400
      ));
      return;
    }

    $content = $entry->content();
    $articleMarkdown = $this->htmlToMarkdown($content);

    // Build messages array
    $messages = [
      [
        "role" => "system",
        "content" => $qa_prompt
      ],
      [
        "role" => "user",
        "content" => "Article content:\n\n" . $articleMarkdown
      ],
      [
        "role" => "assistant",
        "content" => "I've read the article. I'm ready to answer your questions based on its content."
      ]
    ];

    // Add conversation history
    if (!empty($history) && is_array($history)) {
      foreach ($history as $msg) {
        if (isset($msg['role']) && isset($msg['content'])) {
          $messages[] = $msg;
        }
      }
    }

    // Add current question
    $messages[] = [
      "role" => "user",
      "content" => $question
    ];

    // Process $oai_url
    $oai_url = rtrim($oai_url, '/');
    if (!preg_match('/\/v\d+\/?$/', $oai_url)) {
        $oai_url .= '/v1';
    }

    $successResponse = array(
      'response' => array(
        'data' => array(
          "oai_url" => $oai_url . '/chat/completions',
          "oai_key" => $oai_key,
          "model" => $oai_model,
          "messages" => $messages,
          "max_completion_tokens" => $oai_max_tokens,
          "temperature" => $oai_temperature,
          "n" => 1
        ),
        'error' => null
      ),
      'status' => 200
    );

    echo json_encode($successResponse);
    return;
  }

  private function isEmpty($item)
  {
    return $item === null || trim($item) === '';
  }

  private function htmlToMarkdown($content)
  {
    // Create DOMDocument object
    $dom = new DOMDocument();
    libxml_use_internal_errors(true); // Ignore HTML parsing errors
    $dom->loadHTML('<?xml encoding="UTF-8">' . $content);
    libxml_clear_errors();

    // Create XPath object
    $xpath = new DOMXPath($dom);

    // Define an anonymous function to process the node
    $processNode = function ($node, $indentLevel = 0) use (&$processNode, $xpath) {
      $markdown = '';

      // Process text nodes
      if ($node->nodeType === XML_TEXT_NODE) {
        $markdown .= trim($node->nodeValue);
      }

      // Process element nodes
      if ($node->nodeType === XML_ELEMENT_NODE) {
        switch ($node->nodeName) {
          case 'p':
          case 'div':
            foreach ($node->childNodes as $child) {
              $markdown .= $processNode($child);
            }
            $markdown .= "\n\n";
            break;
          case 'h1':
            $markdown .= "# ";
            $markdown .= $processNode($node->firstChild);
            $markdown .= "\n\n";
            break;
          case 'h2':
            $markdown .= "## ";
            $markdown .= $processNode($node->firstChild);
            $markdown .= "\n\n";
            break;
          case 'h3':
            $markdown .= "### ";
            $markdown .= $processNode($node->firstChild);
            $markdown .= "\n\n";
            break;
          case 'h4':
            $markdown .= "#### ";
            $markdown .= $processNode($node->firstChild);
            $markdown .= "\n\n";
            break;
          case 'h5':
            $markdown .= "##### ";
            $markdown .= $processNode($node->firstChild);
            $markdown .= "\n\n";
            break;
          case 'h6':
            $markdown .= "###### ";
            $markdown .= $processNode($node->firstChild);
            $markdown .= "\n\n";
            break;
          case 'a':
            // $markdown .= "[";
            // $markdown .= $processNode($node->firstChild);
            // $markdown .= "](" . $node->getAttribute('href') . ")";
            $markdown .= "`";
            $markdown .= $processNode($node->firstChild);
            $markdown .= "`";
            break;
          case 'img':
            $alt = $node->getAttribute('alt');
            $markdown .= "img: `" . $alt . "`";
            break;
          case 'strong':
          case 'b':
            $markdown .= "**";
            $markdown .= $processNode($node->firstChild);
            $markdown .= "**";
            break;
          case 'em':
          case 'i':
            $markdown .= "*";
            $markdown .= $processNode($node->firstChild);
            $markdown .= "*";
            break;
          case 'ul':
          case 'ol':
            $markdown .= "\n";
            foreach ($node->childNodes as $child) {
              if ($child->nodeName === 'li') {
                $markdown .= str_repeat("  ", $indentLevel) . "- ";
                $markdown .= $processNode($child, $indentLevel + 1);
                $markdown .= "\n";
              }
            }
            $markdown .= "\n";
            break;
          case 'li':
            $markdown .= str_repeat("  ", $indentLevel) . "- ";
            foreach ($node->childNodes as $child) {
              $markdown .= $processNode($child, $indentLevel + 1);
            }
            $markdown .= "\n";
            break;
          case 'br':
            $markdown .= "\n";
            break;
          case 'audio':
          case 'video':
            $alt = $node->getAttribute('alt');
            $markdown .= "[" . ($alt ? $alt : 'Media') . "]";
            break;
          default:
            // Tags not considered, only the text inside is kept
            foreach ($node->childNodes as $child) {
              $markdown .= $processNode($child);
            }
            break;
        }
      }

      return $markdown;
    };

    // Get all nodes
    $nodes = $xpath->query('//body/*');

    // Process all nodes
    $markdown = '';
    foreach ($nodes as $node) {
      $markdown .= $processNode($node);
    }

    // Remove extra line breaks
    $markdown = preg_replace('/(\n){3,}/', "\n\n", $markdown);
    
    return $markdown;
  }

}
