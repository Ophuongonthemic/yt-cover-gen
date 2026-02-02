# YouTube AI Cover Generator

[![Tests](https://github.com/artryazanov/yt-cover-gen/actions/workflows/run-tests.yml/badge.svg)](https://github.com/artryazanov/yt-cover-gen/actions/workflows/run-tests.yml)
[![Latest Stable Version](https://poser.pugx.org/artryazanov/yt-cover-gen/v)](https://packagist.org/packages/artryazanov/yt-cover-gen)
[![Total Downloads](https://poser.pugx.org/artryazanov/yt-cover-gen/downloads)](https://packagist.org/packages/artryazanov/yt-cover-gen)
[![License](https://poser.pugx.org/artryazanov/yt-cover-gen/license)](https://packagist.org/packages/artryazanov/yt-cover-gen)

## Introduction

**YouTube AI Cover Generator** (`artryazanov/yt-cover-gen`) is a framework-agnostic PHP package designed to automatically generate viral, high-CTR (Click-Through Rate) YouTube thumbnails from gameplay screenshots using generic AI models.

It leverages powerful AI vision and image editing capabilities (OpenAI Image Models and Google Gemini) to analyze a screenshot, understand the context, and generate a stylized, professional-looking thumbnail with compelling text overlays and branding, strictly adhering to the game's art style.

## Examples

| Source | Result | Model |
| :---: | :---: | :---: |
| <img src="assets/example1-source.jpg" width="300"> | <img src="assets/example1-gemini-result.jpg" width="300"> | **Gemini**<br>`gemini-3-pro-image-preview` |
| <img src="assets/example2-source.jpg" width="300"> | <img src="assets/example2-openai-result.jpg" width="300"> | **OpenAI**<br>`gpt-image-1` |
| <img src="assets/example3-source.jpg" width="300"> | <img src="assets/example3-openai-result.jpeg" width="300"> | **OpenAI**<br>`gpt-image-1` |

## Features

- **Multi-Driver Support**: Switch seamlessly between OpenAI and Google Gemini.
- **Automatic Bidirectional Fallback**:
    - If `driver` is `openai`: Falls back to Gemini if OpenAI fails.
    - If `driver` is `gemini`: Falls back to OpenAI if Gemini fails (e.g., content refusal).
- **Framework Agnostic**: Can be used in any PHP 8.2+ project.
- **Laravel Integration**: Includes a Service Provider, Facade-friendly architecture, and configuration publishing.
- **Configurable Models**: Supports various OpenAI models (`gpt-image-1`, `gpt-image-1.5`) and Gemini models (`gemini-3-pro-image-preview`, `gemini-2.5-flash-image`, etc.).
- **Smart Image Processing**: Handles image resizing, format conversion, and Base64 encoding/decoding automatically using GD (no external binaries required).
- **Prompt Engineering**: Built-in, battle-tested prompt templates optimized for high CTR.

## Requirements

- PHP 8.2 or higher
- `ext-gd` extension
- `ext-json` extension
- `openai-php/client` (for OpenAI driver)
- `gemini-api-php/client` (for Gemini driver)
- PSR-17 and PSR-18 compatible HTTP client/factory (for Gemini driver)

## Installation

Install the package via Composer:

```bash
composer require artryazanov/yt-cover-gen
```

## Configuration

### Laravel

1.  **Publish the configuration file:**

    ```bash
    php artisan vendor:publish --tag=yt-cover-gen-config
    ```

2.  **Configure environment variables (`.env`):**

    The package supports automatic fallback. Set your preferred primary driver in `YT_COVER_GEN_DRIVER`. If the primary driver fails and credentials for the secondary driver are present, it will automatically attempt generation with the secondary driver.

    ```env
    # Driver Selection: 'openai' or 'gemini'
    # If 'openai': tries OpenAI first -> falls back to Gemini
    # If 'gemini': tries Gemini first -> falls back to OpenAI
    YT_COVER_GEN_DRIVER=openai

    # OpenAI Configuration
    OPENAI_API_KEY=sk-...
    YT_COVER_GEN_OPENAI_MODEL=gpt-image-1
    YT_COVER_GEN_OPENAI_SIZE=1536x1024

    # Gemini Configuration
    GEMINI_API_KEY=AIza...
    YT_COVER_GEN_GEMINI_MODEL=gemini-3-pro-image-preview
    ```

### Generic PHP

For non-Laravel projects, you can use the `CoverGeneratorFactory` to instantiate generators directly.

## Usage

### Basic Usage (Laravel)

Inject the `CoverGeneratorInterface` into your class (Controller, Command, Job, etc.):

```php
use Artryazanov\YtCoverGen\Contracts\CoverGeneratorInterface;

class CreateThumbnail
{
    public function __construct(
        private CoverGeneratorInterface $generator
    ) {}

    public function handle()
    {
        $pathToScreenshot = '/path/to/screenshot.jpg';
        $gameName = 'Elden Ring';
        $videoTitle = 'NO HIT RUN PART 1';

        // Returns absolute path to the generated image
        $coverPath = $this->generator->generate(
            $pathToScreenshot, 
            $gameName, 
            $videoTitle
        );
        
        echo "Thumbnail generated at: $coverPath";
    }
}
```

### Advanced Usage (Generic PHP / Custom Configuration)

You can use the Factory to create generators with specific configurations on the fly.

#### OpenAI Example

```php
use Artryazanov\YtCoverGen\CoverGeneratorFactory;
use Artryazanov\YtCoverGen\Enums\OpenAiModelEnum;

$apiKey = 'your-openai-api-key';

$generator = CoverGeneratorFactory::createOpenAi(
    $apiKey,
    '/path/to/output/dir', // Optional output directory
    OpenAiModelEnum::GPT_IMAGE_1_5->value, // Optional custom model
    '1024x1024' // Optional custom size
);

$path = $generator->generate('screenshot.jpg', 'My Game', 'Awesome Video');
```

#### Google Gemini Example

Gemini requires PSR-18 HTTP Client dependencies (e.g., Guzzle).

```php
use Artryazanov\YtCoverGen\CoverGeneratorFactory;
use Artryazanov\YtCoverGen\Enums\GeminiModelEnum;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;

$client = new Client();
$httpFactory = new HttpFactory();

$generator = CoverGeneratorFactory::createGemini(
    'your-gemini-api-key',
    $client,        // PSR-18 Client
    $httpFactory,   // PSR-17 Request Factory
    $httpFactory,   // PSR-17 Stream Factory
    '/path/to/output/dir',
    GeminiModelEnum::GEMINI_2_5_FLASH_IMAGE->value
);

$path = $generator->generate('screenshot.jpg', 'My Game', 'Awesome Video');
```

## Supported Models

### OpenAI Models
The package includes an Enum `Artryazanov\YtCoverGen\Enums\OpenAiModelEnum` for easy reference:
- `gpt-image-1` (Default)
- `gpt-image-1.5`

### Gemini Models
The package includes an Enum `Artryazanov\YtCoverGen\Enums\GeminiModelEnum`:
- `gemini-3-pro-image-preview` (Default)
- `gemini-2.5-flash-image`

## Testing

Run the tests with:

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
