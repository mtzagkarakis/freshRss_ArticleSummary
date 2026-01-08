# FreshRSS Article Summary Extension - Context

## Model Usage Guidelines

**Default Model: Claude Sonnet 4.5**
- Use Sonnet (claude-sonnet-4-5-20250929) for all coding tasks, debugging, refactoring, and regular development work
- Sonnet provides excellent code quality at good speed and cost efficiency

**When to Use Opus 4.5:**
- Use Opus (claude-opus-4-5-20251101) for planning larger changes or architectural decisions
- When using the Task tool with `subagent_type="Plan"`, specify `model="opus"` for complex planning tasks
- Examples of when to use Opus:
  - Designing new features with multiple components
  - Planning major refactoring across multiple files
  - Architectural decisions that affect the project structure
  - Complex problem-solving requiring deep reasoning

**Example:**
```
For large architectural planning:
Task(subagent_type="Plan", model="opus", prompt="Plan implementation of...")

For regular coding:
Use default Sonnet model (no need to specify)
```

## Project Overview
This is a comprehensive FreshRSS extension that enhances the RSS reading experience with three main features:

1. **AI-Powered Summarization**: Generate concise article summaries using any OpenAI-compatible API
2. **Interactive Q&A**: Ask questions about articles and have conversations with AI about the content
3. **Reader Mode**: View articles in a clean, distraction-free format using php-readability

The extension is designed for self-hosted FreshRSS installations and supports any language model API that conforms to the OpenAI API specification.

## Origin & History
- **Original Source**: Forked from https://github.com/LiangWei88/xExtension-ArticleSummary
- **Reason for Fork**: The original version was not working due to outdated OpenAI API references and deprecated model names
- **Major Changes from Original**:
  - ✨ **NEW**: Interactive Q&A chat interface with conversation history
  - ✨ **NEW**: Reader Mode integration with php-readability library
  - ✨ **NEW**: Collapsible UI sections for better space management
  - ✨ **NEW**: True streaming responses via Server-Sent Events (SSE) for word-by-word display
  - ✨ **NEW**: Session-based chat history (maintains last 3 Q&A pairs)
  - ✨ **NEW**: Direct article links with source domain display
  - Updated to support modern LLM models (GPT-4, Claude 3.5, etc.)
  - Added configurable `max_completion_tokens` parameter (default: 2048)
  - Added configurable `temperature` parameter (default: 1.0)
  - Improved default summarization prompt for better results
  - Fixed API compatibility issues
  - Enhanced UI with better organization and styling

## Technology Stack
- **Backend**: PHP (FreshRSS Minz framework)
- **Frontend**: Vanilla JavaScript (no framework dependencies)
- **Frontend Libraries** (bundled):
  - Axios (~52KB) - HTTP client for API requests
  - Marked (v15.0.1, 38KB) - Markdown to HTML converter
- **Backend Libraries** (bundled):
  - php-readability (fivefilters/readability.php) - Content extraction
  - Masterminds/HTML5 - HTML5 parser
  - PSR/Log - Logging interface
- **API**: OpenAI-compatible API endpoints (OpenAI, Azure OpenAI, local LLMs, etc.)

## FreshRSS Plugin Architecture
FreshRSS uses an extension system based on PHP classes:

1. **Main Extension Class** (`extension.php`):
   - Extends `Minz_Extension`
   - Registers hooks to inject functionality into FreshRSS
   - Key hook: `entry_before_display` - runs before each article is displayed
   - Registers custom controllers and assets (CSS, JS)

2. **Controllers** (`Controllers/`):
   - Named pattern: `FreshExtension_{ExtensionName}_Controller`
   - Handle HTTP requests and business logic
   - Return JSON responses for AJAX calls

3. **Configuration** (`configure.phtml`):
   - HTML template for extension settings page
   - Accessible from FreshRSS admin interface

4. **Metadata** (`metadata.json`):
   - Extension name, author, version, description
   - `type: "user"` means it's a per-user extension (each user has their own config)

## Project Structure
```
.
├── extension.php                          # Main extension class
├── metadata.json                          # Extension metadata
├── configure.phtml                        # Configuration UI template
├── test-libraries.html                    # Test file for validating axios/marked versions
├── CLAUDE.md                              # Project documentation for AI assistants
├── README.md                              # User-facing documentation
├── Controllers/
│   └── ArticleSummaryController.php      # Handles all API requests:
│                                          #   - summarizeAction() - article summarization
│                                          #   - questionAction() - Q&A chat
│                                          #   - readabilityAction() - reader mode content
├── static/
│   ├── script.js                          # Frontend logic:
│   │                                      #   - Summarization with streaming
│   │                                      #   - Q&A chat interface
│   │                                      #   - Reader mode toggle
│   │                                      #   - Collapse/expand UI
│   ├── style.css                          # Complete UI styling
│   ├── axios.js                           # HTTP client library (bundled)
│   └── marked.js                          # Markdown parser library (bundled)
└── libs/                                  # PHP dependencies (bundled)
    ├── autoload.php                       # Composer-style autoloader
    ├── readability/                       # php-readability library
    ├── masterminds/                       # HTML5 parser
    └── psr/                               # PSR logging interfaces
```

