# FreshRSS Article Summary Extension

- [English README](README.md)

This extension for FreshRSS allows users to generate summaries of articles using a language model API that conforms to the OpenAI API specification. The extension provides a user-friendly interface to configure the API endpoint, API key, model name, and a prompt to be added before the content. When activated, it adds a "summarize" button to each article, which, when clicked, sends the article content to the configured API for summarization.

## Features

- **API Configuration**: Easily configure the base URL, API key, model name, prompt, max tokens, and temperature through a simple form.
- **Summarize Button**: Adds a "summarize" button to each article, allowing users to generate a summary with a single click.
- **Markdown Support**: Converts HTML content to Markdown before sending it to the API, ensuring compatibility with various language models.
- **Advanced Settings**: Control output length (max tokens) and creativity (temperature) of the generated summaries.
- **Error Handling**: Provides feedback in case of API errors or incomplete configurations.

## Installation

1. **Download the Extension**: Clone or download this repository to your FreshRSS extensions directory.
2. **Enable the Extension**: Go to the FreshRSS extensions management page and enable the "ArticleSummary" extension.
3. **Configure the Extension**: Navigate to the extension's configuration page to set up your API details.

## Configuration

To configure the extension, follow these steps:

1. **Base URL**: Enter the base URL of your language model API (e.g., `https://api.openai.com/`). Note that the URL should not include the version path (e.g., `/v1`).
2. **API Key**: Provide your API key for authentication.
3. **Model Name**: Specify the model name you wish to use for summarization (e.g., `gpt-5-mini`, `gpt-5-turbo`). This extension supports GPT-5 and newer models.
4. **Prompt**: Add a prompt that will be included before the article content when sending the request to the API.
5. **Max Tokens** (optional): Set the maximum number of tokens for the summary output. Default is 2048.
6. **Temperature** (optional): Control the randomness/creativity of the output (0.0 to 2.0). Lower values make output more focused and deterministic. Default is 0.7.

## Usage

Once configured, the extension will automatically add a "summarize" button to each article. Clicking this button will:

1. Send the article content to the configured API.
2. Display the generated summary below the button.

## Dependencies

- **Axios**: Used for making HTTP requests from the browser.
- **Marked**: Converts Markdown content to HTML for display.

## Contributing

Contributions are welcome! Please feel free to submit issues or pull requests.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Acknowledgments

- Thanks to the FreshRSS community for providing a robust platform for RSS management.
- Inspired by the need for efficient article summarization tools.

## History
- Version: 1.0.0 (2026-01-06)
