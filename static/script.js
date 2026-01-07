if (document.readyState && document.readyState !== 'loading') {
  configureSummarizeButtons();
  configureQAButtons();
} else {
  document.addEventListener('DOMContentLoaded', function() {
    configureSummarizeButtons();
    configureQAButtons();
  }, false);
}

function configureSummarizeButtons() {
  document.getElementById('global').addEventListener('click', function (e) {
    for (var target = e.target; target && target != this; target = target.parentNode) {

      if (target.matches('.flux_header')) {
        target.nextElementSibling.querySelector('.oai-summary-btn').innerHTML = 'Summarize'
      }

      if (target.matches('.oai-summary-btn')) {
        e.preventDefault();
        e.stopPropagation();
        if (target.dataset.request) {
          summarizeButtonClick(target);
        }
        break;
      }
    }
  }, false);
}

function configureQAButtons() {
  document.getElementById('global').addEventListener('click', function (e) {
    for (var target = e.target; target && target != this; target = target.parentNode) {

      // Toggle chatbox when Q&A button is clicked
      if (target.matches('.oai-qa-button')) {
        e.preventDefault();
        e.stopPropagation();
        const container = target.closest('.oai-summary-wrap');
        const chatbox = container.querySelector('.oai-chatbox');
        const entryId = container.dataset.entryId;

        chatbox.classList.toggle('active');

        // Load chat history when opening
        if (chatbox.classList.contains('active')) {
          loadChatHistory(entryId, container);
        }
        break;
      }

      // Send question when send button is clicked
      if (target.matches('.oai-chat-send')) {
        e.preventDefault();
        e.stopPropagation();
        const container = target.closest('.oai-summary-wrap');
        const input = container.querySelector('.oai-chat-input');
        const question = input.value.trim();

        if (question) {
          sendQuestion(container, question);
          input.value = '';
        }
        break;
      }
    }
  }, false);

  // Handle Enter key in chat input
  document.getElementById('global').addEventListener('keydown', function(e) {
    if (e.target.matches('.oai-chat-input') && e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      const container = e.target.closest('.oai-summary-wrap');
      const sendBtn = container.querySelector('.oai-chat-send');
      sendBtn.click();
    }
  }, false);
}

function setOaiState(container, statusType, statusMsg, summaryText) {
  const button = container.querySelector('.oai-summary-btn');
  const content = container.querySelector('.oai-summary-content');
  // Set different states based on statusType
  if (statusType === 1) {
    container.classList.add('oai-loading');
    container.classList.remove('oai-error');
    content.innerHTML = statusMsg;
    button.disabled = true;
  } else if (statusType === 2) {
    container.classList.remove('oai-loading');
    container.classList.add('oai-error');
    content.innerHTML = statusMsg;
    button.disabled = false;
  } else {
    container.classList.remove('oai-loading');
    container.classList.remove('oai-error');
    if (statusMsg === 'finish'){
      button.disabled = false;
    }
  }

  console.log(content);
  
  if (summaryText) {
    content.innerHTML = summaryText.replace(/(?:\r\n|\r|\n)/g, '<br>');
  }
}

async function summarizeButtonClick(target) {
  var container = target.parentNode;
  if (container.classList.contains('oai-loading')) {
    return;
  }

  setOaiState(container, 1, 'Loading...', null);

  // This is the address where PHP gets the parameters
  var url = target.dataset.request;
  var data = {
    ajax: true,
    _csrf: context.csrf
  };

  try {
    const response = await axios.post(url, data, {
      headers: {
        'Content-Type': 'application/json'
      }
    });

    const xresp = response.data;
    console.log(xresp);

    if (response.status !== 200 || !xresp.response || !xresp.response.data) {
      throw new Error('Request Failed');
    }

    if (xresp.response.error) {
      setOaiState(container, 2, xresp.response.data, null);
    } else {
      // Parse parameters returned by PHP
      const oaiParams = xresp.response.data;
      await sendOpenAIRequest(container, oaiParams);
    }
  } catch (error) {
    console.error(error);
    setOaiState(container, 2, 'Request Failed', null);
  }
}

