# FreshRSS Article Summary Extension - Context

## Project Overview
This is a FreshRSS extension that adds AI-powered article summarization capabilities to FreshRSS, a self-hosted RSS feed aggregator. The extension allows users to generate summaries of articles on-demand using any language model API that conforms to the OpenAI API specification.

## Origin & History
- **Original Source**: Forked from https://github.com/LiangWei88/xExtension-ArticleSummary
- **Reason for Fork**: The original version was not working due to outdated OpenAI API references and deprecated model names
- **Major Changes**:
  - Updated to support GPT-5 and newer models
  - Added configurable `max_tokens` parameter (default: 2048)
  - Added configurable `temperature` parameter (default: 0.7)
  - Fixed API compatibility issues
  - Updated from GPT-3.5 references to GPT-5+

## Technology Stack
- **Backend**: PHP
- **Frontend**: Vanilla JavaScript
- **Dependencies**:
  - Axios (HTTP client for browser)
  - Marked (Markdown to HTML converter)
- **API**: OpenAI-compatible API endpoints

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
├── Controllers/
│   └── ArticleSummaryController.php      # Handles summarization requests
└── static/
    ├── script.js                          # Frontend logic for summarization
    ├── style.css                          # Styling for summary UI
    ├── axios.js                           # HTTP client library
    └── marked.js                          # Markdown parser library
```

## Key Files & Their Purposes

### `extension.php`
- **Purpose**: Main extension entry point
- **Key Methods**:
  - `init()`: Registers hooks, controllers, and assets
  - `addSummaryButton()`: Hook callback that injects summary button into each article
  - `handleConfigureAction()`: Saves user configuration settings
- **Configuration Parameters**:
  - `oai_url`: API base URL (e.g., `https://api.openai.com/`)
  - `oai_key`: API authentication key
  - `oai_model`: Model name (e.g., `gpt-5-mini`, `gpt-5-turbo`)
  - `oai_prompt`: System prompt for summarization
  - `oai_max_tokens`: Maximum tokens for summary output (default: 2048)
  - `oai_temperature`: Output randomness/creativity 0.0-2.0 (default: 0.7)

### `Controllers/ArticleSummaryController.php`
- **Purpose**: Handles article summarization requests
- **Main Action**: `summarizeAction()`
  - Validates configuration
  - Fetches article by ID
  - Converts HTML content to Markdown
  - Prepares API request payload
  - Returns JSON with API parameters for frontend to call
- **Key Features**:
  - HTML to Markdown conversion for better LLM processing
  - OpenAI-compatible API format
  - Uses `max_completion_tokens` (new API parameter) instead of deprecated `max_tokens`

### `static/script.js`
- **Purpose**: Frontend logic for summarization
- **Functionality**:
  - Handles summary button clicks
  - Makes API calls to configured endpoint
  - Displays summaries using Marked library
  - Handles errors and loading states

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

1. **Initialization**: Extension registers `entry_before_display` hook
2. **Button Injection**: For each article, a "summarize" button is injected into the content
3. **User Click**: User clicks summary button on an article
4. **Backend Request**: JavaScript calls `ArticleSummaryController::summarizeAction()`
5. **Content Processing**: Controller fetches article, converts HTML to Markdown
6. **API Call Preparation**: Returns API endpoint and payload to frontend
7. **Frontend API Call**: JavaScript makes actual API call to configured LLM endpoint
8. **Display**: Summary is rendered as Markdown and displayed below button

## Configuration Notes

- **URL Format**: Base URL should NOT include version path (e.g., use `https://api.openai.com/` not `https://api.openai.com/v1`)
- The extension automatically appends `/v1/chat/completions` to the base URL
- All configuration is per-user (stored in `FreshRSS_Context::$user_conf`)
- Works with any OpenAI-compatible API endpoint

## Development Notes

- FreshRSS uses the Minz framework (custom MVC framework)
- Controllers must follow naming convention: `FreshExtension_{Name}_Controller`
- CSP policies may need adjustment for external API calls
- HTML content is converted to Markdown to reduce token usage and improve LLM understanding

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

1. **Old API References**: This fork fixes deprecated OpenAI API parameters
2. **Model Names**: Updated to support GPT-5+ models; older model names may not work
3. **Token Limits**: Now configurable via `max_tokens` setting
4. **Temperature Control**: Added temperature parameter for output control

## Future Considerations

- Add support for streaming responses
- Cache summaries to avoid repeated API calls
- Add retry logic for failed API requests
- Support for additional LLM providers
- Batch summarization for multiple articles