## Key Files & Their Purposes

### `extension.php`
- **Purpose**: Main extension entry point
- **Key Methods**:
  - `init()`: Registers hooks, controllers, and assets (CSS, JS, libraries)
  - `addSummaryButton()`: Hook callback that injects complete UI into each article:
    - AI Summary section with Summarize button
    - Q&A chat interface with input area
    - Collapse/Expand controls
    - Reader Mode toggle button
    - Direct article link
  - `handleConfigureAction()`: Saves user configuration settings
- **Configuration Parameters**:
  - `oai_url`: API base URL (e.g., `https://api.openai.com/`)
  - `oai_key`: API authentication key
  - `oai_model`: Model name (e.g., `gpt-4o-mini`, `claude-3-5-sonnet-20241022`)
  - `oai_prompt`: System prompt for summarization
  - `oai_max_tokens`: Maximum completion tokens (default: 2048)
  - `oai_temperature`: Output randomness/creativity 0.0-2.0 (default: 1.0)
  - `oai_readability_default`: Enable Reader Mode by default (default: false)

### `Controllers/ArticleSummaryController.php`
- **Purpose**: Handles all backend API requests
- **Actions**:

  1. **`summarizeAction()`** - Article summarization
     - Validates configuration (URL, key, model, prompt)
     - Fetches article by ID from FreshRSS database
     - Converts HTML content to Markdown using `htmlToMarkdown()`
     - Prepares OpenAI API request payload
     - Returns JSON with API parameters for frontend to call

  2. **`questionAction()`** - Q&A chat
     - Validates configuration
     - Fetches article content
     - Receives question and conversation history from frontend
     - Builds message array with:
       - System prompt (Q&A specific)
       - Article content as context
       - Conversation history (last 3 Q&A pairs)
       - Current question
     - Returns API parameters for frontend to call

  3. **`readabilityAction()`** - Reader Mode
     - Fetches article by ID
     - Loads php-readability library
     - Wraps article HTML in proper document structure
     - Processes with Readability library (aggressive extraction mode)
     - Returns cleaned HTML content
     - Handles errors gracefully with detailed messages

- **Key Features**:
  - HTML to Markdown conversion (`htmlToMarkdown()` method) for better LLM processing
  - OpenAI-compatible API format
  - Uses `max_completion_tokens` (new API parameter) instead of deprecated `max_tokens`
  - Conversation history management for Q&A
  - Bundled Readability library (no Composer required)

### `static/script.js`
- **Purpose**: Complete frontend logic for all features
- **Main Functions**:

  **Summarization**:
  - `configureSummarizeButtons()` - Sets up event listeners
  - `summarizeButtonClick()` - Handles summarize button clicks
  - `sendOpenAIRequest()` - Calls LLM API with streaming enabled, parses SSE format
  - `setOaiState()` - Manages loading/error/success states

  **Q&A Chat**:
  - `configureQAButtons()` - Sets up chat event listeners (click, Enter key)
  - `sendQuestion()` - Handles question submission
  - `sendQuestionRequest()` - Calls LLM API with streaming, parses SSE, updates progressively
  - `loadChatHistory()` / `saveChatHistory()` - Session storage management
  - `displayUserMessage()` / `displayAssistantMessage()` - Message rendering
  - `getChatStorageKey()` - Unique storage key per article

  **Reader Mode**:
  - `configureReadabilityButtons()` - Sets up toggle event listeners
  - `toggleReadabilityMode()` - Switches between original and readable content
  - Caches readable content after first load

  **UI Controls**:
  - `configureCollapseButtons()` - Handles expand/collapse functionality

- **Key Features**:
  - True SSE (Server-Sent Events) streaming for word-by-word response display
  - Incremental text accumulation with buffer handling for incomplete lines
  - Session-based chat history (maintains last 3 Q&A pairs per article)
  - Real-time markdown rendering as content streams in
  - Comprehensive error handling with detailed console logging
  - CSRF token handling for security
  - Event delegation for dynamic content

## API Integration

### OpenAI API Format
```json
{
  "model": "gpt-5-mini",
  "messages": [
    {"role": "system", "content": "prompt"},
    {"role": "user", "content": "article content"}
  ],
  "max_completion_tokens": 2048,
  "temperature": 0.7,
  "n": 1
}
```

The extension uses the OpenAI-compatible API format, which means it works with OpenAI's API and any other service that implements the same specification.

## How It Works