async function sendOpenAIRequest(container, oaiParams) {
  try {
    let body = JSON.parse(JSON.stringify(oaiParams));
    delete body['oai_url'];
    delete body['oai_key'];
    const response = await fetch(oaiParams.oai_url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${oaiParams.oai_key}`
      },
      body: JSON.stringify(body)
    });

    if (!response.ok) {
      throw new Error('Request Failed');
    }

    const reader = response.body.getReader();
    const decoder = new TextDecoder('utf-8');

    while (true) {
      const { done, value } = await reader.read();
      if (done) {
        setOaiState(container, 0, 'finish', null);
        break;
      }

      const chunk = decoder.decode(value, { stream: true });
      const text = JSON.parse(chunk)?.choices[0]?.message?.content || ''
      setOaiState(container, 0, null, marked.parse(text));
    }
  } catch (error) {
    console.error(error);
    setOaiState(container, 2, 'Request Failed', null);
  }
}

// Q&A Chat Functions
function getChatStorageKey(entryId) {
  return `oai-chat-history-${entryId}`;
}

function loadChatHistory(entryId, container) {
  const storageKey = getChatStorageKey(entryId);
  const history = JSON.parse(sessionStorage.getItem(storageKey) || '[]');
  const messagesContainer = container.querySelector('.oai-chat-messages');

  // Clear existing messages
  messagesContainer.innerHTML = '';

  // Display each message from history
  history.forEach(msg => {
    if (msg.role === 'user') {
      displayUserMessage(container, msg.content, false);
    } else if (msg.role === 'assistant') {
      displayAssistantMessage(container, msg.content, false);
    }
  });
}

function saveChatHistory(entryId, history) {
  const storageKey = getChatStorageKey(entryId);
  // Keep only last 6 messages (3 Q&A pairs)
  const trimmedHistory = history.slice(-6);
  sessionStorage.setItem(storageKey, JSON.stringify(trimmedHistory));
}

function displayUserMessage(container, message, scroll = true) {
  const messagesContainer = container.querySelector('.oai-chat-messages');
  const messageDiv = document.createElement('div');
  messageDiv.className = 'oai-chat-message user';
  messageDiv.textContent = message;
  messagesContainer.appendChild(messageDiv);

  if (scroll) {
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
  }
}

function displayAssistantMessage(container, message, scroll = true) {
  const messagesContainer = container.querySelector('.oai-chat-messages');
  const messageDiv = document.createElement('div');
  messageDiv.className = 'oai-chat-message assistant';
  messageDiv.innerHTML = marked.parse(message);
  messagesContainer.appendChild(messageDiv);

  if (scroll) {
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
  }
}

function showLoadingMessage(container) {
  const messagesContainer = container.querySelector('.oai-chat-messages');
  const loadingDiv = document.createElement('div');
  loadingDiv.className = 'oai-chat-loading';
  loadingDiv.textContent = 'Thinking...';
  messagesContainer.appendChild(loadingDiv);
  messagesContainer.scrollTop = messagesContainer.scrollHeight;
  return loadingDiv;
}

function removeLoadingMessage(loadingDiv) {
  if (loadingDiv && loadingDiv.parentNode) {
    loadingDiv.parentNode.removeChild(loadingDiv);
  }
}

async function sendQuestion(container, question) {
  const entryId = container.dataset.entryId;
  const qaButton = container.querySelector('.oai-qa-button');
  const sendBtn = container.querySelector('.oai-chat-send');
  const input = container.querySelector('.oai-chat-input');

  // Disable input while processing
  sendBtn.disabled = true;
  input.disabled = true;

  // Display user message
  displayUserMessage(container, question);

  // Show loading indicator
  const loadingDiv = showLoadingMessage(container);

  // Get conversation history
  const storageKey = getChatStorageKey(entryId);
  const history = JSON.parse(sessionStorage.getItem(storageKey) || '[]');

  // Get request URL from the button
  const url = qaButton.dataset.request;

  try {
    // Send request to backend
    const response = await axios.post(url, {
      ajax: true,
      _csrf: context.csrf,
      question: question,
      history: JSON.stringify(history)
    }, {
      headers: {
        'Content-Type': 'application/json'
      }
    });

    const xresp = response.data;
    console.log(xresp);

    if (response.status !== 200 || !xresp.response || !xresp.response.data) {
      throw new Error('Request Failed');
    }

    if (xresp.response.error) {
      removeLoadingMessage(loadingDiv);
      displayAssistantMessage(container, 'Error: ' + xresp.response.data);
    } else {
      // Get API parameters from backend
      const oaiParams = xresp.response.data;

      // Remove loading message
      removeLoadingMessage(loadingDiv);

      // Send to OpenAI API and get response
      await sendQuestionRequest(container, oaiParams, entryId, question, history);
    }
  } catch (error) {
    console.error(error);
    removeLoadingMessage(loadingDiv);
    displayAssistantMessage(container, 'Error: Request failed. Please try again.');
  } finally {
    // Re-enable input
    sendBtn.disabled = false;
    input.disabled = false;
    input.focus();
  }
}

async function sendQuestionRequest(container, oaiParams, entryId, question, history) {
  try {
    let body = JSON.parse(JSON.stringify(oaiParams));
    delete body['oai_url'];
    delete body['oai_key'];

    const response = await fetch(oaiParams.oai_url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${oaiParams.oai_key}`
      },
      body: JSON.stringify(body)
    });

    if (!response.ok) {
      throw new Error('Request Failed');
    }

    const reader = response.body.getReader();
    const decoder = new TextDecoder('utf-8');
    let fullResponse = '';

    while (true) {
      const { done, value } = await reader.read();
      if (done) {
        // Save conversation to history
        const newHistory = [...history,
          { role: 'user', content: question },
          { role: 'assistant', content: fullResponse }
        ];
        saveChatHistory(entryId, newHistory);
        break;
      }

      const chunk = decoder.decode(value, { stream: true });
      const text = JSON.parse(chunk)?.choices[0]?.message?.content || '';
      fullResponse = text;

      // Update the last message instead of creating new ones
      const messagesContainer = container.querySelector('.oai-chat-messages');
      let lastMessage = messagesContainer.querySelector('.oai-chat-message.assistant:last-child');

      if (!lastMessage) {
        displayAssistantMessage(container, fullResponse);
      } else {
        lastMessage.innerHTML = marked.parse(fullResponse);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
      }
    }
  } catch (error) {
    console.error(error);
    displayAssistantMessage(container, 'Error: Request failed. Please try again.');
  }
}
