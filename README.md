# FreshRSS Article Summary Extension

- [English README](README.md)

A comprehensive FreshRSS extension that adds AI-powered article summarization, interactive Q&A, and reader mode to your RSS feeds. This extension integrates seamlessly with any OpenAI-compatible API to enhance your reading experience.

## Features

### 🤖 AI Summarization
- **One-Click Summaries**: Generate concise article summaries with a single click
- **True Streaming**: Real-time word-by-word display as the AI generates responses (SSE streaming)
- **Configurable Prompts**: Customize the summarization style with your own system prompts
- **Advanced Controls**: Fine-tune output with max tokens and temperature settings

### 💬 Interactive Q&A
- **Ask Questions**: Chat with AI about article content through an interactive interface
- **Conversation History**: Maintains context across multiple questions (last 3 Q&A pairs per session)
- **Contextual Answers**: AI responds based strictly on article content
- **Streaming Chat**: Responses appear word-by-word in real-time
- **Markdown Formatting**: Rich text responses with proper formatting

### 📖 Reader Mode
- **Clean Reading View**: Toggle between original and distraction-free article content
- **Readability Integration**: Uses php-readability library for content extraction
- **Smart Extraction**: Removes ads, navigation, and boilerplate automatically
- **Persistent Toggle**: Switch back and forth without reloading content

### 🎨 User Interface
- **Collapsible Sections**: Minimize AI summary sections to save screen space
- **Direct Article Links**: Quick access to original article with source domain display
- **Responsive Design**: Clean, modern interface that adapts to FreshRSS theme
- **Error Handling**: Clear feedback for configuration issues or API errors

### ⚙️ Configuration
- **OpenAI-Compatible APIs**: Works with OpenAI, Azure OpenAI, and any API following the OpenAI spec
- **Flexible API Settings**: Configure base URL, API key, and model selection
- **Per-User Configuration**: Each FreshRSS user maintains their own settings
- **Default Preferences**: Set Reader Mode as default for all articles

## Installation

1. **Download the Extension**: Clone or download this repository to your FreshRSS extensions directory.
2. **Enable the Extension**: Go to the FreshRSS extensions management page and enable the "ArticleSummary" extension.
3. **Configure the Extension**: Navigate to the extension's configuration page to set up your API details.

## Configuration

Access the extension configuration from FreshRSS extensions management page:

1. **Base URL**: Enter the base URL of your language model API (e.g., `https://api.openai.com/`)
   - Do NOT include the version path (e.g., `/v1`) - the extension adds this automatically
   - Compatible with OpenAI, Azure OpenAI, or any OpenAI-spec compliant endpoint

2. **API Key**: Your API authentication key

3. **Model Name**: The model to use for summarization and Q&A (e.g., `gpt-4o`, `gpt-4o-mini`, `claude-3-5-sonnet-20241022`)
   - Any model supported by your API endpoint

4. **Prompt**: System prompt for article summarization
   - A comprehensive default prompt is provided
   - Customize to adjust summary style, length, and format

5. **Max Completion Tokens** (default: 2048): Maximum length of AI responses
   - Applies to both summaries and Q&A responses
   - Adjust based on your needs and API limits

6. **Temperature** (0-2, default: 1.0): Controls output randomness
   - Lower values (0.3-0.7) = more focused and deterministic
   - Higher values (1.0-1.5) = more creative and varied
   - Note: Some models only support specific temperature values

7. **Enable Reader Mode by Default**: Automatically show articles in cleaned-up Reader Mode

## Usage

### Summarizing Articles

1. Open any article in FreshRSS
2. Click the **"Summarize"** button in the AI Summary section
3. Watch the summary appear in real-time as it's generated
4. The summary appears below the button with markdown formatting

### Asking Questions (Q&A)

1. After reading a summary (or without), click **"Ask a Question"**
2. Type your question in the chat input box
3. Press Enter or click **"Send"**
4. The AI responds based on the article content
5. Continue the conversation - the last 3 Q&A pairs are remembered per session

### Using Reader Mode