### Summarization Flow
1. **Initialization**: Extension registers `entry_before_display` hook
2. **UI Injection**: For each article, complete UI is injected (summary section, chat, reader mode toggle)
3. **User Click**: User clicks "Summarize" button
4. **Backend Request**: JavaScript calls `ArticleSummaryController::summarizeAction()`
5. **Content Processing**: Controller fetches article, converts HTML to Markdown
6. **API Preparation**: Returns API endpoint and payload to frontend
7. **Frontend API Call**: JavaScript makes streaming API call with `stream: true` parameter
8. **SSE Parsing**: Reads Server-Sent Events line-by-line (format: `data: {...}`)
9. **Progressive Display**: Each word/chunk is accumulated and rendered with Markdown in real-time
10. **Completion**: Stream ends with `data: [DONE]`, final state updated

### Q&A Flow
1. **User Opens Chat**: Clicks "Ask a Question" button
2. **Chat Interface**: Input area becomes visible, loads any existing history from session storage
3. **User Types Question**: Enters question and presses Enter or clicks Send
4. **Backend Request**: JavaScript calls `ArticleSummaryController::questionAction()` with question and history
5. **Context Building**: Controller builds conversation with article content + history + question
6. **API Call**: Frontend calls LLM API with full context and `stream: true`
7. **SSE Parsing**: Reads streaming response line-by-line, accumulates text progressively
8. **Progressive Display**: Message updates word-by-word in chat interface with markdown rendering
9. **History Update**: Complete conversation saved to session storage (last 3 Q&A pairs)

### Reader Mode Flow
1. **User Clicks Toggle**: Clicks "Toggle Reader Mode" button
2. **Check Cache**: If readable content already loaded, toggle instantly
3. **First Load**: If not cached, calls `ArticleSummaryController::readabilityAction()`
4. **Content Processing**: Backend uses php-readability to extract main content
5. **Display**: Shows cleaned article, hides original
6. **Toggle Back**: User can switch back to original anytime

## Configuration Notes

- **URL Format**: Base URL should NOT include version path (e.g., use `https://api.openai.com/` not `https://api.openai.com/v1`)
- The extension automatically appends `/v1/chat/completions` to the base URL
- All configuration is per-user (stored in `FreshRSS_Context::$user_conf`)
- Works with any OpenAI-compatible API endpoint

## Development Notes

### FreshRSS Framework
- Uses Minz framework (custom MVC framework)
- Controllers must follow naming convention: `FreshExtension_{Name}_Controller`
- Extension hooks into `entry_before_display` for UI injection
- Per-user configuration stored in `FreshRSS_Context::$user_conf`

### Security
- CSP policies configured to allow external API calls (`default-src: '*'`)
- All requests include CSRF token validation
- API keys never sent to frontend (backend prepares requests)

### Performance
- HTML to Markdown conversion reduces token usage significantly
- Readable content cached after first load
- Chat history limited to last 3 Q&A pairs per article per session
- Event delegation used for efficient event handling

### Dependencies Management
- All frontend libraries bundled (no CDN dependencies)
- All backend libraries bundled (no Composer required at runtime)
- Use `test-libraries.html` to validate library updates before deployment

## Testing

### Library Version Testing

**Current Versions:**
- **axios.js**: ~52KB (stable version, exact version not exposed)
- **marked.js**: v15.0.1 (38KB)

**Test File:** `test-libraries.html`
- Located in project root
- Used to validate library updates before deployment
- Tests the exact APIs used in production: `axios.post()` and `marked.parse()`
- Makes real HTTP requests to verify functionality
- Compare old vs new versions side-by-side

**How to Test Library Updates:**
1. Download new library versions and save as `.new.js` (e.g., `axios.new.js`, `marked.new.js`)
2. Open `test-libraries.html` in a browser
3. Test old versions first to establish baseline
4. Test new versions to verify compatibility
5. If tests pass, rename `.new.js` files to replace old versions
6. If tests fail, investigate breaking changes before updating

**Important:** The extension uses basic, stable APIs that rarely break, but always test before updating in production

## Common Issues & Solutions

1. **API Compatibility**: Uses modern `max_completion_tokens` parameter (not deprecated `max_tokens`)
2. **Model Names**: Supports any modern LLM (GPT-4, Claude 3.5, local models via LM Studio/Ollama)
3. **Streaming**: Requires API to support Server-Sent Events (SSE) streaming format
4. **CORS**: If using local LLM server, ensure CORS headers allow FreshRSS origin
5. **Readability**: Some articles may not extract well - original is always available
6. **Chat History**: Session storage only - cleared when browser session ends

## Feature Status & Roadmap

### ✅ Implemented
- AI-powered article summarization
- Interactive Q&A chat with conversation history
- Reader Mode with php-readability
- Collapsible UI sections
- True Server-Sent Events (SSE) streaming with word-by-word display
- Session-based chat persistence
- Markdown formatting with real-time rendering
- Direct article links
- Configurable prompts, tokens, temperature

### 🔄 Future Considerations
- Summary caching to reduce API costs
- Persistent chat history (database storage)
- Retry logic for failed API requests
- Batch summarization for multiple articles
- Export summaries/chats
- Keyboard shortcuts
- Summary quality ratings/feedback
- Support for different summary lengths (short/medium/long)
- Integration with FreshRSS search