1. Click the **"📖 Toggle Reader Mode"** button below the article
2. View the cleaned-up, distraction-free version of the article
3. Click **"Show Original"** to switch back
4. Toggle as needed - the readable version is cached once loaded

### Collapsing Sections

- Click **"Collapse"** to minimize the AI Summary section
- Click **"Expand"** to restore it
- Useful for managing screen space with multiple articles

## Dependencies

### Frontend (Bundled)
- **Axios** (~52KB): HTTP client for API requests
- **Marked** (v15.0.1, 38KB): Markdown to HTML converter

### Backend (Bundled)
- **php-readability** (fivefilters/readability.php): Content extraction library
- **Masterminds/HTML5**: HTML5 parser for readability
- **PSR/Log**: Logging interface

All dependencies are included in the extension - no external installation required.

## Technical Details

### How It Works

1. **Initialization**: Extension hooks into FreshRSS's `entry_before_display` event
2. **UI Injection**: Adds AI summary section, Q&A interface, and controls to each article
3. **Summarization Flow**:
   - User clicks "Summarize"
   - PHP controller fetches article, converts HTML to Markdown
   - Frontend receives API parameters and calls configured LLM endpoint with streaming enabled
   - Response streams word-by-word via Server-Sent Events (SSE)
   - UI updates in real-time as each chunk arrives with markdown rendering
4. **Q&A Flow**:
   - User types question in chat interface
   - Backend prepares conversation with article context and history
   - AI responds based on article content only
   - Conversation history saved in session storage (last 3 Q&A pairs)
5. **Reader Mode**:
   - PHP Readability library processes article HTML
   - Extracts main content, removes clutter
   - Cached for instant toggling

### API Integration

Uses OpenAI Chat Completions API format:
```json
{
  "model": "gpt-4o-mini",
  "messages": [
    {"role": "system", "content": "system prompt"},
    {"role": "user", "content": "article content"}
  ],
  "max_completion_tokens": 2048,
  "temperature": 1.0
}
```

Compatible with:
- OpenAI API
- Azure OpenAI
- Local LLM servers (LM Studio, Ollama with OpenAI compatibility)
- Any service implementing OpenAI's API specification

## Project Structure

```
ArticleSummary/
├── extension.php              # Main extension class, hooks registration
├── metadata.json              # Extension metadata
├── configure.phtml            # Configuration UI
├── Controllers/
│   └── ArticleSummaryController.php  # API handlers (summarize, Q&A, readability)
├── static/
│   ├── script.js             # Frontend logic (buttons, streaming, chat)
│   ├── style.css             # UI styling
│   ├── axios.js              # HTTP client (bundled)
│   └── marked.js             # Markdown parser (bundled)
├── libs/                     # PHP dependencies (bundled)
│   ├── autoload.php          # Composer-style autoloader
│   └── [readability, masterminds, psr libraries]
└── test-libraries.html       # Testing tool for frontend library updates
```

## Contributing

Contributions are welcome! Please feel free to submit issues or pull requests.

### Areas for Enhancement
- Summary caching to reduce API calls
- Retry logic for failed requests
- Batch summarization
- Additional LLM provider templates

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Acknowledgments

- Original extension by [LiangWei88](https://github.com/LiangWei88/xExtension-ArticleSummary)
- [FreshRSS](https://freshrss.org/) - Self-hosted RSS aggregator
- [php-readability](https://github.com/fivefilters/readability.php) - Content extraction library
- Inspired by the need for efficient article consumption in the age of information overload

## Version History

### v1.0.0 (2026-01-06)
- Complete rewrite with new features:
  - ✨ Interactive Q&A chat interface
  - ✨ Reader Mode with php-readability integration
  - ✨ Collapsible UI sections
  - ✨ Streaming response display
  - ✨ Session-based conversation history
  - ✨ Direct article links with source display
- Updated to modern LLM API standards (GPT-4, Claude 3.5, etc.)
- Added configurable max_completion_tokens (2048 default)
- Added configurable temperature (1.0 default)
- Improved default summarization prompt
- Enhanced UI with better organization and controls
